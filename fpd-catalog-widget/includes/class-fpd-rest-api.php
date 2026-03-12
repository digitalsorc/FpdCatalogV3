<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FPD_Catalog_REST_API {

    public function init() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'fpd-catalog/v1', '/items', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_items' ],
            'permission_callback' => '__return_true', // Publicly accessible for catalog
            'args'                => [
                'category' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'base_product' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'orderby' => [
                    'type' => 'string',
                    'default' => 'date',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'order' => [
                    'type' => 'string',
                    'default' => 'DESC',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'page' => [
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ],
                'per_page' => [
                    'type' => 'integer',
                    'default' => 24,
                    'sanitize_callback' => 'absint',
                ],
                'cache_ttl' => [
                    'type' => 'integer',
                    'default' => 60,
                    'sanitize_callback' => 'absint',
                ]
            ],
        ] );
    }

    public function get_items( WP_REST_Request $request ) {
        $args = [
            'category'     => $request->get_param( 'category' ) ? explode(',', $request->get_param( 'category' )) : [],
            'base_product' => $request->get_param( 'base_product' ) ? explode(',', $request->get_param( 'base_product' )) : [],
            'orderby'      => $request->get_param( 'orderby' ),
            'order'        => $request->get_param( 'order' ),
            'page'         => $request->get_param( 'page' ),
            'per_page'     => $request->get_param( 'per_page' ),
        ];

        // Apply WordPress filter hook
        $args = apply_filters( 'fpd_catalog_query_args', $args );

        $cache_ttl = $request->get_param( 'cache_ttl' ) * MINUTE_IN_SECONDS;
        $cache_key = 'fpd_catalog_' . md5( wp_json_encode( $args ) );

        $items = get_transient( $cache_key );

        if ( false === $items ) {
            $items = FPD_Data_Helper::query_items( $args );
            if ( $cache_ttl > 0 ) {
                set_transient( $cache_key, $items, $cache_ttl );
            }
        }

        $response = rest_ensure_response( $items );
        $response->header( 'Cache-Control', 'max-age=3600, public' );

        return $response;
    }
}
