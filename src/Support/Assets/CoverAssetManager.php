<?php

namespace ApeironKit\Support\Assets;

use ApeironKit\Core\LicenseManager;
use ApeironKit\Support\CoverTypeRegistry;
use ApeironKit\Support\WidgetRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CoverAssetManager {

	private DocumentWidgetIndex $document_index;
	private ElementorContext $context;

	public function __construct( DocumentWidgetIndex $document_index, ElementorContext $context ) {
		$this->document_index = $document_index;
		$this->context        = $context;
	}

	public function print_boot(): void {
		if (
			$this->context->is_editing()
			|| ! is_singular()
			|| in_array( 'apeiron-cover', WidgetRegistry::disabled_types(), true )
			|| ( class_exists( LicenseManager::class ) && ! LicenseManager::instance()->is_valid() )
		) {
			return;
		}

		$settings = $this->document_index->get()['cover_settings'][0] ?? null;
		if ( ! is_array( $settings ) ) {
			return;
		}

		$storage_key = sanitize_key( (string) ( $settings['storage_key'] ?? 'apeiron_cover_opened' ) );
		if ( '' === $storage_key ) {
			$storage_key = 'apeiron_cover_opened';
		} elseif ( 0 !== strpos( $storage_key, 'apeiron_cover_' ) ) {
			$storage_key = 'apeiron_cover_' . $storage_key;
		}

		$config = [
			'firstVisitOnly' => 'yes' === ( $settings['first_visit_only'] ?? '' ),
			'storageKey'     => $storage_key,
			'showDesktop'    => ! array_key_exists( 'show_desktop', $settings ) || 'yes' === $settings['show_desktop'],
			'showTablet'     => ! array_key_exists( 'show_tablet', $settings ) || 'yes' === $settings['show_tablet'],
			'showMobile'     => ! array_key_exists( 'show_mobile', $settings ) || 'yes' === $settings['show_mobile'],
			'background'     => sanitize_hex_color( (string) ( $settings['background_color'] ?? '' ) ) ?: '#bcaf93',
		];

		?>
		<style id="apeiron-cover-boot-css">html.apeiron-cover-booting::after{content:"";position:fixed;inset:0;z-index:2147483001;background:var(--apeiron-cover-boot-bg,#bcaf93)}html.apeiron-cover-booting,html.apeiron-cover-booting body{overflow:hidden!important}</style>
		<script id="apeiron-cover-boot-js">
		(function(w,d,c){var r=d.documentElement,x=Math.max(r.clientWidth||0,w.innerWidth||0),show=x>=1025?c.showDesktop:(x>=768?c.showTablet:c.showMobile),seen=false,t;if(c.firstVisitOnly){try{seen=w.localStorage.getItem(c.storageKey)==='1';}catch(e){}}if(!show||seen){return;}r.style.setProperty('--apeiron-cover-boot-bg',c.background);r.classList.add('apeiron-cover-booting');function release(){w.clearTimeout(t);r.classList.remove('apeiron-cover-booting');r.style.removeProperty('--apeiron-cover-boot-bg');}w.ApeironCoverBoot={release:release};function check(){if(!d.querySelector('[data-apeiron-cover="yes"]')){release();}}if(d.readyState==='loading'){d.addEventListener('DOMContentLoaded',check,{once:true});}else{check();}t=w.setTimeout(function(){d.querySelectorAll('[data-apeiron-cover="yes"]').forEach(function(el){el.classList.add('is-complete');});release();},10000);}(window,document,<?php echo wp_json_encode( $config ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data skalar telah dienkode JSON. ?>));
		</script>
		<?php
	}

	public function enqueue_all_type_styles(): void {
		$this->enqueue_style_handles( $this->sort_style_handles( CoverTypeRegistry::all_style_handles() ) );
	}

	public function enqueue_all_type_scripts(): void {
		$this->enqueue_script_handles( CoverTypeRegistry::all_script_handles() );
	}

	public function enqueue_current_type_styles(): void {
		$this->enqueue_all_type_styles();
	}

	public function enqueue_current_type_scripts(): void {
		$this->enqueue_all_type_scripts();
	}

	/**
	 * @param string[] $handles Handle style.
	 * @return string[]
	 */
	private function sort_style_handles( array $handles ): array {
		$handles = array_values( array_unique( $handles ) );
		$tail    = [];

		$handles = array_values(
			array_filter(
				$handles,
				static function ( string $handle ) use ( &$tail ): bool {
					if ( 'apeiron-kit-cover-responsive' === $handle ) {
						$tail[] = $handle;
						return false;
					}

					return true;
				}
			)
		);

		return array_merge( $handles, array_values( array_unique( $tail ) ) );
	}

	/**
	 * @param string[] $handles Handle style.
	 */
	private function enqueue_style_handles( array $handles ): void {
		foreach ( array_unique( $handles ) as $handle ) {
			if ( wp_style_is( $handle, 'registered' ) || wp_style_is( $handle, 'enqueued' ) ) {
				wp_enqueue_style( $handle );
			}
		}
	}

	/**
	 * @param string[] $handles Handle script.
	 */
	private function enqueue_script_handles( array $handles ): void {
		foreach ( array_unique( $handles ) as $handle ) {
			if ( wp_script_is( $handle, 'registered' ) || wp_script_is( $handle, 'enqueued' ) ) {
				wp_enqueue_script( $handle );
			}
		}
	}
}
