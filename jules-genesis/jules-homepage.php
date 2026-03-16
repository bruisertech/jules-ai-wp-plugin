<?php
/**
 * Jules Genesis - Custom Homepage Hero Section
 * Overrides the front page to display a beautiful full-screen video background with an animated logo.
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' ); // Exit if accessed directly.
}

// Hook into template_include to completely override the front page template
add_filter( 'template_include', 'jules_custom_front_page_template', 99 );

function jules_custom_front_page_template( $template ) {
    if ( is_front_page() ) {
        // Return the path to our custom template file instead of dying
        return plugin_dir_path( __FILE__ ) . 'jules-homepage-template.php';
    }
    return $template;
}
