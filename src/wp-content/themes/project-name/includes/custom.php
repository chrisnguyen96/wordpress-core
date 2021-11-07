<?php
/*
 *  Load the site scripts
 */
function namtech_scripts() {

    $version = '1.0.0';

    // Load CSS
    //-- CSS HTML here
    wp_enqueue_style('all-style-css', THEME_URL . '/assets/css/all.css', array(), $version, 'screen');
    wp_enqueue_style('bootstrap-style-css', THEME_URL . '/assets/css/bootstrap.css', array(), $version, 'screen');
    wp_enqueue_style('ow-style-css', THEME_URL . '/assets/css/owl.carousel.min.css', array(), $version, 'screen');
    wp_enqueue_style('wp-block-library-css', home_url() . '/wp-includes/css/dist/block-library/style.min.css', array(), $version, 'screen');
    wp_enqueue_style('main-style-css', THEME_URL . '/assets/css/main.css', array(), $version, 'screen');

    // Load JS
    //-- JS HTML here
    wp_enqueue_script('query-js', THEME_URL . '/assets/js/jquery.min.js', array('jquery'), $version, true);
    wp_enqueue_script('bundle-scripts-js', THEME_URL . '/assets/js/bootstrap.bundle.min.js', array('jquery'), $version, true);
    wp_enqueue_script('owl-scripts-js', THEME_URL . '/assets/js/owl.carousel.min.js', array('jquery'), $version, true);
    wp_enqueue_script('main-scripts-js', THEME_URL . '/assets/js/main.js', array('jquery'), $version, true);  
}
add_action('wp_enqueue_scripts', 'namtech_scripts');

/**
 * Menu Register
 */
register_nav_menus(
    array(
        "primary"    => __( "Primary Menu"),
        "footer"     => __( "Footer Menu")
    )
);

function add_additional_class_on_li($classes, $item, $args) {    
    if(isset($args->add_li_class)) {
        $classes[] = $args->add_li_class;
    }    
    return $classes;
}
add_filter('nav_menu_css_class', 'add_additional_class_on_li', 1, 3);

/**
 * Register Custom Navigation Walker
 */
function register_navwalker(){
    require_once get_template_directory() . '/includes/class-wp-bootstrap-navwalker.php';
}
add_action( 'after_setup_theme', 'register_navwalker' );

/**
 * Add ACF options page
 */
if (function_exists('acf_add_options_page')) {
    $parent = acf_add_options_page(__('Site Settings', 'namtech'));
}

// Local JSON acf
add_filter('acf/settings/save_json', 'my_acf_json_save_point');
function my_acf_json_save_point($path)
{
    $theme_dir = get_stylesheet_directory();
    // Create our directory if it doesn't exist.
    if ( ! is_dir( $theme_dir .= '/acf-field' ) ) {
        mkdir( $theme_dir, 0755 );
    }
    $path = get_stylesheet_directory() . '/acf-field';
    return $path;
}
add_filter('acf/settings/load_json', 'my_acf_json_load_point');
function my_acf_json_load_point($paths)
{
    // remove original path (optional)
    unset($paths[0]);
    $paths[] = get_stylesheet_directory() . '/acf-field';
    return $paths;
}

/**
 * Saves post type and taxonomy data to JSON files in the theme directory.
 *
 * @param array $data Array of post type data that was just saved.
 */

function pluginize_local_cptui_data( $data = array() ) {
    $theme_dir = get_stylesheet_directory();
    // Create our directory if it doesn't exist.
    if ( ! is_dir( $theme_dir .= '/cptui_data' ) ) {
        mkdir( $theme_dir, 0755 );
    }

    if ( array_key_exists( 'cpt_custom_post_type', $data ) ) {
        // Fetch all of our post types and encode into JSON.
        $cptui_post_types = get_option( 'cptui_post_types', array() );
        $content = json_encode( $cptui_post_types );
        // Save the encoded JSON to a primary file holding all of them.
        file_put_contents( $theme_dir . '/cptui_post_type_data.json', $content );
    }

    if ( array_key_exists( 'cpt_custom_tax', $data ) ) {
        // Fetch all of our taxonomies and encode into JSON.
        $cptui_taxonomies = get_option( 'cptui_taxonomies', array() );
        $content = json_encode( $cptui_taxonomies );
        // Save the encoded JSON to a primary file holding all of them.
        file_put_contents( $theme_dir . '/cptui_taxonomy_data.json', $content );
    }
}
add_action( 'cptui_after_update_post_type', 'pluginize_local_cptui_data' );
add_action( 'cptui_after_update_taxonomy', 'pluginize_local_cptui_data' );

?>