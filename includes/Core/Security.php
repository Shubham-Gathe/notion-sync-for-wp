<?php
namespace NotionSync\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security Service
 */
class Security {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function verify_nonce( $nonce, $action ) {
		return wp_verify_nonce( $nonce, $action );
	}

	public function check_permissions( $capability = 'manage_options' ) {
		return current_user_can( $capability );
	}
}
