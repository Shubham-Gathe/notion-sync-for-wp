<?php
namespace NotionSync\Notion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notion API Client
 */
class NotionClient {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Centralized request handler for Notion API
	 */
	private function request( $method, $endpoint, $body = [] ) {
		$token = get_option( 'notion_sync_token' );
		if ( ! $token ) {
			return new \WP_Error( 'missing_token', __( 'Notion Integration Token is missing.', 'notion-sync-for-wp' ) );
		}

		$url = "https://api.notion.com/v1/" . ltrim( $endpoint, '/' );
		
		$args = [
			'method'  => $method,
			'headers' => [
				'Authorization'  => 'Bearer ' . $token,
				'Notion-Version' => '2022-06-28',
				'Content-Type'   => 'application/json',
			],
			'timeout' => 30,
		];

		if ( ! empty( $body ) ) {
			$args['body'] = json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			\NotionSync\Core\Logger::get_instance()->log( "API Request Error ({$endpoint}): " . $response->get_error_message(), 'error' );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$data        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code < 200 || $status_code >= 300 ) {
			$message = isset( $data['message'] ) ? $data['message'] : __( 'Unknown Notion API error.', 'notion-sync-for-wp' );
			$code    = isset( $data['code'] ) ? $data['code'] : 'api_error';
			
			\NotionSync\Core\Logger::get_instance()->log( "Notion API Error ({$status_code}): {$message}", 'error' );
			return new \WP_Error( $code, $message, $data );
		}

		return $data;
	}

	/**
	 * Get Database Schema (Metadata)
	 */
	public function get_database( $database_id ) {
		return $this->request( 'GET', "databases/{$database_id}" );
	}

	/**
	 * Get Page Metadata
	 */
	public function get_page( $page_id ) {
		return $this->request( 'GET', "pages/{$page_id}" );
	}

	/**
	 * Query Database (Fetch Pages)
	 */
	public function query_database( $database_id, $filter = [], $start_cursor = null ) {
		$body = [];
		if ( ! empty( $filter ) ) {
			$body['filter'] = $filter;
		}
		if ( $start_cursor ) {
			$body['start_cursor'] = $start_cursor;
		}

		return $this->request( 'POST', "databases/{$database_id}/query", $body );
	}

	/**
	 * Get ALL Block Children (Fetch Page Content)
	 * Automatically handles Notion's 100-block pagination limit.
	 */
	public function get_block_children( $block_id, $start_cursor = null ) {
		$all_results = [];
		$cursor      = $start_cursor;

		do {
			$endpoint = "blocks/{$block_id}/children";
			if ( $cursor ) {
				$endpoint .= "?start_cursor=" . urlencode( $cursor );
			}

			$response = $this->request( 'GET', $endpoint );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( ! empty( $response['results'] ) ) {
				$all_results = array_merge( $all_results, $response['results'] );
			}

			$cursor   = $response['next_cursor'] ?? null;
			$has_more = ! empty( $response['has_more'] );

		} while ( $has_more );

		return [
			'results'  => $all_results,
			'has_more' => false,
		];
	}

	/**
	 * Legacy support for connection testing
	 */
	public function test_connection() {
		$db_id = get_option( 'notion_sync_db_id' );
		if ( ! $db_id ) {
			return new \WP_Error( 'missing_db_id', __( 'Database ID is missing.', 'notion-sync-for-wp' ) );
		}

		$result = $this->get_database( $db_id );
		return is_wp_error( $result ) ? $result : true;
	}
}
