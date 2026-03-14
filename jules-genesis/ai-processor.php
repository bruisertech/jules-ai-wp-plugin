<?php
/**
 * Jules Genesis - AI Studio Processor (Photoroom Integration)
 *
 * Takes WooCommerce product images, removes the background, and generates
 * a high-end AI background with consistent framing and branding prompts.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Jules_AI_Processor {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_ai_endpoint' ) );
    }

    public function register_ai_endpoint() {
        register_rest_route( 'jules/v1', '/process-ai-images', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'process_images' ),
            'permission_callback' => array( $this, 'check_permissions' )
        ) );
    }

    public function check_permissions() {
        return current_user_can( 'manage_options' );
    }

    public function process_images( WP_REST_Request $request ) {
        // Obtenemos los productos para procesar (por lotes para no morir en timeout)
        $limit = $request->get_param('limit') ? intval($request->get_param('limit')) : 5;
        $offset = $request->get_param('offset') ? intval($request->get_param('offset')) : 0;

        // La API Key debe obtenerse de una opción segura de WordPress, NO debe estar hardcodeada.
        // Si el usuario acaba de iniciar el plugin, puede estar guardada en la base de datos
        // mediante update_option('photoroom_api_key', 'YOUR_KEY_HERE');
        $api_key = get_option('photoroom_api_key', '');
        if (empty($api_key)) {
            return new WP_REST_Response( array(
                'success' => false,
                'message' => 'Photoroom API key is missing. Please configure it in the database (Option: photoroom_api_key).',
                'log'     => ["Error: Missing API Key."]
            ), 500 );
        }

        $prompt = "Una base de marmolados elegante, atrás una placa dorada con la inscripción lhparfum, iluminación dramática de estudio de lujo, encuadre central perfecto.";

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'post_status'    => 'publish'
        );

        $query = new WP_Query($args);
        $log = [];

        if ($query->have_posts()) {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            while ($query->have_posts()) {
                $query->the_post();
                $product_id = get_the_ID();
                $image_id = get_post_thumbnail_id($product_id);

                if (!$image_id) {
                    $log[] = "Product {$product_id} has no image. Skipping.";
                    continue;
                }

                $image_url = wp_get_attachment_url($image_id);
                $local_path = get_attached_file($image_id);

                if (!file_exists($local_path)) {
                    $log[] = "File not found locally for Product {$product_id}. Skipping.";
                    continue;
                }

                // Generar nueva imagen con Photoroom
                $log[] = "Sending {$image_url} to Photoroom API...";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://image-api.photoroom.com/v2/edit');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);

                $postFields = [
                    'imageFile' => new CURLFile($local_path),
                    'background.prompt' => $prompt,
                    'padding' => '0.1', // Deja espacio para el encuadre central
                    'outputSize' => '1000x1000'
                ];

                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'x-api-key: ' . $api_key
                ]);

                $response = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpcode == 200 && $response) {
                    // Save temporary file
                    $temp_file = wp_tempnam();
                    file_put_contents($temp_file, $response);

                    // Sideload to WordPress
                    $file_array = array(
                        'name'     => sanitize_file_name(get_the_title() . '-ai-studio') . '.jpg',
                        'tmp_name' => $temp_file
                    );

                    $new_image_id = media_handle_sideload($file_array, $product_id);

                    if (!is_wp_error($new_image_id)) {
                        set_post_thumbnail($product_id, $new_image_id);
                        $log[] = "Success: Updated image for Product {$product_id}.";
                    } else {
                        @unlink($temp_file);
                        $log[] = "Failed to sideload Photoroom result for Product {$product_id}: " . $new_image_id->get_error_message();
                    }
                } else {
                    $log[] = "Photoroom API Error HTTP {$httpcode} for Product {$product_id}.";
                    $log[] = "Response: " . substr($response, 0, 200); // Log first 200 chars of error
                }
            }
        } else {
            $log[] = "No products found to process at offset {$offset}.";
        }

        wp_reset_postdata();

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Processed batch.',
            'log'     => $log
        ), 200 );
    }
}

new Jules_AI_Processor();
