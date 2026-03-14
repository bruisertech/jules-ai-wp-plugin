<?php
/**
 * Jules CSS Tweaks
 *
 * Injects targeted CSS directly into the site to fix UI glitches,
 * such as unwanted spacing in the WooCommerce single product gallery.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Jules_CSS_Tweaks {

    public function __construct() {
        // Hook into wp_head to output custom CSS
        add_action( 'wp_head', array( $this, 'inject_css_fixes' ), 999 );
    }

    /**
     * Injects CSS specifically for the single product gallery to fix
     * the extra empty space at the bottom of carousel images.
     */
    public function inject_css_fixes() {
        // Only run on the single product page
        if ( ! is_product() ) {
            return;
        }

        ?>
        <style id="jules-magic-hand-css">
            /* Fix for unwanted space below WooCommerce product gallery images */
            .woocommerce-product-gallery .woocommerce-product-gallery__image {
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
                line-height: 0;
            }
            .woocommerce-product-gallery .woocommerce-product-gallery__image img {
                display: block !important;
                margin-bottom: 0 !important;
            }
            /* Flexslider specific fixes if the theme uses standard WC slider */
            .woocommerce-product-gallery .flex-viewport {
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
            }
        </style>
        <?php
    }
}

new Jules_CSS_Tweaks();
