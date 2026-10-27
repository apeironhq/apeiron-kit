<?php

namespace ApeironKit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Antrean kirim ulang Form Order: bila API Form Order sedang tidak dapat dihubungi, pesanan disimpan sementara di
 * WordPress (opsi tanpa autoload) lalu dikirim ulang otomatis lewat WP-Cron dengan request_id yang sama — backend
 * idempoten sehingga tidak ada pesanan ganda. Data dihapus dari antrean begitu tersimpan di backend.
 */
final class FormOrderOutbox {
	public const OPTION = 'apeiron_form_order_outbox';
	public const CRON_HOOK = 'apeiron_form_order_outbox_retry';
	public const RETRY_ACTION = 'apeiron_form_order_outbox_retry';
	public const DISCARD_ACTION = 'apeiron_form_order_outbox_discard';
	public const MAX_ITEMS = 200;
	private const BATCH = 20;
	private const LOCK = 'apeiron_form_order_outbox_lock';
	/** Jeda kirim ulang (detik): 5 m, 15 m, 30 m, 1 j, 3 j, 6 j, 12 j, lalu tiap 24 j. */
	private const BACKOFF = [ 300, 900, 1800, 3600, 10800, 21600, 43200 ];
	/** Setelah 14 hari pesanan berhenti dikirim ulang otomatis (tetap bisa dikirim manual / dibuang admin). */
	private const MAX_AGE = 1209600;

	public static function register(): void {
		add_action( self::CRON_HOOK, [ self::class, 'process' ] );
		add_action( 'admin_post_' . self::RETRY_ACTION, [ self::class, 'handle_retry_now' ] );
		add_action( 'admin_post_' . self::DISCARD_ACTION, [ self::class, 'handle_discard_stopped' ] );
	}

	/** @return array<string,array<string,mixed>> */
	public static function items(): array {
		$items = get_option( self::OPTION, [] );
		return is_array( $items ) ? $items : [];
	}

	/** @return array{waiting:int,stopped:int} */
	public static function summary(): array {
		$waiting = 0;
		$stopped = 0;
		foreach ( self::items() as $item ) {
			if ( ! empty( $item['stopped'] ) ) { $stopped++; } else { $waiting++; }
		}
		return [ 'waiting' => $waiting, 'stopped' => $stopped ];
	}

	/** Masukkan pesanan ke antrean. false bila antrean penuh (pesanan tetap ditolak seperti sebelumnya). */
	public static function enqueue( array $fields, array $labels, array $meta, array $context, string $submission_id, string $error ): bool {
		if ( ! preg_match( '/^[a-f0-9]{64}$/D', $submission_id ) ) {
			return false;
		}
		$items = self::items();
		if ( ! isset( $items[ $submission_id ] ) ) {
			if ( count( $items ) >= self::MAX_ITEMS ) {
				return false;
			}
			$now = time();
			$items[ $submission_id ] = [
				'fields' => $fields, 'labels' => $labels, 'meta' => $meta, 'context' => $context,
				'attempts' => 0, 'created_at' => $now, 'next_at' => $now + self::BACKOFF[0], 'last_error' => self::short( $error ), 'stopped' => false,
			];
			if ( ! update_option( self::OPTION, $items, false ) ) {
				return false;
			}
		}
		self::schedule( time() + self::BACKOFF[0] );
		return true;
	}

	/**
	 * Kirim ulang pesanan yang sudah waktunya (atau semua, bila $force). Maksimal 20 per putaran.
	 *
	 * @return array{sent:int,waiting:int,stopped:int}
	 */
	public static function process( bool $force = false ): array {
		if ( get_transient( self::LOCK ) ) {
			return [ 'sent' => 0 ] + self::summary();
		}
		set_transient( self::LOCK, 1, 120 );
		$sent = 0;
		try {
			$items = self::items();
			$now = time();
			$done = 0;
			foreach ( $items as $id => $item ) {
				if ( $done >= self::BATCH ) { break; }
				if ( ! is_array( $item ) || ( ! $force && ( ! empty( $item['stopped'] ) || (int) ( $item['next_at'] ?? 0 ) > $now ) ) ) { continue; }
				$done++;
				$failure = null;
				$retryable = true;
				$ok = FormOrderSettings::send_to_api(
					(array) ( $item['fields'] ?? [] ), (array) ( $item['labels'] ?? [] ), (array) ( $item['context'] ?? [] ),
					(string) $id, $failure, false, (array) ( $item['meta'] ?? [] ), $retryable
				);
				if ( $ok ) {
					unset( $items[ $id ] );
					$sent++;
					continue;
				}
				$attempts = (int) ( $item['attempts'] ?? 0 ) + 1;
				$item['attempts'] = $attempts;
				$item['last_error'] = self::short( (string) $failure );
				$item['next_at'] = $now + ( self::BACKOFF[ $attempts ] ?? DAY_IN_SECONDS );
				$item['stopped'] = ! $retryable || ( $now - (int) ( $item['created_at'] ?? $now ) ) > self::MAX_AGE;
				$items[ $id ] = $item;
			}
			update_option( self::OPTION, $items, false );
			$next = null;
			foreach ( $items as $item ) {
				if ( is_array( $item ) && empty( $item['stopped'] ) ) {
					$next = null === $next ? (int) $item['next_at'] : min( $next, (int) $item['next_at'] );
				}
			}
			if ( null !== $next ) {
				self::schedule( max( $next, time() + 60 ) );
			}
		} finally {
			delete_transient( self::LOCK );
		}
		return [ 'sent' => $sent ] + self::summary();
	}

	public static function handle_retry_now(): void {
		self::authorize( self::RETRY_ACTION );
		$result = self::process( true );
		self::back( [ 'outbox_sent' => $result['sent'], 'outbox_left' => $result['waiting'] + $result['stopped'] ] );
	}

	/** Buang pesanan yang sudah berhenti dikirim ulang (setelah admin memeriksanya). */
	public static function handle_discard_stopped(): void {
		self::authorize( self::DISCARD_ACTION );
		$items = array_filter( self::items(), static function ( $item ): bool { return is_array( $item ) && empty( $item['stopped'] ); } );
		update_option( self::OPTION, $items, false );
		self::back( [ 'outbox_discarded' => 1 ] );
	}

	private static function schedule( int $at ): void {
		$current = wp_next_scheduled( self::CRON_HOOK );
		if ( false !== $current && $current <= $at ) {
			return;
		}
		if ( false !== $current ) {
			wp_unschedule_event( $current, self::CRON_HOOK );
		}
		wp_schedule_single_event( $at, self::CRON_HOOK );
	}

	private static function authorize( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Tidak memiliki izin.', 'apeiron-kit' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( $action );
	}

	private static function back( array $args ): void {
		wp_safe_redirect( add_query_arg( array_merge( [ 'page' => 'apeiron-kit', 'tab' => 'form-order' ], $args ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function short( string $text ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, 300 ) : substr( $text, 0, 300 );
	}
}
