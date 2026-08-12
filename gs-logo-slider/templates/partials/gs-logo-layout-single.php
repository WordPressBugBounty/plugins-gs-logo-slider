<?php

namespace GSLOGO;

$single_page_style = gs_get_option( 'single_page_style', 'style-01' );

$single_page_style = apply_filters( 'gs_logo_single_page_style', $single_page_style );

if ( ! is_pro_active() || ! is_gs_logo_pro_valid() ) {
	$single_page_style = 'style-01';
}

?>

<div class="gs-containeer gs-single-container <?php echo 'gs-single-' . esc_attr($single_page_style); ?>">
	
	<div class="gs_logo" id="gs_logo_single">

		<?php
		
		while ( have_posts() ) : the_post();

			if ( ! is_pro_active() || ! is_gs_logo_pro_valid() ) {
				include Template_Loader::locate_template( 'singles/gs-logo-single-style-one.php' );
				break;
			}

			switch ( $single_page_style ) {

				case 'style-01': {
					include Template_Loader::locate_template( 'singles/gs-logo-single-style-one.php' );
					break;
				}

				case 'style-02': {
					include Template_Loader::locate_template( 'singles/gs-logo-single-style-two.php' );
					break;
				}

				case 'style-03': {
					include Template_Loader::locate_template( 'singles/gs-logo-single-style-three.php' );
					break;
				}

				case 'style-04': {
					include Template_Loader::locate_template( 'singles/gs-logo-single-style-four.php' );
					break;
				}

				case 'style-05': {
					include Template_Loader::locate_template( 'singles/gs-logo-single-style-five.php' );
					break;
				}

				default: {
					include Template_Loader::locate_template( 'singles/gs-logo-single-style-one.php' );
				}

			}

		endwhile;

		// include Template_Loader::locate_template( 'partials/gs-logo-layout-navigation.php' ); ?>

	</div>
    
</div>