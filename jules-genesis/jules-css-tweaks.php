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
            /* TARGETED FIX: The theme uses a custom flex layout, not standard WooCommerce.
               The gap is caused by Tailwind's gap-16 (4rem) and gap-24 (6rem) classes on the main product div.
               Let's dramatically reduce this spacing for a tighter, sleeker design. */

            /* Override the massive gaps between image and info */
            div[id^="product-"].flex.flex-col-reverse.lg\:flex-row {
                gap: 2rem !important; /* Was 4rem (gap-16) on mobile */
            }

            @media (min-width: 1024px) {
                div[id^="product-"].flex.flex-col-reverse.lg\:flex-row {
                    gap: 3rem !important; /* Was 6rem (gap-24) on desktop */
                }
            }

            /* The image gallery container also has pt-8 / pt-16 forcing it down, let's remove that excess padding */
            div[id^="product-"] .group.relative.flex.flex-col {
                padding-top: 0 !important; /* Was pt-8 / pt-16 */
                margin-top: 0 !important;
            }

            /* The product info container has pt-8 / pt-32, reduce top padding so it aligns nicely */
            div[id^="product-"] .w-full.lg\:w-1\/2.flex.flex-col.items-center.text-center {
                padding-top: 1rem !important;
            }
            @media (min-width: 1024px) {
                div[id^="product-"] .w-full.lg\:w-1\/2.flex.flex-col.items-center.text-center {
                    padding-top: 2rem !important; /* Was pt-32 */
                }
            }

            /* Fallback resets for standard WooCommerce just in case */
            .woocommerce div.product div.images { margin-bottom: 0 !important; }
            .woocommerce-product-gallery figure.woocommerce-product-gallery__wrapper { margin: 0 !important; padding: 0 !important; }
            .woocommerce-product-gallery .woocommerce-product-gallery__image img { display: block !important; margin-bottom: 0 !important; }

            /* Splide Related Products Carousel Fix */

            /* Universal override for the carousel image container to kill mb-3 */
            .splide__slide a.mb-3 {
                margin-bottom: 0 !important;
            }

            /* If the container itself has a gap, remove it */
            .splide__slide .group.relative.flex.flex-col {
                gap: 0 !important;
            }

            /* Extreme override: Pull the text block up aggressively to cover any invisible space.
               Since we know the exact DOM, we target the flex div directly under the group relative wrapper. */
            .splide__slide .group.relative > div.flex.flex-col.justify-start {
                margin-top: -15px !important; /* A noticeable negative margin to prove it works */
                padding-top: 0 !important;
                position: relative; /* Ensure it renders above */
                z-index: 10;
            }

            /* Remove margins from the taxonomy span to tighten the design further */
            .splide__slide .group.relative > div.flex.flex-col.justify-start > span {
                margin-top: 0 !important;
                margin-bottom: 0.5rem !important; /* Standardize bottom margin */
            }
        </style>
        <?php
    }
}

new Jules_CSS_Tweaks();
