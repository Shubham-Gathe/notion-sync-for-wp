<?php
namespace NotionSync\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTML Helper
 */
class HtmlHelper {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	// HTML rendering utility methods
}
