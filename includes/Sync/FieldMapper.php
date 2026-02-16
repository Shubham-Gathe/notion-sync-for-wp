<?php
namespace NotionSync\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field Mapper
 */
class FieldMapper {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Get stored mapping configuration
	 */
	public function get_mapping() {
		$default = [
			'post_title'   => '',
			'post_content' => '',
			'post_status'  => '',
			'custom_meta'  => [],
		];
		return get_option( 'notion_sync_mapping', $default );
	}

	/**
	 * Save mapping configuration
	 */
	public function save_mapping( $mapping ) {
		return update_option( 'notion_sync_mapping', $mapping );
	}
}
