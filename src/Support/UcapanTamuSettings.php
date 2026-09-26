<?php

namespace ApeironKit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UcapanTamuSettings {

	public const OPTION_NAME        = 'apeiron_ucapan_tamu_settings';
	private const LEGACY_OPTION_NAME = 'apeiron_kit_settings';

	public static function defaults(): array {
		return [
			'comment_moderation'          => 'auto',
			'max_message_length'          => 1000,
			'prevent_duplicate'           => '',
			'enable_rate_limit'           => 'yes',
			'enable_guest_name_from_url'  => '',
			'guest_required_message'      => __( '* Ucapan hanya dapat dikirim oleh tamu yang menerima undangan ini.', 'apeiron-kit' ),
			'success_message'             => __( 'Terima kasih atas ucapannya!', 'apeiron-kit' ),
			'error_message'               => __( 'Gagal mengirim komentar.', 'apeiron-kit' ),
			'reply_action_text'           => __( 'Balas', 'apeiron-kit' ),
			'reply_name_placeholder'      => __( 'Nama Lengkap', 'apeiron-kit' ),
			'reply_message_placeholder'   => __( 'Tulis balasan...', 'apeiron-kit' ),
			'reply_submit_text'           => __( 'Kirim Balasan', 'apeiron-kit' ),
			'reply_cancel_text'           => __( 'Batal', 'apeiron-kit' ),
			'reply_popup_title'           => __( 'Balas Komentar', 'apeiron-kit' ),
			'reply_success_message'       => __( 'Balasan dikirim.', 'apeiron-kit' ),
			'reply_error_message'         => __( 'Gagal mengirim balasan.', 'apeiron-kit' ),
			'show_stickers'               => 'yes',
			'sticker_display_mode'        => 'popup',
			'sticker_media_type'          => 'all',
			'sticker_popup_animation'     => 'fade-scale',
			'sticker_popup_close_animation' => 'same',
			'enable_reply_sticker'        => 'yes',
			'attendence'                  => 'yes',
			'attendance_placeholder'      => __( 'Pilih salah satu', 'apeiron-kit' ),
			'comment_list_mode'           => 'auto',
			'comments_limit'              => 10,
			'items_per_page'              => 5,
			'comment_edit_mode'           => 'popup',
			'comment_reply_mode'          => 'popup',
			'enable_comment_reply'        => 'yes',
			'enable_comment_edit'         => 'yes',
			'enable_comment_delete'       => 'yes',
			'enable_reply_edit'           => 'yes',
			'enable_reply_delete'         => 'yes',
			'style_preset'                => 'clean',
			'style_primary_color'         => '#083c57',
			'style_radius_scale'          => 'soft',
			'style_density'               => 'normal',
			'style_sticker_size'          => 'normal',
			'style_reply_popup_width'     => 520,
			'style_reply_popup_position'  => 'center',
			'style_reply_popup_vertical_offset' => 0,
			'style_reply_popup_field_gap' => 10,
			'style_reply_popup_overlay_color' => '#0f172a',
			'style_reply_popup_overlay_opacity' => 38,
			'style_reply_popup_title_color' => '#253044',
			'style_reply_popup_title_font_size' => 15,
			'style_reply_popup_title_font_weight' => '700',
			'style_reply_popup_textarea_height' => 150,
			'style_delete_popup_animation' => 'soft-scale',
			'style_delete_popup_button_color' => '#ffffff',
			'style_delete_popup_button_background' => '#b42318',
			'style_delete_popup_cancel_color' => '#253044',
			'style_sticker_popup_width'  => 460,
			'style_sticker_popup_height' => 560,
			'style_sticker_popup_position' => 'center',
			'style_sticker_popup_radius' => 14,
			'style_sticker_popup_overlay_color' => '#0f172a',
			'style_sticker_popup_overlay_opacity' => 38,
			'style_sticker_popup_background' => '#ffffff',
			'style_sticker_popup_title_color' => '#253044',
			'style_sticker_tab_background' => '#fbfcff',
			'style_sticker_tab_color' => '#6b7890',
			'style_sticker_tab_active_background' => '#083c57',
			'style_sticker_tab_active_color' => '#ffffff',
			'style_sticker_close_background' => '#fbfcff',
			'style_sticker_close_color' => '#6b7890',
			'style_reply_button_padding_top' => 8,
			'style_reply_button_padding_right' => 12,
			'style_reply_button_padding_bottom' => 8,
			'style_reply_button_padding_left' => 12,
			'style_reply_button_gap' => 8,
			'style_reply_button_radius' => 8,
			'style_reply_submit_button_color' => '#ffffff',
			'style_reply_submit_button_background' => '#083c57',
			'style_reply_cancel_button_color' => '#253044',
			'style_reply_cancel_button_background' => '#ffffff',
			'style_notice_background' => '#ecfdf5',
			'style_notice_text_color' => '#047857',
			'style_notice_border_color' => '#bbf7d0',
			'style_notice_radius' => 8,
			'style_notice_padding_top' => 10,
			'style_notice_padding_right' => 12,
			'style_notice_padding_bottom' => 10,
			'style_notice_padding_left' => 12,
			'style_notice_alignment' => 'left',
			'style_notice_error_background' => '#fef2f2',
			'style_notice_error_text_color' => '#991b1b',
			'style_notice_error_border_color' => '#fecaca',
		];
	}

	public static function widget_defaults(): array {
		return self::defaults();
	}

	public static function get_settings(): array {
		$saved = get_option( self::OPTION_NAME, null );

		if ( null === $saved ) {
			$legacy   = get_option( self::LEGACY_OPTION_NAME, [] );
			$migrated = self::defaults();

			if ( isset( $legacy['comment_moderation'] ) ) {
				$migrated['comment_moderation'] = 'manual' === $legacy['comment_moderation'] ? 'manual' : 'auto';
			}

			update_option( self::OPTION_NAME, $migrated, false );
			return $migrated;
		}

		return self::sanitize( wp_parse_args( (array) $saved, self::defaults() ) );
	}

	public static function sanitize( $input ): array {
		$input    = (array) $input;
		$settings = self::defaults();

		$settings['comment_moderation'] = 'manual' === ( $input['comment_moderation'] ?? 'auto' ) ? 'manual' : 'auto';
		$settings['max_message_length'] = self::sanitize_int_range( $input['max_message_length'] ?? 1000, 50, 5000, 1000 );
		$settings['prevent_duplicate']  = self::sanitize_switch( $input['prevent_duplicate'] ?? '' );
		$settings['enable_rate_limit']  = self::sanitize_switch( $input['enable_rate_limit'] ?? $settings['enable_rate_limit'] );

		$settings['enable_guest_name_from_url'] = self::sanitize_switch( $input['enable_guest_name_from_url'] ?? '' );
		$settings['guest_required_message']     = sanitize_textarea_field( (string) ( $input['guest_required_message'] ?? $settings['guest_required_message'] ) );
		$settings['success_message']            = sanitize_text_field( (string) ( $input['success_message'] ?? $settings['success_message'] ) );
		$settings['error_message']              = sanitize_text_field( (string) ( $input['error_message'] ?? $settings['error_message'] ) );
		$settings['reply_action_text']          = sanitize_text_field( (string) ( $input['reply_action_text'] ?? $settings['reply_action_text'] ) );
		$settings['reply_name_placeholder']     = sanitize_text_field( (string) ( $input['reply_name_placeholder'] ?? $settings['reply_name_placeholder'] ) );
		$settings['reply_message_placeholder']  = sanitize_textarea_field( (string) ( $input['reply_message_placeholder'] ?? $settings['reply_message_placeholder'] ) );
		$settings['reply_submit_text']          = sanitize_text_field( (string) ( $input['reply_submit_text'] ?? $settings['reply_submit_text'] ) );
		$settings['reply_cancel_text']          = sanitize_text_field( (string) ( $input['reply_cancel_text'] ?? $settings['reply_cancel_text'] ) );
		$settings['reply_popup_title']          = sanitize_text_field( (string) ( $input['reply_popup_title'] ?? $settings['reply_popup_title'] ) );
		$settings['reply_success_message']      = sanitize_text_field( (string) ( $input['reply_success_message'] ?? $settings['reply_success_message'] ) );
		$settings['reply_error_message']        = sanitize_text_field( (string) ( $input['reply_error_message'] ?? $settings['reply_error_message'] ) );

		$settings['show_stickers']                 = self::sanitize_switch( $input['show_stickers'] ?? 'yes' );
		$settings['sticker_display_mode']          = self::sanitize_select( $input['sticker_display_mode'] ?? 'popup', [ 'popup', 'inline' ], 'popup' );
		$settings['sticker_media_type']            = self::sanitize_select( $input['sticker_media_type'] ?? 'all', [ 'all', 'image', 'video' ], 'all' );
		$settings['sticker_popup_animation']       = self::sanitize_select( $input['sticker_popup_animation'] ?? 'fade-scale', [ 'fade-scale', 'slide-up', 'slide-down', 'fade', 'none' ], 'fade-scale' );
		$settings['sticker_popup_close_animation'] = self::sanitize_select( $input['sticker_popup_close_animation'] ?? 'same', [ 'same', 'fade-scale', 'slide-up', 'slide-down', 'fade', 'none' ], 'same' );
		$settings['enable_reply_sticker']          = self::sanitize_switch( $input['enable_reply_sticker'] ?? 'yes' );

		$settings['attendence']              = self::sanitize_switch( $input['attendence'] ?? 'yes' );
		$settings['attendance_placeholder'] = sanitize_text_field( (string) ( $input['attendance_placeholder'] ?? $settings['attendance_placeholder'] ) );
		$settings['comment_list_mode']       = self::sanitize_select( $input['comment_list_mode'] ?? 'auto', [ 'auto', 'scroll', 'pagination' ], 'auto' );
		$settings['comments_limit']          = self::sanitize_int_range( $input['comments_limit'] ?? 10, 1, 200, 10 );
		$settings['items_per_page']          = self::sanitize_int_range( $input['items_per_page'] ?? 5, 1, 50, 5 );
		$settings['comment_edit_mode']       = self::sanitize_select( $input['comment_edit_mode'] ?? 'popup', [ 'popup', 'inline' ], 'popup' );
		$settings['comment_reply_mode']      = self::sanitize_select( $input['comment_reply_mode'] ?? 'popup', [ 'popup', 'inline' ], 'popup' );

		$settings['enable_comment_reply']  = self::sanitize_switch( $input['enable_comment_reply'] ?? 'yes' );
		$settings['enable_comment_edit']   = self::sanitize_switch( $input['enable_comment_edit'] ?? 'yes' );
		$settings['enable_comment_delete'] = self::sanitize_switch( $input['enable_comment_delete'] ?? 'yes' );
		$settings['enable_reply_edit']     = self::sanitize_switch( $input['enable_reply_edit'] ?? 'yes' );
		$settings['enable_reply_delete']   = self::sanitize_switch( $input['enable_reply_delete'] ?? 'yes' );
		$settings['style_preset']          = self::sanitize_select( $input['style_preset'] ?? 'clean', [ 'clean', 'soft', 'formal', 'contrast' ], 'clean' );
		$settings['style_primary_color']   = self::sanitize_hex_color_value( $input['style_primary_color'] ?? '#083c57', '#083c57' );
		$settings['style_radius_scale']    = self::sanitize_select( $input['style_radius_scale'] ?? 'soft', [ 'square', 'soft', 'round' ], 'soft' );
		$settings['style_density']         = self::sanitize_select( $input['style_density'] ?? 'normal', [ 'compact', 'normal', 'relaxed' ], 'normal' );
		$settings['style_sticker_size']    = self::sanitize_select( $input['style_sticker_size'] ?? 'normal', [ 'small', 'normal', 'large' ], 'normal' );
		$settings['style_reply_popup_width']                = self::sanitize_int_range( $input['style_reply_popup_width'] ?? 520, 280, 900, 520 );
		$settings['style_reply_popup_position']             = self::sanitize_select( $input['style_reply_popup_position'] ?? 'center', [ 'top', 'center', 'bottom' ], 'center' );
		$settings['style_reply_popup_vertical_offset']      = self::sanitize_int_range( $input['style_reply_popup_vertical_offset'] ?? 0, -160, 160, 0 );
		$settings['style_reply_popup_field_gap']            = self::sanitize_int_range( $input['style_reply_popup_field_gap'] ?? 10, 0, 40, 10 );
		$settings['style_reply_popup_overlay_color']        = self::sanitize_hex_color_value( $input['style_reply_popup_overlay_color'] ?? '#0f172a', '#0f172a' );
		$settings['style_reply_popup_overlay_opacity']      = self::sanitize_int_range( $input['style_reply_popup_overlay_opacity'] ?? 38, 0, 90, 38 );
		$settings['style_reply_popup_title_color']          = self::sanitize_hex_color_value( $input['style_reply_popup_title_color'] ?? '#253044', '#253044' );
		$settings['style_reply_popup_title_font_size']      = self::sanitize_int_range( $input['style_reply_popup_title_font_size'] ?? 15, 11, 32, 15 );
		$settings['style_reply_popup_title_font_weight']    = self::sanitize_select( $input['style_reply_popup_title_font_weight'] ?? '700', [ '400', '500', '600', '700', '800' ], '700' );
		$settings['style_reply_popup_textarea_height']      = self::sanitize_int_range( $input['style_reply_popup_textarea_height'] ?? 150, 80, 420, 150 );
		$settings['style_delete_popup_animation']           = self::sanitize_select( $input['style_delete_popup_animation'] ?? 'soft-scale', [ 'soft-scale', 'slide-up', 'fade', 'none' ], 'soft-scale' );
		$settings['style_delete_popup_button_color']        = self::sanitize_hex_color_value( $input['style_delete_popup_button_color'] ?? '#ffffff', '#ffffff' );
		$settings['style_delete_popup_button_background']   = self::sanitize_hex_color_value( $input['style_delete_popup_button_background'] ?? '#b42318', '#b42318' );
		$settings['style_delete_popup_cancel_color']        = self::sanitize_hex_color_value( $input['style_delete_popup_cancel_color'] ?? '#253044', '#253044' );
		$settings['style_sticker_popup_width']              = self::sanitize_int_range( $input['style_sticker_popup_width'] ?? 460, 280, 760, 460 );
		$settings['style_sticker_popup_height']             = self::sanitize_int_range( $input['style_sticker_popup_height'] ?? 560, 220, 760, 560 );
		$settings['style_sticker_popup_position']           = self::sanitize_select( $input['style_sticker_popup_position'] ?? 'center', [ 'top', 'center', 'bottom' ], 'center' );
		$settings['style_sticker_popup_radius']             = self::sanitize_int_range( $input['style_sticker_popup_radius'] ?? 14, 0, 40, 14 );
		$settings['style_sticker_popup_overlay_color']      = self::sanitize_hex_color_value( $input['style_sticker_popup_overlay_color'] ?? '#0f172a', '#0f172a' );
		$settings['style_sticker_popup_overlay_opacity']    = self::sanitize_int_range( $input['style_sticker_popup_overlay_opacity'] ?? 38, 0, 90, 38 );
		$settings['style_sticker_popup_background']         = self::sanitize_hex_color_value( $input['style_sticker_popup_background'] ?? '#ffffff', '#ffffff' );
		$settings['style_sticker_popup_title_color']        = self::sanitize_hex_color_value( $input['style_sticker_popup_title_color'] ?? '#253044', '#253044' );
		$settings['style_sticker_tab_background']           = self::sanitize_hex_color_value( $input['style_sticker_tab_background'] ?? '#fbfcff', '#fbfcff' );
		$settings['style_sticker_tab_color']                = self::sanitize_hex_color_value( $input['style_sticker_tab_color'] ?? '#6b7890', '#6b7890' );
		$settings['style_sticker_tab_active_background']    = self::sanitize_hex_color_value( $input['style_sticker_tab_active_background'] ?? '#083c57', '#083c57' );
		$settings['style_sticker_tab_active_color']         = self::sanitize_hex_color_value( $input['style_sticker_tab_active_color'] ?? '#ffffff', '#ffffff' );
		$settings['style_sticker_close_background']         = self::sanitize_hex_color_value( $input['style_sticker_close_background'] ?? '#fbfcff', '#fbfcff' );
		$settings['style_sticker_close_color']              = self::sanitize_hex_color_value( $input['style_sticker_close_color'] ?? '#6b7890', '#6b7890' );
		$settings['style_reply_button_padding_top']         = self::sanitize_int_range( $input['style_reply_button_padding_top'] ?? 8, 0, 80, 8 );
		$settings['style_reply_button_padding_right']       = self::sanitize_int_range( $input['style_reply_button_padding_right'] ?? 12, 0, 80, 12 );
		$settings['style_reply_button_padding_bottom']      = self::sanitize_int_range( $input['style_reply_button_padding_bottom'] ?? 8, 0, 80, 8 );
		$settings['style_reply_button_padding_left']        = self::sanitize_int_range( $input['style_reply_button_padding_left'] ?? 12, 0, 80, 12 );
		$settings['style_reply_button_gap']                 = self::sanitize_int_range( $input['style_reply_button_gap'] ?? 8, 0, 32, 8 );
		$settings['style_reply_button_radius']              = self::sanitize_int_range( $input['style_reply_button_radius'] ?? 8, 0, 40, 8 );
		$settings['style_reply_submit_button_color']        = self::sanitize_hex_color_value( $input['style_reply_submit_button_color'] ?? '#ffffff', '#ffffff' );
		$settings['style_reply_submit_button_background']   = self::sanitize_hex_color_value( $input['style_reply_submit_button_background'] ?? '#083c57', '#083c57' );
		$settings['style_reply_cancel_button_color']        = self::sanitize_hex_color_value( $input['style_reply_cancel_button_color'] ?? '#253044', '#253044' );
		$settings['style_reply_cancel_button_background']   = self::sanitize_hex_color_value( $input['style_reply_cancel_button_background'] ?? '#ffffff', '#ffffff' );
		$settings['style_notice_background']                = self::sanitize_hex_color_value( $input['style_notice_background'] ?? '#ecfdf5', '#ecfdf5' );
		$settings['style_notice_text_color']                = self::sanitize_hex_color_value( $input['style_notice_text_color'] ?? '#047857', '#047857' );
		$settings['style_notice_border_color']              = self::sanitize_hex_color_value( $input['style_notice_border_color'] ?? '#bbf7d0', '#bbf7d0' );
		$settings['style_notice_radius']                    = self::sanitize_int_range( $input['style_notice_radius'] ?? 8, 0, 40, 8 );
		$settings['style_notice_padding_top']               = self::sanitize_int_range( $input['style_notice_padding_top'] ?? 10, 0, 80, 10 );
		$settings['style_notice_padding_right']             = self::sanitize_int_range( $input['style_notice_padding_right'] ?? 12, 0, 80, 12 );
		$settings['style_notice_padding_bottom']            = self::sanitize_int_range( $input['style_notice_padding_bottom'] ?? 10, 0, 80, 10 );
		$settings['style_notice_padding_left']              = self::sanitize_int_range( $input['style_notice_padding_left'] ?? 12, 0, 80, 12 );
		$settings['style_notice_alignment']                 = self::sanitize_select( $input['style_notice_alignment'] ?? 'left', [ 'left', 'center', 'right' ], 'left' );
		$settings['style_notice_error_background']          = self::sanitize_hex_color_value( $input['style_notice_error_background'] ?? '#fef2f2', '#fef2f2' );
		$settings['style_notice_error_text_color']          = self::sanitize_hex_color_value( $input['style_notice_error_text_color'] ?? '#991b1b', '#991b1b' );
		$settings['style_notice_error_border_color']        = self::sanitize_hex_color_value( $input['style_notice_error_border_color'] ?? '#fecaca', '#fecaca' );

		return $settings;
	}

	public static function sanitize_form( $input ): array {
		$input = (array) $input;

		foreach ( self::switch_keys() as $key ) {
			if ( ! array_key_exists( $key, $input ) ) {
				$input[ $key ] = '';
			}
		}

		return self::sanitize( $input );
	}

	public static function resolve_widget_settings( array $settings, array $raw_settings = [] ): array {
		return wp_parse_args( $settings, self::widget_defaults() );
	}

	public static function get_visual_style_config( array $settings ): array {
		$settings = wp_parse_args( $settings, self::defaults() );
		$preset   = self::sanitize_select( $settings['style_preset'] ?? 'clean', [ 'clean', 'soft', 'formal', 'contrast' ], 'clean' );
		$primary  = self::sanitize_hex_color_value( $settings['style_primary_color'] ?? '#083c57', '#083c57' );

		$presets = [
			'clean'    => [
				'soft_bg'       => '#f7f7fb',
				'surface'       => '#ffffff',
				'surface_soft'  => '#fbfcff',
				'border'        => '#e5e7ef',
				'border_strong' => '#d6dae5',
				'text'          => '#253044',
				'muted'         => '#6b7890',
				'subtle'        => '#9aa4b5',
			],
			'soft'     => [
				'soft_bg'       => '#f8fafc',
				'surface'       => '#ffffff',
				'surface_soft'  => '#f8fbfd',
				'border'        => '#e2e8f0',
				'border_strong' => '#cbd5e1',
				'text'          => '#243042',
				'muted'         => '#64748b',
				'subtle'        => '#94a3b8',
			],
			'formal'   => [
				'soft_bg'       => '#f6f7f9',
				'surface'       => '#ffffff',
				'surface_soft'  => '#fafafa',
				'border'        => '#dedede',
				'border_strong' => '#c7c7c7',
				'text'          => '#222831',
				'muted'         => '#667085',
				'subtle'        => '#98a2b3',
			],
			'contrast' => [
				'soft_bg'       => '#f3f6f8',
				'surface'       => '#ffffff',
				'surface_soft'  => '#f8fafc',
				'border'        => '#cfd8e3',
				'border_strong' => '#94a3b8',
				'text'          => '#111827',
				'muted'         => '#475569',
				'subtle'        => '#64748b',
			],
		];

		$density = self::sanitize_select( $settings['style_density'] ?? 'normal', [ 'compact', 'normal', 'relaxed' ], 'normal' );
		$density_map = [
			'compact' => [
				'dock_padding'       => '20px',
				'dock_mobile_padding' => '14px',
				'form_gap'           => '14px',
				'form_margin_bottom' => '18px',
				'field_padding'      => '10px 12px',
				'field_mobile_padding' => '9px 11px',
				'button_padding'     => '10px 16px',
				'button_mobile_padding' => '9px 14px',
				'item_padding'       => '12px',
			],
			'normal'  => [
				'dock_padding'       => '28px',
				'dock_mobile_padding' => '16px',
				'form_gap'           => '18px',
				'form_margin_bottom' => '24px',
				'field_padding'      => '12px 14px',
				'field_mobile_padding' => '10px 12px',
				'button_padding'     => '12px 18px',
				'button_mobile_padding' => '10px 16px',
				'item_padding'       => '14px',
			],
			'relaxed' => [
				'dock_padding'       => '34px',
				'dock_mobile_padding' => '20px',
				'form_gap'           => '22px',
				'form_margin_bottom' => '28px',
				'field_padding'      => '14px 16px',
				'field_mobile_padding' => '12px 14px',
				'button_padding'     => '14px 20px',
				'button_mobile_padding' => '12px 18px',
				'item_padding'       => '18px',
			],
		];

		$radius = self::sanitize_select( $settings['style_radius_scale'] ?? 'soft', [ 'square', 'soft', 'round' ], 'soft' );
		$radius_map = [
			'square' => [
				'dock_radius'    => '8px',
				'dock_mobile_radius' => '8px',
				'control_radius' => '6px',
				'list_radius'    => '8px',
				'avatar_radius'  => '6px',
				'pill_radius'    => '6px',
			],
			'soft'   => [
				'dock_radius'    => '14px',
				'dock_mobile_radius' => '10px',
				'control_radius' => '10px',
				'list_radius'    => '12px',
				'avatar_radius'  => '10px',
				'pill_radius'    => '10px',
			],
			'round'  => [
				'dock_radius'    => '20px',
				'dock_mobile_radius' => '14px',
				'control_radius' => '14px',
				'list_radius'    => '16px',
				'avatar_radius'  => '14px',
				'pill_radius'    => '14px',
			],
		];

		$sticker_size = self::sanitize_select( $settings['style_sticker_size'] ?? 'normal', [ 'small', 'normal', 'large' ], 'normal' );
		$sticker_map = [
			'small'  => [
				'sticker_size'         => '36px',
				'sticker_avatar_size'  => '32px',
				'sticker_preview_size' => '32px',
				'sticker_grid_min'     => '46px',
				'sticker_padding'      => '3px',
			],
			'normal' => [
				'sticker_size'         => '42px',
				'sticker_avatar_size'  => '36px',
				'sticker_preview_size' => '36px',
				'sticker_grid_min'     => '52px',
				'sticker_padding'      => '3px',
			],
			'large'  => [
				'sticker_size'         => '52px',
				'sticker_avatar_size'  => '44px',
				'sticker_preview_size' => '44px',
				'sticker_grid_min'     => '64px',
				'sticker_padding'      => '4px',
			],
		];

		$config = array_merge(
			$presets[ $preset ],
			$density_map[ $density ],
			$radius_map[ $radius ],
			$sticker_map[ $sticker_size ]
		);

		$config['primary']       = $primary;
		$config['primary_soft']  = self::hex_to_rgba( $primary, 0.09 );
		$config['reply_popup_width']        = self::sanitize_int_range( $settings['style_reply_popup_width'] ?? 520, 280, 900, 520 ) . 'px';
		$config['reply_popup_position']     = [
			'top'    => 'flex-start',
			'center' => 'center',
			'bottom' => 'flex-end',
		][ self::sanitize_select( $settings['style_reply_popup_position'] ?? 'center', [ 'top', 'center', 'bottom' ], 'center' ) ];
		$config['reply_popup_vertical_offset'] = self::sanitize_int_range( $settings['style_reply_popup_vertical_offset'] ?? 0, -160, 160, 0 ) . 'px';
		$config['reply_popup_field_gap']    = self::sanitize_int_range( $settings['style_reply_popup_field_gap'] ?? 10, 0, 40, 10 ) . 'px';
		$config['reply_popup_overlay']      = self::hex_to_rgba(
			self::sanitize_hex_color_value( $settings['style_reply_popup_overlay_color'] ?? '#0f172a', '#0f172a' ),
			self::sanitize_int_range( $settings['style_reply_popup_overlay_opacity'] ?? 38, 0, 90, 38 ) / 100
		);
		$config['reply_popup_title_color']   = self::sanitize_hex_color_value( $settings['style_reply_popup_title_color'] ?? '#253044', '#253044' );
		$config['reply_popup_title_size']    = self::sanitize_int_range( $settings['style_reply_popup_title_font_size'] ?? 15, 11, 32, 15 ) . 'px';
		$config['reply_popup_title_weight']  = self::sanitize_select( $settings['style_reply_popup_title_font_weight'] ?? '700', [ '400', '500', '600', '700', '800' ], '700' );
		$config['reply_popup_textarea_height'] = self::sanitize_int_range( $settings['style_reply_popup_textarea_height'] ?? 150, 80, 420, 150 ) . 'px';
		$delete_animation = self::sanitize_select( $settings['style_delete_popup_animation'] ?? 'soft-scale', [ 'soft-scale', 'slide-up', 'fade', 'none' ], 'soft-scale' );
		$delete_starts = [
			'soft-scale' => 'translateY(10px) scale(.96)',
			'slide-up'   => 'translateY(22px) scale(1)',
			'fade'       => 'translateY(0) scale(1)',
			'none'       => 'translateY(0) scale(1)',
		];
		$config['delete_popup_start']          = $delete_starts[ $delete_animation ];
		$config['delete_popup_duration']       = 'none' === $delete_animation ? '0ms' : '320ms';
		$config['delete_popup_close_duration'] = 'none' === $delete_animation ? '0ms' : '240ms';
		$config['delete_popup_button_color']   = self::sanitize_hex_color_value( $settings['style_delete_popup_button_color'] ?? '#ffffff', '#ffffff' );
		$config['delete_popup_button_bg']      = self::sanitize_hex_color_value( $settings['style_delete_popup_button_background'] ?? '#b42318', '#b42318' );
		$config['delete_popup_cancel_color']   = self::sanitize_hex_color_value( $settings['style_delete_popup_cancel_color'] ?? '#253044', '#253044' );
		$config['sticker_popup_width']         = self::sanitize_int_range( $settings['style_sticker_popup_width'] ?? 460, 280, 760, 460 ) . 'px';
		$config['sticker_popup_height']        = self::sanitize_int_range( $settings['style_sticker_popup_height'] ?? 560, 220, 760, 560 ) . 'px';
		$config['sticker_popup_position']      = [
			'top'    => 'flex-start',
			'center' => 'center',
			'bottom' => 'flex-end',
		][ self::sanitize_select( $settings['style_sticker_popup_position'] ?? 'center', [ 'top', 'center', 'bottom' ], 'center' ) ];
		$config['sticker_popup_radius']        = self::sanitize_int_range( $settings['style_sticker_popup_radius'] ?? 14, 0, 40, 14 ) . 'px';
		$config['sticker_popup_overlay']       = self::hex_to_rgba(
			self::sanitize_hex_color_value( $settings['style_sticker_popup_overlay_color'] ?? '#0f172a', '#0f172a' ),
			self::sanitize_int_range( $settings['style_sticker_popup_overlay_opacity'] ?? 38, 0, 90, 38 ) / 100
		);
		$config['sticker_popup_background']    = self::sanitize_hex_color_value( $settings['style_sticker_popup_background'] ?? '#ffffff', '#ffffff' );
		$config['sticker_popup_title_color']   = self::sanitize_hex_color_value( $settings['style_sticker_popup_title_color'] ?? '#253044', '#253044' );
		$config['sticker_tab_background']      = self::sanitize_hex_color_value( $settings['style_sticker_tab_background'] ?? '#fbfcff', '#fbfcff' );
		$config['sticker_tab_color']           = self::sanitize_hex_color_value( $settings['style_sticker_tab_color'] ?? '#6b7890', '#6b7890' );
		$config['sticker_tab_active_background'] = self::sanitize_hex_color_value( $settings['style_sticker_tab_active_background'] ?? '#083c57', '#083c57' );
		$config['sticker_tab_active_color']    = self::sanitize_hex_color_value( $settings['style_sticker_tab_active_color'] ?? '#ffffff', '#ffffff' );
		$config['sticker_close_background']    = self::sanitize_hex_color_value( $settings['style_sticker_close_background'] ?? '#fbfcff', '#fbfcff' );
		$config['sticker_close_color']         = self::sanitize_hex_color_value( $settings['style_sticker_close_color'] ?? '#6b7890', '#6b7890' );
		$config['reply_button_padding']        = sprintf(
			'%dpx %dpx %dpx %dpx',
			self::sanitize_int_range( $settings['style_reply_button_padding_top'] ?? 8, 0, 80, 8 ),
			self::sanitize_int_range( $settings['style_reply_button_padding_right'] ?? 12, 0, 80, 12 ),
			self::sanitize_int_range( $settings['style_reply_button_padding_bottom'] ?? 8, 0, 80, 8 ),
			self::sanitize_int_range( $settings['style_reply_button_padding_left'] ?? 12, 0, 80, 12 )
		);
		$config['reply_button_gap']            = self::sanitize_int_range( $settings['style_reply_button_gap'] ?? 8, 0, 32, 8 ) . 'px';
		$config['reply_button_radius']         = self::sanitize_int_range( $settings['style_reply_button_radius'] ?? 8, 0, 40, 8 ) . 'px';
		$config['reply_submit_button_color']   = self::sanitize_hex_color_value( $settings['style_reply_submit_button_color'] ?? '#ffffff', '#ffffff' );
		$config['reply_submit_button_bg']      = self::sanitize_hex_color_value( $settings['style_reply_submit_button_background'] ?? '#083c57', '#083c57' );
		$config['reply_cancel_button_color']   = self::sanitize_hex_color_value( $settings['style_reply_cancel_button_color'] ?? '#253044', '#253044' );
		$config['reply_cancel_button_bg']      = self::sanitize_hex_color_value( $settings['style_reply_cancel_button_background'] ?? '#ffffff', '#ffffff' );
		$config['notice_background']           = self::sanitize_hex_color_value( $settings['style_notice_background'] ?? '#ecfdf5', '#ecfdf5' );
		$config['notice_text_color']           = self::sanitize_hex_color_value( $settings['style_notice_text_color'] ?? '#047857', '#047857' );
		$config['notice_border_color']         = self::sanitize_hex_color_value( $settings['style_notice_border_color'] ?? '#bbf7d0', '#bbf7d0' );
		$config['notice_radius']               = self::sanitize_int_range( $settings['style_notice_radius'] ?? 8, 0, 40, 8 ) . 'px';
		$config['notice_padding']              = sprintf(
			'%dpx %dpx %dpx %dpx',
			self::sanitize_int_range( $settings['style_notice_padding_top'] ?? 10, 0, 80, 10 ),
			self::sanitize_int_range( $settings['style_notice_padding_right'] ?? 12, 0, 80, 12 ),
			self::sanitize_int_range( $settings['style_notice_padding_bottom'] ?? 10, 0, 80, 10 ),
			self::sanitize_int_range( $settings['style_notice_padding_left'] ?? 12, 0, 80, 12 )
		);
		$config['notice_alignment']            = self::sanitize_select( $settings['style_notice_alignment'] ?? 'left', [ 'left', 'center', 'right' ], 'left' );
		$config['notice_error_background']     = self::sanitize_hex_color_value( $settings['style_notice_error_background'] ?? '#fef2f2', '#fef2f2' );
		$config['notice_error_text_color']     = self::sanitize_hex_color_value( $settings['style_notice_error_text_color'] ?? '#991b1b', '#991b1b' );
		$config['notice_error_border_color']   = self::sanitize_hex_color_value( $settings['style_notice_error_border_color'] ?? '#fecaca', '#fecaca' );

		return $config;
	}

	private static function sanitize_switch( $value ): string {
		return 'yes' === (string) $value ? 'yes' : '';
	}

	private static function switch_keys(): array {
		return [
			'prevent_duplicate',
			'enable_rate_limit',
			'enable_guest_name_from_url',
			'show_stickers',
			'enable_reply_sticker',
			'attendence',
			'enable_comment_reply',
			'enable_comment_edit',
			'enable_comment_delete',
			'enable_reply_edit',
			'enable_reply_delete',
		];
	}

	private static function sanitize_select( $value, array $allowed, string $fallback ): string {
		$value = sanitize_key( (string) $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	private static function sanitize_hex_color_value( $value, string $fallback ): string {
		$value = is_string( $value ) ? sanitize_hex_color( $value ) : '';
		return $value ?: $fallback;
	}

	private static function sanitize_int_range( $value, int $min, int $max, int $fallback ): int {
		$value = is_numeric( $value ) ? (int) $value : $fallback;
		return max( $min, min( $max, $value ) );
	}

	private static function hex_to_rgba( string $hex, float $alpha ): string {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) ) {
			return 'rgba(8, 60, 87, ' . $alpha . ')';
		}

		$rgb = [
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		];

		return sprintf( 'rgba(%d, %d, %d, %.2F)', $rgb[0], $rgb[1], $rgb[2], $alpha );
	}

}
