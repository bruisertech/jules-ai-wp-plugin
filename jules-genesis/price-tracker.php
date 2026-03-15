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

        // Analítica de Mercado: Extraer precios y URLs vinculadas (hasta 800 chars de distancia)
        $competitor_data = [];

        if (preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>.{0,800}?\$?\s*([1-9]\d{1,2})[.,](\d{3})\s*(COP)?/is', $html_bing, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $url = $matches[1][$i];
                $price_value = (float) ($matches[2][$i] . $matches[3][$i]);

                // Filtrar basura de buscadores
                if (strpos($url, 'bing.com') !== false || strpos($url, 'microsoft.com') !== false || $url === '#') {
                    continue;
                }

                // Filtrar el falso positivo global de 82.015 y muestras/decants muy baratas
                if ($price_value > 85000 && $price_value != 82015 && $price_value <= 2000000) {
                    $domain = parse_url($url, PHP_URL_HOST);
                    $domain = str_replace('www.', '', $domain); // Limpiar www para la UI

                    $competitor_data[] = [
                        'price'  => $price_value,
                        'url'    => $url,
                        'domain' => $domain ?: 'Tienda Web'
                    ];
                }
            }
        }

        if (empty($competitor_data)) {
            return new WP_REST_Response( array(
                'success' => false,
                'message' => 'El mercado no arrojó resultados claros para este perfume.',
                'my_price' => $my_price
            ), 200 );
        }

        // Eliminar ofertas duplicadas (mismo precio y mismo dominio) para no saturar
        $unique_data = [];
        $seen = [];
        foreach ($competitor_data as $item) {
            $key = $item['price'] . '_' . $item['domain'];
            if (!in_array($key, $seen)) {
                $unique_data[] = $item;
                $seen[] = $key;
            }
        }

        // Calcular el Promedio Exacto del Mercado
        $sum = 0;
        foreach($unique_data as $data) {
            $sum += $data['price'];
        }
        $average_market_price = round($sum / count($unique_data));

        // Clasificar las ofertas frente a TU precio
        $cheaper = [];
        $equal = [];
        $more_expensive = [];

        foreach ($unique_data as $data) {
            // Margen de tolerancia de 1,000 pesos para considerarlo "Igual"
            $diff = $data['price'] - $my_price;

            if (abs($diff) <= 1000) {
                $equal[] = $data;
            } elseif ($diff > 1000) {
                $more_expensive[] = $data;
            } else {
                $cheaper[] = $data;
            }
        }

        // Ordenar arreglos para mostrar los más relevantes (Ascendentes para los baratos, Descendentes para los caros)
        usort($cheaper, function($a, $b) { return $a['price'] <=> $b['price']; }); // Del más barato al menos barato
        usort($more_expensive, function($a, $b) { return $b['price'] <=> $a['price']; }); // Del más carísimo al menos caro

        // Tomar hasta 3 de cada categoría
        $top_cheaper = array_slice($cheaper, 0, 3);
        $top_equal = array_slice($equal, 0, 3);
        $top_expensive = array_slice($more_expensive, 0, 3);

        // Dictamen competitivo
        $status = 'competitive';
        $status_message = '¡Excelente! Estás en sintonía con el promedio del mercado.';

        if ($my_price < ($average_market_price * 0.90)) {
            $status = 'lowest'; // Más de un 10% por debajo del promedio
            $status_message = '¡Líder en precios! Tienes una oferta sumamente agresiva comparada al promedio.';
        } elseif ($my_price > ($average_market_price * 1.10)) {
            $status = 'high'; // Más de un 10% por encima del promedio
            $status_message = 'Atención: Tu precio está considerablemente por encima de la media del mercado.';
        }

        return new WP_REST_Response( array(
            'success' => true,
            'my_price' => $my_price,
            'average_market_price' => $average_market_price,
            'status' => $status,
            'message' => $status_message,
            'offers' => array(
                'cheaper' => $top_cheaper,
                'equal'   => $top_equal,
                'expensive' => $top_expensive
            )
        ), 200 );
    }
}

new Jules_Price_Tracker();
