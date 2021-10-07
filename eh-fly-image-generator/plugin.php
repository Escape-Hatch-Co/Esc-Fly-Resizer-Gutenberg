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

require_once __DIR__ . '/api.php';

/**
 * Escape Hatch Fly Generator
 *
 * Class Esch_Fly_Image_Generator
 */
class Esch_Fly_Image_Generator {

    /**
     * Initialization of Plugin
     */
    public function init() {
        add_filter( 'wp', [ $this, 'render_response' ] );
        add_filter( 'init', [ $this, 'rewrite_rules' ] );
        add_filter( 'query_vars', [ $this, 'query_vars' ] );
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
        add_rewrite_rule( 'esc-fly-image/generate$',
                          'index.php?esc-fly-image-generate=1', 'top' );
    }

    /**
     * Generates & Returns Image from Fly based upon URL
     */
    public function generate_image() {
        preg_match( '/wp-content\/uploads\/(sites\/\d+\/)?fly-images\/(\d+)\/.+-(\d+)x(\d+)(-[lrc]?[tcb])?/',
                    $_SERVER['REQUEST_URI'] ?? '', $image_args );
        if ( ! $image_args ) {
            $this->generate_404();

            return;
        }

        $id     = (int) $image_args[2];
        $width  = (int) $image_args[3];
        $height = (int) $image_args[4];
        $crop   = $image_args[5] ?? '';

        $crop = ltrim( $crop, '-' );

        $crop_arg = self::esch_get_fly_crop_arg_from_abbrev( $crop );

        /** Generate Image */
        $source = fly_get_attachment_image_src( $id, [ $width, $height ],
                                                $crop_arg );

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

        /**
         * Don't Cache WebPs for this first go as they are not true WebPs.
         */
        if ( preg_match( '/\.webp$/', $_SERVER['REQUEST_URI'] ) ) {
            $now = new DateTime();
            header( 'Cache-Control: no-cache, max-age=0' );
            header( sprintf( 'Expires: %s', $now->format( 'r' ) ) );
        }

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

    /**
     * Gets Fly Crop Argument from Abbreviation
     *
     * @param string|bool $crop Crop Abbreviation (e.g. ct, cb, etc).
     *
     * @return bool|string[]
     */
    public static function esch_get_fly_crop_arg_from_abbrev( $crop ) {
        switch ( $crop ) {
            case 'c':
                $crop_arg = TRUE;
                break;
            case 'ct':
                $crop_arg = [ 'center', 'top' ];
                break;
            case 'cb':
                $crop_arg = [ 'center', 'bottom' ];
                break;
            case 'cc':
                $crop_arg = [ 'center', 'center' ];
                break;
            case 'lt':
                $crop_arg = [ 'left', 'top' ];
                break;
            case 'lc':
                $crop_arg = [ 'left', 'center' ];
                break;
            case 'lb':
                $crop_arg = [ 'left', 'bottom' ];
                break;
            case 'rt':
                $crop_arg = [ 'right', 'top' ];
                break;
            case 'rc':
                $crop_arg = [ 'right', 'center' ];
                break;
            case 'rb':
                $crop_arg = [ 'right', 'bottom' ];
                break;
            default:
                $crop_arg = FALSE;
        }

        return $crop_arg;
    }
}

add_action( 'plugins_loaded', [ new Esch_Fly_Image_Generator(), 'init' ] );

/** Activation function */
register_activation_hook( __FILE__,
                          [ new Esch_Fly_Image_Generator(), 'flush_rules' ] );
