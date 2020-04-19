<?php
 
function divichild_enqueue_scripts() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'divichild_enqueue_scripts' );

// Custom Area
// $template_directory = get_template_directory();

// var_dump(class_exists( 'ET_Builder_Module' )); exit;

// // $postsBlurb = new ET_Builder_Module_Posts_Blurb;

// add_action( 'et_builder_ready', 'evr_initialize_divi_modules' );

// function evr_initialize_divi_modules() {
//     if ( ! class_exists( 'ET_Builder_Module' ) ) { return; }
    
//     // require_once "inc/modules/posts-blurb.php";    
// }

