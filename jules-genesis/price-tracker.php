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

        // Extracting prices from the HTML snippets.
        // We look for patterns like $190.000, $ 200,000, 180.000 COP, etc.
        $competitor_prices = [];
        $competitor_sources = [];

        // Match common Colombian price formats in search results (e.g., $150.000 or $ 150.000 or 150.000 COP)
        // This regex looks for a dollar sign, optional space, 2-3 digits, a dot or comma, and 3 digits.
        if (preg_match_all('/\$?\s*([1-9]\d{1,2})[.,](\d{3})\s*(COP)?/i', $html_bing, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                // Combine the thousands and hundreds parts into a single integer
                $price_value = (float) ($matches[1][$i] . $matches[2][$i]);

                // Filter out unrealistic prices (e.g., decants for 20.000 or extremely high fake numbers)
                // Let's assume a full bottle perfume is between 80,000 and 1,500,000 COP
                if ($price_value >= 80000 && $price_value <= 1500000) {
                    $competitor_prices[] = $price_value;
                    $competitor_sources[] = "Resultado de búsqueda: " . $matches[0][$i];
                }
            }
        }

        $competitor_prices = array_unique($competitor_prices);
        sort($competitor_prices);

        if (empty($competitor_prices)) {
            return new WP_REST_Response( array(
                'success' => true,
                'my_price' => $my_price,
                'lowest_competitor' => null,
                'average_competitor' => null,
                'is_lowest' => true,
                'message' => 'No se encontraron competidores claros para este producto en internet.',
                'sources' => []
            ), 200 );
        }

        $lowest_competitor = $competitor_prices[0];
        $average_competitor = array_sum($competitor_prices) / count($competitor_prices);
        $is_lowest = $my_price <= $lowest_competitor;

        // Limit sources to top 3 unique
        $competitor_sources = array_slice(array_unique($competitor_sources), 0, 3);

        return new WP_REST_Response( array(
            'success' => true,
            'my_price' => $my_price,
            'lowest_competitor' => $lowest_competitor,
            'average_competitor' => round($average_competitor),
            'is_lowest' => $is_lowest,
            'message' => $is_lowest ? '¡Felicidades! Tienes el precio más bajo.' : 'Atención: Tu precio está por encima de la competencia.',
            'sources' => $competitor_sources
        ), 200 );
    }
}

new Jules_Price_Tracker();
