<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div <?php echo $render_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor escapes render attributes ?>>
	<span class="apeiron-page-loader__sr-status"><?php echo esc_html( '' !== $loading_text ? $loading_text : __( 'Memuat halaman', 'apeiron-kit' ) ); ?></span>
	<div class="apeiron-page-loader__overlay">
		<div class="apeiron-page-loader__surface">
			<div class="apeiron-page-loader__phase-stage">
				<div class="apeiron-page-loader__sequence">
					<?php if ( '' !== $intro_text ) : ?>
						<div class="apeiron-page-loader__intro"><?php echo esc_html( $intro_text ); ?></div>
					<?php endif; ?>
					<div class="apeiron-page-loader__final">
						<?php if ( '' !== $main_text ) : ?>
							<div class="apeiron-page-loader__mask"><div class="apeiron-page-loader__main"><?php echo esc_html( $main_text ); ?></div></div>
						<?php endif; ?>
						<div class="apeiron-page-loader__rule" aria-hidden="true"><span></span><i></i><span></span></div>
						<?php if ( '' !== $secondary_text ) : ?>
							<div class="apeiron-page-loader__secondary"><?php echo esc_html( $secondary_text ); ?></div>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( 'default' !== $loader_style ) : ?>
				<div class="apeiron-page-loader__loading-phase">
					<?php if ( 'coffee' === $loader_style ) : ?>
					<div class="apeiron-page-loader__visual-wrap" aria-hidden="true">
						<svg class="apeiron-loader-visual apeiron-loader-visual--coffee" viewBox="2.1 1.2 120 150" xmlns="http://www.w3.org/2000/svg">
							<defs><clipPath id="apeironCupInner-<?php echo esc_attr( $this->get_id() ); ?>"><path d="M40 96 H80 L73.5 124 Q72.5 130 66 130 H54 Q47.5 130 46.5 124 Z"/></clipPath><linearGradient id="apeironBrew-<?php echo esc_attr( $this->get_id() ); ?>" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="currentColor" stop-opacity=".92"/><stop offset="100%" stop-color="currentColor" stop-opacity=".34"/></linearGradient></defs>
							<g class="apeiron-loader-coffee__dripper"><path d="M31 12 L57 47 H63 L89 12"/><ellipse cx="60" cy="12" rx="29" ry="4"/></g>
							<g class="apeiron-loader-coffee__drops"><ellipse cx="60" cy="52" rx="2.6" ry="3.6"/><ellipse cx="60" cy="52" rx="2.6" ry="3.6"/><ellipse cx="60" cy="52" rx="2.6" ry="3.6"/></g>
							<g clip-path="url(#apeironCupInner-<?php echo esc_attr( $this->get_id() ); ?>)"><rect class="apeiron-loader-coffee__liquid" x="36" y="130" width="48" height=".01" fill="url(#apeironBrew-<?php echo esc_attr( $this->get_id() ); ?>)"/><ellipse class="apeiron-loader-coffee__surface" cx="60" cy="130" rx="13.5" ry="2.2"/></g>
							<path class="apeiron-loader-coffee__handle" d="M84 100 C95 101 96 112 88 115"/><path class="apeiron-loader-coffee__cup" d="M36 92 H84 L76 126 Q74.5 133 67 133 H53 Q45.5 133 44 126 Z"/><ellipse class="apeiron-loader-coffee__rim" cx="60" cy="92" rx="24" ry="3.6"/><path class="apeiron-loader-coffee__saucer" d="M32 140 Q60 149 88 140"/>
						</svg>
					</div>
					<?php else : ?>
					<div class="apeiron-page-loader__visual-wrap" aria-hidden="true">
						<svg class="apeiron-loader-visual apeiron-loader-visual--water" viewBox="-3.4 -5.4 120 140" xmlns="http://www.w3.org/2000/svg">
							<defs><clipPath id="apeironBowlInner-<?php echo esc_attr( $this->get_id() ); ?>"><path d="M29.5 79 C29.5 105 43 120.5 60 120.5 C77 120.5 90.5 105 90.5 79 Z"/></clipPath><clipPath id="apeironDropClip-<?php echo esc_attr( $this->get_id() ); ?>"><rect width="120" height="79"/><path d="M29.5 79 C29.5 105 43 120.5 60 120.5 C77 120.5 90.5 105 90.5 79 Z"/></clipPath></defs>
							<g class="apeiron-loader-water__tap"><rect x="20" y="6" width="5" height="14" rx="2"/><path d="M25 13 H53 Q60 13 60 20 V27"/></g>
							<g class="apeiron-loader-water__drops" clip-path="url(#apeironDropClip-<?php echo esc_attr( $this->get_id() ); ?>)"><path d="M60 27 C62.6 30.4 63.4 32.6 63.4 34 A3.4 3.4 0 1 1 56.6 34 C56.6 32.6 57.4 30.4 60 27 Z"/><path d="M60 27 C62.2 29.9 62.9 31.8 62.9 33 A2.9 2.9 0 1 1 57.1 33 C57.1 31.8 57.8 29.9 60 27 Z"/></g>
							<g clip-path="url(#apeironBowlInner-<?php echo esc_attr( $this->get_id() ); ?>)"><g class="apeiron-loader-water__level"><g class="apeiron-loader-water__wave wave-back"><path d="M-60 2 q15 -6 30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 L240 120 L-60 120 Z"/></g><g class="apeiron-loader-water__wave wave-front"><path d="M-60 0 q15 -4 30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 t30 0 L240 120 L-60 120 Z"/></g><ellipse class="apeiron-loader-water__ripple ripple-one" cx="60" cy="0" rx="7" ry="2.2"/><ellipse class="apeiron-loader-water__ripple ripple-two" cx="60" cy="0" rx="7" ry="2.2"/></g></g>
							<g class="apeiron-loader-water__bowl"><path d="M28 78 C28 106 42 122 60 122 C78 122 92 106 92 78"/><ellipse cx="60" cy="78" rx="32" ry="4.5"/></g>
						</svg>
					</div>
					<?php endif; ?>

					<div class="apeiron-page-loader__progress">
						<?php if ( '' !== $loading_text ) : ?><div class="apeiron-page-loader__text"><?php echo esc_html( $loading_text ); ?></div><?php endif; ?>
						<div class="apeiron-page-loader__meter" aria-hidden="true"><span><i data-apeiron-page-loader-bar></i></span><?php if ( $show_percentage ) : ?><b class="apeiron-page-loader__percentage"><span data-apeiron-page-loader-percent>0</span>%</b><?php endif; ?></div>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<noscript><style>.apeiron-page-loader{display:none!important}</style></noscript>
<?php
