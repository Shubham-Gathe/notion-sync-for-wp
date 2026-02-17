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
	 * Get stored mapping configuration for a connection
	 */
	public function get_mapping( $connection_id = null ) {
		if ( ! $connection_id ) {
			// Backward compatibility: try to get from old option if no ID provided
			$default = [
				'post_title'   => '',
				'post_content' => '',
				'post_status'  => '',
				'custom_meta'  => [],
			];
			return get_option( 'notion_sync_mapping', $default );
		}

		$repository = ConnectionRepository::get_instance();
		$connection = $repository->get( $connection_id );
		
		return isset( $connection['mapping'] ) ? $connection['mapping'] : [];
	}

	/**
	 * Save mapping configuration for a connection
	 */
	public function save_mapping( $mapping, $connection_id = null ) {
		if ( ! $connection_id ) {
			return update_option( 'notion_sync_mapping', $mapping );
		}

		$repository = ConnectionRepository::get_instance();
		return $repository->save( $connection_id, [ 'mapping' => $mapping ] );
	}

	/**
	 * Get all public post types
	 */
	public function get_post_types() {
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		unset( $post_types['attachment'] );
		return $post_types;
	}

	/**
	 * Get taxonomies for a specific post type
	 */
	public function get_post_type_taxonomies( $post_type ) {
		return get_object_taxonomies( $post_type, 'objects' );
	}
}
