<?php

/*
 * Define Variables
 */
if (!defined('THEME_DIR'))
    define('THEME_DIR', get_template_directory());
if (!defined('THEME_URL'))
    define('THEME_URL', get_template_directory_uri());
if (!defined('LIK_AUTHOR_URL'))
    define('LIK_AUTHOR_URL', 'https://thenatives.com.au/');
if (!defined('LIK_AUTHOR_NAME'))
    define('LIK_AUTHOR_NAME', 'The Natives');
/*
 * Add theme Support
 */
if (function_exists('add_theme_support')) {
    // Add Menu Support
    add_theme_support('menus');

    // Add Thumbnail Theme Support
    add_theme_support('post-thumbnails');

    // Enables post and comment RSS feed links to head
    add_theme_support('automatic-feed-links');
    
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    
    add_theme_support( 'editor-styles');
    add_editor_style( 'style-editor.css' );
}

/**
 * Registers an editor stylesheet for the theme.
 */
function register_editor_stylesheet() {
    add_editor_style( 'assets/css/bootstrap.css' );
    add_editor_style( 'assets/css/main.css' );
}
add_action( 'admin_init', 'register_editor_stylesheet' );

/*
 * Add Image Size for Wordpress
 */
if (function_exists('add_image_size')) {
    
}

/*
 *  Localisation Support
 */
load_theme_textdomain('thenatives', THEME_DIR . '/');


/*
 *  Add page slug to body class
 */
function namtech_add_slug_to_body_class($classes) {
    global $post;
    if (is_home()) {
        $key = array_search('blog', $classes);
        if ($key > -1) {
            unset($classes[$key]);
        }
    } elseif (is_page()) {
        $classes[] = sanitize_html_class($post->post_name);
    } elseif (is_singular()) {
        $classes[] = sanitize_html_class($post->post_name);
    }

    return $classes;
}

add_filter('body_class', 'namtech_add_slug_to_body_class');

/*
 *  Change login logo & url in WP Admin
 */

function namtech_custom_login_logo() {
    echo "<style type='text/css'>
body.login {
                    background: -webkit-linear-gradient(90deg, #16222A 10%, #3A6073 90%); /* Chrome 10+, Saf5.1+ */
                    background: -moz-linear-gradient(90deg, #16222A 10%, #3A6073 90%); /* FF3.6+ */
                    background: -ms-linear-gradient(90deg, #16222A 10%, #3A6073 90%); /* IE10 */
                    background: -o-linear-gradient(90deg, #16222A 10%, #3A6073 90%); /* Opera 11.10+ */
                    background: linear-gradient(90deg, #16222A 10%, #3A6073 90%); /* W3C */
                }

                .login h1 a {
                    background-image: url('" . THEME_URL . "/images/login-logo.png');
                    background-size: 280px 78px;
                    width: 280px;
                    height: 78px;
                }

                .login form {
                    margin-left: auto;
                    margin-right: auto;
                    padding: 30px;
                    border: 1px solid rgba(0, 0, 0, .2);
                    background-clip: padding-box;
                    background: rgba(255, 255, 255, 0.9);
                    box-shadow: 0 0 13px 3px rgba(0, 0, 0, .5);
                    overflow: hidden;
                }

                .login label {
                    color: #333;
                }

                #backtoblog, #nav {
                    display: none;
                }
    </style>";
}

add_action('login_head', 'namtech_custom_login_logo');

/*
 *  Remove wp-logo on admin bar
 */

function namtech_wp_admin_bar_remove() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('wp-logo');
}

add_action('wp_before_admin_bar_render', 'namtech_wp_admin_bar_remove', 0);


/*
 * Change Footer Text in Admin
 */

function namtech_change_footer_text() {
    echo "Powered by <a href='" . LIK_AUTHOR_URL . "' target='_blank'>" . LIK_AUTHOR_NAME . "</a>";
}

add_filter('admin_footer_text', 'namtech_change_footer_text');


/*
 * Include framework files
 */
foreach (glob(THEME_DIR . "/includes/*.php") as $file_name) {
    require_once ( $file_name );
}

