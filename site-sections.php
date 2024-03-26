<?php
/**
 * Plugin Name: Site Sections
 * Description: A plugin to create and manage site sections. Exposed in the WP API for consumption by headless themes or WP apps.
 * Version: 0.31
 * Author: Tdude
 */

// Get rid of WPs pesky adding a paragraph to everything and it's stepmother
remove_filter('the_excerpt', 'wpautop');



// Enqueue styles and scripts
function ss_admin_scripts() {
    wp_enqueue_style('ss-style', plugin_dir_url(__FILE__) . 'style.css');
    // Ensure the media uploader script is loaded
    wp_enqueue_media();
    //wp_enqueue_script('ss-admin-script', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'ss_admin_scripts');



// Register Custom Post Type
function register_ss_cpt() {
    $args = array(
        'public' => true,
        'label'  => 'Site Sections',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'has_archive' => true,
        'menu_icon' => 'dashicons-layout',
        'show_in_rest' => true,
        'rest_base' => 'site_sections',
        'hierarchical' => true,
    );
    register_post_type('ss', $args);
}
add_action('init', 'register_ss_cpt');


// Meta box for custom post type
function ss_add_meta_box() {
    add_meta_box(
        'ss_id',
        'Section Layout Details',
        'ss_meta_box_html',
        'ss'
    );
}
add_action('add_meta_boxes', 'ss_add_meta_box');



add_filter('rest_ss_query', function ($args) {
    $args['meta_key'] = 'post_order';
    $args['orderby'] = 'meta_value_num';
    $args['order'] = 'ASC';
    return $args;
}, 10, 1);


function ss_register_custom_taxonomy() {
    register_taxonomy(
        'ss_cat', // Taxonomy key
        'ss', // Associated CPT
        array(
            'label' => __('Categories (groups/tags)', 'site_sections'), // Visible name for the taxonomy
            'rewrite' => array('slug' => 'ss-cat'), // URL slug for the taxonomy
            'hierarchical' => true, // True to behave like categories, false to behave like tags
            'show_in_rest' => true, // Make sure it's available in the WP REST API
        )
    );
}
add_action('init', 'ss_register_custom_taxonomy');



function ss_add_categories_to_rest() {
    register_rest_field('ss', 'ss_cat_slugs', [
        'get_callback' => function ($object) {
            // Get category terms assigned to the post
            $terms = wp_get_post_terms($object['id'], 'ss_cat', ['fields' => 'slugs']);
            // Return an array of category slugs
            return $terms;
        },
        'schema' => null,
    ]);
}
add_action('rest_api_init', 'ss_add_categories_to_rest');




function ss_meta_box_html($post) {
    wp_nonce_field(plugin_basename(__FILE__), 'ss_noncename');
    $posts = get_posts(array('post_type' => 'ss', 'numberposts' => -1));

    // Fetching existing values
    $meta_header = get_post_meta($post->ID, 'meta_header', true);
    $cta_text = get_post_meta($post->ID, 'cta_text', true);
    $cta_link = get_post_meta($post->ID, 'cta_link', true);
    $cta_text_sec = get_post_meta($post->ID, 'cta_text_sec', true);
    $cta_link_sec = get_post_meta($post->ID, 'cta_link_sec', true);
    $layout = get_post_meta($post->ID, 'layout', true);
    $style = get_post_meta($post->ID, 'style', true);
    $position = get_post_meta($post->ID, 'position', true);
    $icon = get_post_meta($post->ID, 'icon', true);
    $use_boolean = get_post_meta($post->ID, 'use_boolean', true);
    $post_order = get_post_meta($post->ID, 'post_order', true);

    ?>
    <div class="ss-meta-box">
        <label for="layout">Layout:</label>
        <select id="layout" name="layout" class="widefat">
            <option value="default" <?php selected($layout, 'default'); ?>>Default</option>
            <option value="hero_primary" <?php selected($layout, 'hero_primary'); ?>>Hero Primary (Inverted)</option>
            <option value="hero_secondary" <?php selected($layout, 'hero_secondary'); ?>>Hero Secondary (Light)</option>
            <option value="postcard" <?php selected($layout, 'postcard'); ?>>Postcard (Group)</option>
            <option value="features_large" <?php selected($layout, 'features_large'); ?>>Feature Large (Header)</option>
            <option value="features_medium" <?php selected($layout, 'features_medium'); ?>>Feature Medium (Header)</option>
            <option value="features_small" <?php selected($layout, 'features_small'); ?>>Feature Small (Group with tags)</option>
        </select>


        <fieldset>
            <legend>Style (if applicable):</legend>
            <label for="style_light">
                <input type="radio" id="style_light" name="style" value="light" <?php checked($style, 'light', true); ?> />Light
            </label>
            <label for="style_dark">
                <input type="radio" id="style_dark" name="style" value="dark" <?php checked($style, 'dark'); ?> />Dark
            </label>
            <label for="style_gray">
                <input type="radio" id="style_gray" name="style" value="gray" <?php checked($style, 'gray'); ?> />Gray
            </label>
        </fieldset>

        <fieldset>
            <legend>Position (if applicable):</legend>
            <label for="position_left">
                <input type="radio" id="position_left" name="position" value="left" <?php checked($position, 'left', true); ?> />Left
            </label>
            <label for="position_center">
                <input type="radio" id="position_center" name="position" value="center" <?php checked($position, 'center'); ?> />Center
            </label>
            <label for="position_right">
                <input type="radio" id="position_right" name="position" value="right" <?php checked($position, 'right'); ?> />Right
            </label>
        </fieldset>

        <label for="use_boolean">Use Boolean (good to have):
            <input type="checkbox" id="use_boolean" name="use_boolean" value="1" <?php checked($use_boolean, '1'); ?> class="widefat" />
        </label>
        <label for="meta_header">Meta Header:
            <input type="text" id="meta_header" name="meta_header" value="<?php echo esc_attr($meta_header); ?>" class="widefat" />
        </label>
        <label for="cta_text">CTA Text:
            <input type="text" id="cta_text" name="cta_text" value="<?php echo esc_attr($cta_text); ?>" class="widefat" />
        </label>
        <label for="cta_link">CTA Link:
            <input type="url" id="cta_link" name="cta_link" value="<?php echo esc_url($cta_link); ?>" class="widefat" />
        </label>
        <label for="cta_text_sec">Secondary CTA text:
            <input type="text" id="cta_text_sec" name="cta_text_sec" value="<?php echo esc_attr($cta_text_sec); ?>" class="widefat" />
        </label>
        <label for="cta_link_sec">Secondary CTA link:
            <input type="url" id="cta_link_sec" name="cta_link_sec" value="<?php echo esc_url($cta_link_sec); ?>" class="widefat" />
        </label>
        <label for="icon">Icon (Ionicon name):
            <input type="text" id="icon" name="icon" value="<?php echo esc_attr($icon); ?>" class="widefat" />
        </label>
        <label for="post_order">Post order:
            <input type="number" id="post_order" name="post_order" value="<?php echo esc_attr(get_post_meta($post->ID, 'post_order', true)); ?>" class="widefat" />
        </label>
    </div>
    <?php
}



// Save post meta data.
function ss_save_postdata($post_id) {
    if (!isset($_POST['ss_noncename']) || !wp_verify_nonce($_POST['ss_noncename'], plugin_basename(__FILE__)) ||
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    // Sanitize and save the meta fields
    $fields = [
        'meta_header' => sanitize_text_field($_POST['meta_header']),
        'cta_text' => sanitize_text_field($_POST['cta_text']),
        'cta_link' => esc_url_raw($_POST['cta_link']),
        'cta_text_sec' => sanitize_text_field($_POST['cta_text_sec']),
        'cta_link_sec' => esc_url_raw($_POST['cta_link_sec']),
        'layout' => sanitize_text_field($_POST['layout']),
        'style' => sanitize_text_field($_POST['style']),
        'position' => sanitize_text_field($_POST['position']),
        'icon' => sanitize_text_field($_POST['icon']),
        'post_order' => intval($_POST['post_order'])
    ];
    if (isset($_POST['position'])) {
        $fields['position'] = sanitize_text_field($_POST['position']);
    }
    $fields['use_boolean'] = isset($_POST['use_boolean']) ? '1' : ''; // Save '1' if checked

    foreach ($fields as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }
}
add_action('save_post', 'ss_save_postdata');




// Now to get the meta stuff from the API, we need to:
function ss_register_meta_for_rest() {
    $meta_fields = ['meta_header',
                    'cta_text',
                    'cta_link',
                    'cta_text_sec',
                    'cta_link_sec',
                    'layout',
                    'style',
                    'position',
                    'icon',
                    'use_boolean',
                    'post_order'
                ];

    foreach ($meta_fields as $meta_field) {
        register_rest_field('ss', $meta_field, [
            'get_callback' => function($object, $field_name) {
                return get_post_meta($object['id'], $field_name, true);
            },
            'update_callback' => null,
            'schema' => null,
        ]);
    }
}
add_action('rest_api_init', 'ss_register_meta_for_rest');




register_nav_menus( array(
    'menu-1' => esc_html__( 'Primary', 'site_sections' ),
));



// Get it with fetchMenu() in WordPressService.js
add_action( 'rest_api_init', function () {
    register_rest_route( 'ss/v1', '/menus/(?P<menu>[\w-]+)', array(
        'methods' => 'GET',
        'callback' => 'ss_get_menu_items',
        'permission_callback' => '__return_true', // Consider setting proper permissions for security
    ));
});
add_action('rest_api_init', 'ss_register_meta_for_rest');



function ss_get_menu_items( $data ) {
    $menu_location = $data['menu'];
    $locations = get_nav_menu_locations();
    error_log(print_r($locations, true)); // Log the locations to see if 'main' is there

    if (isset($locations[ $menu_location ])) {
        $menu = wp_get_nav_menu_object( $locations[ $menu_location ] );
        $menu_items = wp_get_nav_menu_items($menu->term_id);

        return $menu_items ?: new WP_Error('no_menu', 'No menu items found', array('status' => 404));
    }

    return new WP_Error('no_menu', 'Menu not found', array('status' => 404));
}


// Stuff more posts into a simple API call by default. This can also be set in the fetchPosts() js
add_filter('rest_site_sections_collection_params', function ($query_params) {
    if (isset($query_params['per_page'])) {
        $query_params['per_page']['default'] = 20;
    }
    return $query_params;
});



add_filter('rest_authentication_errors', function ($result) {
    if (!empty($result)) {
        return $result; // Pass through any existing errors
    }

    $rest_route = $GLOBALS['wp']->query_vars['_rest_route'] ?? '';

    if (strpos($rest_route, '/wp/v2/users') !== false) {
        // Check if the user is logged in and has the 'list_users' capability
        if (!is_user_logged_in() || !current_user_can('list_users')) {
            return new WP_Error('rest_forbidden', __('No permissions. These are not the droids you are looking for. Nothing here to see.', 'site_sections'), array('status' => rest_authorization_required_code()));
        }
    }

    return $result;
});

