<?php

namespace GSLOGO;

if (!defined('ABSPATH')) exit;

class Metabox {

	public function __construct() {
		add_action('admin_enqueue_scripts', [$this, 'gs_logo_slider_enqueue_scripts']);
		add_action('add_meta_boxes', [$this, 'gs_logo_slider_add_meta_box']);
		add_filter( 'get_user_option_meta-box-order_gs-logo-slider', [ $this, 'gs_logo_slider_reorder_side_metaboxes' ] );
		add_action('save_post', [$this, 'gs_logo_slider_save_meta_box_data']);
	}

	/**
	 * Enqueue scripts and styles for the metabox.
	 */

	public function gs_logo_slider_enqueue_scripts($hook) {
		if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;

		global $post;

		$post_type = '';
		if ( isset( $_GET['post_type'] ) ) {
			$post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) );
		} elseif ( $post instanceof \WP_Post ) {
			$post_type = $post->post_type;
		}

		if ( 'gs-logo-slider' !== $post_type ) {
			return;
		}

		// Icon font for social link preview in the metabox.
		wp_enqueue_style( 'gs-zmdi-fonts', GSL_PLUGIN_URI . 'assets/libs/material-design-iconic-font/css/material-design-iconic-font.min.css', [], GSL_VERSION );

		if( is_pro_active() ){
			wp_enqueue_media();
			wp_enqueue_style('gs-flatpickr', GSL_PRO_PLUGIN_URI . '/assets/libs/flatpickr/flatpickr.min.css');
			wp_enqueue_script('gs-flatpickr', GSL_PRO_PLUGIN_URI . '/assets/libs/flatpickr/flatpickr.min.js', ['jquery'], null, true);
			wp_enqueue_script('gs-sortablejs', GSL_PRO_PLUGIN_URI . '/assets/libs/sortablejs/Sortable.min.js', [], '1.15.6', true);
		}

	}

	/**
	 * Adds a box to the main column on the Post and Page edit screens.
	 */
	public function gs_logo_slider_add_meta_box( $post_type ) {

		add_meta_box(
			'gs_logo_slider_sectionid',
			__("Logo Additional Info", 'gslogo'),
			[$this, 'gs_logo_slider_meta_box_callback'],
			'gs-logo-slider',
			'normal',
			'high'
		);

		add_meta_box(
			'gs_logo_social_links_sectionid',
			__("Company Social Links", 'gslogo'),
			[$this, 'gs_logo_social_links_callback'],
			'gs-logo-slider',
			'normal',
			'high'
		);

		add_meta_box(
			'gs_logo_life_gallery_sectionid',
			__( 'Life in Company / Image Gallery', 'gslogo' ),
			[ $this, 'gs_logo_life_gallery_callback' ],
			'gs-logo-slider',
			'normal',
			'high'
		);

		add_meta_box(
			'gs_logo_pitch_deck_sectionid',
			__("Pitch Deck / Company Presentation (PDF)", 'gslogo'),
			[$this, 'gs_logo_pitch_deck_callback'],
			'gs-logo-slider',
			'normal',
			'high'
		);

		add_meta_box(
			'gs_logo_media_upload',
			__("Secondary Image", 'gslogo'),
			[$this, 'gs_logo_media_upload'],
			'gs-logo-slider',
			'normal',
			'high'
		);

		add_meta_box(
			'gs_logo_expire_at_sectionid',
			__( 'Logo Expire At', 'gslogo' ),
			[ $this, 'gs_logo_expire_at_callback' ],
			'gs-logo-slider',
			'side',
			'low'
		);
	}

	/**
	 * Put the featured image metabox first in the side column.
	 *
	 * @param array|false $order Saved metabox order for the gs-logo-slider screen.
	 * @return array|false
	 */
	public function gs_logo_slider_reorder_side_metaboxes( $order ) {
		if ( ! empty( $order ) ) {
			return $order;
		}
	
		return array(
			'side'     => 'postimagediv',
		);
	}

	/**
	 * Prints the box content.
	 * 
	 * @param WP_Post $post The object for the current post/page.
	 */
	public function gs_logo_slider_meta_box_callback($post) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field('gs_logo_slider_meta_box', 'gs_logo_slider_meta_box_nonce');

		$client_url           = get_post_meta($post->ID, 'client_url', true);
		$contact_email        = get_post_meta($post->ID, 'gs_logo_contact_email', true);
		$address              = get_post_meta($post->ID, 'gs_logo_address', true);
		$vision               = get_post_meta($post->ID, 'gs_logo_vision', true);
		$mission              = get_post_meta($post->ID, 'gs_logo_mission', true);
		$established_on       = get_post_meta($post->ID, 'gs_logo_established_on', true);
		$num_employees        = get_post_meta($post->ID, 'gs_logo_num_employees', true);
		$formation_structure  = get_post_meta($post->ID, 'gs_logo_formation_structure', true);
		$client_since         = get_post_meta($post->ID, 'gs_logo_client_since', true);
		$map_embed            = get_post_meta($post->ID, 'gs_logo_map_embed', true);
		$video_embed_url      = get_post_meta($post->ID, 'gs_logo_video_embed_url', true);
		$funding_type         = get_post_meta($post->ID, 'gs_logo_funding_type', true);
		$funding_source       = get_post_meta($post->ID, 'gs_logo_funding_source', true);

		$editor_args = [
			'media_buttons' => false,
			'teeny'         => true,
			'textarea_rows' => 6,
			'editor_class'  => 'gs-logo-editor',
			'quicktags'     => false,
			'tinymce'       => [
				'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink,removeformat,undo,redo',
				'toolbar2' => '',
			],
		];

		$is_pro_field_active = is_pro_active() && is_gs_logo_pro_valid();

		?>

			<div class="gs-logo-slider-additional-info">

				<div class="field-group">
					<label for="gs_logo_slider_url_field"><?php _e('Client Site URL', 'gslogo'); ?></label>
					<input type="url" id="gs_logo_slider_url_field" class="form-control" name="gs_logo_slider_url_field" value="<?php echo esc_attr($client_url); ?>" placeholder="<?php esc_attr_e('https://example.com', 'gslogo'); ?>" />
				</div>

				<div class="field-group">
					<label for="gs_logo_contact_email"><?php _e('Contact Email', 'gslogo'); ?></label>
					<input type="email" id="gs_logo_contact_email" class="form-control" name="gs_logo_contact_email" value="<?php echo esc_attr($contact_email); ?>" placeholder="<?php esc_attr_e('contact@example.com', 'gslogo'); ?>" />
				</div>

				<div class="field-group field-group--textarea">
					<label for="gs_logo_address"><?php _e('Address', 'gslogo'); ?></label>
					<textarea id="gs_logo_address" class="form-control" name="gs_logo_address" rows="2" placeholder="<?php esc_attr_e('Street, City, State, Country', 'gslogo'); ?>"><?php echo esc_textarea($address); ?></textarea>
				</div>

				<div class="<?php echo ! $is_pro_field_active ? 'gs-logo-pro-field gs-logo-fields-disable' : ''; ?>">

					<div class="field-group field-group--editor">
						<label for="gs_logo_vision"><?php _e('Vision', 'gslogo'); ?></label>
						<div class="field-control">
							<?php wp_editor( $vision, 'gs_logo_vision', array_merge( $editor_args, [ 'textarea_name' => 'gs_logo_vision' ] ) ); ?>
						</div>
					</div>

					<div class="field-group field-group--editor">
						<label for="gs_logo_mission"><?php _e('Mission', 'gslogo'); ?></label>
						<div class="field-control">
							<?php wp_editor( $mission, 'gs_logo_mission', array_merge( $editor_args, [ 'textarea_name' => 'gs_logo_mission' ] ) ); ?>
						</div>
					</div>

					<div class="field-group">
						<label for="gs_logo_established_on"><?php _e('Established On', 'gslogo'); ?></label>
						<input type="text" id="gs_logo_established_on" class="form-control" name="gs_logo_established_on" value="<?php echo esc_attr($established_on); ?>" placeholder="<?php esc_attr_e('e.g. 1998 or 12 March 1998', 'gslogo'); ?>" />
					</div>

					<div class="field-group">
						<label for="gs_logo_num_employees"><?php _e('Number of Employees', 'gslogo'); ?></label>
						<input type="text" id="gs_logo_num_employees" class="form-control" name="gs_logo_num_employees" value="<?php echo esc_attr($num_employees); ?>" placeholder="<?php esc_attr_e('e.g. 50 or 11-50', 'gslogo'); ?>" />
					</div>

					<div class="field-group">
						<label for="gs_logo_formation_structure"><?php _e('Formation Structure', 'gslogo'); ?></label>
						<select id="gs_logo_formation_structure" class="form-control" name="gs_logo_formation_structure">
							<option value=""><?php esc_html_e('— Select —', 'gslogo'); ?></option>
							<?php foreach ( $this->get_formation_structures() as $key => $label ) : ?>
								<option value="<?php echo esc_attr($key); ?>" <?php selected($formation_structure, $key); ?>><?php echo esc_html($label); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="field-group">
						<label for="gs_logo_client_since_field"><?php _e('Client Since', 'gslogo'); ?></label>
						<input type="text" id="gs_logo_client_since" class="form-control" name="gs_logo_client_since" value="<?php echo esc_attr($client_since); ?>" placeholder="<?php esc_attr_e('Client Since', 'gslogo'); ?>" />
					</div>

					<div class="field-group field-group--textarea">
						<label for="gs_logo_map_embed"><?php _e('Map Location (Embed)', 'gslogo'); ?></label>
						<textarea id="gs_logo_map_embed" class="form-control" name="gs_logo_map_embed" rows="3" placeholder="<?php esc_attr_e('Paste Google Maps embed iframe code here', 'gslogo'); ?>"><?php echo esc_textarea($map_embed); ?></textarea>
					</div>

					<div class="field-group">
						<label for="gs_logo_video_embed_url"><?php _e('Video (Embed URL)', 'gslogo'); ?></label>
						<input type="url" id="gs_logo_video_embed_url" class="form-control" name="gs_logo_video_embed_url" value="<?php echo esc_attr( $video_embed_url ); ?>" placeholder="<?php esc_attr_e( 'https://www.youtube.com/watch?v=... or https://vimeo.com/...', 'gslogo' ); ?>" />
					</div>

					<div class="field-group">
						<label for="gs_logo_funding_type"><?php _e('Funding Type', 'gslogo'); ?></label>
						<select id="gs_logo_funding_type" class="form-control" name="gs_logo_funding_type">
							<option value=""><?php esc_html_e( '— Select —', 'gslogo' ); ?></option>
							<?php foreach ( $this->get_funding_types() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $funding_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="field-group">
						<label for="gs_logo_funding_source"><?php _e('Funding Source', 'gslogo'); ?></label>
						<input type="text" id="gs_logo_funding_source" class="form-control" name="gs_logo_funding_source" value="<?php echo esc_attr( $funding_source ); ?>" placeholder="<?php esc_attr_e( 'Investors, venture capital firms, etc.', 'gslogo' ); ?>" />
					</div>

					<?php $this->render_pro_upgrade_overlay( $is_pro_field_active ); ?>

				</div>

			</div>

		<?php

	}

	/**
	 * Logo expiration date (side metabox, Pro).
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function gs_logo_expire_at_callback( $post ) {

		$expire_at           = get_post_meta( $post->ID, 'gs_logo_expire_at', true );
		$is_pro_field_active = is_pro_active() && is_gs_logo_pro_valid();

		?>
		<div class="gs-logo-expire-at-metabox <?php echo ! $is_pro_field_active ? 'gs-logo-pro-field gs-logo-fields-disable' : ''; ?>">
			<p>
				<label for="gs_logo_expire_at_field"><?php esc_html_e( 'The logo will disappear at exactly this date and time. Leave blank for no expiration.', 'gslogo' ); ?></label>
				<input type="text" id="gs_logo_expire_at" class="widefat" name="gs_logo_expire_at" value="<?php echo esc_attr( $expire_at ); ?>" placeholder="<?php esc_attr_e( 'e.g. 2026-06-02 12:00:00', 'gslogo' ); ?>" />
			</p>
			<?php $this->render_pro_upgrade_overlay( $is_pro_field_active ); ?>
		</div>
		<?php
	}

	/**
	 * Social links repeater metabox (Pro).
	 */
	public function gs_logo_social_links_callback( $post ) {

		$social_links        = get_post_meta( $post->ID, 'gs_logo_social_links', true );
		$social_links        = is_array( $social_links ) ? $social_links : [];
		$icons               = $this->get_social_icons();
		$is_pro_field_active = is_pro_active() && is_gs_logo_pro_valid();

		// Always render at least one empty row template-style for the user.
		if ( empty( $social_links ) ) {
			$social_links = [ [ 'icon' => '', 'name' => '', 'url' => '' ] ];
		}

		?>
		<div class="gs-logo-social-links <?php echo ! $is_pro_field_active ? 'gs-logo-pro-field gs-logo-fields-disable' : ''; ?>">

			<p class="gs-social-help"><?php esc_html_e('Drag rows to reorder. URL is required when a platform is selected.', 'gslogo'); ?></p>

			<div class="gs-social-list" id="gs_logo_social_list" data-icons='<?php echo esc_attr( wp_json_encode( $icons ) ); ?>'>
				<?php foreach ( $social_links as $index => $link ) :
					$this->render_social_row( (int) $index, (array) $link, $icons );
				endforeach; ?>
			</div>

			<button type="button" class="button gs-logo-metabox-action" id="gs_logo_social_add">
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
				<span class="gs-logo-metabox-action__label"><?php esc_html_e('Add Social Link', 'gslogo'); ?></span>
			</button>

			<?php $this->render_pro_upgrade_overlay( $is_pro_field_active ); ?>

		</div>
		<?php
	}

	/**
	 * Render a single social link row.
	 */
	private function render_social_row( $index, array $link, array $icons ) {

		$icon_key    = isset( $link['icon'] ) ? $link['icon'] : '';
		$name        = isset( $link['name'] ) ? $link['name'] : '';
		$url         = isset( $link['url'] ) ? $link['url'] : '';
		$icon_cls    = isset( $icons[ $icon_key ]['icon'] ) ? $icons[ $icon_key ]['icon'] : 'zmdi-link';
		$url_required = ( '' !== $icon_key || '' !== $name );

		?>
		<div class="gs-social-row" data-index="<?php echo esc_attr( $index ); ?>">

			<span class="gs-social-handle" title="<?php esc_attr_e('Drag to reorder', 'gslogo'); ?>">
				<span class="dashicons dashicons-menu"></span>
			</span>

			<span class="gs-social-icon-preview">
				<i class="zmdi <?php echo esc_attr( $icon_cls ); ?>"></i>
			</span>

			<select class="gs-social-icon form-control" name="gs_logo_social_links[<?php echo esc_attr( $index ); ?>][icon]">
				<option value=""><?php esc_html_e('— Platform —', 'gslogo'); ?></option>
				<?php foreach ( $icons as $key => $data ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" data-icon="<?php echo esc_attr( $data['icon'] ); ?>" <?php selected( $icon_key, $key ); ?>><?php echo esc_html( $data['label'] ); ?></option>
				<?php endforeach; ?>
			</select>

			<input type="text" class="gs-social-name form-control" name="gs_logo_social_links[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php esc_attr_e('Display name (optional)', 'gslogo'); ?>" />

			<input type="url" class="gs-social-url form-control" name="gs_logo_social_links[<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" placeholder="<?php esc_attr_e('https://...', 'gslogo'); ?>" <?php echo $url_required ? 'required' : ''; ?> aria-required="<?php echo $url_required ? 'true' : 'false'; ?>" />

			<button type="button" class="gs-social-remove" title="<?php esc_attr_e('Remove', 'gslogo'); ?>">
				<span class="dashicons dashicons-trash"></span>
			</button>

		</div>
		<?php
	}

	/**
	 * Life in company image gallery metabox (Pro).
	 */
	public function gs_logo_life_gallery_callback( $post ) {

		$is_pro_field_active = is_pro_active() && is_gs_logo_pro_valid();
		$gallery_ids         = $is_pro_field_active ? get_post_meta( $post->ID, 'gs_logo_life_gallery', true ) : [];
		$gallery_ids         = is_array( $gallery_ids ) ? array_filter( array_map( 'absint', $gallery_ids ) ) : [];

		?>
		<div class="gs-logo-gallery-uploader-wrap <?php echo ! $is_pro_field_active ? 'gs-logo-pro-field gs-logo-fields-disable' : ''; ?>">

			<p class="gs-gallery-help"><?php esc_html_e( 'Team photos, office shots, and other life-at-company images. Drag to reorder.', 'gslogo' ); ?></p>

			<div id="gs_logo_life_gallery" class="gs-logo-life-gallery">
				<ul class="gs-logo-gallery-list" id="gs_logo_gallery_list">
					<?php foreach ( $gallery_ids as $attachment_id ) :
						$this->render_gallery_item( $attachment_id );
					endforeach; ?>
				</ul>

				<input type="hidden" id="gs_logo_life_gallery_ids" name="gs_logo_life_gallery" value="<?php echo esc_attr( implode( ',', $gallery_ids ) ); ?>" />

				<p class="hide-if-no-js gs-logo-metabox-actions">
					<button type="button" class="button gs-logo-metabox-action" id="gs_logo_gallery_add" data-uploader_title="<?php esc_attr_e( 'Add Gallery Images', 'gslogo' ); ?>" data-uploader_button_text="<?php esc_attr_e( 'Add to Gallery', 'gslogo' ); ?>">
						<span class="dashicons dashicons-images-alt2" aria-hidden="true"></span>
						<span class="gs-logo-metabox-action__label"><?php esc_html_e( 'Add Images', 'gslogo' ); ?></span>
					</button>
					<button type="button" class="button gs-logo-metabox-action gs-logo-metabox-action--muted<?php echo empty( $gallery_ids ) ? ' gs-logo-metabox-action--hidden' : ''; ?>" id="gs_logo_gallery_remove_all">
						<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
						<span class="gs-logo-metabox-action__label"><?php esc_html_e( 'Remove All', 'gslogo' ); ?></span>
					</button>
				</p>
			</div>

			<?php $this->render_pro_upgrade_overlay( $is_pro_field_active ); ?>

		</div>
		<?php
	}

	/**
	 * Render a single gallery list item.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	private function render_gallery_item( $attachment_id ) {

		$attachment_id = absint( $attachment_id );
		if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		$thumb = wp_get_attachment_image( $attachment_id, 'thumbnail' );
		if ( ! $thumb ) {
			return;
		}

		?>
		<li class="gs-logo-gallery-item" data-id="<?php echo esc_attr( $attachment_id ); ?>">
			<span class="gs-gallery-handle" title="<?php esc_attr_e( 'Drag to reorder', 'gslogo' ); ?>">
				<span class="dashicons dashicons-menu"></span>
			</span>
			<div class="gs-logo-gallery-thumb"><?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<button type="button" class="gs-logo-gallery-remove" title="<?php esc_attr_e( 'Remove', 'gslogo' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</li>
		<?php
	}

	/**
	 * Pitch deck PDF metabox (Pro).
	 */
	public function gs_logo_pitch_deck_callback( $post ) {

		$is_pro_field_active = is_pro_active() && is_gs_logo_pro_valid();

		$pdf_id    = $is_pro_field_active ? (int) get_post_meta( $post->ID, 'gs_logo_pitch_deck_id', true ) : 0;
		$pdf_url   = $pdf_id ? wp_get_attachment_url( $pdf_id ) : '';
		$pdf_title = $pdf_id ? get_the_title( $pdf_id ) : '';
		$has_pdf   = ! empty( $pdf_url );

		?>
		<div class="gs-logo-pdf-uploader-wrap <?php echo ! $is_pro_field_active ? 'gs-logo-pro-field gs-logo-fields-disable' : ''; ?>">

			<div id="gs_logo_pitch_deck_upload" class="gs-logo-pdf-uploader">

				<div class="gs-logo-pdf-preview" style="<?php echo $has_pdf ? '' : 'display:none;'; ?>">
					<span class="dashicons dashicons-media-document"></span>
					<a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" class="gs-logo-pdf-name"><?php echo esc_html( $pdf_title ); ?></a>
				</div>

				<input type="hidden" id="gs_logo_pitch_deck_id" name="gs_logo_pitch_deck_id" value="<?php echo esc_attr( $pdf_id ); ?>" />

				<p class="hide-if-no-js gs-logo-metabox-actions">
					<a href="javascript:;" class="button gs-logo-metabox-action gs-logo-pdf-upload" data-uploader_title="<?php esc_attr_e('Choose a PDF', 'gslogo'); ?>" data-uploader_button_text="<?php esc_attr_e('Use this PDF', 'gslogo'); ?>">
						<span class="dashicons dashicons-media-document" aria-hidden="true"></span>
						<span class="gs-logo-metabox-action__label"><?php echo $has_pdf ? esc_html__('Replace PDF', 'gslogo') : esc_html__('Upload PDF', 'gslogo'); ?></span>
					</a>
					<a href="javascript:;" class="button gs-logo-metabox-action gs-logo-metabox-action--muted gs-logo-pdf-remove<?php echo $has_pdf ? '' : ' gs-logo-metabox-action--hidden'; ?>">
						<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
						<span class="gs-logo-metabox-action__label"><?php esc_html_e('Remove PDF', 'gslogo'); ?></span>
					</a>
				</p>

			</div>

			<?php $this->render_pro_upgrade_overlay( $is_pro_field_active ); ?>

		</div>
		<?php
	}

	public function gs_logo_media_upload($post) {

		$is_pro_field_active = is_pro_active() && is_gs_logo_pro_valid();

		global $content_width, $_wp_additional_image_sizes;

		$image_id = $is_pro_field_active ? get_post_meta( $post->ID, '_listing_image_id', true ) : '';

		$old_content_width = $content_width;
		$content_width     = 254;
		$content           = '';

		if ( $image_id && get_post( $image_id ) ) {

			if ( ! isset( $_wp_additional_image_sizes['post-thumbnail'] ) ) {
				$thumbnail_html = wp_get_attachment_image( $image_id, array( $content_width, $content_width ) );
			} else {
				$thumbnail_html = wp_get_attachment_image( $image_id, 'post-thumbnail' );
			}

			if ( ! empty( $thumbnail_html ) ) {
				$content  = $thumbnail_html;
				$content .= '<p class="hide-if-no-js gs-logo-metabox-actions"><a href="javascript:;" class="button gs-logo-metabox-action gs-logo-metabox-action--muted" id="remove_listing_image_button"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><span class="gs-logo-metabox-action__label">' . esc_html__( 'Remove Image', 'gslogo' ) . '</span></a></p>';
				$content .= '<input type="hidden" id="upload_listing_image" name="_listing_cover_image" value="' . esc_attr( $image_id ) . '" />';
			}
		}

		if ( '' === $content ) {
			$content  = '<img src="" style="width:' . esc_attr( $content_width ) . 'px;height:auto;border:0;display:none;" />';
			$content .= '<p class="hide-if-no-js gs-logo-metabox-actions"><a title="' . esc_attr__( 'Upload Image', 'gslogo' ) . '" href="javascript:;" class="button gs-logo-metabox-action" id="upload_listing_image_button" data-uploader_title="' . esc_attr__( 'Choose an image', 'gslogo' ) . '" data-uploader_button_text="' . esc_attr__( 'Upload Image', 'gslogo' ) . '"><span class="dashicons dashicons-format-image" aria-hidden="true"></span><span class="gs-logo-metabox-action__label">' . esc_html__( 'Upload Image', 'gslogo' ) . '</span></a></p>';
			$content .= '<input type="hidden" id="upload_listing_image" name="_listing_cover_image" value="" />';
		}

		$content_width = $old_content_width;

		?>
		<div id="gs_logo_media_upload" class="gs-logo-secondary-image-uploader <?php echo ! $is_pro_field_active ? 'gs-logo-pro-field gs-logo-fields-disable' : ''; ?>">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaped parts above ?>
			<?php $this->render_pro_upgrade_overlay( $is_pro_field_active ); ?>
		</div>
		<?php
	}

	/**
	 * Centered "Upgrade to PRO" overlay for locked metabox fields.
	 *
	 * @param bool $is_pro_field_active Whether Pro is active and licensed.
	 */
	private function render_pro_upgrade_overlay( $is_pro_field_active ) {

		if ( $is_pro_field_active ) {
			return;
		}

		?>
		<div class="gs-logo-pro-field--inner">
			<div class="gs-logo-pro-field--content">
				<a href="https://www.gsplugins.com/product/gs-logo-slider/#pricing" target="_blank"><?php esc_html_e( 'Upgrade to PRO', 'gslogo' ); ?></a>
			</div>
		</div>
		<?php
	}

	function gs_image_uploader_field($name, $value = '') {

		$image      = ' button">Upload Image';
		$image_size = 'full'; // it would be better to use thumbnail size here (150x150 or so)
		$display    = 'none'; // display state ot the "Remove image" button

		$image_attributes = wp_get_attachment_image_src($value, $image_size);

		if ($image_attributes) {

			// $image_attributes[0] - image URL
			// $image_attributes[1] - image width
			// $image_attributes[2] - image height	
			$image = '"><img src="' . esc_attr($image_attributes[0]) . '" />';
			$display = 'inline-block';
		}

		return '<div class="form-group">
					<label for="second_featured_img">Flip Image:</label>
					<div class="gs-image-uploader-area">
						<a href="#" class="gs_upload_image_button' . $image . '</a>
						<input type="hidden" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />
						<a href="#" class="gs_remove_image_button" style="display:inline-block;display:' . esc_attr($display) . '">Remove image</a>
						</div>
					</div>';
	}

	/**
	 * Supported Formation Structure options for the company.
	 *
	 * @return array
	 */
	private function get_formation_structures() {
		return [
			'sole_proprietorship' => __('Sole Proprietorship', 'gslogo'),
			'partnership'         => __('Partnership', 'gslogo'),
			'llc'                 => __('LLC', 'gslogo'),
			'corporation'         => __('Corporation', 'gslogo'),
			'non_profit'          => __('Non-Profit', 'gslogo'),
			'government'          => __('Government', 'gslogo'),
			'cooperative'         => __('Cooperative', 'gslogo'),
			'other'               => __('Other', 'gslogo'),
		];
	}

	/**
	 * Supported funding type options.
	 *
	 * @return array
	 */
	private function get_funding_types() {
		return [
			'angel'           => __( 'Angel', 'gslogo' ),
			'seed'            => __( 'Seed', 'gslogo' ),
			'series_a'        => __( 'Series A', 'gslogo' ),
			'series_b'        => __( 'Series B', 'gslogo' ),
			'series_c'        => __( 'Series C', 'gslogo' ),
			'series_d'        => __( 'Series D+', 'gslogo' ),
			'bootstrapped'    => __( 'Bootstrapped', 'gslogo' ),
			'debt_financing'  => __( 'Debt Financing', 'gslogo' ),
			'grant'           => __( 'Grant', 'gslogo' ),
			'crowdfunding'    => __( 'Crowdfunding', 'gslogo' ),
			'private_equity'  => __( 'Private Equity', 'gslogo' ),
			'other'           => __( 'Other', 'gslogo' ),
		];
	}

	/**
	 * Supported social platforms and their icon classes.
	 *
	 * Uses Material Design Iconic Font (zmdi) which is already enqueued by the plugin,
	 * so no additional icon library is required.
	 *
	 * @return array
	 */
	private function get_social_icons() {
		return [
			'facebook'   => [ 'label' => __('Facebook',     'gslogo'), 'icon' => 'zmdi-facebook' ],
			'twitter'    => [ 'label' => __('X / Twitter',  'gslogo'), 'icon' => 'zmdi-twitter' ],
			'instagram'  => [ 'label' => __('Instagram',    'gslogo'), 'icon' => 'zmdi-instagram' ],
			'linkedin'   => [ 'label' => __('LinkedIn',     'gslogo'), 'icon' => 'zmdi-linkedin' ],
			'youtube'    => [ 'label' => __('YouTube',      'gslogo'), 'icon' => 'zmdi-youtube-play' ],
			'pinterest'  => [ 'label' => __('Pinterest',    'gslogo'), 'icon' => 'zmdi-pinterest' ],
			'github'     => [ 'label' => __('GitHub',       'gslogo'), 'icon' => 'zmdi-github' ],
			'tumblr'     => [ 'label' => __('Tumblr',       'gslogo'), 'icon' => 'zmdi-tumblr' ],
			'vimeo'      => [ 'label' => __('Vimeo',        'gslogo'), 'icon' => 'zmdi-vimeo' ],
			'whatsapp'   => [ 'label' => __('WhatsApp',     'gslogo'), 'icon' => 'zmdi-whatsapp' ],
			'reddit'     => [ 'label' => __('Reddit',       'gslogo'), 'icon' => 'zmdi-reddit' ],
			'skype'      => [ 'label' => __('Skype',        'gslogo'), 'icon' => 'zmdi-skype' ],
			'soundcloud' => [ 'label' => __('SoundCloud',   'gslogo'), 'icon' => 'zmdi-soundcloud' ],
			'dribbble'   => [ 'label' => __('Dribbble',     'gslogo'), 'icon' => 'zmdi-dribbble' ],
			'behance'    => [ 'label' => __('Behance',      'gslogo'), 'icon' => 'zmdi-behance' ],
			'website'    => [ 'label' => __('Website / Other', 'gslogo'), 'icon' => 'zmdi-link' ],
		];
	}

	/**
	 * Sanitize a Google Maps (or similar) embed iframe.
	 * Allows only iframe with safe attributes from a whitelisted set of hosts.
	 *
	 * @param string $value Raw iframe markup.
	 * @return string Sanitized markup or empty string.
	 */
	private function sanitize_map_embed( $value ) {

		$value = trim( (string) wp_unslash( $value ) );

		if ( '' === $value ) return '';

		$allowed_iframe = [
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
		];

		$clean = wp_kses( $value, $allowed_iframe );

		if ( preg_match( '/<iframe[^>]+src=["\']([^"\']+)["\']/i', $clean, $matches ) ) {
			$host = wp_parse_url( $matches[1], PHP_URL_HOST );
			$allowed_hosts = [
				'www.google.com',
				'maps.google.com',
				'www.openstreetmap.org',
				'maps.apple.com',
				'embed.waze.com',
			];

			if ( ! in_array( $host, $allowed_hosts, true ) ) {
				return '';
			}

			return $clean;
		}

		return '';
	}

	/**
	 * Sanitize a corporate video embed URL (YouTube / Vimeo).
	 *
	 * @param string $value Raw URL.
	 * @return string Sanitized URL or empty string.
	 */
	private function sanitize_video_embed_url( $value ) {

		$value = esc_url_raw( trim( (string) wp_unslash( $value ) ) );

		if ( '' === $value ) {
			return '';
		}

		$host = wp_parse_url( $value, PHP_URL_HOST );
		$host = is_string( $host ) ? strtolower( $host ) : '';

		$allowed_hosts = [
			'www.youtube.com',
			'youtube.com',
			'youtu.be',
			'm.youtube.com',
			'www.youtu.be',
			'vimeo.com',
			'www.vimeo.com',
			'player.vimeo.com',
		];

		if ( ! in_array( $host, $allowed_hosts, true ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Sanitize gallery attachment IDs from a comma-separated list.
	 *
	 * @param string $value Comma-separated attachment IDs.
	 * @return array List of valid image attachment IDs.
	 */
	private function sanitize_life_gallery_ids( $value ) {

		$ids = array_filter( array_map( 'absint', explode( ',', (string) wp_unslash( $value ) ) ) );
		$clean = [];

		foreach ( $ids as $id ) {
			if ( $id > 0 && wp_attachment_is_image( $id ) ) {
				$clean[] = $id;
			}
		}

		return $clean;
	}

	/**
	 * Sanitize and normalize the social links repeater submission.
	 *
	 * @param array $raw Raw posted data.
	 * @return array Clean indexed list of links.
	 */
	private function sanitize_social_links( $raw ) {

		if ( ! is_array( $raw ) ) return [];

		$icons = $this->get_social_icons();
		$clean = [];

		foreach ( $raw as $row ) {

			$icon = isset( $row['icon'] ) ? sanitize_key( $row['icon'] ) : '';
			$name = isset( $row['name'] ) ? sanitize_text_field( wp_unslash( $row['name'] ) ) : '';
			$url  = isset( $row['url'] )  ? esc_url_raw( wp_unslash( $row['url'] ) ) : '';

			if ( '' === $icon && '' === $name && '' === $url ) continue;
			if ( '' === $url ) continue;
			if ( '' !== $icon && ! isset( $icons[ $icon ] ) ) $icon = 'website';
			if ( '' === $icon ) $icon = 'website';

			$clean[] = [
				'icon' => $icon,
				'name' => $name,
				'url'  => $url,
			];
		}

		return $clean;
	}

	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function gs_logo_slider_save_meta_box_data($post_id) {

		// Check if our nonce is set.
		if (!isset($_POST['gs_logo_slider_meta_box_nonce'])) {
			return;
		}

		// Verify that the nonce is valid.
		if (!wp_verify_nonce($_POST['gs_logo_slider_meta_box_nonce'], 'gs_logo_slider_meta_box')) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Check the user's permissions.
		if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {

			if (!current_user_can('edit_page', $post_id)) {
				return;
			}
		} else {

			if (!current_user_can('edit_post', $post_id)) {
				return;
			}
		}

		// Bail early if the primary URL field is not present (means our metabox didn't render).
		if (!isset($_POST['gs_logo_slider_url_field'])) {
			return;
		}

		// --- Free fields ---
		$this->save_text_meta( $post_id, 'client_url',            'gs_logo_slider_url_field', 'sanitize_url' );
		$this->save_text_meta( $post_id, 'gs_logo_contact_email', 'gs_logo_contact_email',    'sanitize_email' );
		$this->save_text_meta( $post_id, 'gs_logo_address',       'gs_logo_address',          'sanitize_textarea_field' );

		// --- Pro fields ---
		if ( is_pro_active() ) {

			$this->save_text_meta( $post_id, 'gs_logo_vision',              'gs_logo_vision',              'wp_kses_post' );
			$this->save_text_meta( $post_id, 'gs_logo_mission',             'gs_logo_mission',             'wp_kses_post' );
			$this->save_text_meta( $post_id, 'gs_logo_established_on',      'gs_logo_established_on',      'sanitize_text_field' );
			$this->save_text_meta( $post_id, 'gs_logo_num_employees',       'gs_logo_num_employees',       'sanitize_text_field' );
			$this->save_text_meta( $post_id, 'gs_logo_formation_structure', 'gs_logo_formation_structure', 'sanitize_key' );
			$this->save_text_meta( $post_id, 'gs_logo_expire_at',    'gs_logo_expire_at',    'sanitize_text_field' );
			$this->save_text_meta( $post_id, 'gs_logo_client_since', 'gs_logo_client_since', 'sanitize_text_field' );

			if ( isset( $_POST['gs_logo_video_embed_url'] ) ) {
				$video_url = $this->sanitize_video_embed_url( $_POST['gs_logo_video_embed_url'] );

				if ( '' === $video_url ) {
					delete_post_meta( $post_id, 'gs_logo_video_embed_url' );
				} else {
					update_post_meta( $post_id, 'gs_logo_video_embed_url', $video_url );
				}
			}

			if ( isset( $_POST['gs_logo_funding_type'] ) ) {
				$funding_types = $this->get_funding_types();
				$funding_type  = sanitize_key( wp_unslash( $_POST['gs_logo_funding_type'] ) );

				if ( '' === $funding_type || ! isset( $funding_types[ $funding_type ] ) ) {
					delete_post_meta( $post_id, 'gs_logo_funding_type' );
				} else {
					update_post_meta( $post_id, 'gs_logo_funding_type', $funding_type );
				}
			}

			$this->save_text_meta( $post_id, 'gs_logo_funding_source', 'gs_logo_funding_source', 'sanitize_text_field' );

			if ( isset( $_POST['gs_logo_life_gallery'] ) ) {
				$gallery_ids = $this->sanitize_life_gallery_ids( $_POST['gs_logo_life_gallery'] );

				if ( empty( $gallery_ids ) ) {
					delete_post_meta( $post_id, 'gs_logo_life_gallery' );
				} else {
					update_post_meta( $post_id, 'gs_logo_life_gallery', $gallery_ids );
				}
			}

			if ( isset( $_POST['gs_logo_map_embed'] ) ) {
				$map_embed = $this->sanitize_map_embed( $_POST['gs_logo_map_embed'] );

				if ( '' === $map_embed ) {
					delete_post_meta( $post_id, 'gs_logo_map_embed' );
				} else {
					update_post_meta( $post_id, 'gs_logo_map_embed', $map_embed );
				}
			}

			if ( isset( $_POST['gs_logo_social_links'] ) ) {
				$social = $this->sanitize_social_links( $_POST['gs_logo_social_links'] );

				if ( empty( $social ) ) {
					delete_post_meta( $post_id, 'gs_logo_social_links' );
				} else {
					update_post_meta( $post_id, 'gs_logo_social_links', $social );
				}
			}

			if ( isset( $_POST['gs_logo_pitch_deck_id'] ) ) {
				$pdf_id = absint( $_POST['gs_logo_pitch_deck_id'] );

				if ( $pdf_id > 0 ) {
					update_post_meta( $post_id, 'gs_logo_pitch_deck_id', $pdf_id );
				} else {
					delete_post_meta( $post_id, 'gs_logo_pitch_deck_id' );
				}
			}

			// Secondary Image
			if ( isset( $_POST['_listing_cover_image'] ) ) {
				$image_id = (int) $_POST['_listing_cover_image'];
				update_post_meta( $post_id, '_listing_image_id', $image_id );
			}
		}

	}

	/**
	 * Generic helper to save / delete a text-based meta value.
	 *
	 * @param int      $post_id     Post ID.
	 * @param string   $meta_key    Meta key in the database.
	 * @param string   $post_key    Key in $_POST.
	 * @param callable $sanitizer   Callback used to sanitize the value.
	 */
	private function save_text_meta( $post_id, $meta_key, $post_key, $sanitizer ) {

		if ( ! isset( $_POST[ $post_key ] ) ) return;

		$value = call_user_func( $sanitizer, trim( wp_unslash( $_POST[ $post_key ] ) ) );

		if ( '' === $value || null === $value ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, $value );
		}
	}
}
