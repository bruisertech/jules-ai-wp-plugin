<?php
/**
 * Jules CSS Tweaks
 * Fixes aspect ratio mismatch on product images
 */

function jules_custom_css_tweaks() {
    ?>
    <style>
        /* Magic Luxury Effect: White Gold Marble Background */
        .splide__slide .group div.aspect-\[3\/4\],
        li.product .group div.aspect-\[3\/4\],
        .grid .group div.aspect-\[3\/4\] {
            aspect-ratio: 1 / 1 !important;
            background-image: url('https://images.unsplash.com/photo-1590451375836-31d7bfb958c2?q=80&w=600&auto=format&fit=crop') !important;
            background-size: cover !important;
            background-position: center !important;
            position: relative !important;
            border-radius: 8px !important;
            box-shadow: inset 0 0 40px rgba(0,0,0,0.05), 0 10px 30px rgba(0,0,0,0.05) !important;
            overflow: hidden !important;
            border: 1px solid rgba(212, 175, 55, 0.3) !important; /* Gold trim */
        }

        /* Make the white background of the perfume disappear into the marble */
        .splide__slide .group div.aspect-\[3\/4\] img,
        li.product .group div.aspect-\[3\/4\] img,
        .grid .group div.aspect-\[3\/4\] img {
            object-fit: contain !important;
            height: 90% !important;
            width: 90% !important;
            position: absolute !important;
            top: 5%;
            left: 5%;
            mix-blend-mode: multiply !important; /* The magic trick */
            filter: contrast(1.1) brightness(0.95) !important; /* Make colors pop */
            transition: transform 0.4s ease !important;
        }

        /* Hover effect for extra luxury */
        .grid .group:hover div.aspect-\[3\/4\] img {
            transform: scale(1.05) !important;
        }

        /* The Animated LH Parfum Logo Logo */
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
