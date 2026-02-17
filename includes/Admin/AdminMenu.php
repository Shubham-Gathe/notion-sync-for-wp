<?php
namespace NotionSync\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Menu Registration
 */
class AdminMenu {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
	}

	public function register_menus() {
		add_menu_page(
			__( 'Notion Sync', 'notion-sync-for-wp' ),
			__( 'Notion Sync', 'notion-sync-for-wp' ),
			'manage_options',
			'notion-sync-for-wp',
			[ ConnectionsPage::get_instance(), 'render' ],
			'dashicons-external',
			30
		);

		add_submenu_page(
			'notion-sync-for-wp',
			__( 'Connections', 'notion-sync-for-wp' ),
			__( 'Connections', 'notion-sync-for-wp' ),
			'manage_options',
			'notion-sync-for-wp',
			[ ConnectionsPage::get_instance(), 'render' ]
		);

		add_submenu_page(
			'notion-sync-for-wp',
			__( 'Settings (Global)', 'notion-sync-for-wp' ),
			__( 'Settings (Global)', 'notion-sync-for-wp' ),
			'manage_options',
			'notion-sync-for-wp-settings',
			[ SettingsPage::get_instance(), 'render' ]
		);

		// Register Mapping Page but hide from sidebar (accessible via Edit/Add buttons)
		add_submenu_page(
			null,
			__( 'Field Mapping', 'notion-sync-for-wp' ),
			__( 'Field Mapping', 'notion-sync-for-wp' ),
			'manage_options',
			'notion-sync-for-wp-mapping',
			[ MappingPage::get_instance(), 'render' ]
		);

		add_submenu_page(
			'notion-sync-for-wp',
			__( 'Logs', 'notion-sync-for-wp' ),
			__( 'Logs', 'notion-sync-for-wp' ),
			'manage_options',
			'notion-sync-for-wp-logs',
			[ LogsPage::get_instance(), 'render' ]
		);
	}
}
