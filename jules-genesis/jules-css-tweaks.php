<?php
/**
 * Jules CSS Tweaks
 * Fixes aspect ratio mismatch on product images
 */

function jules_custom_css_tweaks() {
    ?>
    <style>
        /* Clean Minimalist Effect: Transparent Backgrounds */
        .splide__slide .group div.aspect-\[3\/4\],
        li.product .group div.aspect-\[3\/4\],
        .grid .group div.aspect-\[3\/4\] {
            aspect-ratio: 1 / 1 !important;
            background-color: transparent !important; /* Clean slate */
            position: relative !important;
            border-radius: 8px !important;
            overflow: hidden !important;
            border: none !important;
        }

        /* Center the HD transparent perfume bottle perfectly */
        .splide__slide .group div.aspect-\[3\/4\] img,
        li.product .group div.aspect-\[3\/4\] img,
        .grid .group div.aspect-\[3\/4\] img {
            object-fit: contain !important;
            height: 85% !important;
            width: 85% !important;
            position: absolute !important;
            top: 7.5%;
            left: 7.5%;
            transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1) !important;
            filter: drop-shadow(0 10px 15px rgba(0,0,0,0.1)) !important; /* Elegant shadow since they have no background */
        }

        /* Hover effect for depth */
        .grid .group:hover div.aspect-\[3\/4\] img {
            transform: scale(1.08) translateY(-5px) !important;
        }

        /* The Animated LH Parfum Logo */
        @keyframes subtlePulse {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); opacity: 0.8; }
        }

        .splide__slide .group div.aspect-\[3\/4\]::after,
        li.product .group div.aspect-\[3\/4\]::after,
        .grid .group div.aspect-\[3\/4\]::after {
            content: "" !important;
            position: absolute !important;
            bottom: 15px !important;
            right: 15px !important;
            width: 40px !important;
            height: 40px !important;
            background-image: url('/wp-content/plugins/jules-genesis/assets/logo-lh.png') !important;
            background-size: contain !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            z-index: 10 !important;
            animation: subtlePulse 3s infinite ease-in-out !important;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)) !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'jules_custom_css_tweaks', 999);
