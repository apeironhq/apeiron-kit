<?php

declare( strict_types=1 );

namespace ApeironKit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Moves Elementor's admin notices out of the WordPress notice area and into the
 * Apeiron Kit page layout.
 *
 * WordPress fires `admin_notices` / `all_admin_notices` from admin-header.php,
 * before the page callback runs, so every notice lands above `.wrap` and pushes
 * the Apeiron header down. This detaches only the Elementor-owned callbacks on
 * the Apeiron Kit screen, then replays them inside the dashboard layout.
 *
 * Nothing is suppressed: the callbacks are executed unchanged, their markup is
 * emitted verbatim, and `admin_footer` acts as a backstop so a captured notice
 * still prints if the page body never rendered.
 *
 * Ownership is resolved from the file that declares the callback rather than
 * from a list of class names, because Elementor reorganises its namespaces
 * between releases. Anything Elementor adds in future is therefore picked up
 * without changes here, and callbacks owned by WordPress or any other plugin
 * are left exactly where they are.
 */
final class ElementorNoticeRelocator {

	private const HOOKS = [ 'admin_notices', 'all_admin_notices' ];

	/**
	 * Detached callbacks awaiting replay.
	 *
	 * @var callable[]
	 */
	private array $captured = [];

	private bool $rendered = false;

	/**
	 * Absolute, normalised directories whose notices should be relocated.
	 *
	 * @var string[]|null
	 */
	private ?array $owner_dirs = null;

	public function register(): void {
		foreach ( self::HOOKS as $hook ) {
			// PHP_INT_MIN so every other plugin has finished registering by the
			// time this runs: capturing from inside the hook is what guarantees
			// Elementor's own late registrations are visible.
			add_action( $hook, [ $this, 'capture' ], PHP_INT_MIN );
		}

		add_action( 'admin_footer', [ $this, 'render_fallback' ], 1 );
	}

	/**
	 * Detach Elementor-owned callbacks from the notice hook currently firing.
	 */
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

		// Collect first, detach afterwards: removing while iterating the live
		// callback array would re-index it mid-loop.
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

	/**
	 * Replay the captured notices inside the Apeiron Kit layout.
	 */
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
				// A broken third-party notice must not take the dashboard down.
				ob_end_clean();
			}
		}

		if ( '' === trim( $html ) ) {
			return;
		}

		printf(
			'<div class="apeiron-vendor-notices" role="region" aria-label="%s">%s</div>',
			esc_attr__( 'Pemberitahuan Elementor', 'apeiron-kit' ),
			// Already-rendered notice markup produced by Elementor itself.
			// Escaping here would print the raw HTML on screen.
			$html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Opt a notice out of WordPress's client-side notice relocation.
	 *
	 * Relocating on the server is not enough on its own. wp-admin/js/common.js
	 * runs, on every admin page:
	 *
	 *     if ( ! $headerEnd.length ) { $headerEnd = $( '.wrap h1, .wrap h2' ).first(); }
	 *     $( 'div.updated, div.error, div.notice' ).not( '.inline, .below-h2' )
	 *         .insertAfter( $headerEnd );
	 *
	 * so any notice left unmarked is pulled back out of this container on DOM
	 * ready and re-inserted after the first heading — inside the Apeiron brand
	 * block. `inline` is the opt-out core documents for exactly this, and core's
	 * own update_nag() uses it. Only top-level elements are touched, because
	 * those are what the selector above matches; nested markup is left alone.
	 *
	 * @param string $html Rendered notice markup.
	 */
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

		// Never hand back an empty string: a failed re-serialisation would
		// silently swallow the notice.
		return '' !== trim( $rebuilt ) ? $rebuilt : $html;
	}

	/**
	 * Backstop so a captured notice is never silently dropped.
	 */
	public function render_fallback(): void {
		if ( $this->rendered || empty( $this->captured ) || ! $this->is_apeiron_screen() ) {
			return;
		}

		$this->render();
	}

	private function is_apeiron_screen(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check.

		return 'apeiron-kit' === $page;
	}

	/**
	 * @param callable $callback Hook callback.
	 */
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

	/**
	 * Absolute path of the file declaring a callback, or '' when undeterminable.
	 *
	 * @param callable $callback Hook callback.
	 */
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

	/**
	 * @return string[]
	 */
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

		/**
		 * Filter the directories whose admin notices are relocated into the
		 * Apeiron Kit layout.
		 *
		 * @param string[] $dirs Absolute, trailing-slashed, normalised paths.
		 */
		$dirs = (array) apply_filters( 'apeiron_kit_relocated_notice_dirs', array_values( array_unique( $dirs ) ) );

		return $this->owner_dirs = array_filter( array_map( 'strval', $dirs ) );
	}
}
