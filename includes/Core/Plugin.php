<?php
namespace NotionSync\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use NotionSync\Admin\AdminMenu;

/**
 * Main Plugin Controller
 */
class Plugin {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init();
	}

	private function init() {
		// Initialize Core components
		Logger::get_instance();
		Security::get_instance();

		// Initialize Admin components
		if ( is_admin() ) {
			AdminMenu::get_instance();
			\NotionSync\Admin\SettingsPage::get_instance();
		}

		// Future: Initialize Sync engine
	}

	public function get_version() {
		return NOTION_SYNC_VERSION;
	}
}
