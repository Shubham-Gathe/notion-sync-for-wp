<?php
namespace NotionSync\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logs Page Controller
 */
class LogsPage {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function render() {
		$template = NOTION_SYNC_PATH . 'templates/admin/logs-page.php';
		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<div class="wrap"><h1>Sync Logs</h1><p>Template not found.</p></div>';
		}
	}
}
