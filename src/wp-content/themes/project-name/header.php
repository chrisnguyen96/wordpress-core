<!DOCTYPE html>
<html <?php language_attributes();?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title><?php wp_title('| ', true, 'right');?></title>
    <link rel="icon" href="<?php echo THEME_URL; ?>/assets/imgs/favicon.png" sizes="32x32" />
    <link rel="icon" href="<?php echo THEME_URL; ?>/assets/imgs/favicon.png" sizes="192x192" />
    <link rel="apple-touch-icon-precomposed" href="<?php echo THEME_URL; ?>/assets/imgs/favicon.png" />
    <meta name="msapplication-TileImage" content="<?php echo THEME_URL; ?>/assets/imgs/favicon.png" />
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <header>
        <nav class="navbar navbar-expand-md navbar-light bg-light" role="navigation">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-controls="bs-example-navbar-collapse-1" aria-expanded="false" aria-label="<?php esc_attr_e( 'Toggle navigation', 'your-theme-slug' ); ?>">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="#">Navbar</a>
                <?php
                wp_nav_menu( array(
                    'theme_location'    => 'primary',
                    'depth'             => 2,
                    'container'         => 'div',
                    'container_class'   => 'collapse navbar-collapse',
                    'container_id'      => 'bs-example-navbar-collapse-1',
                    'menu_class'        => 'nav navbar-nav',
                    'fallback_cb'       => 'WP_Bootstrap_Navwalker::fallback',
                    'walker'            => new WP_Bootstrap_Navwalker(),
                ) );
                ?>
            </div>
        </nav>
    </header>