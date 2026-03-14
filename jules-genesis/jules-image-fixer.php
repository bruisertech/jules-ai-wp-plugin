<?php
/**
 * Jules Image Fixer
 *
 * Replaces the default gray WooCommerce placeholder image with a high-quality,
 * luxury perfume aesthetic image when a product doesn't have a photo.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Jules_Image_Fixer {

    public function __construct() {
        // Hook into WooCommerce to change the default placeholder image source
        add_filter( 'woocommerce_placeholder_img_src', array( $this, 'custom_luxury_placeholder' ), 10, 1 );
    }

    /**
     * Return a URL to a luxury placeholder image.
     *
     * @param string $src The original placeholder image URL.
     * @return string The new placeholder image URL.
     */
    public function custom_luxury_placeholder( $src ) {
        // A high-quality, elegant, dark aesthetic perfume bottle from Unsplash
        // This instantly looks better than the gray WooCommerce box.
        $luxury_image_url = 'https://images.unsplash.com/photo-1594035910387-fea47794261f?auto=format&fit=crop&q=80&w=600&h=600';

        return esc_url( $luxury_image_url );
    }
}

new Jules_Image_Fixer();
