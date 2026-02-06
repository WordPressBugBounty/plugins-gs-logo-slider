<?php
namespace GSLOGO;

/**
 * GS Logo Slider - Grid Layout 1
 * @author GS Plugins <hello@gsplugins.com>
 * 
 * This template can be overridden by copying it to yourtheme/gs-logo/gs-logo-theme-grid-1.php
 * 
 * @package GS_Logo_Slider/Templates
 * @version 1.0.0
 */

global $gs_logo_loop;

?>

<div class="gs_logo_container gs_logo_container_grid gs_logo_fix_height_and_center <?php echo ( $filter_enabled === 'on' ) ? 'gs-logos-filter-wrapper' : '' ?>" style="justify-content:<?php echo esc_attr($gs_l_align); ?>">

	<?php if ( $gs_logo_loop->have_posts() ) : ?>

		<?php while ( $gs_logo_loop->have_posts() ) : $gs_logo_loop->the_post(); ?>

			<?php
				$single_wrapper_filter_classes = 'gs-filter-single-item ' . \GSLOGO\get_item_terms_slugs( 'logo-category', ' ' );
			?>

			<div class="gs_logo_single--wrapper <?php echo ( $filter_enabled === 'on' ) ? esc_attr( $single_wrapper_filter_classes ) : '' ?>">
				<div class="gs_logo_single">
					
					<!-- Logo Image -->
					<?php include Template_Loader::locate_template( 'partials/gs-logo-layout-image.php' ); ?>

					<!-- Logo Title -->
					<?php include Template_Loader::locate_template( 'partials/gs-logo-layout-title.php' ); ?>

					<!-- Logo Category -->
					<?php include Template_Loader::locate_template( 'partials/gs-logo-layout-cat.php' ); ?>

					<!-- Logo Details -->
					<?php
						if( is_pro_active() && is_gs_logo_pro_valid() ) {
							include Template_Loader::locate_template( 'partials/gs-logo-layout-details-2.php' );
						} else {
							include Template_Loader::locate_template( 'partials/gs-logo-layout-details.php' );
						}
					?>

				</div>
			</div>

		<?php endwhile; ?>
		
	<?php else: ?>

		<?php include Template_Loader::locate_template( 'partials/gs-logo-empty.php' ); ?>
		
	<?php endif; ?>

</div>