<?php

namespace ApeironKit\Support;

use ApeironKit\Support\Assets\AssetRegistrar;
use ApeironKit\Support\Assets\CoverAssetManager;
use ApeironKit\Support\Assets\DocumentWidgetIndex;
use ApeironKit\Support\Assets\EditorNotifier;
use ApeironKit\Support\Assets\ElementorContext;
use ApeironKit\Support\Assets\FrontendAssetEnqueuer;
use ApeironKit\Support\Assets\PageLoaderBoot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Penghubung hook WordPress untuk layanan aset plugin.
 */
class AssetManager {

	private FrontendAssetEnqueuer $frontend;
	private CoverAssetManager $cover;
	private PageLoaderBoot $page_loader;
	private EditorNotifier $editor;

	public function __construct() {
		$context        = new ElementorContext();
		$document_index = new DocumentWidgetIndex();
		$registrar      = new AssetRegistrar();
		$cover_assets   = new CoverAssetManager( $document_index, $context );

		$this->frontend = new FrontendAssetEnqueuer( $registrar, $cover_assets, $document_index, $context );
		$this->cover       = $cover_assets;
		$this->page_loader = new PageLoaderBoot( $registrar, $document_index, $context );
		$this->editor      = new EditorNotifier( $registrar, $context );
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_assets' ], 5 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend' ] );
		add_action( 'wp_head', [ $this, 'print_cover_boot' ], 1 );
		add_action( 'wp_head', [ $this, 'print_page_loader_boot' ], 1 );
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_editor' ] );
		add_filter( 'heartbeat_received', [ $this, 'heartbeat_received' ], 10, 2 );
	}

	public function register_frontend_assets(): void {
		$this->frontend->register_assets();
	}

	public function enqueue_frontend(): void {
		$this->frontend->enqueue();
	}

	public function print_page_loader_boot(): void {
		$this->page_loader->print();
	}

	public function print_cover_boot(): void {
		$this->cover->print_boot();
	}

	public function enqueue_editor(): void {
		$this->editor->enqueue();
	}

	/**
	 * @param array<string,mixed> $response Data respons heartbeat.
	 * @param array<string,mixed> $data     Data permintaan heartbeat.
	 * @return array<string,mixed>
	 */
	public function heartbeat_received( array $response, array $data ): array {
		return $this->editor->heartbeat_received( $response, $data );
	}
}
