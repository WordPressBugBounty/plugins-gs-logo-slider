<?php
namespace GSLOGO;

/**
 * GS Logo Slider - Logo Title Layout
 * @author GS Plugins <hello@gsplugins.com>
 * 
 * This template can be overridden by copying it to yourtheme/gs-logo/partials/gs-logo-layout-title.php
 * 
 * @package GS_Logo_Slider/Templates
 * @version 1.0.0
 */

if ( ! logo_visibility_should_show( 'logo_categories' ) ) {
    return;
}

    $allowed_tags = ['h3', 'h4', 'h5', 'h6', 'span', 'div', 'p'];
    $logo_cat_tag = (string) apply_filters( 'gs_logo_category_tag', 'div' );
    
    if ( ! in_array( $logo_cat_tag, $allowed_tags ) ) {
        $logo_cat_tag = 'div';
    }

    $cats = get_the_terms( get_the_ID(), 'logo-category' );

    $cats = wp_list_pluck( $cats, 'name' );

    printf( '<%1$s class="%2$s">%3$s</%1$s>', $logo_cat_tag, esc_attr( logo_visibility_classes( 'logo_categories', 'gs_logo_cats' ) ), join(', ', $cats) );