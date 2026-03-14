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

        // Hook into individual product image retrieval to override specific missing products
        add_filter( 'woocommerce_product_get_image', array( $this, 'custom_product_image_override' ), 10, 6 );
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

    /**
     * Override the HTML image tag for specific products missing their photos.
     *
     * @param string $image HTML img element or empty string on failure.
     * @param WC_Product $product Product object.
     * @param string $size Image size.
     * @param array $attr Image attributes.
     * @param bool $placeholder True to return $placeholder if image not found.
     * @param string $image HTML img element.
     * @return string HTML img element.
     */
    public function custom_product_image_override( $image, $product, $size, $attr, $placeholder, $original_image ) {
        // Magic Hand: Fix Habibi Musk using a Lattafa style image
        if ( $product && $product->get_slug() === 'habibi-musk' && ! $product->get_image_id() ) {
            // Unsplash image of an ornate, gold/arabic style perfume bottle
            $lattafa_image_url = 'https://images.unsplash.com/photo-1629198688000-71f23e745b6e?auto=format&fit=crop&q=80&w=600&h=600';

            $alt_text = esc_attr( $product->get_name() );
            $class = isset( $attr['class'] ) ? esc_attr( $attr['class'] ) : 'wp-post-image';

            return '<img src="' . esc_url( $lattafa_image_url ) . '" alt="' . $alt_text . '" class="' . $class . '" loading="lazy" />';
        }

        return $image;
    }
}

new Jules_Image_Fixer();
