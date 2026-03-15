<?php
/**
 * Jules Checkout Manager
 * Customizes the WooCommerce Checkout experience for lhparfum.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    return; // Changed exit to return to avoid breaking bash if sourced, though exit is standard for WP.
}

class Jules_Checkout_Manager {

    public function __construct() {
        // 1. Forzar envío a dirección de facturación (Ocultar "Ship to a different address")
        add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );

        // 2. Modificar los campos del checkout
        add_filter( 'woocommerce_checkout_fields', array( $this, 'custom_checkout_fields' ), 9999 );

        // 3. Encolar scripts personalizados (sin Google Places)
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_scripts' ) );

        // 4. Remover el título de "Billing details" (Detalles de facturación)
        add_filter( 'woocommerce_checkout_fields', array( $this, 'remove_billing_title' ) );
    }

    /**
     * Modifica, reorganiza y oculta campos del checkout.
     */
    public function custom_checkout_fields( $fields ) {
        // Ocultar campos innecesarios visualmente (Postcode y State/Department)
        // Convertimos a oculto para que Mipaquete pueda leerlos si los llenamos por JS.
        if ( isset( $fields['billing']['billing_postcode'] ) ) {
            $fields['billing']['billing_postcode']['type'] = 'hidden';
            $fields['billing']['billing_postcode']['class'] = array( 'jules-hidden-field' );
            $fields['billing']['billing_postcode']['label'] = '';
            $fields['billing']['billing_postcode']['required'] = false; // No requerirlo obligatoriamente por WC nativo
        }

        if ( isset( $fields['shipping']['shipping_postcode'] ) ) {
            $fields['shipping']['shipping_postcode']['type'] = 'hidden';
            $fields['shipping']['shipping_postcode']['class'] = array( 'jules-hidden-field' );
            $fields['shipping']['shipping_postcode']['label'] = '';
            $fields['shipping']['shipping_postcode']['required'] = false;
        }

        if ( isset( $fields['billing']['billing_state'] ) ) {
            // Hacemos que el departamento no sea obligatorio y lo ocultamos
            $fields['billing']['billing_state']['required'] = false;
            $fields['billing']['billing_state']['class'][] = 'jules-hidden-field';
        }

        if ( isset( $fields['shipping']['shipping_state'] ) ) {
            $fields['shipping']['shipping_state']['required'] = false;
            $fields['shipping']['shipping_state']['class'][] = 'jules-hidden-field';
        }

        // Eliminar Order Notes
        if ( isset( $fields['order']['order_comments'] ) ) {
            unset( $fields['order']['order_comments'] );
        }

        // Simplificar y añadir clases para estilos de "burbuja", traducir al español
        $bubble_classes = array( 'jules-bubble-input' );

        // Traducir y modificar campos
        if ( isset( $fields['billing']['billing_first_name'] ) ) {
            $fields['billing']['billing_first_name']['label'] = 'Nombre';
            $fields['billing']['billing_first_name']['placeholder'] = 'Tu nombre';
        }
        if ( isset( $fields['billing']['billing_last_name'] ) ) {
            $fields['billing']['billing_last_name']['label'] = 'Apellido';
            $fields['billing']['billing_last_name']['placeholder'] = 'Tu apellido';
        }
        if ( isset( $fields['billing']['billing_email'] ) ) {
            $fields['billing']['billing_email']['label'] = 'Correo electrónico';
            $fields['billing']['billing_email']['placeholder'] = 'tu@email.com';
        }
        if ( isset( $fields['billing']['billing_phone'] ) ) {
            $fields['billing']['billing_phone']['label'] = 'Teléfono';
            $fields['billing']['billing_phone']['placeholder'] = 'Tu número de celular';
            $fields['billing']['billing_phone']['required'] = true; // Hacer el teléfono obligatorio
        }
        if ( isset( $fields['billing']['billing_address_1'] ) ) {
            $fields['billing']['billing_address_1']['label'] = 'Dirección de entrega';
            $fields['billing']['billing_address_1']['placeholder'] = 'Calle 123 # 45-67';
        }
        if ( isset( $fields['billing']['billing_address_2'] ) ) {
            $fields['billing']['billing_address_2']['label'] = 'Detalles adicionales (Apto, piso, torre, etc.)';
            $fields['billing']['billing_address_2']['placeholder'] = 'Apto 101, Torre 2';
            $fields['billing']['billing_address_2']['required'] = false;
        }
        if ( isset( $fields['billing']['billing_city'] ) ) {
            $fields['billing']['billing_city']['label'] = 'Ciudad';
            $fields['billing']['billing_city']['placeholder'] = 'Selecciona tu ciudad';
        }
        if ( isset( $fields['billing']['billing_country'] ) ) {
            $fields['billing']['billing_country']['label'] = 'País';
        }


        $target_fields = array(
            'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone',
            'billing_address_1', 'billing_address_2', 'billing_city', 'billing_country'
        );

        foreach ( $target_fields as $field_key ) {
            if ( isset( $fields['billing'][ $field_key ] ) ) {
                $fields['billing'][ $field_key ]['class'] = array_merge(
                    isset( $fields['billing'][ $field_key ]['class'] ) ? $fields['billing'][ $field_key ]['class'] : array(),
                    $bubble_classes
                );
            }
        }

        return $fields;
    }

    /**
     * Elimina el titulo H3 por hook si es posible
     */
    public function remove_billing_title($fields) {
        // En algunos temas se puede modificar el titulo usando hooks,
        // pero la forma mas segura y estandar es por CSS.
        // Haremos ambas cosas. CSS en jules-css-tweaks.php
        return $fields;
    }

    /**
     * Encola el JS personalizado para resolver el código postal según la ciudad
     */
    public function enqueue_checkout_scripts() {
        if ( is_checkout() && ! is_order_received_page() ) {
            // Script inline para manejar autollenado de código postal
            wp_register_script( 'jules-checkout-js', false, array('jquery'), '', true );
            wp_enqueue_script( 'jules-checkout-js' );

            wp_add_inline_script( 'jules-checkout-js', '
                jQuery(document).ready(function($) {
                    // Mapeo basico de ciudades a codigos postales (usamos 110011 como fallback generico en Colombia)
                    // Las transportadoras son flexibles
                    var cityToPostcode = {
                        "Bogotá": "110011", "Bogota": "110011", "Medellín": "050001", "Medellin": "050001",
                        "Cali": "760001", "Barranquilla": "080001", "Cartagena": "130001",
                        "Bucaramanga": "680001", "Pereira": "660001", "Manizales": "170001",
                        "Cúcuta": "540001", "Cucuta": "540001", "Ibagué": "730001", "Ibague": "730001",
                        "Santa Marta": "470001", "Villavicencio": "500001", "Pasto": "520001",
                        "Montería": "230001", "Monteria": "230001", "Valledupar": "200001",
                        "Popayán": "190001", "Popayan": "190001", "Armenia": "630001",
                        "Neiva": "410001", "Sincelejo": "700001", "Riohacha": "440001",
                        "Tunja": "150001", "Florencia": "180001", "Quibdó": "270001", "Quibdo": "270001",
                        "Arauca": "810001", "Yopal": "850001", "Mocoa": "860001", "San José del Guaviare": "950001",
                        "Leticia": "910001", "Puerto Carreño": "990001", "Inírida": "940001", "Inirida": "940001",
                        "Mitú": "970001", "Mitu": "970001", "San Andrés": "880001", "San Andres": "880001"
                    };

                    function updatePostcode() {
                        var city = $("#billing_city").val();
                        var postcode = "110011"; // Default a Bogota para no fallar el envio

                        // Si la ciudad esta en el mapa, usamos su codigo postal
                        if (cityToPostcode[city]) {
                            postcode = cityToPostcode[city];
                        } else if (city && typeof city === "string" && city.length > 0) {
                            // Buscar match parcial
                            for (var key in cityToPostcode) {
                                if (city.toLowerCase().includes(key.toLowerCase())) {
                                    postcode = cityToPostcode[key];
                                    break;
                                }
                            }
                        }

                        // Establecer el valor
                        var $postcodeInput = $("#billing_postcode");
                        if ($postcodeInput.length) {
                            $postcodeInput.val(postcode);
                        }
                    }

                    // Escuchar cambios en la ciudad
                    $(document.body).on("change", "#billing_city", function() {
                        updatePostcode();
                    });

                    // Algunos temas actualizan la ciudad con select2, tambien escuchamos eso
                    $(document.body).on("select2:select", "#billing_city", function() {
                         updatePostcode();
                    });

                    // Ejecutar una vez al cargar por si la ciudad ya esta seleccionada
                    setTimeout(updatePostcode, 1000);
                });
            ' );
        }
    }
}

new Jules_Checkout_Manager();
