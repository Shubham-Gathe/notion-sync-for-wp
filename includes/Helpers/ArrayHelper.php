<?php
namespace NotionSync\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Array Helper
 */
class ArrayHelper {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	// Array utility methods
}
