<?php

declare( strict_types=1 );

namespace ApeironKit\Support;

use ApeironKit\Elementor\Widgets\AutoScroll\AutoScroll;
use ApeironKit\Elementor\Widgets\ClipboardTap\ClipboardTap;
use ApeironKit\Elementor\Widgets\CommentDock\CommentDock;
use ApeironKit\Elementor\Widgets\Countdown\Countdown;
use ApeironKit\Elementor\Widgets\Countdown\LegacyPulseCountdown;
use ApeironKit\Elementor\Widgets\Cover\Cover;
use ApeironKit\Elementor\Widgets\GuestInvitationManager\GuestInvitationManager;
use ApeironKit\Elementor\Widgets\PageLoader\PageLoader;
use ApeironKit\Elementor\Widgets\SignalForm\SignalForm;
use ApeironKit\Elementor\Widgets\SocialProof\SocialProof;
use ApeironKit\Elementor\Widgets\SoundscapePlayer\SoundscapePlayer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central source of truth for Apeiron widget identity, assets, and dashboard metadata.
 */
final class WidgetRegistry {

	public const DISABLED_OPTION = 'apeiron_kit_disabled_widgets';

	/**
	 * Technical definitions for widgets that are available and registerable.
	 *
	 * `type`  = Elementor widget type (get_name()) — public contract.
	 * `css`   = registered CSS handle.
	 * `js`    = registered JS handle.
	 * `class` = FQCN of the widget.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private const ACTIVE_WIDGETS = [
		'countdown'     => [
			'class' => Countdown::class,
			'type'  => 'apeiron-countdown',
			'css'   => 'apeiron-kit-countdown',
			'js'    => 'apeiron-kit-countdown-js',
			'css_file' => 'apeiron-countdown',
			'js_file'  => 'widgets/countdown',
			'js_deps'  => [],
		],
		'soundscape'    => [
			'class' => SoundscapePlayer::class,
			'type'  => 'apeiron-soundscape',
			'css'   => 'apeiron-kit-soundscape',
			'js'    => 'apeiron-kit-soundscape-js',
			'css_file' => 'apeiron-soundscape',
			'js_file'  => 'widgets/soundscape',
			'js_deps'  => [],
		],
		'signal_form'   => [
			'class' => SignalForm::class,
			'type'  => 'apeiron-signal-form',
			'css'   => 'apeiron-kit-signal-form',
			'js'    => 'apeiron-kit-signal-form-js',
			'css_file' => 'apeiron-signal-form',
			'js_file'  => 'widgets/signal-form',
			'js_deps'  => [],
		],
		'clipboard_tap' => [
			'class' => ClipboardTap::class,
			'type'  => 'apeiron-clipboard-tap',
			'css'   => 'apeiron-kit-clipboard-tap',
			'js'    => 'apeiron-kit-clipboard-js',
			'css_file' => 'apeiron-clipboard-tap',
			'js_file'  => 'widgets/clipboard',
			'js_deps'  => [],
		],
		'comment_dock'  => [
			'class' => CommentDock::class,
			'type'  => 'apeiron-comment-dock',
			'css'   => 'apeiron-kit-comment-dock',
			'js'    => 'apeiron-kit-comment-dock-js',
			'css_file' => 'apeiron-comment-dock',
			'js_file'  => 'widgets/comment-dock',
			'js_deps'  => [ 'jquery' ],
		],
		'guest_manager' => [
			'class' => GuestInvitationManager::class,
			'type'  => 'apeiron-guest-invitation-manager',
			'css'   => 'apeiron-kit-guest-invitation',
			'js'    => 'apeiron-kit-guest-invitation-js',
			'css_file' => 'apeiron-guest-invitation',
			'js_file'  => 'widgets/guest-invitation',
			'js_deps'  => [],
		],
		'autoscroll'    => [
			'class' => AutoScroll::class,
			'type'  => 'apeiron-autoscroll',
			'css'   => 'apeiron-kit-autoscroll',
			'js'    => 'apeiron-kit-autoscroll-js',
			'css_file' => 'apeiron-autoscroll',
			'js_file'  => 'widgets/autoscroll',
			'js_deps'  => [],
		],
		'social_proof'  => [
			'class' => SocialProof::class,
			'type'  => 'apeiron-social-proof',
			'css'   => 'apeiron-kit-social-proof',
			'js'    => 'apeiron-kit-social-proof-js',
			'css_file' => 'apeiron-social-proof',
			'js_file'  => 'widgets/social-proof',
			'js_deps'  => [],
		],
		'page_loader'   => [
			'class' => PageLoader::class,
			'type'  => 'apeiron-page-loader',
			'css'   => 'apeiron-kit-page-loader',
			'js'    => 'apeiron-kit-page-loader-js',
			'css_file' => 'apeiron-page-loader',
			'js_file'  => 'widgets/page-loader',
			'js_deps'  => [ 'apeiron-kit-page-loader-boot', 'jquery' ],
		],
		'cover'         => [
			'class' => Cover::class,
			'type'  => 'apeiron-cover',
			'css'   => 'apeiron-kit-cover',
			'js'    => 'apeiron-kit-cover-js',
			'css_file' => 'apeiron-cover',
			'js_file'  => 'widgets/cover',
			'js_deps'  => [],
		],
	];

	/**
	 * Legacy widget classes registered for backward compatibility only.
	 *
	 * These are not part of the dashboard, features, or asset order maps.
	 * They exist solely so pages saved with a deprecated `widgetType`
	 * value can still render. They share CSS/JS handles with their
	 * canonical counterparts.
	 *
	 * @var array<string,array<string,string>>
	 */
	private const LEGACY_ALIASES = [
		'countdown_pulse_legacy' => [
			'class'         => LegacyPulseCountdown::class,
			'type'          => 'apeiron-pulse-countdown',
			'canonical'     => 'countdown',
		],
	];

	/**
	 * Keep the existing Elementor registration order intact.
	 *
	 * @var string[]
	 */
	private const REGISTRATION_ORDER = [
		'countdown',
		'soundscape',
		'signal_form',
		'clipboard_tap',
		'comment_dock',
		'guest_manager',
		'autoscroll',
		'social_proof',
		'page_loader',
		'cover',
	];

	/**
	 * Keep the existing dashboard and toggle order intact.
	 *
	 * @var string[]
	 */
	private const DASHBOARD_ORDER = [
		'comment_dock',
		'countdown',
		'guest_manager',
		'clipboard_tap',
		'soundscape',
		'signal_form',
		'social_proof',
		'autoscroll',
		'page_loader',
		'cover',
	];

	/**
	 * Keep the existing frontend asset enqueue order intact.
	 *
	 * @var string[]
	 */
	private const ASSET_ORDER = [
		'autoscroll',
		'guest_manager',
		'countdown',
		'soundscape',
		'signal_form',
		'clipboard_tap',
		'comment_dock',
		'social_proof',
		'page_loader',
		'cover',
	];

	/**
	 * @return string[]
	 */
	public static function allowed_slugs(): array {
		return array_keys( self::ACTIVE_WIDGETS );
	}

	/**
	 * @return string[] Sanitized disabled widget slugs.
	 */
	public static function disabled_slugs(): array {
		return self::sanitize_slugs( get_option( self::DISABLED_OPTION, [] ) );
	}

	/**
	 * @return string[] Disabled Elementor widget types.
	 */
	public static function disabled_types(): array {
		$types    = [];
		$slug_map = self::slug_to_type_map();

		foreach ( self::disabled_slugs() as $slug ) {
			if ( isset( $slug_map[ $slug ] ) ) {
				$types[] = $slug_map[ $slug ];
			}
		}

		return array_values( array_unique( $types ) );
	}

	/**
	 * Sanitize and whitelist widget slugs stored in an option.
	 *
	 * @param mixed $slugs Raw option value.
	 * @return string[]
	 */
	public static function sanitize_slugs( $slugs ): array {
		if ( ! is_array( $slugs ) ) {
			return [];
		}

		$allowed = array_fill_keys( self::allowed_slugs(), true );
		$clean   = [];

		foreach ( $slugs as $slug ) {
			if ( ! is_scalar( $slug ) ) {
				continue;
			}

			$slug = sanitize_key( (string) $slug );
			if ( isset( $allowed[ $slug ] ) ) {
				$clean[] = $slug;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * @return class-string[]
	 */
	public static function widget_classes(): array {
		$classes = [];

		foreach ( self::REGISTRATION_ORDER as $slug ) {
			$classes[] = self::ACTIVE_WIDGETS[ $slug ]['class'];
		}

		return $classes;
	}

	/**
	 * Legacy alias widget classes (kept for backward compatibility).
	 *
	 * @return class-string[]
	 */
	public static function legacy_widget_classes(): array {
		return array_map(
			static fn( array $definition ): string => $definition['class'],
			self::LEGACY_ALIASES
		);
	}

	/**
	 * Map every legacy Elementor widget type to its canonical slug.
	 *
	 * @return array<string,string> Legacy type => canonical slug.
	 */
	public static function legacy_type_to_slug_map(): array {
		$map = [];

		foreach ( self::LEGACY_ALIASES as $definition ) {
			$map[ $definition['type'] ] = $definition['canonical'];
		}

		return $map;
	}

	/**
	 * @return array<class-string,string>
	 */
	public static function class_to_slug_map(): array {
		$map = [];

		foreach ( self::REGISTRATION_ORDER as $slug ) {
			$map[ self::ACTIVE_WIDGETS[ $slug ]['class'] ] = $slug;
		}

		foreach ( self::LEGACY_ALIASES as $definition ) {
			$map[ $definition['class'] ] = $definition['canonical'];
		}

		return $map;
	}

	/**
	 * @return array<string,string>
	 */
	public static function slug_to_type_map(): array {
		$map = [];

		foreach ( self::ACTIVE_WIDGETS as $slug => $definition ) {
			$map[ $slug ] = $definition['type'];
		}

		return $map;
	}

	/**
	 * @return array<string,string>
	 */
	public static function type_to_slug_map(): array {
		$map = array_flip( self::slug_to_type_map() );

		foreach ( self::LEGACY_ALIASES as $definition ) {
			$map[ $definition['type'] ] = $definition['canonical'];
		}

		return $map;
	}

	/**
	 * @return array<string,string> Widget type => CSS handle.
	 */
	public static function css_map(): array {
		return self::asset_handle_map( 'css' );
	}

	/**
	 * @return array<string,string> Widget type => JS handle.
	 */
	public static function js_map(): array {
		return self::asset_handle_map( 'js' );
	}

	/**
	 * Return the ordered list of asset registrations (slug => [type, css, js]).
	 *
	 * Used by AssetManager to auto-generate wp_register_style/script calls
	 * without hard-coding per-widget registration blocks.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function asset_registrations(): array {
		$rows = [];

		foreach ( self::ASSET_ORDER as $slug ) {
			if ( ! isset( self::ACTIVE_WIDGETS[ $slug ] ) ) {
				continue;
			}

			$rows[] = [
				'slug' => $slug,
				'type' => self::ACTIVE_WIDGETS[ $slug ]['type'],
				'css'  => self::ACTIVE_WIDGETS[ $slug ]['css'],
				'js'   => self::ACTIVE_WIDGETS[ $slug ]['js'],
				'css_file' => self::ACTIVE_WIDGETS[ $slug ]['css_file'],
				'js_file'  => self::ACTIVE_WIDGETS[ $slug ]['js_file'],
				'js_deps'  => self::ACTIVE_WIDGETS[ $slug ]['js_deps'],
			];
		}

		return $rows;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function features(): array {
		$features = self::dashboard_feature_definitions();
		$items    = [];

		foreach ( self::DASHBOARD_ORDER as $slug ) {
			$items[] = array_merge( [ 'slug' => $slug ], $features[ $slug ] );
		}

		return $items;
	}

	/**
	 * @return array<string,string>
	 */
	private static function asset_handle_map( string $handle_key ): array {
		$map = [];

		foreach ( self::ASSET_ORDER as $slug ) {
			if ( isset( self::ACTIVE_WIDGETS[ $slug ][ $handle_key ] ) ) {
				$type         = self::ACTIVE_WIDGETS[ $slug ]['type'];
				$map[ $type ] = self::ACTIVE_WIDGETS[ $slug ][ $handle_key ];
			}
		}

		return $map;
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	private static function dashboard_feature_definitions(): array {
		return [
			'comment_dock'  => [
				'icon'        => 'format-chat',
				'color'       => 'primary',
				'label'       => __( 'Ucapan Tamu', 'apeiron-kit' ),
				'description' => __( 'Widget ucapan lengkap dengan komentar, balasan, stiker, dan moderasi.', 'apeiron-kit' ),
				'group'       => __( 'Interaksi', 'apeiron-kit' ),
				'group_key'   => 'interaksi',
			],
			'countdown'     => [
				'icon'        => 'clock',
				'color'       => 'amber',
				'label'       => __( 'Hitung Mundur', 'apeiron-kit' ),
				'description' => __( 'Countdown acara dengan tampilan fleksibel untuk halaman undangan.', 'apeiron-kit' ),
				'group'       => __( 'Event', 'apeiron-kit' ),
				'group_key'   => 'event',
			],
			'guest_manager' => [
				'icon'        => 'groups',
				'color'       => 'blue',
				'label'       => __( 'Manajemen Tamu', 'apeiron-kit' ),
				'description' => __( 'Kelola daftar tamu, pesan undangan, dan tautan personal.', 'apeiron-kit' ),
				'group'       => __( 'Manajemen', 'apeiron-kit' ),
				'group_key'   => 'manajemen',
			],
			'clipboard_tap' => [
				'icon'        => 'clipboard',
				'color'       => 'teal',
				'label'       => __( 'Tombol Salin', 'apeiron-kit' ),
				'description' => __( 'Tombol salin cepat untuk rekening, alamat, atau teks penting.', 'apeiron-kit' ),
				'group'       => __( 'Utilitas', 'apeiron-kit' ),
				'group_key'   => 'utilitas',
			],
			'soundscape'    => [
				'icon'        => 'controls-volumeon',
				'color'       => 'rose',
				'label'       => __( 'Tombol Audio', 'apeiron-kit' ),
				'description' => __( 'Kontrol audio untuk musik latar dari file audio atau YouTube.', 'apeiron-kit' ),
				'group'       => __( 'Media', 'apeiron-kit' ),
				'group_key'   => 'media',
			],
			'signal_form'   => [
				'icon'        => 'share',
				'color'       => 'green',
				'label'       => __( 'Form WhatsApp', 'apeiron-kit' ),
				'description' => __( 'Form singkat yang mengarahkan pesan ke nomor WhatsApp.', 'apeiron-kit' ),
				'group'       => __( 'Konversi', 'apeiron-kit' ),
				'group_key'   => 'konversi',
			],
			'social_proof'  => [
				'icon'        => 'megaphone',
				'color'       => 'amber',
				'label'       => __( 'Aktivitas', 'apeiron-kit' ),
				'description' => __( 'Notifikasi aktivitas terbaru untuk membangun kepercayaan pengunjung.', 'apeiron-kit' ),
				'group'       => __( 'Marketing', 'apeiron-kit' ),
				'group_key'   => 'marketing',
			],
			'autoscroll'    => [
				'icon'        => 'arrow-down-alt',
				'color'       => 'primary',
				'label'       => __( 'Gulir Otomatis', 'apeiron-kit' ),
				'description' => __( 'Navigasi scroll otomatis untuk pengalaman membaca yang lebih halus.', 'apeiron-kit' ),
				'group'       => __( 'Navigasi', 'apeiron-kit' ),
				'group_key'   => 'navigasi',
			],
			'page_loader'   => [
				'icon'        => 'update',
				'color'       => 'blue',
				'label'       => __( 'Pemuat Halaman', 'apeiron-kit' ),
				'description' => __( 'Coffee Loader untuk transisi pemuatan halaman.', 'apeiron-kit' ),
				'group'       => __( 'Performa', 'apeiron-kit' ),
				'group_key'   => 'performa',
			],
			'cover'         => [
				'icon'        => 'format-image',
				'color'       => 'rose',
				'label'       => __( 'Sampul', 'apeiron-kit' ),
				'description' => __( 'Cover pembuka undangan dengan panel, pita, kartu penerima, dan animasi buka undangan.', 'apeiron-kit' ),
				'group'       => __( 'Event', 'apeiron-kit' ),
				'group_key'   => 'event',
			],
		];
	}
}
