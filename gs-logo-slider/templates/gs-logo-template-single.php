<?php

namespace GSLOGO;
/**
 * GS Logo - Single Template 
 * @author GS Plugins <hello@gsplugins.com>
 * 
 * This template can be overridden by copying it to yourtheme/gs-logo/gs-logo-layout-single.php
 * 
 * @package GS_Logo/Templates
 * @version 1.0.0
 */

remove_action( 'genesis_sidebar', 'genesis_do_sidebar' );

get_header();

include Template_Loader::locate_template( 'partials/gs-logo-layout-single.php' );

get_footer();