<?php
/**
 * Jules CSS Tweaks
 * Fixes aspect ratio mismatch on product images
 */

function jules_custom_css_tweaks() {
    ?>
    <style>
        /* Force square aspect ratio on product image containers to match physical image dimensions */
        .splide__slide .group div.aspect-\[3\/4\],
        li.product .group div.aspect-\[3\/4\] {
            aspect-ratio: 1 / 1 !important;
        }

        /* Ensure the image covers the container without being cut off weirdly */
        .splide__slide .group div.aspect-\[3\/4\] img,
        li.product .group div.aspect-\[3\/4\] img {
            object-fit: cover !important;
            height: 100% !important;
            width: 100% !important;
            position: absolute !important;
            top: 0;
            left: 0;
        }
    </style>
    <?php
}
add_action('wp_head', 'jules_custom_css_tweaks', 999);
