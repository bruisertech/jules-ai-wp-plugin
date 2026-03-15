<?php
/**
 * Jules Genesis - Interactive Price Tracker Tool
 * Scrapes search engines for competitor prices and compares them with the local store.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Jules_Price_Tracker {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
    }

    public function register_endpoints() {
        // Endpoint to scan competitor prices
        register_rest_route( 'jules/v1', '/compare-price', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'compare_price' ),
            'permission_callback' => array( $this, 'check_permissions' )
        ) );
    }

    public function check_permissions() {
        return current_user_can( 'manage_options' );
    }

    public function compare_price( WP_REST_Request $request ) {
        $product_id = intval($request->get_param('product_id'));
        if (!$product_id) {
            return new WP_Error( 'no_id', 'Product ID is required.', array( 'status' => 400 ) );
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error( 'no_product', 'Product not found.', array( 'status' => 404 ) );
        }

        $my_price = (float) $product->get_price();
        $product_name = $product->get_name();

        // Search Bing for the product price in Colombia
        $search_query = urlencode($product_name . ' perfume precio colombia comprar');
        $url_bing = 'https://www.bing.com/search?q=' . $search_query;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_bing);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $html_bing = curl_exec($ch);
        curl_close($ch);

        // Dividir el HTML por resultados de búsqueda orgánicos (li clase b_algo)
        // para extraer tanto el precio como la URL de la tienda
        $competitor_data = [];
        $competitor_sources = [];

        if (preg_match_all('/<li class="b_algo".*?<\/li>/s', $html_bing, $blocks)) {
            foreach ($blocks[0] as $block) {
                // Extraer URL del bloque
                $url = '';
                if (preg_match('/<a href="([^"]+)"/', $block, $url_match)) {
                    $url = $url_match[1];
                }

                // Match common Colombian price formats
                if (preg_match_all('/\$?\s*([1-9]\d{1,2})[.,](\d{3})\s*(COP)?/i', $block, $matches)) {
                    for ($i = 0; $i < count($matches[0]); $i++) {
                        $price_value = (float) ($matches[1][$i] . $matches[2][$i]);

                        // Ignorar el falso positivo de 82.015 COP y precios muy bajos (decants)
                        if ($price_value > 85000 && $price_value != 82015 && $price_value <= 1500000) {
                            $competitor_data[] = [
                                'price' => $price_value,
                                'url'   => $url
                            ];
                            $competitor_sources[] = "Encontrado " . $matches[0][$i] . " en: " . substr($url, 0, 40) . "...";
                        }
                    }
                }
            }
        }

        if (empty($competitor_data)) {
            return new WP_REST_Response( array(
                'success' => true,
                'my_price' => $my_price,
                'lowest_competitor' => null,
                'lowest_url' => null,
                'average_competitor' => null,
                'is_lowest' => true,
                'message' => 'No se encontraron competidores claros para este producto en internet.',
                'sources' => []
            ), 200 );
        }

        // Sort data by price to find the lowest
        usort($competitor_data, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });

        $lowest_competitor = $competitor_data[0]['price'];
        $lowest_url = $competitor_data[0]['url'];

        // Calculate average
        $sum = 0;
        foreach($competitor_data as $data) {
            $sum += $data['price'];
        }
        $average_competitor = $sum / count($competitor_data);

        $is_lowest = $my_price <= $lowest_competitor;

        // Limit sources to top 3 unique
        $competitor_sources = array_slice(array_unique($competitor_sources), 0, 3);

        return new WP_REST_Response( array(
            'success' => true,
            'my_price' => $my_price,
            'lowest_competitor' => $lowest_competitor,
            'lowest_url' => $lowest_url,
            'average_competitor' => round($average_competitor),
            'is_lowest' => $is_lowest,
            'message' => $is_lowest ? '¡Felicidades! Tienes el precio más bajo.' : 'Atención: Tu precio está por encima de la competencia.',
            'sources' => $competitor_sources
        ), 200 );
    }
}

new Jules_Price_Tracker();
