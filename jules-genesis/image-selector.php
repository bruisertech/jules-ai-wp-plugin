<?php
/**
 * Jules Genesis - Interactive Image Selector Tool
 * Allows the store owner to search and pick from 5 image candidates.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Jules_Image_Selector {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
    }

    public function register_endpoints() {
        // Endpoint to list products
        register_rest_route( 'jules/v1', '/products-no-image', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_products' ),
            'permission_callback' => array( $this, 'check_permissions' )
        ) );

        // Endpoint to fetch candidates
        register_rest_route( 'jules/v1', '/fetch-candidates', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'fetch_candidates' ),
            'permission_callback' => array( $this, 'check_permissions' )
        ) );

        // Endpoint to assign image
        register_rest_route( 'jules/v1', '/assign-image', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'assign_image' ),
            'permission_callback' => array( $this, 'check_permissions' )
        ) );
    }

    public function check_permissions() {
        return current_user_can( 'manage_options' );
    }

    public function get_products( WP_REST_Request $request ) {
        // We will fetch all products to let the user update them if they don't like current ones.
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 100, // Should be enough for 48 products
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC'
        );

        $query = new WP_Query($args);
        $products = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $thumb_id = get_post_thumbnail_id($id);
                $thumb_url = $thumb_id ? wp_get_attachment_url($thumb_id) : null;

                $products[] = [
                    'id' => $id,
                    'name' => get_the_title(),
                    'current_image' => $thumb_url
                ];
            }
        }
        wp_reset_postdata();

        return new WP_REST_Response( array(
            'success' => true,
            'products' => $products
        ), 200 );
    }

    public function fetch_candidates( WP_REST_Request $request ) {
        $query = $request->get_param('q');
        if (empty($query)) {
            return new WP_Error( 'no_query', 'Query is required.', array( 'status' => 400 ) );
        }

        // Buscar imágenes del producto en alta calidad pero sin restringir a fondo transparente
        $search_query = urlencode($query . ' perfume bottle white background -site:pinterest.com');
        $url_bing = 'https://www.bing.com/images/search?q=' . $search_query;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_bing);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $html_bing = curl_exec($ch);
        curl_close($ch);

        $candidates = [];
        // Extraer hasta 9 imágenes de alta resolución
        if (preg_match_all('/murl&quot;:&quot;(https:\/\/[^&"]+)&quot;/', $html_bing, $matches)) {
            $unique_urls = array_unique($matches[1]);
            // Tomamos las 9 primeras URLs válidas
            $candidates = array_slice(array_values($unique_urls), 0, 9);
        }

        return new WP_REST_Response( array(
            'success' => true,
            'candidates' => $candidates
        ), 200 );
    }

    public function assign_image( WP_REST_Request $request ) {
        $product_id = intval($request->get_param('product_id'));
        $image_url = $request->get_param('image_url');
        $product_name = get_the_title($product_id);

        if (!$product_id || empty($image_url)) {
            return new WP_Error( 'invalid_data', 'Product ID and Image URL are required.', array( 'status' => 400 ) );
        }

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            return new WP_Error( 'download_failed', 'Failed to download image: ' . $tmp->get_error_message(), array( 'status' => 500 ) );
        }

        // Always try to save as png if the URL has no clear extension but claims to be transparent
        $ext = preg_match('/\.png/i', $image_url) ? '.png' : '.jpg';

        $file_array = array(
            'name'     => sanitize_file_name($product_name) . '-selected' . $ext,
            'tmp_name' => $tmp
        );

        $id = media_handle_sideload($file_array, $product_id);

        if (is_wp_error($id)) {
            @unlink($tmp);
            return new WP_Error( 'sideload_failed', 'Failed to attach image: ' . $id->get_error_message(), array( 'status' => 500 ) );
        }

        // Set as featured image
        set_post_thumbnail($product_id, $id);

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Image assigned successfully.',
            'new_image_url' => wp_get_attachment_url($id)
        ), 200 );
    }
}

new Jules_Image_Selector();
