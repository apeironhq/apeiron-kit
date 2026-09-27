<?php

declare( strict_types=1 );

namespace ApeironKit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Tampilkan notice milik Elementor di dalam halaman Apeiron tanpa mengubah notice lain. */
final class ElementorNoticeRelocator {

	private const HOOKS = [ 'admin_notices', 'all_admin_notices' ];

	/** @var callable[] Callback notice yang akan ditampilkan ulang. */
	private array $captured = [];

	private bool $rendered = false;

	/** @var string[]|null Direktori sumber notice Elementor. */
	private ?array $owner_dirs = null;

	public function register(): void {
		foreach ( self::HOOKS as $hook ) {
			// Tangkap notice setelah callback Elementor selesai didaftarkan.
			add_action( $hook, [ $this, 'capture' ], PHP_INT_MIN );
		}

		add_action( 'admin_footer', [ $this, 'render_fallback' ], 1 );
	}

	/** Pindahkan hanya callback notice milik Elementor. */
	public function capture(): void {
		if ( ! $this->is_apeiron_screen() ) {
			return;
		}

		$hook = current_action();
		if ( ! is_string( $hook ) || '' === $hook ) {
			return;
		}

		global $wp_filter;
		if ( empty( $wp_filter[ $hook ] ) || ! isset( $wp_filter[ $hook ]->callbacks ) ) {
			return;
		}

		// Kumpulkan callback sebelum dilepas agar iterasi hook tetap stabil.
		$pending = [];
		foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
			if ( ! is_array( $callbacks ) ) {
				continue;
			}

			foreach ( $callbacks as $entry ) {
				$callback = $entry['function'] ?? null;
				if ( null === $callback || ! is_callable( $callback ) ) {
					continue;
				}
				if ( ! $this->is_owned_by_elementor( $callback ) ) {
					continue;
				}

				$pending[] = [ $callback, (int) $priority ];
			}
		}

		foreach ( $pending as [ $callback, $priority ] ) {
			remove_action( $hook, $callback, $priority );
			$this->captured[] = $callback;
		}
	}

	/** Tampilkan notice yang telah dikumpulkan di halaman Apeiron. */
	public function render(): void {
		if ( $this->rendered ) {
			return;
		}

		$this->rendered = true;

		if ( empty( $this->captured ) ) {
			return;
		}

		$html = '';
		foreach ( $this->captured as $callback ) {
			ob_start();
			try {
				call_user_func( $callback );
				$html .= $this->pin_in_place( (string) ob_get_clean() );
			} catch ( \Throwable $exception ) {
				// Kegagalan callback notice tidak boleh menghentikan halaman.
				ob_end_clean();
			}
		}

		if ( '' === trim( $html ) ) {
			return;
		}

		printf(
			'<div class="apeiron-vendor-notices" role="region" aria-label="%s">%s</div>',
			esc_attr__( 'Pemberitahuan Elementor', 'apeiron-kit' ),
			// Markup notice Elementor sudah dirender; escaping akan menampilkan HTML mentah.
			$html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/** Beri kelas inline agar WordPress tidak memindahkan notice dari area Apeiron. */
	private function pin_in_place( string $html ): string {
		if ( '' === trim( $html ) || ! class_exists( '\DOMDocument' ) ) {
			return $html;
		}

		$document = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$loaded   = $document->loadHTML(
			'<?xml encoding="utf-8"?><body>' . $html . '</body>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			return $html;
		}

		$body = $document->getElementsByTagName( 'body' )->item( 0 );
		if ( null === $body ) {
			return $html;
		}

		$changed = false;
		foreach ( $body->childNodes as $node ) {
			if ( ! $node instanceof \DOMElement || 'div' !== strtolower( $node->nodeName ) ) {
				continue;
			}

			$classes = preg_split( '/\s+/', (string) $node->getAttribute( 'class' ), -1, PREG_SPLIT_NO_EMPTY ) ?: [];
			if ( ! array_intersect( $classes, [ 'notice', 'updated', 'error' ] ) ) {
				continue;
			}
			if ( in_array( 'inline', $classes, true ) ) {
				continue;
			}

			$classes[] = 'inline';
			$node->setAttribute( 'class', implode( ' ', $classes ) );
			$changed = true;
		}

		if ( ! $changed ) {
			return $html;
		}

		$rebuilt = '';
		foreach ( $body->childNodes as $node ) {
			$rebuilt .= (string) $document->saveHTML( $node );
		}

		// Jika serialisasi gagal, tetap tampilkan notice asli.
		return '' !== trim( $rebuilt ) ? $rebuilt : $html;
	}

	/** Tampilkan notice tersimpan jika halaman belum merendernya. */
	public function render_fallback(): void {
		if ( $this->rendered || empty( $this->captured ) || ! $this->is_apeiron_screen() ) {
			return;
		}

		$this->render();
	}

	private function is_apeiron_screen(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Hanya membaca halaman admin.

		return 'apeiron-kit' === $page;
	}

	/** @param callable $callback Callback hook. */
	private function is_owned_by_elementor( $callback ): bool {
		$file = $this->resolve_callback_file( $callback );
		if ( '' === $file ) {
			return false;
		}

		foreach ( $this->owner_dirs() as $dir ) {
			if ( 0 === strpos( $file, $dir ) ) {
				return true;
			}
		}

		return false;
	}

	/** @param callable $callback Callback yang dicari lokasi berkasnya. */
	private function resolve_callback_file( $callback ): string {
		try {
			if ( is_string( $callback ) && function_exists( $callback ) ) {
				$reflection = new \ReflectionFunction( $callback );
			} elseif ( $callback instanceof \Closure ) {
				$reflection = new \ReflectionFunction( $callback );
			} elseif ( is_array( $callback ) && 2 === count( $callback ) ) {
				$target = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];
				$reflection = new \ReflectionMethod( $target, (string) $callback[1] );
			} elseif ( is_object( $callback ) && method_exists( $callback, '__invoke' ) ) {
				$reflection = new \ReflectionMethod( $callback, '__invoke' );
			} else {
				return '';
			}
		} catch ( \ReflectionException $exception ) {
			return '';
		}

		$file = $reflection->getFileName();

		return is_string( $file ) ? wp_normalize_path( $file ) : '';
	}

	/** @return string[] */
	private function owner_dirs(): array {
		if ( null !== $this->owner_dirs ) {
			return $this->owner_dirs;
		}

		$dirs = [];
		foreach ( [ 'ELEMENTOR_PATH', 'ELEMENTOR_PRO_PATH' ] as $constant ) {
			if ( defined( $constant ) && is_string( constant( $constant ) ) ) {
				$dirs[] = trailingslashit( wp_normalize_path( constant( $constant ) ) );
			}
		}

		if ( defined( 'WP_PLUGIN_DIR' ) ) {
			$plugin_dir = trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) );
			$dirs[]     = $plugin_dir . 'elementor/';
			$dirs[]     = $plugin_dir . 'elementor-pro/';
		}

		/** @param string[] $dirs Direktori notice Elementor yang telah dinormalisasi. */
		$dirs = (array) apply_filters( 'apeiron_kit_relocated_notice_dirs', array_values( array_unique( $dirs ) ) );

		return $this->owner_dirs = array_filter( array_map( 'strval', $dirs ) );
	}
}
