<?php
namespace GSLOGO;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function is_divi_active() {
    if (!defined('ET_BUILDER_PLUGIN_ACTIVE') || !ET_BUILDER_PLUGIN_ACTIVE) return false;
    return et_core_is_builder_used_on_current_request();
}

function is_divi_editor() {
    // Divi 4 computed property (legacy layouts during migration).
    if ( ! empty( $_POST['action'] ) && $_POST['action'] == 'et_pb_process_computed_property' && ! empty( $_POST['module_type'] ) && $_POST['module_type'] == 'gs_logo_slider' ) {
        return true;
    }

    // Divi 5 Visual Builder / Theme Builder.
    if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() && function_exists( 'et_builder_d5_enabled' ) && et_builder_d5_enabled() ) {
        return true;
    }

    // Divi 5 REST preview for GS Logo module.
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        $rest_route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';
        if ( $rest_route && false !== strpos( $rest_route, '/gs-logo/v1/divi' ) ) {
            return true;
        }
    }

    return false;
}

function is_pro_active() {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    return defined('GSL_PRO_VERSION') && is_plugin_active( GSL_PRO_PLUGIN );
}

function gs_echo_return($content, $echo = false) {

    if ($echo) {
        echo gs_wp_kses($content);
    } else {
        return $content;
    }
}

function minimize_css_simple($css) {
    // https://datayze.com/howto/minify-css-with-php
    $css = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $css); // negative look ahead
    $css = preg_replace('/\s{2,}/', ' ', $css);
    $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
    $css = preg_replace('/;}/', '}', $css);
    return $css;
}

function gs_wp_kses($content) {

    $allowed_tags = wp_kses_allowed_html('post');

    $input_common_atts = ['class' => true, 'id' => true, 'style' => true, 'novalidate' => true, 'name' => true, 'width' => true, 'height' => true, 'data' => true, 'title' => true, 'placeholder' => true, 'value' => true];

    $allowed_tags = array_merge_recursive($allowed_tags, [
        'select' => $input_common_atts,
        'input' => array_merge($input_common_atts, ['type' => true, 'checked' => true]),
        'option' => ['class' => true, 'id' => true, 'selected' => true, 'data' => true, 'value' => true]
    ]);

    return wp_kses(stripslashes_deep($content), $allowed_tags);
}

function gs_allowed_tags($tags) {
    return $tags;
}

function gs_validate_boolean( $var ) {

    if (empty($var)) return false;

    if (gettype($var) == 'string' && strtolower($var) == 'on') return true;
    if (gettype($var) == 'string' && strtolower($var) == 'off') return false;

    return wp_validate_boolean($var);
}

function get_gs_logo_query( $atts ) {

    $args = array_merge([
        'order'             => 'DESC',
        'orderby'           => 'date',
        'posts_per_page'    => -1,
        'meta_query'        => [],
        'tax_query'         => [],
        'paged'             => 1
    ], $atts);

    $args['post_type'] = 'gs-logo-slider';

    return new \WP_Query(apply_filters('gs_logo_wp_query_args', $args));
}

function gs_get_option( $option, $default = '' ) {

    $options = get_option('gs_logo_slider_shortcode_prefs');

    if (isset($options[$option])) {
        return $options[$option];
    }

    return $default;
}

function gs_get_meta_values( $meta_key = '', $post_type = 'gs-logo-slider', $status = 'publish', $order_by = true, $order = 'ASC' ) {

    global $wpdb;

    if (empty($meta_key)) return [];

    if ($order_by) {
        $order == 'ASC' ? $order : 'DESC';
        $order_by = sprintf('ORDER BY pm.meta_value %s', $order);
    } else {
        $order_by = '';
    }

    $result = $wpdb->get_col($wpdb->prepare("
        SELECT pm.meta_value FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = %s 
        AND p.post_status = %s 
        AND p.post_type = %s 
        {$order_by}
    ", $meta_key, $status, $post_type));

    return $result;
}

function gs_get_meta_values_options( $meta_key = '', $post_type = 'gs-logo-slider', $status = 'publish', $echo = true ) {

    $meta_values = gs_get_meta_values( $meta_key, $post_type, $status );

    $html = '';

    foreach ($meta_values as $meta_value) {
        $html .= sprintf('<option value=".%s">%s</option>', sanitize_title($meta_value), esc_html($meta_value));
    }

    return gs_echo_return( $html, $echo );

}

function gs_get_terms( $tax_name, $order = 'ASC', $orderby = 'name', $include = '', $exclude = '' ) {

    $terms = get_terms([
        'taxonomy' => $tax_name,
        'orderby'  => $orderby,
        'order'    => $order,
        'include'  => $include,
        'exclude'  => $exclude,
    ]);

    return wp_list_pluck($terms, 'name', 'slug');
}

function gs_get_terms_options( $term_name, $echo = true, $order = 'ASC', $orderby = 'name', $include = '', $exclude = '' ) {

    $terms = gs_get_terms( $term_name, $order, $orderby, $include, $exclude );
    
    $html = '';

    foreach ( $terms as $term_slug => $term_name ) {
        $html.= sprintf( '<option value=".%s">%s</option>', $term_slug, $term_name );
    }

    return gs_echo_return( $html, $echo );

}

function get_shortcodes() {
    return plugin()->builder->_get_shortcodes( null, false, true );
}

function is_preview() {
    return isset( $_REQUEST['gslogo_shortcode_preview'] ) && !empty($_REQUEST['gslogo_shortcode_preview']);
}

// GET FEATURED IMAGE
function gs_get_featured_image( $post_ID ) {
    $post_thumbnail_id = get_post_thumbnail_id( $post_ID );
    if ( $post_thumbnail_id ) {
        $post_thumbnail_img = wp_get_attachment_image_src( $post_thumbnail_id, 'medium' );
        return $post_thumbnail_img[0];
    }
}

function gs_update_plugin_version() {
    $option_key = 'gs_logo_slider_version';
    $old_version = get_option($option_key);

    if ( GSL_VERSION !==  $old_version ) {
        update_option( $option_key, GSL_VERSION );

        plugin()->builder->maybe_upgrade_data($old_version);
        return true;
    }
    return false;
}

/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function gs_appsero_init() {

    if ( !class_exists('GSLogoAppSero\Insights') ) {
        require_once GSL_PLUGIN_DIR . 'includes/appsero/Client.php';
    }

    $client = new \GSLogoAppSero\Client('2f95117b-b1c6-4486-88c0-6b6d815856bf', 'GS Logo Slider', __FILE__);
    // Active insights
    $client->insights()->init();
}

function get_item_terms_slugs( $term_name, $separator = ' ' ) {

    global $post;

    $terms = get_the_terms( $post->ID, $term_name );

    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        $terms = implode( $separator, wp_list_pluck( $terms, 'slug' ) );
        return $terms;
    }

    return $terms;

}

function gs_str_replace_first($search, $replace, $subject) {
    $search = '/'.preg_quote($search, '/').'/';
    return preg_replace($search, $replace, $subject, 1);
}

function change_key($settings, $old_key, $new_key) {

    if (!array_key_exists($old_key, $settings)) return $settings;

    $settings[$new_key] = $settings[$old_key];
    unset($settings[$old_key]);

    return $settings;
}

/**
 * Upgrade notice if compatibility fails
 */
function pro_compatibility_notice() {

    $screen = get_current_screen();
    
    if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) return;
    if ( 'update' === $screen->base && 'update' === $screen->id ) return;

    if ( ! current_user_can( 'update_plugins' ) ) return;

    $upgrade_url = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . GSL_PRO_PLUGIN ), 'upgrade-plugin_' . GSL_PRO_PLUGIN );
    $message = '<p>' . __( 'GS Logo Slider is not working because you need to upgrade the GS Logo Slider Pro plugin to latest version.', 'gslogo' ) . '</p>';
    $message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $upgrade_url, __( 'Upgrade GS Logo Slider Pro Now', 'gslogo' ) ) . '</p>';

    echo '<div class="error"><p>' . $message . '</p></div>';
    
}

/**
 * Compatibility check with Pro plugin
 */
function is_pro_compatible() {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    if ( defined('GSL_PRO_VERSION') && is_plugin_active( GSL_PRO_PLUGIN ) ) {
        if ( version_compare( GSL_PRO_VERSION, GSL_MIN_PRO_VERSION, '<' ) ) {
            add_action( 'admin_notices', 'GSLOGO\pro_compatibility_notice' );
            return false;
        }
    }
    return true;
}

/**
 * Activation redirects
 */
function on_activation() {
    add_option('gslogo_activation_redirect', true);
}

/**
 * Remove Reviews Metadata on plugin Deactivation.
 */
function on_deactivation() {
    delete_option('gslogo_active_time');
    delete_option('gslogo_maybe_later');
    delete_option('gsadmin_maybe_later');
    delete_option('gslogo_review_dismiss');
    delete_user_meta( get_current_user_id(), 'gslogo_ignore_notice279' );
}

/**
 * Plugins action links
 */
function add_pro_link( $links ) {
    if ( ! is_pro_active() ) {
        $links[] = '<a style="color: red; font-weight: bold;" class="gs-pro-link" href="https://www.gsplugins.com/product/gs-logo-slider" target="_blank">Go Pro!</a>';
    }
    $links[] = '<a href="https://www.gsplugins.com/wordpress-plugins" target="_blank">GS Plugins</a>';
    return $links;
}

/**
 * Plugins Load Text Domain
 */
function gs_load_textdomain() {
    load_plugin_textdomain( 'gslogo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

function is_gs_logo_pro_valid() {
    if ( ! function_exists( 'gs_logo_pro_is_valid' ) ) {
        return false;
    }
    return gs_logo_pro_is_valid();
}

function get_current_full_url() {
    $protocol = is_ssl() ? 'https://' : 'http://';
    $host     = $_SERVER['HTTP_HOST'];
    $request  = $_SERVER['REQUEST_URI'];
    return $protocol . $host . $request;
}

function get_ajax_pagination( $shortcode_id, $items_per_page = 6, $paged = 1 ) {

    // Generate page parameter name
    $param_name = 'paged' . $shortcode_id;
    
    // Current Page Number
    $current = max( 1, $paged ?? 1 );

    // Calculate total pages
    $total_pages = ceil( $GLOBALS['gs_logo_loop']->found_posts / $items_per_page );

    // Generate the current URL with the page placeholder
    $current_url = get_current_full_url();
    $current_url = remove_query_arg( $param_name, $current_url );
    $current_url = add_query_arg( $param_name, '%#%', $current_url );

    $prev_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 256 512"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 214.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"/></svg>';
    $next_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 256 512"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M247.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L179.2 256 41.9 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z"/></svg>';
    
    
    // Print the pagination links
    $pagination = "<div class='gs-logo-pagination gs-logo-ajax-pagination-link'>";
    $pagination .= paginate_links( array(
        'base' => $current_url,
        'current' => $current,
        'total' => $total_pages,
        'prev_next' => true,
        'next_text' => $next_icon,
        'prev_text' => $prev_icon
    ));
    $pagination .= "</div>";

    return $pagination;
}

function is_grid_theme( $theme ){
    return in_array( $theme, array( 'grid1', 'grid2', 'grid3', 'rounded-border' ) );
}

function is_list_theme( $theme ){
    return in_array( $theme, array( 'list1', 'list2', 'list3', 'list4' ) );
}

/**
 * Formation structure options (matches metabox).
 *
 * @return array<string, string>
 */
function gs_logo_get_formation_structures() {
	return apply_filters( 'gs_logo_formation_structures', [
		'sole_proprietorship' => __( 'Sole Proprietorship', 'gslogo' ),
		'partnership'         => __( 'Partnership', 'gslogo' ),
		'llc'                 => __( 'LLC', 'gslogo' ),
		'corporation'         => __( 'Corporation', 'gslogo' ),
		'non_profit'          => __( 'Non-Profit', 'gslogo' ),
		'government'          => __( 'Government', 'gslogo' ),
		'cooperative'         => __( 'Cooperative', 'gslogo' ),
		'other'               => __( 'Other', 'gslogo' ),
	] );
}

/**
 * Funding type options (matches metabox).
 *
 * @return array<string, string>
 */
function gs_logo_get_funding_types() {
	return apply_filters( 'gs_logo_funding_types', [
		'angel'          => __( 'Angel', 'gslogo' ),
		'seed'           => __( 'Seed', 'gslogo' ),
		'series_a'       => __( 'Series A', 'gslogo' ),
		'series_b'       => __( 'Series B', 'gslogo' ),
		'series_c'       => __( 'Series C', 'gslogo' ),
		'series_d'       => __( 'Series D+', 'gslogo' ),
		'bootstrapped'   => __( 'Bootstrapped', 'gslogo' ),
		'debt_financing' => __( 'Debt Financing', 'gslogo' ),
		'grant'          => __( 'Grant', 'gslogo' ),
		'crowdfunding'   => __( 'Crowdfunding', 'gslogo' ),
		'private_equity' => __( 'Private Equity', 'gslogo' ),
		'other'          => __( 'Other', 'gslogo' ),
	] );
}

/**
 * @param string $key Formation structure key.
 * @return string
 */
function gs_logo_get_formation_structure_label( $key ) {
	$structures = gs_logo_get_formation_structures();
	return isset( $structures[ $key ] ) ? $structures[ $key ] : '';
}

/**
 * @param string $key Funding type key.
 * @return string
 */
function gs_logo_get_funding_type_label( $key ) {
	$types = gs_logo_get_funding_types();
	return isset( $types[ $key ] ) ? $types[ $key ] : '';
}

/**
 * Social platform labels for single templates.
 *
 * @return array<string, string>
 */
function gs_logo_get_social_platform_labels() {
	return apply_filters( 'gs_logo_social_platform_labels', [
		'facebook'   => __( 'Facebook', 'gslogo' ),
		'twitter'    => __( 'X / Twitter', 'gslogo' ),
		'instagram'  => __( 'Instagram', 'gslogo' ),
		'linkedin'   => __( 'LinkedIn', 'gslogo' ),
		'youtube'    => __( 'YouTube', 'gslogo' ),
		'pinterest'  => __( 'Pinterest', 'gslogo' ),
		'github'     => __( 'GitHub', 'gslogo' ),
		'tumblr'     => __( 'Tumblr', 'gslogo' ),
		'vimeo'      => __( 'Vimeo', 'gslogo' ),
		'whatsapp'   => __( 'WhatsApp', 'gslogo' ),
		'reddit'     => __( 'Reddit', 'gslogo' ),
		'skype'      => __( 'Skype', 'gslogo' ),
		'soundcloud' => __( 'SoundCloud', 'gslogo' ),
		'dribbble'   => __( 'Dribbble', 'gslogo' ),
		'behance'    => __( 'Behance', 'gslogo' ),
		'website'    => __( 'Website / Other', 'gslogo' ),
	] );
}

/**
 * Map social icon keys to inline SVG sprite IDs.
 *
 * @param string $icon_key Platform key.
 * @return string
 */
function gs_logo_single_social_sprite_id( $icon_key ) {
	$icon_key = sanitize_key( (string) $icon_key );

	$map = [
		'facebook'   => 'gs-i-facebook',
		'twitter'    => 'gs-i-twitter',
		'x'          => 'gs-i-twitter',
		'instagram'  => 'gs-i-instagram',
		'linkedin'   => 'gs-i-linkedin',
		'youtube'    => 'gs-i-youtube',
		'pinterest'  => 'gs-i-pinterest',
		'github'     => 'gs-i-github',
		'tumblr'     => 'gs-i-tumblr',
		'vimeo'      => 'gs-i-vimeo',
		'whatsapp'   => 'gs-i-whatsapp',
		'reddit'     => 'gs-i-reddit',
		'skype'      => 'gs-i-skype',
		'soundcloud' => 'gs-i-soundcloud',
		'dribbble'   => 'gs-i-dribbble',
		'behance'    => 'gs-i-behance',
		'website'    => 'gs-i-globe',
	];

	return isset( $map[ $icon_key ] ) ? $map[ $icon_key ] : 'gs-i-globe';
}

/**
 * Map social icon keys to brand modifier class names for template styling.
 *
 * @param string $icon_key Platform key.
 * @return string
 */
function gs_logo_single_social_brand_class( $icon_key ) {
	$icon_key = sanitize_key( (string) $icon_key );

	$map = [
		'facebook'   => 'facebook',
		'twitter'    => 'twitter',
		'x'          => 'twitter',
		'instagram'  => 'instagram',
		'linkedin'   => 'linkedin',
		'youtube'    => 'youtube',
		'pinterest'  => 'pinterest',
		'github'     => 'github',
		'tumblr'     => 'tumblr',
		'vimeo'      => 'vimeo',
		'whatsapp'   => 'whatsapp',
		'reddit'     => 'reddit',
		'skype'      => 'skype',
		'soundcloud' => 'soundcloud',
		'dribbble'   => 'dribbble',
		'behance'    => 'behance',
		'website'    => 'default',
	];

	return isset( $map[ $icon_key ] ) ? $map[ $icon_key ] : 'default';
}

/**
 * Whether a social sprite uses filled paths (vs stroke icons like globe/link).
 *
 * @param string $sprite_id Sprite symbol id.
 * @return bool
 */
function gs_logo_single_social_is_brand_sprite( $sprite_id ) {
	$sprite_id = sanitize_key( ltrim( (string) $sprite_id, '#' ) );

	return ! in_array( $sprite_id, [ 'gs-i-globe', 'gs-i-link' ], true );
}

/**
 * Parse a YouTube or Vimeo URL for single-page video blocks.
 *
 * @param string $url Video URL.
 * @return array|null
 */
/**
 * Build taxonomy columns for single logo templates.
 *
 * @param int $post_id Logo post ID.
 * @return array<int, array<string, mixed>>
 */
function gs_logo_get_single_taxonomy_columns( $post_id ) {
	$taxonomy_columns = [];
	$taxonomy_map     = [
		'logo-category'    => [ 'empty_icon' => 'gs-i-building' ],
		'logo-tag'         => [ 'empty_icon' => 'gs-i-link' ],
		'logo-extra-one'   => [ 'empty_icon' => 'gs-i-building' ],
		'logo-extra-two'   => [ 'empty_icon' => 'gs-i-building' ],
		'logo-extra-three' => [ 'empty_icon' => 'gs-i-building' ],
		'logo-extra-four'  => [ 'empty_icon' => 'gs-i-building' ],
		'logo-extra-five'  => [ 'empty_icon' => 'gs-i-link' ],
	];

	foreach ( $taxonomy_map as $taxonomy_slug => $taxonomy_meta ) {
		if ( ! taxonomy_exists( $taxonomy_slug ) ) {
			continue;
		}

		$tax_object = get_taxonomy( $taxonomy_slug );
		$terms      = get_the_terms( $post_id, $taxonomy_slug );

		if ( is_wp_error( $terms ) ) {
			$terms = [];
		}

		$taxonomy_columns[] = [
			'slug'       => $taxonomy_slug,
			'label'      => $tax_object && ! empty( $tax_object->labels->name ) ? $tax_object->labels->name : $taxonomy_slug,
			'singular'   => $tax_object && ! empty( $tax_object->labels->singular_name ) ? $tax_object->labels->singular_name : $taxonomy_slug,
			'terms'      => $terms,
			'empty_icon' => $taxonomy_meta['empty_icon'],
			'is_tag'     => ( 'logo-tag' === $taxonomy_slug || 0 === strpos( $taxonomy_slug, 'logo-extra-' ) ),
		];
	}

	return $taxonomy_columns;
}

function gs_logo_get_video_embed_data( $url ) {

	$url = trim( (string) $url );

	if ( '' === $url ) {
		return null;
	}

	if ( preg_match( '#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([a-zA-Z0-9_-]{6,})#i', $url, $matches ) ) {
		$video_id = $matches[1];
		return [
			'provider'    => 'youtube',
			'id'          => $video_id,
			'watch_url'   => $url,
			'thumbnail'   => sprintf( 'https://img.youtube.com/vi/%s/hqdefault.jpg', $video_id ),
			'embed_url'   => sprintf( 'https://www.youtube.com/embed/%s', $video_id ),
			'watch_label' => __( 'Watch on YouTube', 'gslogo' ),
		];
	}

	if ( preg_match( '#vimeo\.com/(?:video/)?(\d+)#i', $url, $matches ) ) {
		$video_id = $matches[1];
		return [
			'provider'    => 'vimeo',
			'id'          => $video_id,
			'watch_url'   => $url,
			'thumbnail'   => '',
			'embed_url'   => sprintf( 'https://player.vimeo.com/video/%s', $video_id ),
			'watch_label' => __( 'Watch on Vimeo', 'gslogo' ),
		];
	}

	return null;
}

function get_term_ids_by_slugs( $slugs = [], $taxonomy = '' ) {

    if ( empty( $slugs ) || empty( $taxonomy ) ) {
        return [];
    }

    global $wpdb;

    $slugs = array_map( 'sanitize_title', (array) $slugs );
    
    $placeholders = implode( ',', array_fill( 0, count( $slugs ), '%s' ) );

    $query = $wpdb->prepare(
        "SELECT t.term_id 
         FROM {$wpdb->terms} AS t
         INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
         WHERE tt.taxonomy = %s
         AND t.slug IN ($placeholders)",
        array_merge( [ $taxonomy ], $slugs )
    );

    return $wpdb->get_col( $query );

}

function get_logo_visibility_field( $group, $field_key ) {

	$defaults = [
		'desktop'          => true,
		'tablet'           => true,
		'mobile_landscape' => true,
		'mobile'           => true,
	];

	$visibility_settings = isset( $GLOBALS['gs_logo_visibility_settings'] ) ? $GLOBALS['gs_logo_visibility_settings'] : null;

	if ( empty( $visibility_settings ) && isset( $GLOBALS['gs_logo_shortcode_settings']['visibility_settings'] ) ) {
		$visibility_settings = $GLOBALS['gs_logo_shortcode_settings']['visibility_settings'];
	}

	if ( empty( $visibility_settings ) || ! is_array( $visibility_settings ) ) {
		return $defaults;
	}

	if ( ! isset( $visibility_settings[ $group ][ $field_key ] ) || ! is_array( $visibility_settings[ $group ][ $field_key ] ) ) {
		return $defaults;
	}

	return wp_parse_args( $visibility_settings[ $group ][ $field_key ], $defaults );
}

function get_current_visibility_group() {

	if ( ! empty( $GLOBALS['gs_logo_visibility_group'] ) ) {
		return $GLOBALS['gs_logo_visibility_group'];
	}

	return 'initial';
}

function is_visible( $field, $device = '' ) {

	if ( ! is_array( $field ) ) {
		return true;
	}

	if ( empty( $device ) ) {
		return wp_validate_boolean( $field['desktop'] ?? false )
			|| wp_validate_boolean( $field['tablet'] ?? false )
			|| wp_validate_boolean( $field['mobile_landscape'] ?? false )
			|| wp_validate_boolean( $field['mobile'] ?? false );
	}

	if ( in_array( $device, [ 'desktop', 'tablet', 'mobile_landscape', 'mobile' ], true ) ) {
		return isset( $field[ $device ] ) ? wp_validate_boolean( $field[ $device ] ) : false;
	}

	return false;
}

function get_visible_classes( $field, $additional_class = '' ) {

	$devices = [
		'desktop'          => 'gs-logo--hide-md',
		'tablet'           => 'gs-logo--hide-sm',
		'mobile_landscape' => 'gs-logo--hide-xs',
		'mobile'           => 'gs-logo--hide-xxs',
	];

	$classes = [];

	if ( ! empty( $additional_class ) ) {
		$classes[] = $additional_class;
	}

	if ( ! is_array( $field ) ) {
		return $classes;
	}

	foreach ( $devices as $device => $class ) {
		if ( ! is_visible( $field, $device ) ) {
			$classes[] = $class;
		}
	}

	return $classes;
}

function print_visible_classes( $field, $additional_class = '' ) {
	$classes = get_visible_classes( $field, $additional_class );
	echo esc_attr( implode( ' ', $classes ) );
}

function logo_visibility_should_show( $field_key, $group = null ) {

	if ( empty( $group ) ) {
		$group = get_current_visibility_group();
	}

	return is_visible( get_logo_visibility_field( $group, $field_key ) );
}

function logo_visibility_classes( $field_key, $additional_class = '', $group = null ) {

	if ( empty( $group ) ) {
		$group = get_current_visibility_group();
	}

	return implode( ' ', get_visible_classes( get_logo_visibility_field( $group, $field_key ), $additional_class ) );
}

/**
 * Visibility classes for a table column that represents multiple fields.
 * A device hide class is added only when every field is hidden on that device.
 */
function logo_visibility_classes_any( $field_keys, $additional_class = '', $group = null ) {

	if ( empty( $group ) ) {
		$group = get_current_visibility_group();
	}

	$devices = [
		'desktop'          => 'gs-logo--hide-md',
		'tablet'           => 'gs-logo--hide-sm',
		'mobile_landscape' => 'gs-logo--hide-xs',
		'mobile'           => 'gs-logo--hide-xxs',
	];

	$classes = [];

	if ( ! empty( $additional_class ) ) {
		$classes[] = $additional_class;
	}

	foreach ( $devices as $device => $class ) {
		$any_visible = false;

		foreach ( (array) $field_keys as $field_key ) {
			if ( is_visible( get_logo_visibility_field( $group, $field_key ), $device ) ) {
				$any_visible = true;
				break;
			}
		}

		if ( ! $any_visible ) {
			$classes[] = $class;
		}
	}

	return implode( ' ', $classes );
}

function logo_visibility_empty_if_hidden( $value, $field_key, $empty = '' ) {

	if ( logo_visibility_should_show( $field_key ) ) {
		return $value;
	}

	return $empty;
}

function logo_visibility_filter_rows( $rows ) {

	$filtered = [];

	foreach ( (array) $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$key = isset( $row['visibility_key'] ) ? $row['visibility_key'] : '';

		if ( $key && ! logo_visibility_should_show( $key ) ) {
			continue;
		}

		$filtered[] = $row;
	}

	return $filtered;
}
