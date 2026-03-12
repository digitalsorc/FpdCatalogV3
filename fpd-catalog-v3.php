<?php
/**
 * Plugin Name: FPD Catalog V3
 * Description: Elementor widget that renders a visual product catalog grid compositing FPD base products and designs.
 * Version: 3.0.0
 * Author: DigitalSorc
 * Text Domain: fpd-catalog-v3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'FPD_CATALOG_V3_VERSION', '3.0.0' );
define( 'FPD_CATALOG_V3_DIR', plugin_dir_path( __FILE__ ) );
define( 'FPD_CATALOG_V3_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Plugin Class
 */
final class FPD_Catalog_V3_Plugin {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	public function init() {
		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] );
			return;
		}

		// Check for minimum Elementor version
		if ( ! version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_minimum_elementor_version' ] );
			return;
		}

		// Check for PHP version
		if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php_version' ] );
			return;
		}

		// Include files
		require_once FPD_CATALOG_V3_DIR . 'includes/class-fpd-data-helper.php';
		require_once FPD_CATALOG_V3_DIR . 'includes/class-fpd-rest-api.php';

		// Register REST API
		$rest_api = new FPD_Catalog_V3_REST_API();
		$rest_api->init();

		// Register Widget
		add_action( 'elementor/widgets/register', [ $this, 'init_widgets' ] );

		// Register Scripts and Styles
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'widget_styles' ] );
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'widget_scripts' ] );
        
        // Register Dynamic Tag
        add_action( 'elementor/dynamic_tags/register', [ $this, 'register_dynamic_tags' ] );
	}

	public function init_widgets( $widgets_manager ) {
		require_once FPD_CATALOG_V3_DIR . 'includes/class-fpd-widget.php';
		$widgets_manager->register( new FPD_Catalog_V3_Widget() );
	}

	public function widget_styles() {
		wp_register_style( 'fpd-catalog-v3-css', FPD_CATALOG_V3_URL . 'assets/css/fpd-catalog-v3.css', [], FPD_CATALOG_V3_VERSION );
	}

	public function widget_scripts() {
		wp_register_script( 'fpd-catalog-v3-render-js', FPD_CATALOG_V3_URL . 'assets/js/fpd-catalog-render.js', [ 'jquery' ], FPD_CATALOG_V3_VERSION, true );
		wp_register_script( 'fpd-catalog-v3-filter-js', FPD_CATALOG_V3_URL . 'assets/js/fpd-catalog-filter.js', [], FPD_CATALOG_V3_VERSION, true );
        
        wp_localize_script( 'fpd-catalog-v3-render-js', 'fpdCatalogV3Data', [
            'restUrl' => esc_url_raw( rest_url( 'fpd-catalog-v3/v1/items' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        ]);
	}
    
    public function register_dynamic_tags( $dynamic_tags ) {
        require_once FPD_CATALOG_V3_DIR . 'includes/class-fpd-dynamic-tag.php';
        $dynamic_tags->register( new FPD_Current_Filter_Label_Tag_V3() );
    }

	public function admin_notice_missing_main_plugin() {
		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'fpd-catalog-v3' ),
			'<strong>' . esc_html__( 'FPD Catalog V3', 'fpd-catalog-v3' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'fpd-catalog-v3' ) . '</strong>'
		);
		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	public function admin_notice_minimum_elementor_version() {
		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'fpd-catalog-v3' ),
			'<strong>' . esc_html__( 'FPD Catalog V3', 'fpd-catalog-v3' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'fpd-catalog-v3' ) . '</strong>',
			'3.5.0'
		);
		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	public function admin_notice_minimum_php_version() {
		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
		$message = sprintf(
			/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'fpd-catalog-v3' ),
			'<strong>' . esc_html__( 'FPD Catalog V3', 'fpd-catalog-v3' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'fpd-catalog-v3' ) . '</strong>',
			'7.0'
		);
		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}
}

FPD_Catalog_V3_Plugin::instance();
