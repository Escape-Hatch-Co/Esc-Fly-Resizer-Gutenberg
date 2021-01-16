<?php
/**
 * Plugin Name: Escape Hatch - Fly Image Generator
 * Plugin URI: https://escapehatch.co
 * Description: Generates Fly Images when created in Gutenberg.
 * Author: Escape Hatch
 * Author URI: https://escapehatch.co
 * Version: 1.0.0
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package CGB
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Escape Hatch Fly Generator
 *
 * Class Esc_Fly_Image_Generator
 */
class Esc_Fly_Image_Generator {

	/**
	 * Initialization of Plugin
	 */
	public function init() {
		add_filter( 'wp', array( $this, 'render_response' ) );
		add_filter( 'init', array( $this, 'rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
	}

	/**
	 * Outputs Image
	 */
	public function render_response() {
		$generate_image = get_query_var( 'esc-fly-image-generate' );

		//If Generate Image Exists, generate and return image.
		if ( $generate_image ) {
			$this->generate_image();

			//The above function will exit, so this will only be called if the image could not be generated.
			$this->generate_404();
		}
	}

	/**
	 * Sets Up Query Vars
	 *
	 * @param array $vars Allowed Variables.
	 *
	 * @return array
	 */
	public function query_vars( $vars ): array {
		array_push( $vars, 'esc-fly-image-generate' );
		return $vars;
	}

	/**
	 * Adds & Flushes Rewrite Rules.
	 */
	public function flush_rules() {
		$this->rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Adds Rewrite Rules.
	 */
	public function rewrite_rules() {
		add_rewrite_rule( 'esc-fly-image/generate$', 'index.php?esc-fly-image-generate=1', 'top' );
	}

	/**
	 * Generates & Returns Image from Fly based upon URL
	 */
	public function generate_image() {
		preg_match( '/wp-content\/uploads\/fly-images\/(\d+)\/.+-(\d+)x(\d+)(-c)?/', $_SERVER['REQUEST_URI'] ?? '', $image_args );
		if ( ! $image_args ) {
			$this->generate_404();
			return;
		}

		$id     = $image_args[1];
		$width  = $image_args[2];
		$height = $image_args[3];
		$crop   = (bool) ( $image_args[4] ?? false );

		/** Generate Image */
		$source = fly_get_attachment_image_src( $id, [ $width, $height ], $crop );

		$uri = $source['src'] ?? '';
		if ( ! $uri ) {
			$this->generate_404();
			return;
		}

		/**
		 * Original File Path
		 *
		 * @var string $original Original Image.
		 */
		$original = str_replace( trim( home_url(), '/' ), ABSPATH, $uri );

		if ( ! file_exists( $original ) ) {
			$this->generate_404();
			return;
		}

		/**
		 * Returns File
		 */
		$resource  = fopen( $original, 'rb' );
		$mime_type = mime_content_type( $resource );

		header( sprintf( 'Content-Type: %s', $mime_type ) );
		header( sprintf( 'Content-Length: %d', filesize( $original ) ) );
		fpassthru( $resource );
		exit( 0 );
	}

	/**
	 * Sets up Template to return 404.
	 */
	public function generate_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}

add_action( 'plugins_loaded', [ new Esc_Fly_Image_Generator(), 'init' ] );

/** Activation function */
register_activation_hook( __FILE__, [ new Esc_Fly_Image_Generator(), 'flush_rules' ] );
