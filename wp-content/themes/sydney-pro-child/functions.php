<?php
/**
 * Sydney child functions
 *
 */


/**
 * Enqueues the parent stylesheet. Do not remove this function.
 *
 */
add_action( 'wp_enqueue_scripts', 'sydney_pro_child_enqueue' );
function sydney_pro_child_enqueue() {

  wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

}

/* ADD YOUR CUSTOM FUNCTIONS BELOW */

function sydney_child_dequeue_script() {
 wp_dequeue_script('sydney-main');
}

add_action( 'wp_print_scripts', 'sydney_child_dequeue_script', 100 );

add_action( 'wp_enqueue_scripts', 'sydney_child_enqueue' );

function sydney_child_enqueue() {
  wp_enqueue_script( 'sydney-main-child', get_stylesheet_directory_uri() . '/js/sydney-main-child.js', array('jquery'),'', true );
}




/**********
WOOCOMMERCE
**********/
add_filter( 'woocommerce_breadcrumb_home_url', 'woo_custom_breadrumb_home_url' );
function woo_custom_breadrumb_home_url() {
    return 'http://victoriafontaine.com/ks/shop/';
}

add_filter( 'woocommerce_breadcrumb_defaults', 'jk_change_breadcrumb_home_text' );
function jk_change_breadcrumb_home_text( $defaults ) {
    // Change the breadcrumb home text from 'Home' to 'Shop'
  $defaults['home'] = 'Shop';
  return $defaults;
}
//REMOVE TRAINING PACKAGES FROM STOREFRONT
function custom_pre_get_posts_query( $q ) {

    $tax_query = (array) $q->get( 'tax_query' );

    $tax_query[] = array(
           'taxonomy' => 'product_cat',
           'field' => 'slug',
           'terms' => array( 'training-package' ), // Don't display products in the clothing category on the shop page.
           'operator' => 'NOT IN'
    );


    $q->set( 'tax_query', $tax_query );

}
add_action( 'woocommerce_product_query', 'custom_pre_get_posts_query' );



/***
BLOG 
***/
//remove taxonomy from archive pages
add_filter( 'get_the_archive_title', function ($title) {
    if ( is_category() ) {
            $title = single_cat_title( '', false );
        } elseif ( is_tag() ) {
            $title = single_tag_title( '', false );
        } elseif ( is_author() ) {
            $title = '<span class="vcard">' . get_the_author() . '</span>' ;
        }
    return $title;
});

/*------------------------------------*\
      Custom Stylesheet Functions
\*------------------------------------*/

function register_custom_stylesheets() {
    wp_register_style( 'event_page', get_stylesheet_directory_uri() . '/event.css' );
}

function add_event_stylesheet() {
    if ( is_page_template('template-events.php'))
    wp_enqueue_style( 'event_page' );
}

add_action( 'init', 'register_custom_stylesheets' ); 
add_action( 'wp_enqueue_scripts', 'add_event_stylesheet' );
?>