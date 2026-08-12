<?php
namespace GSLOGO;

/**
 * GS Logo - Single Template Style One
 *
 * @package GS_Logo/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();

$client_url          = get_post_meta( $post_id, 'client_url', true );
$contact_email       = get_post_meta( $post_id, 'gs_logo_contact_email', true );
$address             = get_post_meta( $post_id, 'gs_logo_address', true );
$vision              = get_post_meta( $post_id, 'gs_logo_vision', true );
$mission             = get_post_meta( $post_id, 'gs_logo_mission', true );
$established_on      = get_post_meta( $post_id, 'gs_logo_established_on', true );
$num_employees       = get_post_meta( $post_id, 'gs_logo_num_employees', true );
$formation_structure = get_post_meta( $post_id, 'gs_logo_formation_structure', true );
$client_since        = get_post_meta( $post_id, 'gs_logo_client_since', true );
$map_embed           = get_post_meta( $post_id, 'gs_logo_map_embed', true );
$video_embed_url     = get_post_meta( $post_id, 'gs_logo_video_embed_url', true );
$funding_type        = get_post_meta( $post_id, 'gs_logo_funding_type', true );
$funding_source      = get_post_meta( $post_id, 'gs_logo_funding_source', true );
$social_links        = get_post_meta( $post_id, 'gs_logo_social_links', true );
$life_gallery        = get_post_meta( $post_id, 'gs_logo_life_gallery', true );
$pitch_deck_id       = (int) get_post_meta( $post_id, 'gs_logo_pitch_deck_id', true );

$social_links  = is_array( $social_links ) ? $social_links : [];
$life_gallery  = is_array( $life_gallery ) ? array_filter( array_map( 'absint', $life_gallery ) ) : [];
$video_data    = gs_logo_get_video_embed_data( $video_embed_url );
$formation_label = gs_logo_get_formation_structure_label( $formation_structure );
$funding_label   = gs_logo_get_funding_type_label( $funding_type );

$hero_intro = has_excerpt( $post_id )
	? get_the_excerpt( $post_id )
	: wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 40, '…' );

$about_content = apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) );

$facts = [];

if ( $established_on ) {
	$facts[] = [
		'icon'  => 'gs-i-calendar',
		'label' => __( 'Established On', 'gslogo' ),
		'value' => $established_on,
	];
}

if ( $num_employees ) {
	$facts[] = [
		'icon'  => 'gs-i-users',
		'label' => __( 'Employees', 'gslogo' ),
		'value' => $num_employees,
	];
}

if ( $formation_label ) {
	$facts[] = [
		'icon'  => 'gs-i-building',
		'label' => __( 'Formation Structure', 'gslogo' ),
		'value' => $formation_label,
	];
}

if ( $client_since ) {
	$facts[] = [
		'icon'  => 'gs-i-user-check',
		'label' => __( 'Client Since', 'gslogo' ),
		'value' => $client_since,
	];
}

if ( $funding_label ) {
	$facts[] = [
		'icon'  => 'gs-i-send',
		'label' => __( 'Funding Type', 'gslogo' ),
		'value' => $funding_label,
	];
}

if ( $funding_source ) {
	$facts[] = [
		'icon'  => 'gs-i-info',
		'label' => __( 'Funding Source', 'gslogo' ),
		'value' => $funding_source,
	];
}

$facts = apply_filters( 'gs_logo_single_style_one_facts', $facts, $post_id );

$has_vision_mission = ( $vision || $mission );
$vm_count           = (int) (bool) $vision + (int) (bool) $mission;
$has_map            = ! empty( $map_embed );
$has_video          = ! empty( $video_data );
$has_media_row      = $has_map || $has_video;
$media_count        = (int) $has_map + (int) $has_video;

$gallery_images = [];

foreach ( $life_gallery as $attachment_id ) {
	if ( ! wp_attachment_is_image( $attachment_id ) ) {
		continue;
	}
	$gallery_images[] = $attachment_id;
}

$social_items = [];
$labels       = gs_logo_get_social_platform_labels();

foreach ( $social_links as $link ) {
	$url = isset( $link['url'] ) ? trim( (string) $link['url'] ) : '';
	if ( '' === $url ) {
		continue;
	}
	$icon_key = isset( $link['icon'] ) ? sanitize_key( $link['icon'] ) : 'website';
	$name     = isset( $link['name'] ) ? trim( (string) $link['name'] ) : '';
	if ( '' === $name ) {
		$name = isset( $labels[ $icon_key ] ) ? $labels[ $icon_key ] : __( 'Website / Other', 'gslogo' );
	}
	$social_items[] = [
		'icon'  => $icon_key,
		'name'  => $name,
		'url'   => $url,
		'sprite'=> gs_logo_single_social_sprite_id( $icon_key ),
	];
}

$taxonomy_columns = gs_logo_get_single_taxonomy_columns( $post_id );

$has_contact_sidebar = ( $client_url || $contact_email || $address );
$has_tax_sidebar     = ! empty( $taxonomy_columns );
$has_social_sidebar  = ! empty( $social_items );
$has_sidebar         = ( $has_contact_sidebar || $has_tax_sidebar || $has_social_sidebar );

$pitch_deck_url   = '';
$pitch_deck_title = '';
$pitch_deck_size  = '';

if ( $pitch_deck_id > 0 ) {
	$pitch_deck_url   = wp_get_attachment_url( $pitch_deck_id );
	$pitch_deck_title = get_the_title( $pitch_deck_id );
	$file_path        = get_attached_file( $pitch_deck_id );
	if ( $file_path && file_exists( $file_path ) ) {
		$pitch_deck_size = size_format( filesize( $file_path ) );
	}
}

include Template_Loader::locate_template( 'partials/gs-logo-single-icons.php' );
?>

<main class="gs-logo-page">

	<section class="gs-hero gs-single-reveal">
		<div class="gs-logo-card gs-hero-logo">
			<?php if ( has_post_thumbnail( $post_id ) ) : ?>
				<?php echo get_the_post_thumbnail( $post_id, 'large', [ 'class' => 'gs-hero-logo-img', 'alt' => esc_attr( get_the_title( $post_id ) ) ] ); ?>
			<?php endif; ?>
		</div>

		<div class="gs-hero-content">

			<h1><?php the_title(); ?></h1>

			<?php if ( $hero_intro ) : ?>
				<p><?php echo esc_html( $hero_intro ); ?></p>
			<?php endif; ?>

			<?php if ( $client_url || $contact_email ) : ?>
				<div class="gs-actions">
					<?php if ( $client_url ) : ?>
						<a class="gs-btn gs-btn-primary" href="<?php echo esc_url( $client_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Visit Website', 'gslogo' ); ?>
							<svg class="gs-btn-icon" aria-hidden="true"><use href="#gs-i-external"></use></svg>
						</a>
					<?php endif; ?>
					<?php if ( $contact_email ) : ?>
						<a class="gs-btn gs-btn-secondary" href="<?php echo esc_url( 'mailto:' . antispambot( $contact_email ) ); ?>">
							<?php esc_html_e( 'Contact Company', 'gslogo' ); ?>
							<svg class="gs-btn-icon" aria-hidden="true"><use href="#gs-i-mail"></use></svg>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( ! empty( $facts ) ) : ?>
		<section class="gs-logo-card gs-facts gs-single-reveal" aria-label="<?php esc_attr_e( 'Company facts', 'gslogo' ); ?>">
			<?php foreach ( $facts as $fact ) : ?>
				<div class="gs-fact">
					<span class="gs-icon">
						<svg aria-hidden="true"><use href="#<?php echo esc_attr( $fact['icon'] ); ?>"></use></svg>
					</span>
					<div>
						<span><?php echo esc_html( $fact['label'] ); ?></span>
						<strong><?php echo esc_html( $fact['value'] ); ?></strong>
					</div>
				</div>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>

	<div class="gs-layout<?php echo $has_sidebar ? '' : ' gs-layout--full'; ?>">

		<div class="gs-main">

			<?php if ( $about_content ) : ?>
				<section class="gs-logo-card gs-section gs-single-reveal">
					<span class="gs-dot-pattern" aria-hidden="true"></span>
					<h2><?php esc_html_e( 'About Company', 'gslogo' ); ?></h2>
					<div class="gs-about-content">
						<?php echo $about_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered content ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $has_vision_mission ) : ?>
				<section class="gs-logo-card gs-section gs-vm-grid<?php echo 1 === $vm_count ? ' gs-vm-grid--single' : ''; ?> gs-single-reveal">
					<?php if ( $vision ) : ?>
						<div class="gs-vm-item">
							<span class="gs-icon"><svg aria-hidden="true"><use href="#gs-i-eye"></use></svg></span>
							<div>
								<h3><?php esc_html_e( 'Our Vision', 'gslogo' ); ?></h3>
								<div class="gs-vm-body"><?php echo wp_kses_post( $vision ); ?></div>
							</div>
						</div>
					<?php endif; ?>
					<?php if ( $mission ) : ?>
						<div class="gs-vm-item">
							<span class="gs-icon green"><svg aria-hidden="true"><use href="#gs-i-target"></use></svg></span>
							<div>
								<h3><?php esc_html_e( 'Our Mission', 'gslogo' ); ?></h3>
								<div class="gs-vm-body"><?php echo wp_kses_post( $mission ); ?></div>
							</div>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $gallery_images ) ) : ?>
				<section class="gs-logo-card gs-section gs-gallery-section gs-single-reveal">
					<div class="gs-gallery-header">
						<h2><?php esc_html_e( 'Life in Company', 'gslogo' ); ?></h2>
						<p><?php esc_html_e( 'Team photos, office shots, and other life-at-company images.', 'gslogo' ); ?></p>
					</div>
					<div class="gs-gallery">
						<?php foreach ( $gallery_images as $attachment_id ) :
							$full_image    = wp_get_attachment_image_src( $attachment_id, 'full' );
							$image_alt     = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
							$image_caption = wp_get_attachment_caption( $attachment_id );
							if ( empty( $full_image[0] ) ) {
								continue;
							}
							?>
							<figure>
								<a
									class="gs-gallery-link"
									href="<?php echo esc_url( $full_image[0] ); ?>"
									data-caption="<?php echo esc_attr( $image_caption ); ?>"
								>
									<?php
									echo wp_get_attachment_image(
										$attachment_id,
										'medium_large',
										false,
										[
											'loading' => 'lazy',
											'alt'     => $image_alt ? $image_alt : get_the_title( $attachment_id ),
										]
									);
									?>
								</a>
							</figure>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $has_media_row ) : ?>
				<section class="gs-logo-card gs-section gs-single-reveal">
					<h2><?php esc_html_e( 'Location & Video', 'gslogo' ); ?></h2>
					<div class="gs-media-grid<?php echo 1 === $media_count ? ' gs-media-grid--single' : ''; ?>">
						<?php if ( $has_map ) : ?>
							<div class="gs-media-box">
								<h3><?php esc_html_e( 'Map Location', 'gslogo' ); ?></h3>
								<div class="gs-map gs-map--embed">
									<?php
									echo wp_kses(
										$map_embed,
										[
											'iframe' => [
												'src'             => true,
												'width'           => true,
												'height'          => true,
												'frameborder'     => true,
												'style'           => true,
												'allow'           => true,
												'allowfullscreen' => true,
												'loading'         => true,
												'referrerpolicy'  => true,
												'title'           => true,
											],
										]
									);
									?>
								</div>
							</div>
						<?php endif; ?>
						<?php if ( $has_video ) : ?>
							<div class="gs-media-box">
								<h3><?php esc_html_e( 'Company Video', 'gslogo' ); ?></h3>
								<div class="gs-video-embed">
									<iframe
										src="<?php echo esc_url( $video_data['embed_url'] ); ?>"
										title="<?php echo esc_attr( sprintf( __( '%s company video', 'gslogo' ), get_the_title( $post_id ) ) ); ?>"
										allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
										allowfullscreen
										loading="lazy"
										referrerpolicy="strict-origin-when-cross-origin"
									></iframe>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $pitch_deck_url ) : ?>
				<section class="gs-logo-card gs-section gs-single-reveal">
					<h2><?php esc_html_e( 'Pitch Deck', 'gslogo' ); ?></h2>
					<p><?php esc_html_e( 'Company presentation and business overview document.', 'gslogo' ); ?></p>
					<div class="gs-file-card">
						<div class="gs-file-left">
							<span class="gs-file-icon"><svg aria-hidden="true"><use href="#gs-i-file"></use></svg></span>
							<div>
								<span class="gs-file-title"><?php echo esc_html( $pitch_deck_title ? $pitch_deck_title : __( 'Pitch Deck', 'gslogo' ) ); ?></span>
								<span class="gs-file-meta"><?php esc_html_e( 'Company Presentation PDF', 'gslogo' ); ?></span>
								<?php if ( $pitch_deck_size ) : ?>
									<span class="gs-file-meta"><?php echo esc_html( $pitch_deck_size ); ?></span>
								<?php endif; ?>
							</div>
						</div>
						<a class="gs-btn gs-btn-secondary" href="<?php echo esc_url( $pitch_deck_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'View / Download', 'gslogo' ); ?>
						</a>
					</div>
				</section>
			<?php endif; ?>

		</div>

		<?php if ( $has_sidebar ) : ?>
		<aside class="gs-sidebar">

			<?php if ( $has_contact_sidebar ) : ?>
				<section class="gs-logo-card gs-side-card gs-single-reveal">
					<h2><?php esc_html_e( 'Contact Info', 'gslogo' ); ?></h2>
					<div class="gs-contact-list">
						<?php if ( $client_url ) : ?>
							<div class="gs-contact-item">
								<span class="gs-icon"><svg aria-hidden="true"><use href="#gs-i-link"></use></svg></span>
								<div>
									<span><?php esc_html_e( 'Client Site URL', 'gslogo' ); ?></span>
									<a href="<?php echo esc_url( $client_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $client_url ); ?></a>
								</div>
							</div>
						<?php endif; ?>
						<?php if ( $contact_email ) : ?>
							<div class="gs-contact-item">
								<span class="gs-icon"><svg aria-hidden="true"><use href="#gs-i-mail"></use></svg></span>
								<div>
									<span><?php esc_html_e( 'Contact Email', 'gslogo' ); ?></span>
									<a href="<?php echo esc_url( 'mailto:' . antispambot( $contact_email ) ); ?>"><?php echo esc_html( antispambot( $contact_email ) ); ?></a>
								</div>
							</div>
						<?php endif; ?>
						<?php if ( $address ) : ?>
							<div class="gs-contact-item">
								<span class="gs-icon"><svg aria-hidden="true"><use href="#gs-i-map-pin"></use></svg></span>
								<div>
									<span><?php esc_html_e( 'Address', 'gslogo' ); ?></span>
									<p><?php echo nl2br( esc_html( $address ) ); ?></p>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $has_tax_sidebar ) : ?>
				<section class="gs-logo-card gs-side-card gs-single-reveal">
					<h2><?php esc_html_e( 'Categories & Tags', 'gslogo' ); ?></h2>
					<?php foreach ( $taxonomy_columns as $tax_column ) : ?>
						<div class="gs-chip-group">
							<span class="gs-chip-title"><?php echo esc_html( $tax_column['label'] ); ?></span>
							<?php if ( ! empty( $tax_column['terms'] ) ) : ?>
								<?php foreach ( $tax_column['terms'] as $term ) : ?>
									<span class="gs-chip"><?php echo esc_html( $term->name ); ?></span>
								<?php endforeach; ?>
							<?php else : ?>
								<span class="gs-tax-empty-note">
									<?php
									printf(
										/* translators: %s: taxonomy singular label */
										esc_html__( 'No %s specified', 'gslogo' ),
										esc_html( strtolower( $tax_column['singular'] ) )
									);
									?>
								</span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</section>
			<?php endif; ?>

			<?php if ( $has_social_sidebar ) : ?>
				<section class="gs-logo-card gs-side-card gs-single-reveal">
					<h2><?php esc_html_e( 'Social Links', 'gslogo' ); ?></h2>
					<div class="gs-social-list">
						<?php foreach ( $social_items as $social ) : ?>
							<div class="gs-social-item">
								<span class="gs-icon<?php echo gs_logo_single_social_is_brand_sprite( $social['sprite'] ) ? ' is-brand' : ''; ?>">
									<svg aria-hidden="true"><use href="#<?php echo esc_attr( $social['sprite'] ); ?>"></use></svg>
								</span>
								<div>
									<strong><?php echo esc_html( $social['name'] ); ?></strong>
									<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $social['url'] ); ?></a>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

		</aside>
		<?php endif; ?>

	</div>

	<?php if ( ! empty( $gallery_images ) ) : ?>
		<div class="gs-logo-lightbox" id="gs-logo-life-lightbox" hidden>
			<div class="gs-logo-lightbox__backdrop" data-close="true" aria-hidden="true"></div>
			<div class="gs-logo-lightbox__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Image gallery', 'gslogo' ); ?>">
				<button type="button" class="gs-logo-lightbox__close" aria-label="<?php esc_attr_e( 'Close', 'gslogo' ); ?>">
					<svg class="gs-logo-lightbox__icon" aria-hidden="true"><use href="#gs-i-x"></use></svg>
				</button>
				<div class="gs-logo-lightbox__stage">
					<button type="button" class="gs-logo-lightbox__nav gs-logo-lightbox__nav--prev" aria-label="<?php esc_attr_e( 'Previous image', 'gslogo' ); ?>">
						<svg class="gs-logo-lightbox__icon" aria-hidden="true"><use href="#gs-i-chevron-left"></use></svg>
					</button>
					<div class="gs-logo-lightbox__content">
						<figure class="gs-logo-lightbox__figure">
							<img class="gs-logo-lightbox__img" src="" alt="" />
						</figure>
						<p class="gs-logo-lightbox__caption"></p>
					</div>
					<button type="button" class="gs-logo-lightbox__nav gs-logo-lightbox__nav--next" aria-label="<?php esc_attr_e( 'Next image', 'gslogo' ); ?>">
						<svg class="gs-logo-lightbox__icon" aria-hidden="true"><use href="#gs-i-chevron-right"></use></svg>
					</button>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $contact_email ) : ?>
		<section class="gs-cta gs-single-reveal">
			<div>
				<h2><?php esc_html_e( "Let's build something amazing together", 'gslogo' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: company name */
						esc_html__( 'Interested in working with %s? Let\'s talk.', 'gslogo' ),
						esc_html( get_the_title( $post_id ) )
					);
					?>
				</p>
			</div>
			<a class="gs-btn gs-btn-primary" href="<?php echo esc_url( 'mailto:' . antispambot( $contact_email ) ); ?>">
				<?php esc_html_e( 'Contact Company', 'gslogo' ); ?>
				<svg class="gs-btn-icon" aria-hidden="true"><use href="#gs-i-mail"></use></svg>
			</a>
		</section>
	<?php endif; ?>

</main>
