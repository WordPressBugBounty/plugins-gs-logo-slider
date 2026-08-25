<?php
namespace GSLOGO;

/**
 * GS Logo Slider - Logo Details Layout
 * @author GS Plugins <hello@gsplugins.com>
 * 
 * This template can be overridden by copying it to yourtheme/gs-logo/partials/gs-logo-layout-details.php
 * 
 * @package GS_Logo_Slider/Templates
 * @version 1.0.0
 */

if ( ! logo_visibility_should_show( 'logo_content' ) ) {
    return;
}

?>

<div class="<?php echo esc_attr( logo_visibility_classes( 'logo_content', 'gs-logo-details justify' ) ); ?>"><?php the_content(); ?></div>