<?php
namespace NotionSync\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Page Controller
 */
class SettingsPage {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_notion_sync_test_connection', [ $this, 'handle_test_connection' ] );
		add_action( 'wp_ajax_notion_sync_mass_sync', [ $this, 'handle_mass_sync' ] );
	}

	/**
	 * Handle mass sync AJAX request
	 */
	public function handle_mass_sync() {
		check_ajax_referer( 'notion_sync_admin_nonce', '_ajax_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'notion-sync-for-wp' ) );
		}

		$conn_id      = isset( $_POST['connection_id'] ) ? sanitize_text_field( $_POST['connection_id'] ) : '';
		$cursor       = isset( $_POST['cursor'] ) ? sanitize_text_field( $_POST['cursor'] ) : null;
		
		if ( ! $conn_id ) {
			wp_send_json_error( __( 'Connection ID is missing.', 'notion-sync-for-wp' ) );
		}

		$sync_service = \NotionSync\Sync\SyncService::get_instance();
		$results      = $sync_service->sync_database( $conn_id, $cursor );

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( $results->get_error_message() );
		}

		wp_send_json_success( $results );
	}

	public function handle_test_connection() {
		check_ajax_referer( 'notion_sync_admin_nonce', '_ajax_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'notion-sync-for-wp' ) );
		}

		$db_id = isset( $_POST['database_id'] ) ? sanitize_text_field( $_POST['database_id'] ) : '';
		
		if ( ! $db_id ) {
			// Fallback to legacy or currently edited connection if possible
			$db_id = get_option( 'notion_sync_db_id' );
		}

		if ( ! $db_id ) {
			wp_send_json_error( __( 'No Database ID provided for testing.', 'notion-sync-for-wp' ) );
		}

		$notion_client = \NotionSync\Notion\NotionClient::get_instance();
		$result        = $notion_client->get_database( $db_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( __( 'Connection successful! Database found.', 'notion-sync-for-wp' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'notion-sync-for-wp' ) === false ) {
			return;
		}

		wp_enqueue_style( 'notion-sync-for-wp-admin', NOTION_SYNC_URL . 'assets/css/admin.css', [], NOTION_SYNC_VERSION );
		wp_enqueue_script( 'notion-sync-for-wp-admin', NOTION_SYNC_URL . 'assets/js/admin.js', [ 'jquery' ], NOTION_SYNC_VERSION, true );

		wp_localize_script( 'notion-sync-for-wp-admin', 'notionSyncAdmin', [
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'adminUrl' => admin_url(),
			'nonce'    => wp_create_nonce( 'notion_sync_admin_nonce' ),
		] );
	}

	public function register_settings() {
		register_setting( 'notion_sync_settings_group', 'notion_sync_token', [
			'sanitize_callback' => 'sanitize_text_field',
		] );

		add_settings_section(
			'notion_sync_main_section',
			__( 'Global API Settings', 'notion-sync-for-wp' ),
			null,
			'notion-sync-for-wp'
		);

		add_settings_field(
			'notion_sync_token',
			__( 'Notion Integration Token', 'notion-sync-for-wp' ),
			[ $this, 'render_token_field' ],
			'notion-sync-for-wp',
			'notion_sync_main_section'
		);
	}

	public function render_token_field() {
		$token = get_option( 'notion_sync_token' );
		echo '<input type="password" name="notion_sync_token" value="' . esc_attr( $token ) . '" class="regular-text" placeholder="secret_...">';
		echo '<p class="description">' . esc_html__( 'This token is shared across all database connections.', 'notion-sync-for-wp' ) . '</p>';
	}

	public function render() {
		$template = NOTION_SYNC_PATH . 'templates/admin/settings-page.php';
		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<div class="wrap"><h1>Notion Sync Settings</h1><p>Template not found.</p></div>';
		}
	}
}
