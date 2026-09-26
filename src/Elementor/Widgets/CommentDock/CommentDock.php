<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\CommentDock;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\CommentDock\CommentRenderer;
use ApeironKit\Elementor\Widgets\CommentDock\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\CommentDock\Concerns\RegistersStyleControls;
use ApeironKit\Elementor\Widgets\CommentDock\StickerLibrary;
use ApeironKit\Support\CommentDockSettingsStore;
use ApeironKit\Support\UcapanTamuSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CommentDock extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	private const LIST_MODES = [ 'auto', 'scroll', 'pagination' ];

	private const POPUP_ANIMATIONS = [ 'fade-scale', 'slide-up', 'slide-down', 'fade', 'none' ];

	private const STICKER_POSITIONS = [ 'avatar', 'top', 'above_text', 'below_text', 'beside_text' ];

	public function get_name() {
		return 'apeiron-comment-dock';
	}

	public function get_title() {
		return __( 'Ucapan Tamu', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'apeiron-icon-comment';
	}

	public function get_keywords() {
		return [ 'comment', 'ucapan', 'tamu', 'guestbook', 'rsvp', 'wish' ];
	}

	public function get_style_depends() {
		$styles   = parent::get_style_depends();
		$styles[] = 'apeiron-kit-comment-dock';

		return $styles;
	}

	public function get_script_depends() {
		$scripts   = parent::get_script_depends();
		$scripts[] = 'apeiron-kit-comment-dock-js';

		return $scripts;
	}

	protected function register_widget_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	protected function render_widget() {
		$element_data = method_exists( $this, 'get_data' ) ? $this->get_data() : [];
		$raw_settings = is_array( $element_data['settings'] ?? null ) ? $element_data['settings'] : $this->get_settings();
		$settings     = $this->get_settings_for_display();
		$is_editor    = $this->is_elementor_editor_preview();
		$settings     = UcapanTamuSettings::resolve_widget_settings(
			is_array( $settings ) ? $settings : [],
			is_array( $raw_settings ) ? $raw_settings : []
		);

		$context = $this->build_render_context( $settings, is_array( $raw_settings ) ? $raw_settings : [], $is_editor );

		/**
		 * Filter the full render context before markup is emitted.
		 *
		 * @since 1.1.0
		 *
		 * @param array $context  Render context passed to the partial.
		 * @param array $settings Resolved settings array.
		 * @param self  $widget   Widget instance.
		 */
		$context = (array) apply_filters( 'apeiron_comment_dock_render_context', $context, $settings, $this );

		$settings                         = (array) $context['settings'];
		$element_id                       = (string) $context['element_id'];
		$post_id                          = (int) $context['post_id'];
		$target_token                     = (string) $context['target_token'];
		$wrapper_classes                  = (array) $context['wrapper_classes'];
		$display_mode                     = (string) $context['display_mode'];
		$attendance_options               = (array) $context['attendance_options'];
		$attendance_placeholder           = (string) $context['attendance_placeholder'];
		$show_attendence                  = (bool) $context['show_attendence'];
		$attendance_summary_position      = (string) $context['attendance_summary_position'];
		$attendance_summary_html          = (string) $context['attendance_summary_html'];
		$use_pagination                   = (bool) $context['use_pagination'];
		$show_avatar                      = (bool) $context['show_avatar'];
		$show_date                        = (bool) $context['show_date'];
		$list_display_mode                = (string) $context['list_display_mode'];
		$list_classes                     = (array) $context['list_classes'];
		$sticker_comment_position         = (string) $context['sticker_comment_position'];
		$default_avatar_url               = (string) $context['default_avatar_url'];
		$permissions                      = (array) $context['permissions'];
		$reply_depth_limit                = (int) $context['reply_depth_limit'];
		$reply_action_text                = (string) $context['reply_action_text'];
		$reply_name_placeholder           = (string) $context['reply_name_placeholder'];
		$reply_message_placeholder        = (string) $context['reply_message_placeholder'];
		$reply_submit_text                = (string) $context['reply_submit_text'];
		$reply_cancel_text                = (string) $context['reply_cancel_text'];
		$reply_popup_title                = (string) $context['reply_popup_title'];
		$reply_success_message            = (string) $context['reply_success_message'];
		$reply_error_message              = (string) $context['reply_error_message'];
		$guest_name_from_url_enabled      = (bool) $context['guest_name_from_url_enabled'];
		$guest_required_message           = (string) $context['guest_required_message'];
		$invited_guest_name               = (string) $context['invited_guest_name'];
		$invited_guest_token              = (string) $context['invited_guest_token'];
		$is_guest_restricted_without_name = (bool) $context['is_guest_restricted_without_name'];
		$enable_reply_sticker             = (bool) $context['enable_reply_sticker'];
		$show_stickers                    = (bool) $context['show_stickers'];
		$sticker_label                    = (string) $context['sticker_label'];
		$sticker_display_mode             = (string) $context['sticker_display_mode'];
		$sticker_popup_position           = (string) $context['sticker_popup_position'];
		$sticker_trigger_label            = (string) $context['sticker_trigger_label'];
		$sticker_trigger_icon_text        = (string) $context['sticker_trigger_icon_text'];
		$sticker_popup_animation          = (string) $context['sticker_popup_animation'];
		$sticker_popup_close_animation    = (string) $context['sticker_popup_close_animation'];
		$stickers                         = (array) $context['stickers'];
		$image_stickers                   = (array) $context['image_stickers'];
		$video_stickers                   = (array) $context['video_stickers'];
		$comments                         = (array) $context['comments'];
		$comment_tree                     = (array) $context['comment_tree'];
		$comment_renderer                 = $context['comment_renderer'];
		$items_per_page                   = (int) $context['items_per_page'];
		$initial_batch                    = (int) $context['initial_batch'];
		$total                            = (int) $context['total'];
		$has_more_top                     = (bool) $context['has_more_top'];
		$is_editor                        = (bool) $context['is_editor'];

		require __DIR__ . '/Partials/comment-dock.php';
	}

	private function build_render_context( array $settings, array $raw_settings, bool $is_editor ): array {
		// `get_the_ID()` can point at a Theme Builder template or loop item while
		// rendering. Comments belong to the viewed page, not that render context.
		$post_id    = get_queried_object_id() ?: ( get_the_ID() ?: 0 );
		$element_id = $this->get_id();

		$guest_name_from_url_enabled = 'yes' === ( $settings['enable_guest_name_from_url'] ?? '' );
		$guest_required_message      = trim( (string) ( $settings['guest_required_message'] ?? '' ) );
		if ( '' === $guest_required_message ) {
			$guest_required_message = __( '* Ucapan hanya dapat dikirim oleh tamu yang menerima undangan ini.', 'apeiron-kit' );
		}

		$invited_guest_name = '';
		if ( $guest_name_from_url_enabled && isset( $_GET['to'] ) && ! is_array( $_GET['to'] ) ) {
			$invited_guest_name = sanitize_text_field( wp_unslash( (string) $_GET['to'] ) );
		}

		// Require a pre-shared guest_token; never mint one from public `to` input.
		$invited_guest_token = '';
		if ( '' !== $invited_guest_name && isset( $_GET['guest_token'] ) && is_string( $_GET['guest_token'] ) ) {
			$provided = sanitize_text_field( wp_unslash( (string) $_GET['guest_token'] ) );
			$expected = wp_hash( 'apeiron-invited-guest|' . $post_id . '|' . $element_id . '|' . $invited_guest_name );
			if ( hash_equals( $expected, $provided ) ) {
				$invited_guest_token = $provided;
			}
		}

		$is_guest_restricted_without_name = $guest_name_from_url_enabled && (
			'' === $invited_guest_name || '' === $invited_guest_token
		);

		// Elementor save is authoritative; front-end render only self-heals REST fallback.
		if ( $post_id && $element_id && ! $is_editor ) {
			CommentDockSettingsStore::persist_once( $post_id, $element_id, $raw_settings ?: $settings );
		}

		$target_token = wp_hash( 'apeiron-comment-dock|' . $post_id . '|' . $element_id );

		$comment_list_mode = $this->resolve_list_mode( $settings );
		$use_pagination    = 'pagination' === $comment_list_mode;
		$items_per_page    = max( 1, (int) ( $settings['items_per_page'] ?? 5 ) );
		$comments_limit    = max( 1, (int) ( $settings['comments_limit'] ?? 10 ) );

		// Bound top-level comments in SQL; REST tree mode loads later batches.
		$top_limit = $use_pagination ? $items_per_page : $comments_limit;
		if ( in_array( $comment_list_mode, [ 'auto', 'scroll' ], true ) ) {
			// One extra item detects overflow without loading the full guestbook.
			$top_limit++;
		}
		$top_limit = max( 1, min( 200, $top_limit ) );

		// Keep a bounded comment batch visible during Elementor control rerenders.
		$query_result = $this->query_comments( $post_id, $element_id, $top_limit );

		$top_comments = $query_result['top_comments'];
		$comment_tree = $this->build_comment_tree( $query_result['comments'] );

		$show_avatar        = 'yes' === ( $settings['show_avatar'] ?? 'yes' );
		$show_attendence    = 'yes' === ( $settings['attendence'] ?? 'yes' );
		$show_date          = 'yes' === ( $settings['show_date'] ?? 'yes' );
		$default_avatar_url = ! empty( $settings['default_avatar']['url'] )
			? $settings['default_avatar']['url']
			: APEIRON_KIT_URL . 'assets/img/man-avatar.webp';

		$show_stickers            = 'yes' === ( $settings['show_stickers'] ?? 'yes' );
		$sticker_label            = $settings['sticker_label'] ?? __( 'Stiker', 'apeiron-kit' );
		[ $allow_image, $allow_video ] = StickerLibrary::resolve_media_permissions( $settings, $raw_settings );
		$stickers                 = $show_stickers
			? StickerLibrary::load( StickerLibrary::DEFAULT_FOLDER, $allow_image, $allow_video )
			: [];
		$show_stickers            = $show_stickers && ! empty( $stickers );
		$enable_reply_sticker     = $show_stickers && 'yes' === ( $settings['enable_reply_sticker'] ?? 'yes' );
		$sticker_display_mode     = ( $settings['sticker_display_mode'] ?? 'popup' ) === 'inline' ? 'inline' : 'popup';
		$sticker_popup_position   = ( $settings['sticker_popup_position'] ?? 'center' ) === 'bottom' ? 'bottom' : 'center';
		$sticker_trigger_label    = $settings['sticker_trigger_label'] ?? __( 'Pilih Stiker', 'apeiron-kit' );
		$sticker_trigger_icon_text = $settings['sticker_trigger_icon_text'] ?? '+';
		$sticker_popup_animation  = $this->resolve_popup_animation( (string) ( $settings['sticker_popup_animation'] ?? 'fade-scale' ) );
		$sticker_popup_close_animation = $this->resolve_popup_close_animation( (string) ( $settings['sticker_popup_close_animation'] ?? 'same' ), $sticker_popup_animation );
		$sticker_comment_position = $this->resolve_sticker_position( (string) ( $settings['sticker_comment_position'] ?? 'avatar' ), $show_avatar );

		$image_stickers = [];
		$video_stickers = [];
		foreach ( $stickers as $sticker ) {
			if ( 'video' === ( $sticker['type'] ?? 'image' ) ) {
				$video_stickers[] = $sticker;
			} else {
				$image_stickers[] = $sticker;
			}
		}

		if ( $use_pagination ) {
			$total = $is_editor
				? count( $top_comments )
				: $this->count_top_level_comments( $post_id, $element_id );
		} else {
			$total = count( $top_comments );
		}

		if ( $use_pagination ) {
			$initial_batch = $items_per_page;
			$comments      = $top_comments;
			$has_more_top  = false;
		} else {
			$initial_batch = $comments_limit;
			$comments      = array_slice( $top_comments, 0, $initial_batch );
			$has_more_top  = count( $top_comments ) > count( $comments );
		}

		$display_mode                = $settings['attendance_display'] ?? 'icon';
		// Derive attendance slugs from saved labels, not resolved Dynamic Tags.
		$attendance_options          = CommentRenderer::prepare_attendance_options(
			$settings['attendance_options'] ?? [],
			is_array( $raw_settings['attendance_options'] ?? null ) ? $raw_settings['attendance_options'] : []
		);
		$renderer_settings           = array_merge(
			$settings,
			[
				'_apeiron_post_id'      => $post_id,
				'_apeiron_element_id'   => $element_id,
				'_apeiron_target_token' => $target_token,
			]
		);
		$comment_renderer            = CommentRenderer::from_settings( $renderer_settings, $attendance_options );
		$show_attendance_summary     = $show_attendence && 'yes' === ( $settings['show_attendance_summary'] ?? 'yes' );
		$attendance_counts           = $show_attendance_summary
			? $this->get_attendance_counts( $post_id, $element_id, $attendance_options )
			: [];
		$attendance_summary_position = $settings['attendance_summary_position'] ?? 'after_submit';
		$attendance_placeholder      = trim( (string) ( $settings['attendance_placeholder'] ?? '' ) );
		$attendance_placeholder      = '' !== $attendance_placeholder ? $attendance_placeholder : __( 'Pilih salah satu', 'apeiron-kit' );
		$attendance_summary_html     = ( ! empty( $attendance_options ) && $show_attendance_summary )
			? $this->render_attendance_summary( $settings, $attendance_options, $attendance_counts )
			: '';

		$list_classes      = [ 'apeiron-kit-comment-list' ];
		$list_display_mode = 'scroll' === $comment_list_mode ? 'scroll' : 'auto';
		if ( ! $use_pagination ) {
			if ( 'scroll' === $list_display_mode ) {
				$list_classes[] = 'is-scrollable';
			} elseif ( $has_more_top ) {
				$list_classes[] = 'apeiron-kit-comment-list-scrollable';
			}
		}

		$reply_action_text         = $this->text_or_default( $settings, 'reply_action_text', __( 'Balas', 'apeiron-kit' ) );
		$reply_name_placeholder    = $this->text_or_default( $settings, 'reply_name_placeholder', __( 'Nama Lengkap', 'apeiron-kit' ) );
		$reply_message_placeholder = $this->text_or_default( $settings, 'reply_message_placeholder', __( 'Tulis balasan...', 'apeiron-kit' ) );
		$reply_submit_text         = $this->text_or_default( $settings, 'reply_submit_text', __( 'Kirim Balasan', 'apeiron-kit' ) );
		$reply_cancel_text         = $this->text_or_default( $settings, 'reply_cancel_text', __( 'Batal', 'apeiron-kit' ) );
		$reply_popup_title         = $this->text_or_default( $settings, 'reply_popup_title', __( 'Balas Komentar', 'apeiron-kit' ) );
		$reply_success_message     = $this->text_or_default( $settings, 'reply_success_message', __( 'Balasan dikirim.', 'apeiron-kit' ) );
		$reply_error_message       = $this->text_or_default( $settings, 'reply_error_message', __( 'Gagal mengirim balasan.', 'apeiron-kit' ) );

		$wrapper_classes = [ 'apeiron-comment-dock' ];
		$wrapper_classes[] = 'icon' === $display_mode ? 'is-icon-only' : 'is-icon-text';
		if ( ! array_key_exists( 'fields_focus_border_color', $raw_settings ) ) {
			$wrapper_classes[] = 'uses-default-focus-border-color';
		}
		if ( ! array_key_exists( 'button_border_border', $raw_settings ) ) {
			$wrapper_classes[] = 'uses-default-submit-border';
		}
		if ( ! array_key_exists( 'pagination_border_border', $raw_settings ) ) {
			$wrapper_classes[] = 'uses-default-pagination-border';
		}
		foreach ( [ 'sticker_popup', 'modal_dialog' ] as $shadow ) {
			$type_key = $shadow . '_box_shadow_type';
			if ( array_key_exists( $type_key, $raw_settings ) && 'yes' !== $raw_settings[ $type_key ] ) {
				$wrapper_classes[] = 'has-' . str_replace( '_', '-', $shadow ) . '-shadow-disabled';
			}
		}

		$attendance_item_sizing = $settings['attendance_summary_item_sizing'] ?? 'fit_equal';
		if ( 'equal' === $attendance_item_sizing ) {
			$wrapper_classes[] = 'is-attendance-items-equal';
		} elseif ( 'auto' === $attendance_item_sizing ) {
			$wrapper_classes[] = 'is-attendance-items-auto';
		} else {
			$wrapper_classes[] = 'is-attendance-items-fit-equal';
		}

		return [
			'settings'                         => $settings,
			'is_editor'                        => $is_editor,
			'element_id'                       => $element_id,
			'post_id'                          => $post_id,
			'target_token'                     => $target_token,
			'wrapper_classes'                  => $wrapper_classes,
			'display_mode'                     => $display_mode,
			'attendance_options'               => $attendance_options,
			'attendance_placeholder'           => $attendance_placeholder,
			'show_attendence'                  => $show_attendence,
			'attendance_summary_position'      => $attendance_summary_position,
			'attendance_summary_html'          => $attendance_summary_html,
			'use_pagination'                   => $use_pagination,
			'show_avatar'                      => $show_avatar,
			'show_date'                        => $show_date,
			'list_display_mode'                => $list_display_mode,
			'list_classes'                     => $list_classes,
			'sticker_comment_position'         => $sticker_comment_position,
			'default_avatar_url'               => $default_avatar_url,
			'permissions'                      => $this->get_permission_settings( $settings ),
			'reply_depth_limit'                => $this->get_reply_depth_limit(),
			'reply_action_text'                => $reply_action_text,
			'reply_name_placeholder'           => $reply_name_placeholder,
			'reply_message_placeholder'        => $reply_message_placeholder,
			'reply_submit_text'                => $reply_submit_text,
			'reply_cancel_text'                => $reply_cancel_text,
			'reply_popup_title'                => $reply_popup_title,
			'reply_success_message'            => $reply_success_message,
			'reply_error_message'              => $reply_error_message,
			'guest_name_from_url_enabled'      => $guest_name_from_url_enabled,
			'guest_required_message'           => $guest_required_message,
			'invited_guest_name'               => $invited_guest_name,
			'invited_guest_token'              => $invited_guest_token,
			'is_guest_restricted_without_name' => $is_guest_restricted_without_name,
			'enable_reply_sticker'             => $enable_reply_sticker,
			'show_stickers'                    => $show_stickers,
			'sticker_label'                    => $sticker_label,
			'sticker_display_mode'             => $sticker_display_mode,
			'sticker_popup_position'           => $sticker_popup_position,
			'sticker_trigger_label'            => $sticker_trigger_label,
			'sticker_trigger_icon_text'        => $sticker_trigger_icon_text,
			'sticker_popup_animation'          => $sticker_popup_animation,
			'sticker_popup_close_animation'    => $sticker_popup_close_animation,
			'stickers'                         => $stickers,
			'image_stickers'                   => $image_stickers,
			'video_stickers'                   => $video_stickers,
			'comments'                         => $comments,
			'comment_tree'                     => $comment_tree,
			'comment_renderer'                 => $comment_renderer,
			'items_per_page'                   => $items_per_page,
			'initial_batch'                    => $initial_batch,
			'total'                            => $total,
			'has_more_top'                     => $has_more_top,
		];
	}

	private function resolve_list_mode( array $settings ): string {
		$mode = (string) ( $settings['comment_list_mode'] ?? '' );

		if ( '' === $mode ) {
			$mode = 'yes' === ( $settings['use_pagination'] ?? 'no' )
				? 'pagination'
				: ( $settings['list_display_mode'] ?? 'auto' );
		}

		return in_array( $mode, self::LIST_MODES, true ) ? $mode : 'auto';
	}

	private function resolve_popup_animation( string $animation ): string {
		return in_array( $animation, self::POPUP_ANIMATIONS, true ) ? $animation : 'fade-scale';
	}

	private function resolve_popup_close_animation( string $close_animation, string $open_animation ): string {
		if ( 'same' === $close_animation ) {
			return $open_animation;
		}

		return in_array( $close_animation, self::POPUP_ANIMATIONS, true ) ? $close_animation : $open_animation;
	}

	private function resolve_sticker_position( string $position, bool $show_avatar ): string {
		if ( ! in_array( $position, self::STICKER_POSITIONS, true ) ) {
			$position = 'avatar';
		}
		if ( ! $show_avatar && 'avatar' === $position ) {
			$position = 'above_text';
		}

		return $position;
	}

	private function text_or_default( array $settings, string $key, string $default ): string {
		$value = trim( (string) ( $settings[ $key ] ?? '' ) );

		return '' !== $value ? $value : $default;
	}

	/**
	 * @param object[] $comments
	 * @return array<int,object[]>
	 */
	private function build_comment_tree( array $comments ): array {
		$tree = [];
		foreach ( $comments as $comment ) {
			$parent_id            = max( 0, (int) ( $comment->comment_parent ?? 0 ) );
			$tree[ $parent_id ][] = $comment;
		}

		return $tree;
	}

	/**
	 * @param array<string,mixed> $settings
	 * @return array<string,bool>
	 */
	private function get_permission_settings( array $settings ): array {
		return [
			'comment_reply'  => 'yes' === ( $settings['enable_comment_reply'] ?? 'yes' ),
			'comment_edit'   => 'yes' === ( $settings['enable_comment_edit'] ?? 'yes' ),
			'comment_delete' => 'yes' === ( $settings['enable_comment_delete'] ?? 'yes' ),
			'reply_edit'     => 'yes' === ( $settings['enable_reply_edit'] ?? 'yes' ),
			'reply_delete'   => 'yes' === ( $settings['enable_reply_delete'] ?? 'yes' ),
		];
	}

	private function get_reply_depth_limit(): int {
		return max( 1, min( 10, (int) get_option( 'thread_comments_depth', 5 ) ) );
	}

	/** @return array{comments:object[],top_comments:object[]} */
	private function query_comments( int $post_id, string $element_id, int $top_limit = 100 ): array {
		if ( ! $post_id || '' === $element_id || $top_limit <= 0 ) {
			return [
				'comments'     => [],
				'top_comments' => [],
			];
		}

		$args = [
			'post_id'    => $post_id,
			'status'     => 'approve',
			'meta_query' => [
				[
					'key'   => 'apeiron_element_id',
					'value' => $element_id,
				],
			],
		];

		$top_comments = get_comments(
			array_merge(
				$args,
				[
					'parent' => 0,
					'number' => $top_limit,
					'orderby' => 'comment_date_gmt',
					'order'   => 'DESC',
				]
			)
		);

		// Walk descendants level-by-level so initial render matches REST tree mode.
		$pending_ids = array_map( static fn( $c ): int => (int) $c->comment_ID, $top_comments );
		$seen_ids    = array_fill_keys( $pending_ids, true );
		$replies     = [];
		$depth       = 0;
		$reply_limit = max( 1, min( 2000, $top_limit * 20 ) );

		while ( ! empty( $pending_ids ) && $depth < $this->get_reply_depth_limit() && count( $replies ) < $reply_limit ) {
			$remaining = $reply_limit - count( $replies );
			$batch     = get_comments(
				array_merge(
					$args,
					[
						'parent__in' => $pending_ids,
						'number'     => $remaining,
						'orderby'    => 'comment_date_gmt',
						'order'      => 'ASC',
					]
				)
			);
			$pending_ids = [];

			foreach ( $batch as $reply ) {
				$reply_id = (int) $reply->comment_ID;
				if ( isset( $seen_ids[ $reply_id ] ) ) {
					continue;
				}

				$seen_ids[ $reply_id ] = true;
				$replies[]              = $reply;
				$pending_ids[]           = $reply_id;
			}
			++$depth;
		}

		return [
			'comments'     => array_merge( $top_comments, $replies ),
			'top_comments' => $top_comments,
		];
	}

	private function count_top_level_comments( int $post_id, string $element_id ): int {
		if ( ! $post_id || '' === $element_id ) {
			return 0;
		}

		return (int) get_comments(
			[
				'post_id'    => $post_id,
				'status'     => 'approve',
				'parent'     => 0,
				'count'      => true,
				'meta_query' => [
					[
						'key'   => 'apeiron_element_id',
						'value' => $element_id,
					],
				],
			]
		);
	}

	/** @return array<string,int> */
	private function get_attendance_counts( int $post_id, string $element_id, array $options ): array {
		global $wpdb;

		$counts = array_fill_keys( array_keys( $options ), 0 );
		if ( $post_id <= 0 || '' === $element_id || empty( $counts ) ) {
			return $counts;
		}

		$values       = array_keys( $counts );
		$placeholders = implode( ', ', array_fill( 0, count( $values ), '%s' ) );
		$sql          = "
			SELECT attendance.meta_value, COUNT(DISTINCT comments.comment_ID)
			FROM {$wpdb->comments} AS comments
			INNER JOIN {$wpdb->commentmeta} AS attendance
				ON attendance.comment_id = comments.comment_ID
				AND attendance.meta_key = %s
			WHERE comments.comment_post_ID = %d
				AND comments.comment_parent = 0
				AND comments.comment_approved = '1'
				AND attendance.meta_value IN ({$placeholders})
				AND EXISTS (
					SELECT 1
					FROM {$wpdb->commentmeta} AS element
					WHERE element.comment_id = comments.comment_ID
						AND element.meta_key = %s
						AND element.meta_value = %s
				)
			GROUP BY attendance.meta_value
		";
		$params       = array_merge( [ 'apeiron_attendance_value', $post_id ], $values, [ 'apeiron_element_id', $element_id ] );
		$rows         = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_N );

		foreach ( $rows as $row ) {
			$value = (string) $row[0];
			if ( isset( $counts[ $value ] ) ) {
				$counts[ $value ] = (int) $row[1];
			}
		}

		return $counts;
	}

	private function render_attendance_summary( array $settings, array $options, array $counts ): string {
		if ( 'yes' !== ( $settings['show_attendance_summary'] ?? 'yes' ) || empty( $options ) ) {
			return '';
		}

		$show_title      = 'yes' === ( $settings['show_attendance_summary_title'] ?? 'yes' );
		$show_title_line = 'yes' === ( $settings['show_attendance_summary_title_line'] ?? '' );
		$show_count      = 'yes' === ( $settings['show_attendance_summary_count'] ?? 'yes' );
		$show_label      = 'yes' === ( $settings['show_attendance_summary_label'] ?? 'yes' );

		if ( ! $show_title && ! $show_count && ! $show_label ) {
			return '';
		}

		ob_start();
		?>
		<div class="apeiron-kit-attendance-summary" data-attendance-summary>
			<?php if ( $show_title && ! empty( $settings['attendance_summary_title'] ) ) : ?>
				<div class="apeiron-kit-attendance-summary-head">
					<?php if ( $show_title_line ) : ?>
						<span class="apeiron-kit-attendance-summary-line" aria-hidden="true"></span>
					<?php endif; ?>
					<span class="apeiron-kit-attendance-summary-title"><?php echo esc_html( $settings['attendance_summary_title'] ); ?></span>
					<?php if ( $show_title_line ) : ?>
						<span class="apeiron-kit-attendance-summary-line" aria-hidden="true"></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="apeiron-kit-attendance-summary-list">
				<?php foreach ( $options as $slug => $option ) : ?>
					<?php
					$index        = isset( $option['index'] ) ? (int) $option['index'] : -1;
					$item_classes = [ 'apeiron-kit-attendance-summary-item' ];
					if ( $index >= 0 ) {
						$item_classes[] = $index <= 2 ? 'status-index-' . $index : 'status-index-3-plus';
					}
					$count = (int) ( $counts[ $slug ] ?? 0 );
					?>
					<div class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>" data-attendance-summary-item="<?php echo esc_attr( $slug ); ?>">
						<?php if ( $show_count ) : ?>
							<div class="apeiron-kit-attendance-summary-count" data-attendance-value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( (string) $count ); ?></div>
						<?php endif; ?>
						<?php if ( $show_label ) : ?>
							<div class="apeiron-kit-attendance-summary-label"><?php echo esc_html( $option['label'] ?? $slug ); ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
