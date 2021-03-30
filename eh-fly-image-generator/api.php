<?php
/**
 * API Functions
 *
 * @package    Escape Hatch
 * @subpackage Escape Hatch
 * @since      2021 Mar
 */

/** API Actions */
add_action( 'rest_api_init', 'esch_register_images_route', 10, 0 );

/**
 * Registers Image Route
 */
function esch_register_images_route() {
    register_rest_route(
        'esc/v1',
        '/dynamic-images/(?P<id>\d+)/(?P<width>\d+)/(?P<height>\d+)(?P<crop>/([lrc]?[tcb]))?/',
        [
            'methods'             => 'GET',
            'callback'            => 'esch_api_get_images',
            'permission_callback' => 'esch_get_private_data_permissions_check',
        ]
    );
}

/**
 * Callback to Get Images
 *
 * @param WP_REST_Request $request Rest Request.
 *
 * @return WP_Error|WP_REST_Response
 */
function esch_api_get_images( $request ) {
    if ( ! function_exists( 'fly_get_attachment_image_src' ) ) {
        return new WP_Error( 'plugin_missing', esc_html__( 'Plugin required to resize images is missing', 'esch' ), array( 'status' => 400 ) );
    }

    $image_id = $request->get_param( 'id' );
    $width    = (bool) $request->get_param( 'width' ) ? $request->get_param( 'width' ) : null;
    $height   = (bool) $request->get_param( 'height' ) ? $request->get_param( 'height' ) : null;
    $crop     = (bool) $request->get_param( 'crop' )
        ? ltrim( $request->get_param( 'crop' ), '/' )
        : false;

    if ( ! wp_attachment_is_image( $image_id ) ) {
        return new WP_Error( 'non_attachment', esc_html__( 'Requested asset is not an image', 'esch' ), array( 'status' => 400 ) );
    }

    $crop_arg = Esch_Fly_Image_Generator::esch_get_fly_crop_arg_from_abbrev( $crop );

    $sources = [
        '1x' => fly_get_attachment_image_src( $image_id, [ $width, $height ], $crop_arg ),
        '2x' => fly_get_attachment_image_src(
            $image_id,
            [
                $width ? $width * 2 : $width,
                $height ? $height * 2 : $height,
            ],
            $crop_arg
        ),
    ];

    return new WP_REST_Response( $sources );
}


/**
 * Check to ensure user can edit posts.
 *
 * @return bool|WP_Error
 */
function esch_get_private_data_permissions_check() {
    // Restrict endpoint to only users who have the edit_posts capability.
    if ( ! current_user_can( 'edit_posts' ) ) {
        return new WP_Error( 'rest_forbidden', esc_html__( 'Cannot View This Data', 'esch' ), array( 'status' => 401 ) );
    }

    return true;
}
