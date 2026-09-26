<?php

namespace ApeironKit\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use ApeironKit\Support\ErrorLogger;
use ApeironKit\Support\CommentOwnership;
use ApeironKit\Support\UcapanTamuSettings;
use ApeironKit\Support\WidgetRegistry;
use ApeironKit\Elementor\Widgets\CommentDock\CommentRenderer;
use ApeironKit\Elementor\Widgets\CommentDock\StickerLibrary;

class CommentsController {

	private string $namespace = 'apeiron-kit/v1';

	private const DEFAULT_PER_PAGE = 50;
	private const MAX_PER_PAGE = 200;
	private const MAX_TREE_DEPTH = 10;
	private const MAX_TREE_REPLIES = 1000;
	private const CACHE_GROUP = 'apeiron_comment_dock';
	private const OWNER_COOKIE = CommentOwnership::OWNER_COOKIE;
	private ?CommentRateLimiter $rate_limiter = null;

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/comments',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'fetch_comments' ],
					'permission_callback' => '__return_true',
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'store_comment' ],
					'permission_callback' => [ $this, 'verify_nonce' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/nonce',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'refresh_nonce' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			$this->namespace,
			'/comments/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_comment' ],
					'permission_callback' => [ $this, 'verify_nonce' ],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_comment' ],
					'permission_callback' => [ $this, 'verify_nonce' ],
				],
			]
		);
	}

	public function refresh_nonce( WP_REST_Request $request ): WP_REST_Response {
		nocache_headers();

		return new WP_REST_Response(
			[
				'nonce' => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	public function fetch_comments( WP_REST_Request $request ): WP_REST_Response {
		if ( $this->is_comment_dock_disabled() ) {
			return new WP_REST_Response(
				[ 'code' => 'widget_disabled', 'message' => __( 'Widget tidak aktif.', 'apeiron-kit' ) ],
				403
			);
		}

		$rate_error = $this->check_rate_limit( 'get' );
		if ( is_wp_error( $rate_error ) ) {
			return new WP_REST_Response(
				[ 'code' => 'rate_limit', 'message' => $rate_error->get_error_message() ],
				429
			);
		}

		$post_id    = (int) $request->get_param( 'post_id' );
		$element_id = sanitize_text_field( (string) $request->get_param( 'element_id' ) );
		$per_page   = min( absint( $request->get_param( 'per_page' ) ) ?: self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE );
		$page       = max( 1, absint( $request->get_param( 'page' ) ) );
		$offset     = ( $page - 1 ) * $per_page;

		if ( ! $post_id || empty( $element_id ) ) {
			return new WP_REST_Response( [ 'items' => [], 'total' => 0, 'page' => 1, 'per_page' => $per_page ] );
		}

		$target_error = $this->validate_comment_target( $post_id, $element_id, '', false );
		if ( is_wp_error( $target_error ) ) {
			return new WP_REST_Response( [ 'items' => [], 'total' => 0, 'page' => 1, 'per_page' => $per_page ] );
		}

		if ( 'tree' === sanitize_key( (string) $request->get_param( 'mode' ) ) ) {
			return $this->fetch_comments_tree( $post_id, $element_id, $page, $per_page );
		}

		$cache_key = $this->comments_cache_key( $post_id, $element_id, 'page', $page, $per_page );
		$cached    = $this->get_comments_cache( $cache_key );
		if ( is_array( $cached ) ) {
			$total    = (int) $cached['total'];
			$comments = (array) $cached['comments'];
		} else {
			$count_args = [
				'post_id'    => $post_id,
				'status'     => 'approve',
				'count'      => true,
				'meta_query' => [
					[
						'key'   => 'apeiron_element_id',
						'value' => $element_id,
					],
				],
			];
			$total = (int) get_comments( $count_args );

			$comments = get_comments(
				[
					'post_id'    => $post_id,
					'status'     => 'approve',
					'orderby'    => 'comment_date_gmt',
					'order'      => 'DESC',
					'number'     => $per_page,
					'offset'     => $offset,
					'meta_query' => [
						[
							'key'   => 'apeiron_element_id',
							'value' => $element_id,
						],
					],
				]
			);
			$this->set_comments_cache( $cache_key, compact( 'total', 'comments' ) );
		}

		$widget_settings = $this->get_comment_dock_settings( $post_id, $element_id );
		$widget_settings = UcapanTamuSettings::resolve_widget_settings(
			is_array( $widget_settings ) ? $widget_settings : [],
			is_array( $widget_settings ) ? $widget_settings : []
		);
		$permissions     = $this->get_permission_settings( $widget_settings );
		$depth_limit     = $this->get_reply_depth_limit();
		$controller      = $this;
		$depths          = $this->build_comment_depths( $comments );
		$renderer        = $this->create_renderer( $widget_settings, $post_id, $element_id );
		$items      = array_map(
			static function ( $comment ) use ( $controller, $permissions, $depth_limit, $renderer, $depths ) {
				return $controller->prepare_comment_response( $comment, $permissions, $depth_limit, $renderer, $depths );
			},
			$comments
		);

		return new WP_REST_Response( [
			'items'    => $items,
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
			'pages'    => max( 1, (int) ceil( $total / $per_page ) ),
		] );
	}

	/** `total` is top-level comment count used by load-more. */
	private function fetch_comments_tree( int $post_id, string $element_id, int $page, int $per_page ): WP_REST_Response {
		$cache_key = $this->comments_cache_key( $post_id, $element_id, 'tree', $page, $per_page );
		$cached    = $this->get_comments_cache( $cache_key );
		if ( is_array( $cached ) ) {
			$total     = (int) $cached['total'];
			$top       = (array) $cached['top'];
			$tree      = (array) $cached['tree'];
			$truncated = (bool) $cached['truncated'];
		} else {
			$query_args = [
				'post_id'    => $post_id,
				'status'     => 'approve',
				'orderby'    => 'comment_date_gmt',
				'order'      => 'DESC',
				'meta_query' => [
					[
						'key'   => 'apeiron_element_id',
						'value' => $element_id,
					],
				],
			];

			$total  = (int) get_comments( array_merge( $query_args, [ 'parent' => 0, 'count' => true ] ) );
			$offset = ( $page - 1 ) * $per_page;
			$top    = get_comments(
				array_merge(
					$query_args,
					[
						'parent' => 0,
						'number' => $per_page,
						'offset' => $offset,
					]
				)
			);

			$tree        = [ 0 => $top ];
			$pending_ids = array_map( static fn( $comment ): int => (int) $comment->comment_ID, $top );
			$seen_ids    = array_fill_keys( $pending_ids, true );
			$depth       = 0;
			$reply_count = 0;
			$truncated   = false;

			while ( ! empty( $pending_ids ) && $depth < self::MAX_TREE_DEPTH && $reply_count < self::MAX_TREE_REPLIES ) {
				$remaining = self::MAX_TREE_REPLIES - $reply_count;
				$replies   = get_comments(
					array_merge(
						$query_args,
						[
							'parent__in' => $pending_ids,
							'number'     => $remaining + 1,
							'order'      => 'ASC',
						]
					)
				);
				$pending_ids = [];

				if ( count( $replies ) > $remaining ) {
					$replies   = array_slice( $replies, 0, $remaining );
					$truncated = true;
				}

				foreach ( $replies as $reply ) {
					$reply_id  = (int) $reply->comment_ID;
					$parent_id = max( 0, (int) ( $reply->comment_parent ?? 0 ) );

					if ( isset( $seen_ids[ $reply_id ] ) ) {
						continue;
					}

					$seen_ids[ $reply_id ] = true;
					$tree[ $parent_id ][]  = $reply;
					$pending_ids[]         = $reply_id;
					++$reply_count;
				}

				$pending_ids = array_values( array_unique( $pending_ids ) );
				++$depth;

				if ( $truncated ) {
					break;
				}
			}

			if ( ! $truncated && ! empty( $pending_ids ) ) {
				$truncated = ! empty(
					get_comments(
						array_merge(
							$query_args,
							[
								'parent__in' => $pending_ids,
								'number'     => 1,
								'fields'     => 'ids',
								'order'      => 'ASC',
							]
						)
					)
				);
			}
			$this->set_comments_cache( $cache_key, compact( 'total', 'top', 'tree', 'truncated' ) );
		}

		$items = [];
		if ( ! empty( $top ) ) {
			$widget_settings = $this->get_comment_dock_settings( $post_id, $element_id );
			$widget_settings = UcapanTamuSettings::resolve_widget_settings(
				is_array( $widget_settings ) ? $widget_settings : [],
				is_array( $widget_settings ) ? $widget_settings : []
			);
			$renderer = $this->create_renderer( $widget_settings, $post_id, $element_id );

			foreach ( $top as $comment ) {
				$items[] = [
					'id'   => (int) $comment->comment_ID,
					'html' => $renderer->render_item( $comment, $tree, 0 ),
				];
			}
		}

		return new WP_REST_Response(
			[
				'items'    => $items,
				'total'    => $total,
				'page'     => $page,
				'per_page' => $per_page,
				'pages'    => max( 1, (int) ceil( max( 1, $total ) / $per_page ) ),
				'truncated' => $truncated,
			]
		);
	}

	public function store_comment( WP_REST_Request $request ) {
		if ( $this->is_comment_dock_disabled() ) {
			return new WP_Error(
				'widget_disabled',
				__( 'Widget tidak aktif.', 'apeiron-kit' ),
				[ 'status' => 403 ]
			);
		}

		$rate_limit_error = $this->check_rate_limit( 'post' );
		if ( is_wp_error( $rate_limit_error ) ) {
			return $rate_limit_error;
		}

		$post_id    = (int) $request->get_param( 'post_id' );
		$element_id = sanitize_text_field( (string) $request->get_param( 'element_id' ) );
		$target_token = sanitize_text_field( (string) $request->get_param( 'target_token' ) );
		$name       = sanitize_text_field( (string) $request->get_param( 'name' ) );
		$invited_guest_name  = sanitize_text_field( (string) $request->get_param( 'invited_guest_name' ) );
		$invited_guest_token = sanitize_text_field( (string) $request->get_param( 'invited_guest_token' ) );
		$email      = sanitize_email( (string) $request->get_param( 'email' ) );
		$message    = trim( wp_kses_post( (string) $request->get_param( 'message' ) ) );
		$attendance_value = sanitize_key( (string) $request->get_param( 'attendance' ) );
		$attendance_label = sanitize_text_field( (string) $request->get_param( 'attendance_label' ) );
		$parent_id        = absint( $request->get_param( 'parent_id' ) );
		$requested_sticker_src = trim( (string) $request->get_param( 'sticker_src' ) );
		$sticker_src      = $this->sanitize_sticker_src( $requested_sticker_src );
		$sticker_type     = $sticker_src ? $this->guess_sticker_type( $sticker_src ) : '';

		if ( ! $post_id || empty( $element_id ) ) {
			return new WP_Error( 'apeiron_kit_invalid', __( 'Permintaan tidak valid.', 'apeiron-kit' ), [ 'status' => 422 ] );
		}

		if ( '' !== $requested_sticker_src && '' === $sticker_src ) {
			return new WP_Error( 'apeiron_kit_invalid', __( 'Stiker yang dipilih tidak valid.', 'apeiron-kit' ), [ 'status' => 422 ] );
		}

		$target_error = $this->validate_comment_target( $post_id, $element_id, $target_token, true );
		if ( is_wp_error( $target_error ) ) {
			return $target_error;
		}

		$raw_widget_settings = $this->get_comment_dock_settings( $post_id, $element_id );
		$widget_settings = UcapanTamuSettings::resolve_widget_settings(
			is_array( $raw_widget_settings ) ? $raw_widget_settings : [],
			is_array( $raw_widget_settings ) ? $raw_widget_settings : []
		);
		$permissions     = $this->get_permission_settings( $widget_settings );
		$depth_limit     = $this->get_reply_depth_limit();
		if ( 'yes' === ( $widget_settings['enable_guest_name_from_url'] ?? '' ) ) {
			$guest_required_message = sanitize_text_field( (string) ( $widget_settings['guest_required_message'] ?? '' ) );
			if ( '' === $guest_required_message ) {
				$guest_required_message = __( '* Ucapan hanya dapat dikirim oleh tamu yang menerima undangan ini.', 'apeiron-kit' );
			}

			if ( '' === $invited_guest_name || ! $this->is_valid_invited_guest_token( $post_id, $element_id, $invited_guest_name, $invited_guest_token ) ) {
				return new WP_Error(
					'apeiron_kit_invited_guest_required',
					$guest_required_message,
					[ 'status' => 403 ]
				);
			}

			$name = $invited_guest_name;
		}

		if ( $parent_id > 0 ) {
			if ( ! $permissions['comment_reply'] ) {
				return new WP_Error( 'apeiron_kit_forbidden', __( 'Fitur balas komentar sedang dinonaktifkan.', 'apeiron-kit' ), [ 'status' => 403 ] );
			}

			$parent = get_comment( $parent_id );
			if ( ! $parent ) {
				return new WP_Error( 'apeiron_kit_invalid', __( 'Komentar induk sudah tidak tersedia. Muat ulang halaman.', 'apeiron-kit' ), [ 'status' => 404 ] );
			}

			$parent_status = wp_get_comment_status( $parent_id );
			$can_reply_to_pending = 'unapproved' === $parent_status
				&& ( $this->is_comment_owner( $parent_id ) || current_user_can( 'moderate_comments' ) );
			if ( 'approved' !== $parent_status && ! $can_reply_to_pending ) {
				return new WP_Error( 'apeiron_kit_invalid', __( 'Komentar induk belum disetujui atau sudah tidak aktif.', 'apeiron-kit' ), [ 'status' => 422 ] );
			}

			if ( (int) $parent->comment_post_ID !== $post_id ) {
				return new WP_Error( 'apeiron_kit_invalid', __( 'Target komentar telah berubah. Muat ulang halaman lalu coba lagi.', 'apeiron-kit' ), [ 'status' => 409 ] );
			}

			if ( (string) get_comment_meta( $parent_id, 'apeiron_element_id', true ) !== $element_id ) {
				return new WP_Error( 'apeiron_kit_invalid', __( 'Komentar induk tidak sesuai target.', 'apeiron-kit' ), [ 'status' => 422 ] );
			}

			if ( $this->get_comment_depth( $parent_id ) >= $depth_limit ) {
				return new WP_Error( 'apeiron_kit_invalid', __( 'Batas kedalaman balasan sudah tercapai.', 'apeiron-kit' ), [ 'status' => 422 ] );
			}

			$attendance_value = '';
			$attendance_label = '';
			if ( 'yes' !== ( $widget_settings['enable_reply_sticker'] ?? 'yes' ) ) {
				$sticker_src  = '';
				$sticker_type = '';
			}
		}

		$attendance_options = $this->prepare_attendance_options( $widget_settings['attendance_options'] ?? [] );
		if ( empty( $attendance_options ) ) {
			$attendance_options = $this->get_default_attendance_options();
		}
		$attendance_enabled = 'yes' === ( $widget_settings['attendence'] ?? $widget_settings['attendance'] ?? 'yes' );
		if ( ! $attendance_enabled ) {
			$attendance_value = '';
			$attendance_label = '';
		} elseif ( 0 === $parent_id && ! empty( $attendance_options ) && '' === $attendance_value ) {
			return new WP_Error( 'apeiron_kit_invalid', __( 'Silakan pilih status kehadiran.', 'apeiron-kit' ), [ 'status' => 422 ] );
		} elseif ( '' !== $attendance_value && ! isset( $attendance_options[ $attendance_value ] ) ) {
			return new WP_Error( 'apeiron_kit_invalid', __( 'Pilihan kehadiran tidak valid.', 'apeiron-kit' ), [ 'status' => 422 ] );
		}

		if ( $attendance_value && isset( $attendance_options[ $attendance_value ] ) ) {
			$attendance_label = (string) $attendance_options[ $attendance_value ];
		}

		$stickers_enabled = 'yes' === ( $widget_settings['show_stickers'] ?? 'yes' );
		[ $allow_image, $allow_video ] = StickerLibrary::resolve_media_permissions(
			$widget_settings,
			is_array( $raw_widget_settings ) ? $raw_widget_settings : []
		);
		if ( ! $stickers_enabled || ( ! $allow_image && ! $allow_video ) ) {
			$sticker_src  = '';
			$sticker_type = '';
		} elseif ( $sticker_src ) {
			$stored_type = $sticker_type ?: $this->guess_sticker_type( $sticker_src );
			if ( ( 'image' === $stored_type && ! $allow_image ) || ( 'video' === $stored_type && ! $allow_video ) ) {
				return new WP_Error( 'apeiron_kit_invalid', __( 'Sticker tidak valid untuk pengaturan widget ini.', 'apeiron-kit' ), [ 'status' => 422 ] );
			}
			$sticker_type = $stored_type;
		}

		if ( $this->text_length( $name ) > 100 ) {
			return new WP_Error( 'apeiron_kit_invalid', __( 'Nama terlalu panjang (maksimal 100 karakter).', 'apeiron-kit' ), [ 'status' => 422 ] );
		}

		if ( strlen( $email ) > 254 ) {
			return new WP_Error( 'apeiron_kit_invalid', __( 'Email terlalu panjang.', 'apeiron-kit' ), [ 'status' => 422 ] );
		}

		$global_settings = UcapanTamuSettings::get_settings();
		$max_length      = (int) ( $global_settings['max_message_length'] ?? 1000 );
		if ( $max_length < 50 || $max_length > 5000 ) {
			$max_length = 1000;
		}

		if ( $this->text_length( $message ) > $max_length ) {
			/* translators: %d: max character count */
			return new WP_Error( 'apeiron_kit_invalid', sprintf( __( 'Pesan terlalu panjang (maksimal %d karakter).', 'apeiron-kit' ), $max_length ), [ 'status' => 422 ] );
		}

		if ( empty( $name ) || empty( $message ) ) {
			return new WP_Error( 'apeiron_kit_invalid', __( 'Nama dan pesan wajib diisi.', 'apeiron-kit' ), [ 'status' => 422 ] );
		}

		if ( ! empty( $email ) && ( ! is_email( $email ) || strlen( $email ) > 254 ) ) {
			return new WP_Error( 'apeiron_kit_invalid', __( 'Email tidak valid.', 'apeiron-kit' ), [ 'status' => 422 ] );
		}

		$moderation      = ( $global_settings['comment_moderation'] ?? 'auto' ) === 'manual' ? 'manual' : 'auto';
		$requires_review = 'manual' === $moderation;
		$status          = $requires_review ? 0 : 1;

		$commentdata = wp_slash(
			[
				'comment_post_ID'      => $post_id,
				'comment_author'       => $name,
				'comment_author_email' => $email,
				'comment_author_url'   => '',
				'comment_content'      => $message,
				'comment_type'         => 'comment',
				'comment_approved'     => $status,
				'comment_parent'       => $parent_id,
			]
		);

		// Use WordPress submission pipeline for flood, duplicate, moderation, and comment hooks.
		$comment_id = wp_new_comment( $commentdata, true );

		if ( is_wp_error( $comment_id ) ) {
			if ( ! empty( $global_settings['prevent_duplicate'] ) && 'comment_duplicate' === $comment_id->get_error_code() ) {
				return new WP_Error( 'apeiron_kit_duplicate', __( 'Ucapan yang sama sudah pernah dikirim.', 'apeiron-kit' ), [ 'status' => 409 ] );
			}

			ErrorLogger::error( 'Failed to insert comment', [
				'post_id' => $post_id,
				'element_id' => $element_id,
				'error' => $comment_id->get_error_message(),
			] );
			return $comment_id;
		}

		$comment_id = (int) $comment_id;
		if ( $comment_id <= 0 ) {
			ErrorLogger::error( 'Failed to insert comment', [
				'post_id' => $post_id,
				'element_id' => $element_id,
				'error' => 'wp_new_comment returned non-positive id',
			] );
			return new WP_Error( 'apeiron_kit_failed', __( 'Gagal mengirim komentar.', 'apeiron-kit' ), [ 'status' => 500 ] );
		}

		$inserted_status = wp_get_comment_status( $comment_id );
		if ( ! in_array( $inserted_status, [ 'spam', 'trash' ], true ) ) {
			if ( $requires_review ) {
				wp_set_comment_status( $comment_id, 'hold' );
			} elseif ( 'approved' !== $inserted_status ) {
				wp_set_comment_status( $comment_id, 'approve' );
			}
		}

		$owner_token = $this->get_or_create_owner_token();
		$required_meta = [
			'apeiron_element_id'      => $element_id,
			'apeiron_owner_token_hash' => hash( 'sha256', $owner_token ),
			'apeiron_owner_user_id'   => get_current_user_id(),
		];
		foreach ( $required_meta as $meta_key => $meta_value ) {
			if ( false === add_comment_meta( $comment_id, $meta_key, $meta_value, true ) ) {
				wp_delete_comment( $comment_id, true );
				ErrorLogger::error( 'Failed to attach required comment metadata', [ 'comment_id' => $comment_id, 'meta_key' => $meta_key ] );
				return new WP_Error( 'apeiron_kit_failed', __( 'Gagal mengirim komentar.', 'apeiron-kit' ), [ 'status' => 500 ] );
			}
		}

		if ( $attendance_value ) {
			add_comment_meta( $comment_id, 'apeiron_attendance_value', $attendance_value, true );
		}

		if ( $attendance_label ) {
			add_comment_meta( $comment_id, 'apeiron_attendance_label', $attendance_label, true );
		}

		if ( $sticker_src ) {
			add_comment_meta( $comment_id, 'apeiron_sticker_src', $sticker_src, true );
			$stored_type = $sticker_type ?: $this->guess_sticker_type( $sticker_src );
			add_comment_meta( $comment_id, 'apeiron_sticker_type', $stored_type, true );
		}
		$this->invalidate_comments_cache( $post_id, $element_id );

		$comment  = get_comment( $comment_id );
		// WordPress and third-party moderation filters can override the requested
		// approval value. Trust persisted status, not the pre-insert setting.
		$requires_review = ! $comment || 'approved' !== wp_get_comment_status( $comment_id );
		$renderer = $this->create_renderer( $widget_settings, $post_id, $element_id );

		return new WP_REST_Response(
			[
				'ok'      => true,
				'message' => $requires_review ? __( 'Komentar dikirim dan menunggu moderasi.', 'apeiron-kit' ) : __( 'Komentar dipublikasikan.', 'apeiron-kit' ),
				'comment' => $requires_review || ! $comment
					? null
					: $this->prepare_comment_response( $comment, $permissions, $depth_limit, $renderer ),
			],
			201
		);
	}

	public function update_comment( WP_REST_Request $request ) {
		if ( $this->is_comment_dock_disabled() ) {
			return new WP_Error( 'widget_disabled', __( 'Widget tidak aktif.', 'apeiron-kit' ), [ 'status' => 403 ] );
		}

		$comment_id = absint( $request['id'] );
		$comment    = get_comment( $comment_id );
		if ( ! $comment ) {
			return new WP_Error( 'apeiron_kit_not_found', __( 'Komentar tidak ditemukan.', 'apeiron-kit' ), [ 'status' => 404 ] );
		}

		$target = $this->validate_existing_comment_access( $comment );
		if ( is_wp_error( $target ) ) {
			return $target;
		}

		$message = trim( wp_kses_post( (string) $request->get_param( 'message' ) ) );
		if ( '' === $message ) {
			return new WP_Error( 'apeiron_kit_invalid', __( 'Pesan wajib diisi.', 'apeiron-kit' ), [ 'status' => 422 ] );
		}
		$global_settings = UcapanTamuSettings::get_settings();
		$max_length      = (int) ( $global_settings['max_message_length'] ?? 1000 );
		if ( $max_length < 50 || $max_length > 5000 ) {
			$max_length = 1000;
		}
		if ( $this->text_length( $message ) > $max_length ) {
			/* translators: %d: max character count */
			return new WP_Error( 'apeiron_kit_invalid', sprintf( __( 'Pesan terlalu panjang (maksimal %d karakter).', 'apeiron-kit' ), $max_length ), [ 'status' => 422 ] );
		}

		$settings    = $this->get_comment_dock_settings( (int) $comment->comment_post_ID, (string) $target['element_id'] );
		$settings    = UcapanTamuSettings::resolve_widget_settings(
			is_array( $settings ) ? $settings : [],
			is_array( $settings ) ? $settings : []
		);
		$permissions = $this->get_permission_settings( $settings );
		$is_reply    = (int) $comment->comment_parent > 0;
		if ( ! ( $is_reply ? $permissions['reply_edit'] : $permissions['comment_edit'] ) ) {
			return new WP_Error( 'apeiron_kit_forbidden', __( 'Fitur edit komentar sedang dinonaktifkan.', 'apeiron-kit' ), [ 'status' => 403 ] );
		}
		if ( ! $this->is_comment_owner( $comment_id ) ) {
			return new WP_Error( 'apeiron_kit_forbidden', __( 'Anda hanya dapat mengedit komentar milik sendiri.', 'apeiron-kit' ), [ 'status' => 403 ] );
		}

		$result = wp_update_comment(
			wp_slash(
				[
					'comment_ID'      => $comment_id,
					'comment_content' => $message,
				]
			)
		);

		if ( false === $result ) {
			return new WP_Error( 'apeiron_kit_failed', __( 'Gagal memperbarui komentar.', 'apeiron-kit' ), [ 'status' => 500 ] );
		}
		$this->invalidate_comments_cache( (int) $comment->comment_post_ID, (string) $target['element_id'] );

		$updated  = get_comment( $comment_id );
		$renderer = $this->create_renderer( $settings, (int) $comment->comment_post_ID, (string) $target['element_id'] );
		return new WP_REST_Response(
			[
				'ok'      => true,
				'message' => __( 'Komentar diperbarui.', 'apeiron-kit' ),
				'comment' => $this->prepare_comment_response( $updated, $permissions, $this->get_reply_depth_limit(), $renderer ),
			]
		);
	}

	public function delete_comment( WP_REST_Request $request ) {
		if ( $this->is_comment_dock_disabled() ) {
			return new WP_Error( 'widget_disabled', __( 'Widget tidak aktif.', 'apeiron-kit' ), [ 'status' => 403 ] );
		}

		$comment_id = absint( $request['id'] );
		$comment    = get_comment( $comment_id );
		if ( ! $comment ) {
			return new WP_Error( 'apeiron_kit_not_found', __( 'Komentar tidak ditemukan.', 'apeiron-kit' ), [ 'status' => 404 ] );
		}

		$target = $this->validate_existing_comment_access( $comment );
		if ( is_wp_error( $target ) ) {
			return $target;
		}

		$settings    = $this->get_comment_dock_settings( (int) $comment->comment_post_ID, (string) $target['element_id'] );
		$settings    = UcapanTamuSettings::resolve_widget_settings(
			is_array( $settings ) ? $settings : [],
			is_array( $settings ) ? $settings : []
		);
		$permissions = $this->get_permission_settings( $settings );
		$is_reply    = (int) $comment->comment_parent > 0;
		if ( ! ( $is_reply ? $permissions['reply_delete'] : $permissions['comment_delete'] ) ) {
			return new WP_Error( 'apeiron_kit_forbidden', __( 'Fitur hapus komentar sedang dinonaktifkan.', 'apeiron-kit' ), [ 'status' => 403 ] );
		}
		if ( ! $this->is_comment_owner( $comment_id ) ) {
			return new WP_Error( 'apeiron_kit_forbidden', __( 'Anda hanya dapat menghapus komentar milik sendiri.', 'apeiron-kit' ), [ 'status' => 403 ] );
		}

		if ( ! wp_delete_comment( $comment_id, true ) ) {
			return new WP_Error( 'apeiron_kit_failed', __( 'Gagal menghapus komentar.', 'apeiron-kit' ), [ 'status' => 500 ] );
		}
		$post_id    = (int) $comment->comment_post_ID;
		$element_id = (string) $target['element_id'];
		$this->invalidate_comments_cache( $post_id, $element_id );
		$state = $this->get_target_state( $post_id, $element_id, $settings, $request );

		return new WP_REST_Response(
			array_merge( $state, [
				'ok'      => true,
				'message' => __( 'Komentar dihapus.', 'apeiron-kit' ),
				'id'      => $comment_id,
			] )
		);
	}

	public function verify_nonce( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce ) {
			return false;
		}
		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	private function validate_comment_target( int $post_id, string $element_id, string $target_token = '', bool $require_token = true ) {
		if ( ! $this->is_valid_element_id( $element_id ) ) {
			return new WP_Error( 'apeiron_kit_invalid_target', __( 'Target komentar tidak valid.', 'apeiron-kit' ), [ 'status' => 422 ] );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'apeiron_kit_invalid_target', __( 'Target komentar tidak ditemukan.', 'apeiron-kit' ), [ 'status' => 404 ] );
		}

		// Prevent comments API access from exposing non-public invitations.
		if ( ! $this->is_post_publicly_viewable( $post ) ) {
			return new WP_Error( 'apeiron_kit_invalid_target', __( 'Target komentar tidak ditemukan.', 'apeiron-kit' ), [ 'status' => 404 ] );
		}

		// A valid HMAC proves the exact post/element pair was rendered by this server.
		// Theme Builder and cached previews may not have a fallback option yet, so POST
		// requests can use that proof. Tokenless reads still require stored settings,
		// preventing removed widgets from remaining publicly queryable.
		$has_valid_token = $require_token && $this->is_valid_target_token( $post_id, $element_id, $target_token );
		$settings = $this->get_comment_dock_settings( $post_id, $element_id );
		if ( null === $settings && ! $has_valid_token ) {
			return new WP_Error( 'apeiron_kit_invalid_target', __( 'Target komentar tidak ditemukan.', 'apeiron-kit' ), [ 'status' => 404 ] );
		}

		if ( $require_token && ! comments_open( $post_id ) ) {
			return new WP_Error( 'apeiron_kit_comments_closed', __( 'Komentar untuk halaman ini sudah ditutup.', 'apeiron-kit' ), [ 'status' => 403 ] );
		}

		if ( $require_token && ! $has_valid_token ) {
			return new WP_Error( 'apeiron_kit_invalid_target', __( 'Target komentar tidak valid.', 'apeiron-kit' ), [ 'status' => 403 ] );
		}

		return true;
	}

	/**
	 * @param \WP_Post $post
	 */
	private function is_post_publicly_viewable( $post ): bool {
		if ( ! is_object( $post ) ) {
			return false;
		}

		if ( ! isset( $post->post_status, $post->post_password ) ) {
			return false;
		}

		if ( 'publish' !== $post->post_status ) {
			return current_user_can( 'edit_post', (int) $post->ID );
		}

		return '' === $post->post_password;
	}

	private function is_valid_element_id( string $element_id ): bool {
		if ( '' === $element_id || strlen( $element_id ) > 100 ) {
			return false;
		}

		return (bool) preg_match( '/^[A-Za-z0-9_-]+$/', $element_id );
	}

	private function is_valid_target_token( int $post_id, string $element_id, string $target_token ): bool {
		if ( '' === $target_token ) {
			return false;
		}

		$expected = wp_hash( 'apeiron-comment-dock|' . $post_id . '|' . $element_id );
		return hash_equals( $expected, $target_token );
	}

	private function is_valid_invited_guest_token( int $post_id, string $element_id, string $guest_name, string $token ): bool {
		if ( '' === $guest_name || '' === $token ) {
			return false;
		}

		$expected = wp_hash( 'apeiron-invited-guest|' . $post_id . '|' . $element_id . '|' . $guest_name );
		return hash_equals( $expected, $token );
	}

	private function get_comment_dock_settings( int $post_id, string $element_id ): ?array {
		static $cache = [];
		$cache_key = $post_id . '|' . $element_id;
		if ( array_key_exists( $cache_key, $cache ) ) {
			return $cache[ $cache_key ];
		}

		$raw = get_post_meta( $post_id, '_elementor_data', true );
		if ( ! is_string( $raw ) || '' === $raw ) {
			$fallback = get_option( 'apeiron_comment_dock_settings_' . md5( $post_id . '|' . $element_id ), null );
			$cache[ $cache_key ] = is_array( $fallback ) ? $fallback : null;
			return $cache[ $cache_key ];
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			$fallback = get_option( 'apeiron_comment_dock_settings_' . md5( $post_id . '|' . $element_id ), null );
			$cache[ $cache_key ] = is_array( $fallback ) ? $fallback : null;
			return $cache[ $cache_key ];
		}

		$settings = $this->find_comment_dock_settings( $data, $element_id );
		if ( null === $settings ) {
			$fallback = get_option( 'apeiron_comment_dock_settings_' . md5( $post_id . '|' . $element_id ), null );
			$settings = is_array( $fallback ) ? $fallback : null;
		}
		$cache[ $cache_key ] = $settings;
		return $settings;
	}

	private function find_comment_dock_settings( array $elements, string $element_id ): ?array {
		foreach ( $elements as $element ) {
			if (
				is_array( $element )
				&& ( $element['id'] ?? '' ) === $element_id
				&& ( $element['widgetType'] ?? '' ) === 'apeiron-comment-dock'
			) {
				return is_array( $element['settings'] ?? null ) ? $element['settings'] : [];
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$settings = $this->find_comment_dock_settings( $element['elements'], $element_id );
				if ( null !== $settings ) {
					return $settings;
				}
			}
		}

		return null;
	}

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

	/**
	 * @param object[] $comments
	 * @return array<int,int>
	 */
	private function build_comment_depths( array $comments ): array {
		$parents       = [];
		$pending_ids   = [];
		$known_ids     = [];

		foreach ( $comments as $comment ) {
			$id            = (int) ( $comment->comment_ID ?? 0 );
			$parent_id     = max( 0, (int) ( $comment->comment_parent ?? 0 ) );
			$parents[ $id ] = $parent_id;
			$known_ids[ $id ] = true;

			if ( $parent_id > 0 ) {
				$pending_ids[ $parent_id ] = true;
			}
		}

		$pending_ids = array_keys( $pending_ids );

		while ( ! empty( $pending_ids ) ) {
			$ancestors = get_comments(
				[
					'include' => array_map( 'absint', $pending_ids ),
					'status'  => 'all',
				]
			);
			$next_ids = [];

			foreach ( $ancestors as $ancestor ) {
				$id        = (int) $ancestor->comment_ID;
				$parent_id = max( 0, (int) ( $ancestor->comment_parent ?? 0 ) );
				$parents[ $id ] = $parent_id;
				$known_ids[ $id ] = true;

				if ( $parent_id > 0 && ! isset( $parents[ $parent_id ] ) ) {
					$next_ids[ $parent_id ] = true;
				}
			}

			$pending_ids = array_keys( $next_ids );
		}

		$depths = [];
		foreach ( array_keys( $known_ids ) as $comment_id ) {
			$depth  = 1;
			$current = $comment_id;
			$visited = [];

			while ( isset( $parents[ $current ] ) && $parents[ $current ] > 0 && $depth < 20 ) {
				if ( isset( $visited[ $current ] ) ) {
					break;
				}

				$visited[ $current ] = true;
				$depth++;
				$current = $parents[ $current ];
			}

			$depths[ $comment_id ] = $depth;
		}

		return $depths;
	}

	private function get_comment_depth( int $comment_id ): int {
		$depth   = 1;
		$current = get_comment( $comment_id );
		while ( $current && (int) $current->comment_parent > 0 && $depth < 20 ) {
			$depth++;
			$current = get_comment( (int) $current->comment_parent );
		}
		return $depth;
	}

	private function get_or_create_owner_token(): string {
		$token = '';
		if ( ! empty( $_COOKIE[ self::OWNER_COOKIE ] ) ) {
			$token = sanitize_text_field( wp_unslash( (string) $_COOKIE[ self::OWNER_COOKIE ] ) );
		}

		if ( ! preg_match( '/^[a-f0-9]{32,64}$/', $token ) ) {
			$token = wp_generate_password( 48, false, false );
			$token = strtolower( preg_replace( '/[^a-f0-9]/', '', hash( 'sha256', $token . microtime( true ) ) ) );
		}

		if ( ! headers_sent() ) {
			setcookie(
				self::OWNER_COOKIE,
				$token,
				[
					'expires'  => time() + YEAR_IN_SECONDS,
					'path'     => COOKIEPATH ?: '/',
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				]
			);
			$_COOKIE[ self::OWNER_COOKIE ] = $token;
		}

		return $token;
	}

	private function is_comment_owner( int $comment_id ): bool {
		return CommentOwnership::is_owner( $comment_id );
	}

	private function validate_existing_comment_access( $comment ) {
		if ( 'comment' !== (string) $comment->comment_type ) {
			return new WP_Error( 'apeiron_kit_invalid_target', __( 'Komentar tidak valid.', 'apeiron-kit' ), [ 'status' => 422 ] );
		}

		$element_id = (string) get_comment_meta( (int) $comment->comment_ID, 'apeiron_element_id', true );
		if ( '' === $element_id || ! $this->is_valid_element_id( $element_id ) ) {
			return new WP_Error( 'apeiron_kit_invalid_target', __( 'Target komentar tidak valid.', 'apeiron-kit' ), [ 'status' => 422 ] );
		}

		$target_error = $this->validate_comment_target( (int) $comment->comment_post_ID, $element_id, '', false );
		if ( is_wp_error( $target_error ) ) {
			return $target_error;
		}

		return [ 'element_id' => $element_id ];
	}

	private function prepare_comment_response( $comment, array $permissions, int $depth_limit, ?CommentRenderer $renderer = null, array $depths = [] ): array {
		$comment_id = (int) $comment->comment_ID;
		$depth      = $depths[ $comment_id ] ?? $this->get_comment_depth( $comment_id );
		$is_reply   = (int) $comment->comment_parent > 0;
		$is_owner   = $this->is_comment_owner( $comment_id );

		return [
			'id'               => $comment_id,
			'parentId'         => (int) $comment->comment_parent,
			'depth'            => $depth - 1,
			'name'             => esc_html( $comment->comment_author ),
			'message'          => wp_kses_post( wpautop( $comment->comment_content ) ),
			'rawMessage'       => esc_textarea( $comment->comment_content ),
			'createdAt'        => (int) get_comment_date( 'U', $comment ),
			'attendanceLabel'  => $is_reply ? '' : esc_html( (string) get_comment_meta( $comment_id, 'apeiron_attendance_label', true ) ),
			'attendanceValue'  => $is_reply ? '' : esc_html( (string) get_comment_meta( $comment_id, 'apeiron_attendance_value', true ) ),
			'avatar'           => esc_url( get_avatar_url( $comment->comment_author_email, [ 'size' => 40 ] ) ),
			'sticker'          => $this->prepare_sticker_payload( $comment_id ),
			'canReply'         => $permissions['comment_reply'] && $depth < $depth_limit,
			'canEdit'          => $is_owner && ( $is_reply ? $permissions['reply_edit'] : $permissions['comment_edit'] ),
			'canDelete'        => $is_owner && ( $is_reply ? $permissions['reply_delete'] : $permissions['comment_delete'] ),
			// Shared server-rendered markup; JS appends it directly.
			'html'             => $renderer ? $renderer->render_item( $comment, [], max( 0, $depth - 1 ) ) : '',
		];
	}

	private function prepare_attendance_options( array $options ): array {
		$prepared   = [];
		$used_slugs = [];
		foreach ( $options as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( $option['option_label'] ?? '' ) );
			$slug  = ! empty( $option['option_slug'] ) ? sanitize_key( (string) $option['option_slug'] ) : sanitize_title( $label );
			if ( '' === $slug || '' === $label ) {
				continue;
			}

			$original_slug = $slug;
			$counter       = 2;
			while ( isset( $used_slugs[ $slug ] ) ) {
				$slug = $original_slug . '-' . $counter;
				$counter++;
			}

			$used_slugs[ $slug ] = true;
			$prepared[ $slug ]  = $label;
		}

		return $prepared;
	}

	private function get_default_attendance_options(): array {
		return [
			'hadir'       => __( 'Hadir', 'apeiron-kit' ),
			'tidak-hadir' => __( 'Tidak Hadir', 'apeiron-kit' ),
			'ragu'        => __( 'Masih Ragu', 'apeiron-kit' ),
		];
	}

	private function create_renderer( array $settings, int $post_id, string $element_id ): CommentRenderer {
		$settings['_apeiron_post_id']      = $post_id;
		$settings['_apeiron_element_id']   = $element_id;
		$settings['_apeiron_target_token'] = wp_hash( 'apeiron-comment-dock|' . $post_id . '|' . $element_id );

		return CommentRenderer::from_settings( $settings );
	}

	private function sanitize_sticker_src( string $value ): string {
		$value = trim( $value );
		if ( empty( $value ) ) {
			return '';
		}

		$resolved = StickerLibrary::resolve( $value );
		return null === $resolved ? '' : $resolved['relative'];
	}

	private function guess_sticker_type( string $relative ): string {
		$ext = strtolower( pathinfo( $relative, PATHINFO_EXTENSION ) );
		return in_array( $ext, [ 'webm', 'mp4' ], true ) ? 'video' : 'image';
	}

	private function prepare_sticker_payload( int $comment_id ): array {
		$relative = (string) get_comment_meta( $comment_id, 'apeiron_sticker_src', true );
		if ( empty( $relative ) ) {
			return [];
		}

		$resolved = StickerLibrary::resolve( $relative );
		if ( null === $resolved ) {
			return [];
		}

		return [
			'src'  => $resolved['url'],
			'type' => $resolved['type'],
		];
	}

	private function text_length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}

	private function comments_cache_key( int $post_id, string $element_id, string $mode, int $page, int $per_page ): string {
		$version_key = 'version_' . md5( $post_id . '|' . $element_id );
		$version     = wp_cache_get( $version_key, self::CACHE_GROUP );
		if ( false === $version ) {
			$version = (int) get_transient( 'apeiron_comment_dock_' . $version_key );
			if ( $version < 1 ) {
				$version = 1;
			}
			wp_cache_set( $version_key, $version, self::CACHE_GROUP, 2 * MINUTE_IN_SECONDS );
		}

		// Payload excludes rendered HTML; ownership actions are always rendered per visitor.
		return 'comments_' . md5( implode( '|', [ $post_id, $element_id, $mode, $page, $per_page, (int) $version ] ) );
	}

	private function invalidate_comments_cache( int $post_id, string $element_id ): void {
		$key = 'version_' . md5( $post_id . '|' . $element_id );
		$version = (int) wp_cache_get( $key, self::CACHE_GROUP );
		if ( $version < 1 ) {
			$version = (int) get_transient( 'apeiron_comment_dock_' . $key );
		}
		$version = max( 1, $version ) + 1;
		wp_cache_set( $key, $version, self::CACHE_GROUP, 2 * MINUTE_IN_SECONDS );
		set_transient( 'apeiron_comment_dock_' . $key, $version, 2 * MINUTE_IN_SECONDS );
	}

	private function get_comments_cache( string $key ) {
		$value = wp_cache_get( $key, self::CACHE_GROUP );
		if ( false !== $value ) {
			return $value;
		}

		$value = get_transient( 'apeiron_comment_dock_' . $key );
		if ( false !== $value ) {
			wp_cache_set( $key, $value, self::CACHE_GROUP, MINUTE_IN_SECONDS );
		}
		return $value;
	}

	private function set_comments_cache( string $key, array $value ): void {
		wp_cache_set( $key, $value, self::CACHE_GROUP, MINUTE_IN_SECONDS );
		set_transient( 'apeiron_comment_dock_' . $key, $value, MINUTE_IN_SECONDS );
	}

	private function get_target_state( int $post_id, string $element_id, array $settings, WP_REST_Request $request ): array {
		$per_page = min( absint( $request->get_param( 'per_page' ) ) ?: self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE );
		$total    = (int) get_comments( [
			'post_id' => $post_id,
			'parent' => 0,
			'status' => 'approve',
			'count' => true,
			'meta_key' => 'apeiron_element_id',
			'meta_value' => $element_id,
		] );
		$pages = max( 1, (int) ceil( $total / $per_page ) );
		$page  = min( max( 1, absint( $request->get_param( 'page' ) ) ), $pages );
		$state = compact( 'total', 'page', 'pages' );
		$state['per_page'] = $per_page;

		if ( 'yes' === ( $settings['attendence'] ?? $settings['attendance'] ?? 'yes' ) && 'yes' === ( $settings['show_attendance_summary'] ?? 'yes' ) ) {
			$state['attendance'] = [];
			foreach ( array_keys( $this->prepare_attendance_options( $settings['attendance_options'] ?? [] ) ) as $value ) {
				$state['attendance'][ $value ] = (int) get_comments( [
					'post_id' => $post_id,
					'parent' => 0,
					'status' => 'approve',
					'count' => true,
					'meta_query' => [
						[ 'key' => 'apeiron_element_id', 'value' => $element_id ],
						[ 'key' => 'apeiron_attendance_value', 'value' => $value ],
					],
				] );
			}
		}

		return $state;
	}

	/** @return bool|WP_Error */
	private function check_rate_limit( string $type = 'post' ) {
		if ( null === $this->rate_limiter ) {
			$this->rate_limiter = new CommentRateLimiter();
		}

		return $this->rate_limiter->check( $type );
	}

	private function is_comment_dock_disabled(): bool {
		return in_array(
			'comment_dock',
			WidgetRegistry::disabled_slugs(),
			true
		);
	}
}
