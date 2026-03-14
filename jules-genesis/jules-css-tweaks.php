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

        /* The Gold 'lhparfum' Plaque */
        .splide__slide .group div.aspect-\[3\/4\]::after,
        li.product .group div.aspect-\[3\/4\]::after,
        .grid .group div.aspect-\[3\/4\]::after {
            content: "lhparfum" !important;
            position: absolute !important;
            bottom: 12px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            background: linear-gradient(135deg, #f2d576 0%, #d4af37 50%, #b5952f 100%) !important;
            color: #1a1a1a !important;
            padding: 4px 16px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            letter-spacing: 2px !important;
            text-transform: uppercase !important;
            border-radius: 2px !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15), inset 0 1px 2px rgba(255,255,255,0.4) !important;
            z-index: 10 !important;
            font-family: 'Playfair Display', serif !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'jules_custom_css_tweaks', 999);
