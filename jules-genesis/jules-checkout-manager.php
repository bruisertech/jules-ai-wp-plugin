<?php
/**
 * Jules Checkout Manager
 * Customizes the WooCommerce Checkout experience for lhparfum.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Jules_Checkout_Manager {

    public function __construct() {
        // 1. Forzar envío a dirección de facturación (Ocultar "Ship to a different address")
        add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );

        // 2. Modificar los campos del checkout
        add_filter( 'woocommerce_checkout_fields', array( $this, 'custom_checkout_fields' ), 9999 );

        // 3. Encolar Google Places API y scripts personalizados
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_scripts' ) );
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

        // Simplificar y añadir clases para estilos de "burbuja"
        $bubble_classes = array( 'jules-bubble-input' );

        $target_fields = array(
            'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone',
            'billing_address_1', 'billing_address_2', 'billing_city'
        );

        foreach ( $target_fields as $field_key ) {
            if ( isset( $fields['billing'][ $field_key ] ) ) {
                $fields['billing'][ $field_key ]['class'] = array_merge(
                    isset( $fields['billing'][ $field_key ]['class'] ) ? $fields['billing'][ $field_key ]['class'] : array(),
                    $bubble_classes
                );
            }
        }

        // Renombrar algunos labels si es necesario para que sea más limpio
        if ( isset( $fields['billing']['billing_address_1'] ) ) {
            $fields['billing']['billing_address_1']['placeholder'] = 'Busca tu dirección...';
            $fields['billing']['billing_address_1']['label'] = 'Dirección de entrega';
        }

        return $fields;
    }

    /**
     * Encola el script de Google Places Autocomplete y el JS personalizado
     */
    public function enqueue_checkout_scripts() {
        if ( is_checkout() && ! is_order_received_page() ) {
            // Encolar Google Maps API (API Key secured via WP Option, with default fallback)
            $api_key = get_option( 'jules_google_maps_api_key', 'AIzaSyC0R34YApUxu3h7hsh5p8Kq8EnKcPNJLfE' );

            wp_enqueue_script(
                'google-places-api',
                'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&libraries=places',
                array(),
                null,
                true
            );

            // Script inline para manejar Autocomplete y autollenado de código postal/departamento
            wp_add_inline_script( 'google-places-api', '
                document.addEventListener("DOMContentLoaded", function() {
                    var addressInput = document.getElementById("billing_address_1");
                    if ( ! addressInput ) return;

                    var autocomplete = new google.maps.places.Autocomplete(addressInput, {
                        types: ["address"],
                        componentRestrictions: { country: "CO" } // Limitar a Colombia
                    });

                    // Mapeo simple de nombres de departamentos en Google a códigos de estado WC para Colombia (ej. Antioquia -> ANT)
                    // WooCommerce uses specific ISO 3166-2 codes for states in Colombia, like ANT, DC, CUN, VAL, etc.
                    var stateCodeMap = {
                        "Amazonas": "AMA", "Antioquia": "ANT", "Arauca": "ARA", "Atlántico": "ATL",
                        "Bolívar": "BOL", "Boyacá": "BOY", "Caldas": "CAL", "Caquetá": "CAQ",
                        "Casanare": "CAS", "Cauca": "CAU", "Cesar": "CES", "Chocó": "CHO",
                        "Córdoba": "COR", "Cundinamarca": "CUN", "Bogotá": "DC", "Bogotá, D.C.": "DC", "Bogota": "DC",
                        "Guainía": "GUA", "Guaviare": "GUV", "Huila": "HUI", "La Guajira": "LAG",
                        "Magdalena": "MAG", "Meta": "MET", "Nariño": "NAR", "Norte de Santander": "NSA",
                        "Putumayo": "PUT", "Quindío": "QUI", "Risaralda": "RIS", "San Andrés y Providencia": "SAP",
                        "Santander": "SAN", "Sucre": "SUC", "Tolima": "TOL", "Valle del Cauca": "VAC", "Valle": "VAC",
                        "Vaupés": "VAU", "Vichada": "VID"
                    };

                    autocomplete.addListener("place_changed", function() {
                        var place = autocomplete.getPlace();
                        if (!place.geometry) {
                            return;
                        }

                        // Parsear componentes de la dirección
                        var postalCode = "";
                        var stateName = "";
                        var city = "";

                        for (var i = 0; i < place.address_components.length; i++) {
                            var component = place.address_components[i];
                            var type = component.types[0];

                            if (type === "postal_code") {
                                postalCode = component.long_name;
                            }
                            if (type === "administrative_area_level_1") {
                                stateName = component.long_name;
                            }
                            if (type === "locality" || type === "administrative_area_level_2") {
                                city = component.long_name;
                            }
                        }

                        // Llenar código postal oculto
                        var postcodeInput = document.getElementById("billing_postcode");
                        if (postcodeInput && postalCode) {
                            postcodeInput.value = postalCode;
                        } else if (postcodeInput) {
                            postcodeInput.value = "110011";
                        }

                        // Llenar el departamento (Estado) para WooCommerce / mipaquete
                        var stateInput = document.getElementById("billing_state");
                        if (stateInput && stateName) {
                            // Buscar el código en el mapa, o si no lo encuentra usar el nombre directamente (como fallback)
                            var stateCode = stateCodeMap[stateName] || stateName;

                            stateInput.value = stateCode;
                            // Si el input es un select (muy probable en WooCommerce), disparar evento de cambio
                            var event = new Event("change", { bubbles: true });
                            stateInput.dispatchEvent(event);

                            // Si es select2 (usado por WC para estados), forzar la actualización visual/lógica
                            if (window.jQuery && jQuery(stateInput).hasClass("select2-hidden-accessible")) {
                                jQuery(stateInput).trigger("change.select2");
                            }
                        }

                        // Seleccionar la ciudad si es posible (Select2 en WooCommerce)
                        if ( city ) {
                            var cityInput = document.getElementById("billing_city");
                            if (cityInput) {
                                cityInput.value = city;
                                var event = new Event("change", { bubbles: true });
                                cityInput.dispatchEvent(event);
                            }
                        }
                    });

                    // Ocultar "Ship to a different address" bloque completo por si el filtro no es suficiente
                    var shipToDifferent = document.getElementById("ship-to-different-address");
                    if ( shipToDifferent ) {
                        shipToDifferent.style.display = "none";
                        var checkbox = document.getElementById("ship-to-different-address-checkbox");
                        if (checkbox) checkbox.checked = false;
                    }
                });
            ' );
        }
    }
}

new Jules_Checkout_Manager();
