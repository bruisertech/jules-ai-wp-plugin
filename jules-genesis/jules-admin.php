<?php
/**
 * Jules Admin Interface
 *
 * Registers the Jules Live admin page and loads the React UI.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Jules_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function register_admin_menu() {
        add_menu_page(
            '🔥 Consola de Inyección Bruiser Tech', // Page title
            '🔥 Consola Bruiser',                   // Menu title
            'manage_options',                       // Capability
            'jules-live',                           // Menu slug
            array( $this, 'render_admin_page' ),    // Callback
            'dashicons-superhero',                  // Icon
            3                                       // Position
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_jules-live' !== $hook ) {
            return;
        }

        // Load WordPress React dependencies
        wp_enqueue_script(
            'jules-admin-app',
            plugin_dir_url( __FILE__ ) . 'assets/app.js',
            array( 'wp-element', 'wp-components', 'wp-api-fetch' ),
            filemtime( plugin_dir_path( __FILE__ ) . 'assets/app.js' ),
            true
        );

        // Pass necessary variables to React
        wp_localize_script( 'jules-admin-app', 'julesGlobal', array(
            'rest_url' => esc_url_raw( rest_url() ),
            'nonce'    => wp_create_nonce( 'wp_rest' ), // Allows authentication for apiFetch out of the box
        ) );

        // Ensure standard WP Components styling is loaded
        wp_enqueue_style( 'wp-components' );

        // Add basic styles
        wp_add_inline_style( 'wp-components', '
            #jules-admin-root { padding: 20px 20px 20px 0; }
            .jules-undo-btn {
                background: #d4af37 !important;
                border-color: #b5952f !important;
                color: #1a1a1a !important;
                font-size: 16px !important;
                padding: 10px 20px !important;
                height: auto !important;
                font-weight: bold;
            }
            .jules-undo-btn:hover {
                background: #e6c555 !important;
            }
            .jules-log-container {
                background: #2b2b2b;
                color: #fdfdfc;
                padding: 15px;
                border-radius: 5px;
                font-family: monospace;
                margin-top: 20px;
                max-height: 400px;
                overflow-y: auto;
            }
        ' );
    }

    public function render_admin_page() {
        echo '<div id="jules-admin-root"></div>';
    }
}

new Jules_Admin();
