<?php
/**
 * Plugin Name: Jules Genesis
 * Description: Autonomous Lead Developer Plugin for Bruiser Tech. Allows secure remote file modification and self-evolution.
 * Version: 1.0.0
 * Author: Jules
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Require the Undo Engine
require_once plugin_dir_path( __FILE__ ) . 'jules-undo-engine.php';

// Require the Admin Interface
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'jules-admin.php';
}

// Require the Image Fixer for WooCommerce Placeholders
require_once plugin_dir_path( __FILE__ ) . 'jules-image-fixer.php';

// Require the CSS Tweaks Injector
require_once plugin_dir_path( __FILE__ ) . 'jules-css-tweaks.php';

// Require the Checkout Manager
require_once plugin_dir_path( __FILE__ ) . 'jules-checkout-manager.php';

// Require the Import Tool
require_once plugin_dir_path( __FILE__ ) . 'import-tool.php';

// Require the AI Background Processor
require_once plugin_dir_path( __FILE__ ) . 'ai-processor.php';

// Require the Interactive Image Selector Backend
require_once plugin_dir_path( __FILE__ ) . 'image-selector.php';

// Require the Price Tracker Backend
require_once plugin_dir_path( __FILE__ ) . 'price-tracker.php';

class Jules_Core {

    private $undo_engine;

    public function __construct() {
        $this->undo_engine = new Jules_Undo_Engine();

        // Hook into REST API initialization
        add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
    }

    /**
     * Register REST API endpoints for remote operations.
     */
    public function register_endpoints() {
        // Write file endpoint
        register_rest_route( 'jules/v1', '/write', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_write_request' ),
            'permission_callback' => array( $this, 'check_permissions' ),
            'args'                => array(
                'filepath' => array(
                    'required' => true,
                    'type'     => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'content' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
            ),
        ) );

        // Undo endpoint
        register_rest_route( 'jules/v1', '/undo', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_undo_request' ),
            'permission_callback' => array( $this, 'check_permissions' ),
        ) );

        // Activity Log endpoint
        register_rest_route( 'jules/v1', '/log', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'handle_log_request' ),
            'permission_callback' => array( $this, 'check_permissions' ),
        ) );
    }

    /**
     * Permission callback for REST API endpoints.
     * Enforces authentication (e.g., Application Passwords).
     */
    public function check_permissions() {
        // WordPress REST API automatically handles authentication via Application Passwords or Cookies.
        // If a user is not logged in or hasn't provided valid credentials, current_user_can will fail.
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permissions to execute this action.', 'jules-genesis' ), array( 'status' => 401 ) );
        }

        return true;
    }

    /**
     * Handle the file write request.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_write_request( $request ) {
        $filepath = $request->get_param( 'filepath' );
        $content  = $request->get_param( 'content' );

        // Resolve absolute path (Assuming paths provided are absolute or relative to ABSPATH)
        if ( strpos( $filepath, ABSPATH ) !== 0 ) {
            // If it's not an absolute path, assume it's relative to ABSPATH
            $filepath = ABSPATH . ltrim( $filepath, '/' );
        }

        // Ensure $this->undo_engine is available via global context if necessary, but it should be available as property

        // Make sure the directory exists
        $dirname = dirname( $filepath );
        if ( ! file_exists( $dirname ) ) {
            wp_mkdir_p( $dirname );
        }

        // Take a snapshot (handles both existing and brand new files)
        $this->undo_engine->snapshot( $filepath );

        // Write the new content to the file
        $result = file_put_contents( $filepath, $content );

        if ( $result !== false ) {
            $this->undo_engine->log( "[File Written] Successfully wrote to {$filepath}" );
            return rest_ensure_response( array(
                'success' => true,
                'message' => "File {$filepath} written successfully.",
            ) );
        } else {
            $this->undo_engine->log( "[Write Error] Failed to write to {$filepath}" );
            return new WP_Error( 'write_failed', esc_html__( 'Failed to write to file.', 'jules-genesis' ), array( 'status' => 500 ) );
        }
    }

    /**
     * Handle the undo request from the React interface or API.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_undo_request( $request ) {
        $result = $this->undo_engine->undo();

        if ( $result['success'] ) {
            return rest_ensure_response( $result );
        } else {
            return new WP_Error( 'undo_failed', $result['message'], array( 'status' => 400 ) );
        }
    }

    /**
     * Handle the request to fetch the activity log.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_log_request( $request ) {
        $log_file = plugin_dir_path( __FILE__ ) . 'activity.log';
        $log_content = '';

        $ascii_art = "
                                                 )
   (                           *   )      (   ( /(
 ( )\ (     (  (       (  (  ` )  /((     )\  )\())
 )((_))(   ))\ )\ (   ))\ )(  ( )(_))\  (((_)((_)\
((_)_(()\ /((_|(_))\ /((_|()\(_(_()|(_) )\___ _((_)
 | _ )((_|_))( (_|(_|_))  ((_)_   _| __((/ __| || |
 | _ \ '_| || || (_-< -_)| '_| | | | _| | (__| __ |
 |___/_|  \_,_||_/__|___||_|   |_| |___| \___|_||_|

";
        $signature = "\n> Desarrollado por Daniel Contreras Herrera.\n> Sistema de inyección directa a WordPress avanzado, registrado y documentado.\n\n========================================================================\n\n";

        if ( file_exists( $log_file ) ) {
            // Read the last 50 lines to keep the response manageable
            $lines = file( $log_file );
            if ( is_array( $lines ) ) {
                $last_lines = array_slice( $lines, -50 );
                $log_content = implode( '', $last_lines );
            }
        } else {
            $log_content = 'Log file not found or empty.';
        }

        $final_output = $ascii_art . $signature . $log_content;

        return rest_ensure_response( array(
            'success' => true,
            'log'     => $final_output,
        ) );
    }
}

// Initialize the plugin
new Jules_Core();
