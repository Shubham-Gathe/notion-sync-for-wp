<?php
namespace NotionSync\Notion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notion Database Service
 */
class DatabaseService {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Parse raw Notion database response into clean property list
	 */
	public function get_available_properties( $raw_db ) {
		if ( empty( $raw_db['properties'] ) ) {
			return [];
		}

		$properties = [];
		foreach ( $raw_db['properties'] as $id => $prop ) {
			$properties[ $id ] = [
				'id'   => $id,
				'name' => $prop['name'],
				'type' => $prop['type'],
			];
		}

		return $properties;
	}
}
