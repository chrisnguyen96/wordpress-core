<?php
/*
 * Create blocks for site
 */

function namtech_block_category( $categories, $post ) {
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'thenatives-blocks',
                'title' => __( 'The Natives Blocks', 'thenatives-blocks' ),
            ),
        )
    );
}
add_filter( 'block_categories', 'namtech_block_category', 10, 2);

add_action('acf/init', 'namtech_acf_init_block_types');
function namtech_acf_init_block_types() {
    // Check function exists.
    if( function_exists('acf_register_block_type') ) {

        acf_register_block_type(array(
            'name'              => 'Spacing',
            'title'             => __('Spacing'),
            'description'       => __('A custom Spacing block.'),
            'render_template'   => 'template-parts/blocks/spacing/block.php',
            'category'          => 'thenatives-blocks',
            'icon'              => 'admin-customizer',
            'mode'              => true,
            'example' => array(
                'attributes'		=> array(
                    'mode'			=> 'preview',
                    'data'			=> array(
                        'content' 	=> __('<img width="300px" height="150px" src="template-parts/blocks/spacing/screenshot.png">'),
                    ),
                )
            ),
        ));
    }
}