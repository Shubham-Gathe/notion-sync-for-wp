<?php
namespace NotionSync\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logging Service
 */
class Logger {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function log( $message, $level = 'info' ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$time = date( 'Y-m-d H:i:s' );
		$log_entry = "[{$time}] [{$level}] {$message}" . PHP_EOL;
		
		error_log( "[NotionSync] " . $message );
	}
}
