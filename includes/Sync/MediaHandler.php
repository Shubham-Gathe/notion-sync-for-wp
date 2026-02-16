<?php
namespace NotionSync\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Media Handler
 */
class MediaHandler {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	// Image and file handling
}
