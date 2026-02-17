<?php
namespace NotionSync\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Connection Repository
 * 
 * Manages the storage and retrieval of multiple Notion database connections.
 */
class ConnectionRepository {
	private static $instance = null;
	private $option_name = 'notion_sync_connections';

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Get all connections
	 */
	public function get_all() {
		$connections = get_option( $this->option_name, [] );
		return is_array( $connections ) ? $connections : [];
	}

	/**
	 * Get a specific connection by ID
	 */
	public function get( $id ) {
		$connections = $this->get_all();
		return isset( $connections[ $id ] ) ? $connections[ $id ] : null;
	}

	/**
	 * Save a connection (Create or Update)
	 */
	public function save( $id, $data ) {
		$connections = $this->get_all();
		
		// Ensure data is an array
		if ( ! is_array( $data ) ) {
			return false;
		}

		$connections[ $id ] = array_merge( 
			isset( $connections[ $id ] ) ? $connections[ $id ] : [],
			$data 
		);

		return update_option( $this->option_name, $connections );
	}

	/**
	 * Create a new connection
	 */
	public function create( $data ) {
		$id = uniqid( 'conn_' );
		if ( $this->save( $id, $data ) ) {
			return $id;
		}
		return false;
	}

	/**
	 * Delete a connection
	 */
	public function delete( $id ) {
		$connections = $this->get_all();
		if ( isset( $connections[ $id ] ) ) {
			unset( $connections[ $id ] );
			return update_option( $this->option_name, $connections );
		}
		return false;
	}

	/**
	 * Check if a database ID is already registered in any connection
	 */
	public function exists_by_db_id( $database_id, $exclude_id = null ) {
		$connections = $this->get_all();
		foreach ( $connections as $id => $conn ) {
			if ( $id === $exclude_id ) continue;
			if ( isset( $conn['database_id'] ) && $conn['database_id'] === $database_id ) {
				return true;
			}
		}
		return false;
	}
}
