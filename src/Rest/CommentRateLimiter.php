<?php

declare( strict_types=1 );

namespace ApeironKit\Rest;

use ApeironKit\Support\UcapanTamuSettings;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CommentRateLimiter {

	private const POST_MAX = 20;
	private const GET_MAX  = 30;
	private const WINDOW   = MINUTE_IN_SECONDS;

	/** @return true|WP_Error */
	public function check( string $type = 'post' ) {
		$settings = UcapanTamuSettings::get_settings();
		$enabled  = 'yes' === ( $settings['enable_rate_limit'] ?? '' );
		$enabled  = apply_filters( 'apeiron_kit_comment_rate_limit_enabled', $enabled, $type );
		if ( ! $enabled || current_user_can( 'moderate_comments' ) ) {
			return true;
		}

		$key          = $type . '_' . md5( $this->identifier() );
		$max_requests = 'post' === $type ? self::POST_MAX : self::GET_MAX;
		if ( wp_using_ext_object_cache() ) {
			wp_cache_add( $key, 0, 'apeiron_comment_rate', self::WINDOW );
			$count = wp_cache_incr( $key, 1, 'apeiron_comment_rate' );
			if ( false !== $count ) {
				return $count > $max_requests ? $this->error() : true;
			}
		}

		// Transient fallback is process-safe enough for normal traffic, but not atomic.
		$transient_key = 'apeiron_comment_rate_' . $key;
		$count         = (int) get_transient( $transient_key );
		if ( $count >= $max_requests ) {
			return $this->error();
		}

		set_transient( $transient_key, $count + 1, self::WINDOW );
		return true;
	}

	private function error(): WP_Error {
		return new WP_Error(
			'rate_limit',
			__( 'Terlalu banyak request. Silakan tunggu sebentar sebelum mencoba lagi.', 'apeiron-kit' ),
			[ 'status' => 429 ]
		);
	}

	private function identifier(): string {
		$ip = $this->client_ip();
		if ( '' !== $ip ) {
			return 'ip:' . $ip;
		}

		$user_agent      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$accept_language = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '';
		if ( '' === $user_agent && '' === $accept_language ) {
			return 'anonymous';
		}

		// No-IP fallback is intentionally coarse and spoofable, so it never grants bypass.
		return 'fallback:' . hash( 'sha256', strtolower( $user_agent ) . '|' . strtolower( $accept_language ) );
	}

	private function client_ip(): string {
		$remote_addr = $this->server_ip( 'REMOTE_ADDR' );
		if ( '' === $remote_addr ) {
			return '';
		}

		$trusted = apply_filters( 'apeiron_kit_trusted_proxy_ips', [], $remote_addr );
		if ( ! is_array( $trusted ) || ! $this->matches_trusted_proxy( $remote_addr, $trusted ) ) {
			return $remote_addr;
		}

		foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR' ] as $key ) {
			$forwarded = $this->server_ip( $key );
			if ( '' !== $forwarded ) {
				return $forwarded;
			}
		}

		return $remote_addr;
	}

	private function server_ip( string $key ): string {
		if ( empty( $_SERVER[ $key ] ) || is_array( $_SERVER[ $key ] ) ) {
			return '';
		}

		$value = sanitize_text_field( wp_unslash( (string) $_SERVER[ $key ] ) );
		foreach ( explode( ',', $value ) as $candidate ) {
			$candidate = trim( $candidate );
			if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
				return $candidate;
			}
		}

		return '';
	}

	private function matches_trusted_proxy( string $ip, array $trusted_proxies ): bool {
		foreach ( $trusted_proxies as $trusted_proxy ) {
			$trusted_proxy = trim( (string) $trusted_proxy );
			if ( '' === $trusted_proxy ) {
				continue;
			}

			if ( false !== strpos( $trusted_proxy, '/' ) ) {
				if ( $this->matches_cidr( $ip, $trusted_proxy ) ) {
					return true;
				}
				continue;
			}

			if ( $ip === $trusted_proxy ) {
				return true;
			}
		}

		return false;
	}

	private function matches_cidr( string $ip, string $cidr ): bool {
		$parts = explode( '/', $cidr, 2 );
		if ( 2 !== count( $parts ) ) {
			return false;
		}

		$ip_bin     = inet_pton( $ip );
		$subnet_bin = inet_pton( trim( $parts[0] ) );
		$bits       = (int) $parts[1];
		if ( false === $ip_bin || false === $subnet_bin || strlen( $ip_bin ) !== strlen( $subnet_bin ) ) {
			return false;
		}

		$max_bits = strlen( $ip_bin ) * 8;
		if ( $bits < 0 || $bits > $max_bits ) {
			return false;
		}

		$bytes     = intdiv( $bits, 8 );
		$remainder = $bits % 8;
		if ( $bytes > 0 && substr( $ip_bin, 0, $bytes ) !== substr( $subnet_bin, 0, $bytes ) ) {
			return false;
		}
		if ( 0 === $remainder ) {
			return true;
		}

		$mask = ( ~ ( 255 >> $remainder ) ) & 255;
		return ( ord( $ip_bin[ $bytes ] ) & $mask ) === ( ord( $subnet_bin[ $bytes ] ) & $mask );
	}
}
