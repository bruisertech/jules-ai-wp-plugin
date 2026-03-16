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

// Require Homepage Module
if ( file_exists( plugin_dir_path( __FILE__ ) . 'jules-homepage.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'jules-homepage.php';
}



// Require the Admin Interface
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'jules-admin.php';
}

// Require the Image Fixer for WooCommerce Placeholders
require_once plugin_dir_path( __FILE__ ) . 'jules-image-fixer.php';


// Require the React Apps for Consola Bruiser
if ( file_exists( plugin_dir_path( __FILE__ ) . 'image-selector.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'image-selector.php';
}
if ( file_exists( plugin_dir_path( __FILE__ ) . 'price-tracker.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'price-tracker.php';
}

// Require the CSS Tweaks Injector
require_once plugin_dir_path( __FILE__ ) . 'jules-css-tweaks.php';

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

        return rest_ensure_response( array(
            'success' => true,
            'log'     => $log_content,
        ) );
    }
}

// Initialize the plugin
new Jules_Core();

// Disable the "has been added to your cart" native WooCommerce notices
add_filter( 'wc_add_to_cart_message_html', '__return_empty_string' );


// Prevent page reload on add to cart by enforcing AJAX
add_action('wp_footer', 'jules_ajax_add_to_cart_script', 99);
function jules_ajax_add_to_cart_script() {
    // Only apply if WooCommerce is active
    if (!function_exists('WC')) return;
    ?>
    <script>
    jQuery(document).ready(function($) {

        // Ensure standard AJAX is forced for links containing ?add-to-cart=
        $(document).on('click', 'a.add_to_cart_button:not(.ajax_add_to_cart), a[href*="add-to-cart="]', function(e) {
            var $btn = $(this);
            var href = $btn.attr('href') || '';

            // Allow variable/grouped product redirects, but if it has `add-to-cart=`, intercept!
            if (href.indexOf('add-to-cart=') === -1 && !$btn.attr('data-product_id')) {
                return true;
            }

            // Stop page refresh immediately!
            e.preventDefault();

            $btn.addClass('loading').css('opacity', '0.5');

            var product_id = $btn.attr('data-product_id');
            if (!product_id) {
                var match = href.match(/add-to-cart=([0-9]+)/);
                if (match) {
                    product_id = match[1];
                }
            }

            var quantity = $btn.attr('data-quantity') || 1;

            if (!product_id) {
                window.location.href = href;
                return;
            }

            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
                data: {
                    product_id: product_id,
                    quantity: quantity
                },
                success: function(response) {
                    if (response.error && response.product_url) {
                        window.location = response.product_url;
                        return;
                    }

                    $btn.removeClass('loading').css('opacity', '1');

                    // Trigger WooCommerce sidecart update event natively
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);
                },
                error: function() {
                    window.location.href = href;
                }
            });
        });

        // 2. Intercept single product page forms to prevent reload
        $('form.cart').on('submit', function(e) {
            var $form = $(this);

            if ($form.closest('.product').hasClass('product-type-external')) {
                return true;
            }

            if (typeof wc_add_to_cart_params === 'undefined') {
                return true;
            }

            e.preventDefault();

            var $btn = $form.find('button[type="submit"]');

            $btn.addClass('loading').css('opacity', '0.5');

            var formData = new FormData($form[0]);
            var product_id = $form.find('input[name="product_id"]').val() || $btn.val() || $form.find('input[name="add-to-cart"]').val();
            var quantity = $form.find('input[name="quantity"]').val() || 1;
            var variation_id = $form.find('input[name="variation_id"]').val() || 0;

            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
                data: {
                    product_id: product_id,
                    quantity: quantity,
                    variation_id: variation_id,
                },
                success: function(response) {
                    if (response.error && response.product_url) {
                        window.location = response.product_url;
                        return;
                    }

                    $btn.removeClass('loading').css('opacity', '1');
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);
                },
                error: function() {
                    $form.off('submit').submit();
                }
            });
        });
    });
    </script>
    <?php
}
