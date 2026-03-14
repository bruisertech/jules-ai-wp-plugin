<?php
/**
 * Jules Genesis - Secure One-Off Import Tool
 *
 * This file is kept in the repository for record-keeping and potential future use.
 * It is secured by a strict authentication check.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Jules_Import_Tool {

    public function __construct() {
        // Register a secure REST endpoint for running imports
        add_action( 'rest_api_init', array( $this, 'register_import_endpoint' ) );
    }

    public function register_import_endpoint() {
        register_rest_route( 'jules/v1', '/import-catalog', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'process_import' ),
            'permission_callback' => array( $this, 'check_permissions' )
        ) );
    }

    public function check_permissions( WP_REST_Request $request ) {
        // Only allow administrators or users with manage_options to run this destructive action
        return current_user_can( 'manage_options' );
    }

    public function process_import( WP_REST_Request $request ) {
        if ( ! function_exists( 'wc_get_products' ) ) {
            return new WP_Error( 'no_woo', 'WooCommerce is not active.', array( 'status' => 500 ) );
        }

        $log = [];
        $log[] = "Iniciando proceso de actualización del catálogo...";

        // 1. Enviar productos actuales a la papelera (Trash)
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'pending', 'draft')
        );

        $products_query = new WP_Query($args);
        if ($products_query->have_posts()) {
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $post_id = get_the_ID();
                wp_trash_post($post_id);
                $log[] = "Producto movido a papelera: ID " . $post_id . " - " . get_the_title();
            }
        }
        wp_reset_postdata();

        // 2. Leer los datos y crear los nuevos productos
        $json_data = file_get_contents( plugin_dir_path( __FILE__ ) . 'perfumes_data.json' );
        $perfumes = json_decode($json_data, true);

        if (!$perfumes) {
            return new WP_Error( 'no_data', 'Error leyendo perfumes_data.json', array( 'status' => 500 ) );
        }

        foreach ($perfumes as $p) {
            $product = new WC_Product_Simple();

            // Título y Descripción corta
            $product->set_name($p['name']);
            $product->set_short_description($p['desc']);

            // Precio
            $product->set_regular_price($p['price']);
            $product->set_price($p['price']);

            // Visibilidad y Estado
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');

            // Descargar y adjuntar imagen si está disponible
            if (!empty($p['image_url'])) {
                $image_id = $this->sideload_image($p['image_url'], $p['name']);
                if ($image_id) {
                    $product->set_image_id($image_id);
                }
            }

            // Guardar para obtener el ID
            $product_id = $product->save();

            if ($product_id) {
                $log[] = "Creado nuevo producto: ID " . $product_id . " - " . $p['name'] . " ($" . number_format($p['price'], 0, ',', '.') . ")";

                // Asignar taxonomías personalizadas
                $taxonomies_to_set = [
                    'lh_rareza' => strtolower($p['rareza']),
                    'lh_marca'  => $p['marca'],
                    'lh_genero' => $p['genero'],
                    'lh_aroma'  => $p['aroma']
                ];

                foreach ($taxonomies_to_set as $tax_slug => $term_name) {
                    $term_id = $this->get_or_create_term($term_name, $tax_slug);
                    if ($term_id) {
                        wp_set_object_terms($product_id, [(int)$term_id], $tax_slug, true);
                    }
                }

            } else {
                $log[] = "Error al crear: " . $p['name'];
            }
        }

        $log[] = "Proceso de actualización de catálogo finalizado exitosamente.";

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Catalog imported successfully.',
            'log'     => $log
        ), 200 );
    }

    private function get_or_create_term($term_name, $taxonomy) {
        if (empty($term_name)) return false;

        $term = get_term_by('name', $term_name, $taxonomy);
        if ($term) {
            return $term->term_id;
        }

        $new_term = wp_insert_term($term_name, $taxonomy);
        if (!is_wp_error($new_term)) {
            return $new_term['term_id'];
        }
        return false;
    }

    private function sideload_image($url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            return false;
        }

        $file_array = array(
            'name'     => sanitize_file_name($title) . '.jpg',
            'tmp_name' => $tmp
        );

        $id = media_handle_sideload($file_array, 0);

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }

        return $id;
    }
}

new Jules_Import_Tool();
