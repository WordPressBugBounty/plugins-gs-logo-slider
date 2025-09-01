<?php
namespace GSLOGO;

/**
 * GS Logo Slider - Logo Details Layout 2
 * @author GS Plugins <hello@gsplugins.com>
 * 
 * This template can be overridden by copying it to yourtheme/gs-logo/partials/gs-logo-layout-details-2.php
 * 
 * @package GS_Logo_Slider/Templates
 * @version 1.0.0
 */

if( ! is_pro_active() ) return;

?>

<?php if( 'on' === $gs_l_show_content ): ?>

    <div class="gs-logo-details justify"><?php echo gs_logo_trim_content( get_the_content(), $gs_l_content_limit_count, $gs_l_content_limit_type, $gs_l_read_more_text ); ?></div>

<?php endif; ?>

<?php if( 'on' === $gs_l_show_excerpt ): ?>

    <div class="gs-logo-details justify"><?php echo gs_logo_trim_content( get_the_excerpt(), $gs_l_excerpt_limit_count, $gs_l_excerpt_limit_type, $gs_l_read_more_text ); ?></div>

<?php endif; ?>
