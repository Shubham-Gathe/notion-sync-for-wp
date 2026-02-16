<?php
namespace NotionSync\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Media Handling Service
 */
class MediaService {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
	}

	/**
	 * Sideload an image from a URL into the WordPress Media Library
	 *
	 * @param string $url Notion image URL
	 * @param int $post_id Post ID to attach the image to
	 * @param string $desc Image description
	 * @return int|WP_Error attachment ID on success
	 */
	public function sideload_image( $url, $post_id = 0, $desc = '' ) {
		if ( empty( $url ) ) {
			return new \WP_Error( 'empty_url', __( 'Image URL is empty.', 'notion-sync-for-wp' ) );
		}

		// Check if the image already exists in our library to avoid duplicates
		// Notion URLs are temporary (S3 signed), so we can't reliably check the URL itself.
		// However, we can store the original Notion URL or a hash in meta for deduplication.
		
		$url_path = parse_url( $url, PHP_URL_PATH );
		$filename = basename( $url_path );
		
		// If it's a Notion S3 URL, the filename might be messy or missing extension
		if ( strpos( $filename, '.' ) === false ) {
			$filename .= '.jpg'; // Fallback
		}

		// Use native WP function to download and attach
		$attachment_id = media_sideload_image( $url, $post_id, $desc, 'id' );

		if ( is_wp_error( $attachment_id ) ) {
			\NotionSync\Core\Logger::get_instance()->log( "Media Sideload Failed for {$url}: " . $attachment_id->get_error_message(), 'error' );
		} else {
			\NotionSync\Core\Logger::get_instance()->log( "Media Sideload Successful: Attachment ID {$attachment_id}", 'info' );
		}

		return $attachment_id;
	}
}
