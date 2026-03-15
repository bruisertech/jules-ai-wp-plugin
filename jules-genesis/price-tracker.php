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

        // Analítica de Mercado: Extraer precios y URLs vinculadas
        $competitor_data = [];

        // Volvemos a dividir el DOM orgánico por bloques de resultados para mayor precisión
        if (preg_match_all('/<li class="b_algo".*?<\/li>/s', $html_bing, $blocks)) {
            foreach ($blocks[0] as $block) {
                $url = '';

                // En Bing los enlaces reales del resultado están dentro de <h2> o son el primer enlace
                if (preg_match('/<h2>\s*<a[^>]+href="([^"]+)"/is', $block, $url_match)) {
                    $url = $url_match[1];
                } elseif (preg_match('/<a[^>]+href="([^"]+)"/is', $block, $url_match)) {
                    $url = $url_match[1];
                }

                // Si el enlace es de rastreo de Bing (empieza por /ck/a? o contiene bing.com/ck), descodificar la URL real
                if (strpos($url, '/ck/a?') !== false || strpos($url, 'bing.com/ck') !== false) {
                    if (preg_match('/&u=a1([a-zA-Z0-9_-]+)/', $url, $u_match)) {
                        // Bing encodes the URL in a base64-like string after 'u=a1'.
                        // We replace characters to make it standard base64 and add padding.
                        $b64 = strtr($u_match[1], '-_', '+/');
                        $b64 = str_pad($b64, strlen($b64) % 4 === 0 ? strlen($b64) : strlen($b64) + (4 - (strlen($b64) % 4)), '=', STR_PAD_RIGHT);
                        $decoded = base64_decode($b64);
                        if (strpos($decoded, 'http') === 0) {
                            $url = $decoded;
                        }
                    }
                }

                // Filtrar URLs basura de Microsoft o vacías (después de decodificar)
                if (empty($url) || strpos($url, 'microsoft.com') !== false || strpos($url, 'bing.com') !== false || $url === '#') {
                    continue;
                }

                // Extraer el texto limpio del bloque para no atrapar IDs de html
                $text_block = strip_tags($block);

                // Buscar precios en formato COP
                if (preg_match_all('/\$?\s*([1-9]\d{1,2})[.,](\d{3})\s*(COP)?/i', $text_block, $matches)) {
                    for ($i = 0; $i < count($matches[0]); $i++) {
                        $price_value = (float) ($matches[1][$i] . $matches[2][$i]);

                        // Filtrar falsos positivos globales de anuncios (82.015, 818.958) y evitar decants baratos
                        if ($price_value > 85000 && $price_value != 82015 && $price_value != 818958 && $price_value <= 2000000) {
                            $domain = parse_url($url, PHP_URL_HOST);
                            $domain = str_replace('www.', '', $domain);

                            $competitor_data[] = [
                                'price'  => $price_value,
                                'url'    => $url,
                                'domain' => $domain ?: 'Tienda Web'
                            ];
                            break; // Tomar sólo el primer precio válido de este resultado para no duplicar
                        }
                    }
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
