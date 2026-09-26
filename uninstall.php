<?php
/**
 * Uninstall handler for Apeiron Kit
 *
 * This file runs when the plugin is deleted from WordPress admin.
 * It cleans up all plugin data from the database.
 *
 * @package ApeironKit
 * @since 1.0.5
 */

// Exit if not called by WordPress uninstall
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all plugin options from database
 */
$options_to_delete = [
	'apeiron_kit_settings',
	'apeiron_kit_license',
	'apeiron_kit_license_cache',
	'apeiron_kit_license_persistent',
	'apeiron_kit_license_salt',
	'apeiron_kit_license_api_url',
	'apeiron_kit_api_key_encrypted',
	'apeiron_kit_api_salt',
	'apeiron_kit_check_fail_count',
	'apeiron_social_proof_settings',
	'apeiron_ucapan_tamu_settings',
	'apeiron_kit_disabled_widgets',
	'apeiron_cover_settings',
	'apeiron_kit_content_cleanup_version',
	'apeiron_kit_content_cleanup_cursor_v1',
	'apeiron_kit_content_cleanup_lock',
];

$transients_to_delete = [
	'apeiron_kit_license_valid',
	'apeiron_kit_activation_notice',
];

global $wpdb;
$cleanup_current_blog = static function () use ( $options_to_delete, $transients_to_delete ): void {
	global $wpdb;

	foreach ( $options_to_delete as $option ) {
		delete_option( $option );
	}
	delete_post_meta_by_key( '_apeiron_widget_usage_index' );

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'apeiron_comment_dock_settings_' ) . '%' ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'apeiron_comment_dock_settings_index_' ) . '%' ) );
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->commentmeta} WHERE meta_key IN ( %s, %s, %s, %s, %s, %s, %s )",
			'apeiron_element_id',
			'apeiron_attendance_value',
			'apeiron_attendance_label',
			'apeiron_owner_user_id',
			'apeiron_owner_token_hash',
			'apeiron_sticker_src',
			'apeiron_sticker_type'
		)
	);

	foreach ( $transients_to_delete as $transient ) {
		delete_transient( $transient );
	}
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_apeiron_comment_rate_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_apeiron_comment_rate_' ) . '%'
		)
	);
	wp_clear_scheduled_hook( 'apeiron_kit_check_license' );
};

$current_blog_id = get_current_blog_id();
$cleanup_current_blog();

if ( is_multisite() ) {
	$cursor     = 0;
	$batch_size = 100;
	do {
		$blog_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT blog_id FROM {$wpdb->blogs}
				WHERE blog_id > %d AND blog_id <> %d
				ORDER BY blog_id ASC LIMIT %d",
				$cursor,
				$current_blog_id,
				$batch_size
			)
		);

		foreach ( $blog_ids as $blog_id ) {
			$blog_id = (int) $blog_id;
			$cursor  = $blog_id;
			switch_to_blog( $blog_id );
			try {
				$cleanup_current_blog();
			} finally {
				restore_current_blog();
			}
		}
	} while ( count( $blog_ids ) === $batch_size );
}

/**
 * Optional: Delete uploaded client photos
 * Uncomment the following code if you want to remove uploaded files on uninstall
 * WARNING: This will permanently delete user-uploaded images
 */
/*
$upload_dir = wp_upload_dir();
$photos_dir = trailingslashit( $upload_dir['basedir'] ) . 'apeiron-client-photos';

if ( is_dir( $photos_dir ) ) {
	$files = glob( $photos_dir . '/*' );
	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}
	rmdir( $photos_dir );
}
*/
