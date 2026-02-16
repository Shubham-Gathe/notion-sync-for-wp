<?php
namespace NotionSync\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PSR-4 Compliant Autoloader for Notion Sync
 */
class Autoloader {
	private static $instance = null;
	private $namespaces = [];

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		spl_autoload_register( [ $this, 'autoload' ] );
	}

	public function register_namespace( $namespace, $path ) {
		$this->namespaces[ ltrim( $namespace, '\\' ) ] = rtrim( $path, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
	}

	public function autoload( $class ) {
		foreach ( $this->namespaces as $namespace => $path ) {
			if ( 0 === strpos( $class, $namespace ) ) {
				$relative_class = substr( $class, strlen( $namespace ) );
				$file = $path . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

				if ( file_exists( $file ) ) {
					require $file;
					return true;
				}
			}
		}
		return false;
	}
}
