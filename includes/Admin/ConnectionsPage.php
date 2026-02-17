<?php
namespace NotionSync\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use NotionSync\Sync\ConnectionRepository;

/**
 * Connections Page Controller
 */
class ConnectionsPage {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', [ $this, 'handle_actions' ] );
	}

	/**
	 * Handle admin actions (Sync, Delete, Add)
	 */
	public function handle_actions() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'notion-sync-for-wp' ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		$id     = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : '';

		if ( 'delete' === $action && $id ) {
			check_admin_referer( 'delete_connection_' . $id );
			ConnectionRepository::get_instance()->delete( $id );
			wp_safe_redirect( admin_url( 'admin.php?page=notion-sync-for-wp&deleted=1' ) );
			exit;
		}
	}

	public function render() {
		$template = NOTION_SYNC_PATH . 'templates/admin/connections-page.php';
		$repository = ConnectionRepository::get_instance();
		$connections = $repository->get_all();

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<div class="wrap"><h1>Connections</h1><p>Template not found.</p></div>';
		}
	}
}
