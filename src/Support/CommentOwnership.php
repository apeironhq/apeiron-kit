<?php

namespace ApeironKit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CommentOwnership {

	public const OWNER_COOKIE = 'apeiron_comment_owner';

	public static function is_owner( int $comment_id ): bool {
		if ( $comment_id <= 0 ) {
			return false;
		}

		$owner_user_id = (int) get_comment_meta( $comment_id, 'apeiron_owner_user_id', true );
		if ( $owner_user_id > 0 && get_current_user_id() === $owner_user_id ) {
			return true;
		}

		$hash = (string) get_comment_meta( $comment_id, 'apeiron_owner_token_hash', true );
		if ( '' === $hash || empty( $_COOKIE[ self::OWNER_COOKIE ] ) ) {
			return false;
		}

		$token = sanitize_text_field( wp_unslash( (string) $_COOKIE[ self::OWNER_COOKIE ] ) );
		if ( ! preg_match( '/^[a-f0-9]{32,64}$/', $token ) ) {
			return false;
		}

		return hash_equals( $hash, hash( 'sha256', $token ) );
	}
}
