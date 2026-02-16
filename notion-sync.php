<?php
/**
 * Plugin Name:       Notion Sync for WP
 * Description:       Sync Notion databases and pages to WordPress posts/pages.
 * Version:           1.0.0
 * Author:            Antigravity
 * Text Domain:       notion-sync-for-wp
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants
define( 'NOTION_SYNC_VERSION', '1.0.0' );
define( 'NOTION_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'NOTION_SYNC_URL', plugin_dir_url( __FILE__ ) );

// Load Autoloader
require_once NOTION_SYNC_PATH . 'includes/Core/Autoloader.php';

/**
 * Initialize Plugin
 */
function notion_sync_init() {
	$autoloader = \NotionSync\Core\Autoloader::get_instance();
	$autoloader->register_namespace( 'NotionSync\\', NOTION_SYNC_PATH . 'includes/' );

	// Start Plugin
	\NotionSync\Core\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'notion_sync_init' );
