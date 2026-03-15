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
        li.product .group div.aspect-\[3\/4\],
        .grid .group div.aspect-\[3\/4\] {
            aspect-ratio: 1 / 1 !important;
        }

        /* Ensure the image covers the container without being cut off weirdly */
        .splide__slide .group div.aspect-\[3\/4\] img,
        li.product .group div.aspect-\[3\/4\] img,
        .grid .group div.aspect-\[3\/4\] img {
            object-fit: cover !important;
            height: 100% !important;
            width: 100% !important;
            position: absolute !important;
            top: 0;
            left: 0;
        }

        /* ------------------------------------------------------------- */
        /* CHECKOUT STYLES - "CHAT BUBBLE" / MODERN TRANSPARENT AESTHETIC */
        /* ------------------------------------------------------------- */


        /* Hide Billing Details heading */
        .woocommerce-billing-fields h3 {
            display: none !important;
        }

        /* Force checkout fields to be visible as modern chat bubbles */
        .woocommerce-checkout .jules-bubble-input input:not([type="checkbox"]):not([type="radio"]),
        .woocommerce-checkout .jules-bubble-input textarea,
        .woocommerce-checkout .jules-bubble-input select,
        .woocommerce-checkout .jules-bubble-input .select2-selection {
            background-color: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 20px !important;
            padding: 12px 18px !important;
            color: inherit !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05) !important;
            transition: all 0.3s ease !important;
            width: 100% !important;
        }

        /* Dark mode support - auto adapts if body has dark mode class but let's be explicit if needed */
        @media (prefers-color-scheme: dark) {
            .woocommerce-checkout .jules-bubble-input input:not([type="checkbox"]):not([type="radio"]),
            .woocommerce-checkout .jules-bubble-input textarea,
            .woocommerce-checkout .jules-bubble-input select,
            .woocommerce-checkout .jules-bubble-input .select2-selection {
                background-color: rgba(0, 0, 0, 0.2) !important;
                border-color: rgba(255, 255, 255, 0.1) !important;
            }
        }

        .woocommerce-checkout .jules-bubble-input input:focus,
        .woocommerce-checkout .jules-bubble-input textarea:focus,
        .woocommerce-checkout .jules-bubble-input select:focus,
        .woocommerce-checkout .jules-bubble-input .select2-selection:focus {
            outline: none !important;
            border-color: #d4af37 !important; /* Brand Primary Color */
            box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2) !important;
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

        /* Hide labels for a cleaner look if desired, or just style them elegantly */
        .woocommerce-checkout .jules-bubble-input label {
            font-size: 0.85rem !important;
            opacity: 0.8 !important;
            margin-bottom: 5px !important;
            display: inline-block !important;
            margin-left: 10px !important;
        }

        /* Select2 Dropdown fixes (City search hidden text) */
        .select2-container--default .select2-search--dropdown .select2-search__field {
            color: #000000 !important; /* Force text to be visible when typing */
            border-radius: 10px !important;
        }
        .select2-results__option {
            color: #333333 !important; /* Force dropdown options to be readable */
        }

        /* Select2 general fixes to align with our bubble style */
        .woocommerce-checkout .select2-container .select2-selection--single {
            height: auto !important;
            background-color: transparent !important;
        }
        .woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: inherit !important;
            line-height: normal !important;
            padding: 0 !important;
        }
        .woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 50% !important;
            transform: translateY(-50%) !important;
            right: 15px !important;
        }

        /* Hide specific elements designated by backend */
        .jules-hidden-field {
            display: none !important;
        }

        /* ------------------------------------------------------------- */
        /* CAROUSEL FIXES ("COMPLETA TU COLECCIÓN") ON CHECKOUT          */
        /* ------------------------------------------------------------- */

        /* Prevent taxonomy pills (rareza, genero) from getting cut off */
        .woocommerce-checkout .splide__slide .group {
            overflow: visible !important;
        }
        .woocommerce-checkout .splide__list {
            padding-top: 15px !important;
            padding-bottom: 15px !important;
        }
        .woocommerce-checkout .splide__slide {
            /* Add some breathing room so pills that float above don't clip */
            margin-top: 10px;
        }

    </style>
    <?php
}
add_action('wp_head', 'jules_custom_css_tweaks', 999);
