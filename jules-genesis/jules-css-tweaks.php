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
            /* AGGRESSIVE fix for unwanted space below WooCommerce product gallery images */

            /* Remove margins from the main images column wrapper */
            .woocommerce div.product div.images {
                margin-bottom: 0 !important;
            }

            /* Remove padding/margin from the gallery figure wrapper */
            .woocommerce-product-gallery figure.woocommerce-product-gallery__wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Target the specific image wrappers */
            .woocommerce-product-gallery .woocommerce-product-gallery__image {
                margin: 0 !important;
                padding: 0 !important;
                line-height: 0 !important;
                display: block !important;
            }

            /* Target the image itself to act as a block, removing descender spacing */
            .woocommerce-product-gallery .woocommerce-product-gallery__image img {
                display: block !important;
                margin-bottom: 0 !important;
                width: 100% !important; /* Ensure it fills but doesn't overflow */
            }

            /* If the theme uses WooCommerce's Flexslider, remove its bottom spacing completely */
            .woocommerce-product-gallery .flex-viewport,
            .woocommerce-product-gallery .flex-control-nav {
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
                height: auto !important;
            }

            /* If the theme hides the thumbnails (.flex-control-nav) but leaves empty space for them */
            .woocommerce-product-gallery ol.flex-control-nav.flex-control-thumbs {
                display: none !important; /* Hide thumbs completely if they are empty */
                margin: 0 !important;
                padding: 0 !important;
            }
        </style>
        <?php
    }
}

new Jules_CSS_Tweaks();
