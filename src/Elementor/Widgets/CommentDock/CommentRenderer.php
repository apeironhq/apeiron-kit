<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\CommentDock;

use Elementor\Icons_Manager;
use ApeironKit\Support\CommentOwnership;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CommentRenderer {

	/** @var array{
	 *   date_format:string, reply_action_text:string, attendance_options:array,
	 *   display_mode:string, show_attendence:bool, show_avatar:bool, show_date:bool,
	 *   sticker_comment_position:string, default_avatar_url:string,
	 *   permissions:array, reply_depth_limit:int, post_id:int, element_id:string,
	 *   target_token:string
	 * } */
	private array $ctx;

	public function __construct( array $ctx ) {
		$this->ctx = $ctx;
	}

	public static function from_settings( array $settings, ?array $attendance_options = null ): self {
		$show_avatar = 'yes' === ( $settings['show_avatar'] ?? 'yes' );

		$sticker_position = $settings['sticker_comment_position'] ?? 'avatar';
		if ( ! in_array( $sticker_position, [ 'avatar', 'top', 'above_text', 'below_text', 'beside_text' ], true ) ) {
			$sticker_position = 'avatar';
		}
		if ( ! $show_avatar && 'avatar' === $sticker_position ) {
			$sticker_position = 'above_text';
		}

		$reply_action_text = trim( (string) ( $settings['reply_action_text'] ?? '' ) );
		if ( '' === $reply_action_text ) {
			$reply_action_text = __( 'Balas', 'apeiron-kit' );
		}

		$default_avatar_url = ! empty( $settings['default_avatar']['url'] )
			? $settings['default_avatar']['url']
			: APEIRON_KIT_URL . 'assets/img/man-avatar.webp';

		return new self(
			[
				'date_format'              => $settings['date_format_type'] ?? 'relative',
				'reply_action_text'        => $reply_action_text,
				'attendance_options'       => $attendance_options ?? self::prepare_attendance_options( $settings['attendance_options'] ?? [] ),
				'display_mode'             => $settings['attendance_display'] ?? 'icon',
				'show_attendence'          => 'yes' === ( $settings['attendence'] ?? 'yes' ),
				'show_avatar'              => $show_avatar,
				'show_date'                => 'yes' === ( $settings['show_date'] ?? 'yes' ),
				'sticker_comment_position' => $sticker_position,
				'default_avatar_url'       => $default_avatar_url,
				'permissions'              => [
					'comment_reply'  => 'yes' === ( $settings['enable_comment_reply'] ?? 'yes' ),
					'comment_edit'   => 'yes' === ( $settings['enable_comment_edit'] ?? 'yes' ),
					'comment_delete' => 'yes' === ( $settings['enable_comment_delete'] ?? 'yes' ),
					'reply_edit'     => 'yes' === ( $settings['enable_reply_edit'] ?? 'yes' ),
					'reply_delete'   => 'yes' === ( $settings['enable_reply_delete'] ?? 'yes' ),
				],
				'reply_depth_limit'        => max( 1, min( 10, (int) get_option( 'thread_comments_depth', 5 ) ) ),
				'post_id'                  => max( 0, (int) ( $settings['_apeiron_post_id'] ?? 0 ) ),
				'element_id'               => (string) ( $settings['_apeiron_element_id'] ?? '' ),
				'target_token'             => (string) ( $settings['_apeiron_target_token'] ?? '' ),
			]
		);
	}

	public function attendance_options(): array {
		return $this->ctx['attendance_options'];
	}

	/** @param array<int,object[]> $tree */
	public function render_item( $comment, array $tree = [], int $depth = 0, bool $hidden = false ): string {
		$ctx                      = $this->ctx;
		$settings                 = [ 'reply_action_text' => $ctx['reply_action_text'], 'date_format_type' => $ctx['date_format'] ];
		$attendance_options       = $ctx['attendance_options'];
		$display_mode             = $ctx['display_mode'];
		$show_attendence          = $ctx['show_attendence'];
		$show_avatar              = $ctx['show_avatar'];
		$show_date                = $ctx['show_date'];
		$sticker_comment_position = $ctx['sticker_comment_position'];
		$default_avatar_url       = $ctx['default_avatar_url'];
		$permissions              = $ctx['permissions'];
		$reply_depth_limit        = $ctx['reply_depth_limit'];

		$comment_id = (int) $comment->comment_ID;
		$is_reply   = (int) $comment->comment_parent > 0;
		$is_owner   = CommentOwnership::is_owner( $comment_id );
		$can_reply  = $permissions['comment_reply'] && $depth < max( 0, $reply_depth_limit - 1 );
		$can_edit   = $is_owner && ( $is_reply ? $permissions['reply_edit'] : $permissions['comment_edit'] );
		$can_delete = $is_owner && ( $is_reply ? $permissions['reply_delete'] : $permissions['comment_delete'] );
		$comment_sticker            = $this->get_comment_sticker( $comment_id );
		$effective_sticker_position = ( $is_reply && 'avatar' === $sticker_comment_position ) ? 'top' : $sticker_comment_position;
		$attendance_value = (string) get_comment_meta( $comment_id, 'apeiron_attendance_value', true );
		$attendance_label = (string) get_comment_meta( $comment_id, 'apeiron_attendance_label', true );
		$badge_index      = isset( $attendance_options[ $attendance_value ] ) ? $attendance_options[ $attendance_value ]['index'] : -1;
		$badge_html       = $is_reply ? '' : $this->render_attendance_badge(
			$display_mode,
			$attendance_value,
			$attendance_options,
			$attendance_label,
			$badge_index
		);
		$children     = $tree[ $comment_id ] ?? [];
		$item_classes = [ 'apeiron-kit-comment-item' ];
		if ( $is_reply ) {
			$item_classes[] = 'is-reply';
		}
		if ( ! empty( $comment_sticker ) && 'top' === $effective_sticker_position ) {
			$item_classes[] = 'has-top-sticker';
		}
		$reply_action_text = $ctx['reply_action_text'];

		ob_start();
		?>
		<li class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>"
			data-comment-id="<?php echo esc_attr( $comment_id ); ?>"
			data-parent-id="<?php echo esc_attr( (int) $comment->comment_parent ); ?>"
			data-raw-message="<?php echo esc_attr( $comment->comment_content ); ?>"
			data-attendance-value="<?php echo esc_attr( $is_reply ? '' : $attendance_value ); ?>"
			data-post-id="<?php echo esc_attr( $ctx['post_id'] ?: (int) $comment->comment_post_ID ); ?>"
			data-element-id="<?php echo esc_attr( $ctx['element_id'] ); ?>"
			data-target-token="<?php echo esc_attr( $ctx['target_token'] ); ?>"
			data-depth="<?php echo esc_attr( $depth ); ?>"
			data-can-reply="<?php echo esc_attr( $can_reply ? 'yes' : 'no' ); ?>"
			<?php echo $hidden ? 'style="display:none"' : ''; ?>>
			<div class="apeiron-kit-comment-wrapper">
				<?php if ( $show_avatar && ! $is_reply && ( empty( $comment_sticker ) || 'avatar' === $effective_sticker_position ) ) : ?>
					<?php
					$avatar_classes = [ 'apeiron-kit-comment-avatar' ];
					$avatar_sticker = 'avatar' === $effective_sticker_position ? $comment_sticker : [];
					if ( ! empty( $avatar_sticker ) ) {
						$avatar_classes[] = 'has-sticker';
					}
					?>
					<div class="<?php echo esc_attr( implode( ' ', $avatar_classes ) ); ?>">
						<?php echo $this->render_comment_avatar( $comment, $avatar_sticker, $default_avatar_url ); ?>
					</div>
				<?php endif; ?>
				<div class="apeiron-kit-comment-content <?php echo ! empty( $comment_sticker ) ? 'has-comment-sticker sticker-position-' . esc_attr( $effective_sticker_position ) : ''; ?>">
					<?php if ( ! empty( $comment_sticker ) && 'top' === $effective_sticker_position ) : ?>
						<?php echo $this->render_comment_sticker_media( $comment_sticker, $comment->comment_author ); ?>
					<?php endif; ?>
					<div class="apeiron-kit-comment-info">
						<span class="apeiron-kit-comment-author"><?php echo esc_html( $comment->comment_author ); ?></span>
						<?php if ( $show_attendence && ! $is_reply ) : ?>
							<?php echo $badge_html; ?>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $comment_sticker ) && 'above_text' === $effective_sticker_position ) : ?>
						<?php echo $this->render_comment_sticker_media( $comment_sticker, $comment->comment_author ); ?>
					<?php endif; ?>
					<div class="apeiron-kit-comment-text">
						<?php if ( ! empty( $comment_sticker ) && 'beside_text' === $effective_sticker_position ) : ?>
							<?php echo $this->render_comment_sticker_media( $comment_sticker, $comment->comment_author ); ?>
						<?php endif; ?>
						<?php echo wp_kses_post( wpautop( $comment->comment_content ) ); ?>
					</div>
					<?php if ( ! empty( $comment_sticker ) && 'below_text' === $effective_sticker_position ) : ?>
						<?php echo $this->render_comment_sticker_media( $comment_sticker, $comment->comment_author ); ?>
					<?php endif; ?>
					<div class="apeiron-kit-comment-footer">
						<?php if ( $show_date ) : ?>
							<span class="apeiron-kit-comment-time"><?php echo esc_html( $this->format_comment_date( $comment, $ctx['date_format'] ) ); ?></span>
						<?php endif; ?>
						<?php if ( $can_reply || $can_edit || $can_delete ) : ?>
							<div class="apeiron-kit-comment-actions" aria-label="<?php esc_attr_e( 'Aksi komentar', 'apeiron-kit' ); ?>">
								<?php if ( $can_reply ) : ?>
									<button type="button" class="apeiron-kit-comment-action" data-comment-action="reply"><?php echo esc_html( $reply_action_text ); ?></button>
								<?php endif; ?>
								<?php if ( $can_edit ) : ?>
									<button type="button" class="apeiron-kit-comment-action" data-comment-action="edit"><?php esc_html_e( 'Edit', 'apeiron-kit' ); ?></button>
								<?php endif; ?>
								<?php if ( $can_delete ) : ?>
									<button type="button" class="apeiron-kit-comment-action is-danger" data-comment-action="delete"><?php esc_html_e( 'Hapus', 'apeiron-kit' ); ?></button>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<ul class="apeiron-kit-comment-replies" <?php echo empty( $children ) ? 'hidden' : ''; ?>>
				<?php foreach ( $children as $child ) : ?>
					<?php echo $this->render_item( $child, $tree, $depth + 1, false ); ?>
				<?php endforeach; ?>
			</ul>
		</li>
		<?php
		return (string) ob_get_clean();
	}

	public static function prepare_attendance_options( array $options, array $raw_options = [] ): array {
		$prepared   = [];
		$index      = 0;
		$used_slugs = [];
		$raw_labels = self::map_raw_option_labels( $raw_options );

		foreach ( $options as $row_index => $option ) {
			// Derive stored attendance keys from saved labels, not resolved Dynamic Tags.
			$slug_source = $option['option_label'] ?? '';
			$row_id      = (string) ( $option['_id'] ?? '' );
			if ( '' !== $row_id && isset( $raw_labels['id'][ $row_id ] ) ) {
				$slug_source = $raw_labels['id'][ $row_id ];
			} elseif ( isset( $raw_labels['index'][ $row_index ] ) ) {
				$slug_source = $raw_labels['index'][ $row_index ];
			}

			$slug = ! empty( $option['option_slug'] ) ? sanitize_key( $option['option_slug'] ) : sanitize_title( $slug_source );
			if ( empty( $slug ) ) {
				continue;
			}

			$original_slug = $slug;
			$counter       = 2;
			while ( isset( $used_slugs[ $slug ] ) ) {
				$slug = $original_slug . '-' . $counter;
				$counter++;
			}
			$used_slugs[ $slug ] = true;

			$icon = '';
			if ( ! empty( $option['option_icon']['value'] ) ) {
				ob_start();
				Icons_Manager::render_icon(
					$option['option_icon'],
					[ 'aria-hidden' => 'true' ]
				);
				$icon = ob_get_clean();
			}

			$prepared[ $slug ] = [
				'label' => $option['option_label'],
				'icon'  => $icon,
				'index' => $index,
			];
			$index++;
		}

		return $prepared;
	}

	/** @return array{id:array<string,string>,index:array<int,string>} */
	private static function map_raw_option_labels( array $raw_options ): array {
		$by_id    = [];
		$by_index = [];

		foreach ( array_values( $raw_options ) as $row_index => $row ) {
			if ( ! is_array( $row ) || ! isset( $row['option_label'] ) || ! is_string( $row['option_label'] ) ) {
				continue;
			}

			$by_index[ $row_index ] = $row['option_label'];
			$row_id                 = (string) ( $row['_id'] ?? '' );
			if ( '' !== $row_id ) {
				$by_id[ $row_id ] = $row['option_label'];
			}
		}

		return [
			'id'    => $by_id,
			'index' => $by_index,
		];
	}

	private function render_attendance_badge( string $display_mode, string $value, array $options, string $fallback, int $index = -1 ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$option    = $options[ $value ] ?? null;
		$label     = $option['label'] ?? $fallback;
		$icon      = $option['icon'] ?? '';
		$has_label = 'icon-label' === $display_mode && ! empty( $label );

		if ( empty( $icon ) && ! $has_label ) {
			return '';
		}

		$wrapper_classes   = [ 'apeiron-kit-comment-pill' ];
		$wrapper_classes[] = 'icon' === $display_mode ? 'is-icon-only' : 'is-icon-text';

		if ( $index >= 0 ) {
			$wrapper_classes[] = $index <= 2 ? 'status-index-' . $index : 'status-index-3-plus';
		}

		$icon_markup = $icon ?: '';
		if ( empty( $icon_markup ) && ! empty( $label ) ) {
			$icon_markup = '<span class="apeiron-kit-comment-pill-dot"></span>';
		}

		$icon_html = sprintf( '<span class="apeiron-kit-comment-pill-icon">%s</span>', $icon_markup );
		$text_html = $has_label
			? sprintf( '<span class="apeiron-kit-comment-pill-text">%s</span>', esc_html( $label ) )
			: '';

		return sprintf(
			'<span class="%s">%s%s</span>',
			esc_attr( implode( ' ', $wrapper_classes ) ),
			$icon_html,
			$text_html
		);
	}

	private function get_comment_sticker( int $comment_id ): array {
		$relative = (string) get_comment_meta( $comment_id, 'apeiron_sticker_src', true );
		if ( empty( $relative ) ) {
			return [];
		}

		$resolved = StickerLibrary::resolve( $relative );
		if ( null === $resolved ) {
			return [];
		}

		return [
			'url'  => $resolved['url'],
			'type' => $resolved['type'],
		];
	}

	private function render_comment_avatar( $comment, array $sticker, string $default_avatar_url ): string {
		if ( ! empty( $sticker['url'] ) ) {
			return $this->render_comment_sticker_media( $sticker, $comment->comment_author );
		}

		$avatar_url = $default_avatar_url;
		if ( empty( $avatar_url ) ) {
			$avatar_url = APEIRON_KIT_URL . 'assets/img/man-avatar.webp';
		}

		return sprintf(
			'<img src="%1$s" alt="%2$s" />',
			esc_url( $avatar_url ),
			esc_attr( $comment->comment_author )
		);
	}

	private function render_comment_sticker_media( array $sticker, string $alt = '' ): string {
		if ( empty( $sticker['url'] ) ) {
			return '';
		}

		if ( 'video' === ( $sticker['type'] ?? 'image' ) ) {
			// Lazy: do not autoplay on render. A shared IntersectionObserver plays
			// the video only while it is on screen (see LazyVideo in comment-dock.js).
			$media = sprintf(
				'<video src="%1$s" class="apeiron-kit-sticker-avatar" data-apeiron-lazy-video preload="none" muted loop playsinline></video>',
				esc_url( $sticker['url'] )
			);
		} else {
			$media = sprintf(
				'<img src="%1$s" class="apeiron-kit-sticker-avatar" alt="%2$s" />',
				esc_url( $sticker['url'] ),
				esc_attr( $alt ?: __( 'Stiker', 'apeiron-kit' ) )
			);
		}

		return sprintf( '<span class="apeiron-kit-comment-sticker">%s</span>', $media );
	}

	private function format_comment_date( $comment, string $format_type = 'relative' ): string {
		$comment_date = get_comment_date( 'U', $comment );
		$current_time = time();
		$diff         = $current_time - (int) $comment_date;

		switch ( $format_type ) {
			case 'relative':
				return $this->get_relative_time( $diff );

			case 'custom':
				$day   = get_comment_date( 'j', $comment );
				$month = get_comment_date( 'F', $comment );
				$year  = get_comment_date( 'Y', $comment );

				$months = [
					'January'   => 'Januari',
					'February'  => 'Februari',
					'March'     => 'Maret',
					'April'     => 'April',
					'May'       => 'Mei',
					'June'      => 'Juni',
					'July'      => 'Juli',
					'August'    => 'Agustus',
					'September' => 'September',
					'October'   => 'Oktober',
					'November'  => 'November',
					'December'  => 'Desember',
				];

				$month_id = $months[ $month ] ?? $month;

				return sprintf( '%d %s %s', $day, $month_id, $year );

			case 'default':
			default:
				return get_comment_date( get_option( 'date_format' ), $comment );
		}
	}

	private function get_relative_time( int $diff ): string {
		if ( $diff < 60 ) {
			return __( 'Baru saja', 'apeiron-kit' );
		}

		$minutes = floor( $diff / 60 );
		if ( $minutes < 60 ) {
			return sprintf( _n( '%d menit yang lalu', '%d menit yang lalu', $minutes, 'apeiron-kit' ), $minutes );
		}

		$hours = floor( $diff / 3600 );
		if ( $hours < 24 ) {
			return sprintf( _n( '%d jam yang lalu', '%d jam yang lalu', $hours, 'apeiron-kit' ), $hours );
		}

		$days = floor( $diff / 86400 );
		if ( $days < 7 ) {
			return sprintf( _n( '%d hari yang lalu', '%d hari yang lalu', $days, 'apeiron-kit' ), $days );
		}

		$weeks = floor( $days / 7 );
		if ( $weeks < 4 ) {
			return sprintf( _n( '%d minggu yang lalu', '%d minggu yang lalu', $weeks, 'apeiron-kit' ), $weeks );
		}

		$months = floor( $days / 30 );
		if ( $months < 12 ) {
			return sprintf( _n( '%d bulan yang lalu', '%d bulan yang lalu', $months, 'apeiron-kit' ), $months );
		}

		$years = floor( $days / 365 );
		return sprintf( _n( '%d tahun yang lalu', '%d tahun yang lalu', $years, 'apeiron-kit' ), $years );
	}
}
