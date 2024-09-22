<?php

namespace GSLOGO;

if (!defined('ABSPATH')) exit;

class Metabox {

	public function __construct() {
		add_action('add_meta_boxes', [$this, 'gs_logo_slider_add_meta_box']);
		add_action('save_post', [$this, 'gs_logo_slider_save_meta_box_data']);
	}

	/**
	 * Adds a box to the main column on the Post and Page edit screens.
	 */
	public function gs_logo_slider_add_meta_box( $post_type ) {

		add_meta_box(
			'gs_logo_slider_sectionid',
			__("Client's URL", 'gslogo'),
			[$this, 'gs_logo_slider_meta_box_callback'],
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
	}

	/**
	 * Prints the box content.
	 * 
	 * @param WP_Post $post The object for the current post/page.
	 */
	public function gs_logo_slider_meta_box_callback($post) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field('gs_logo_slider_meta_box', 'gs_logo_slider_meta_box_nonce');

		/*
		* Use get_post_meta() to retrieve an existing value
		* from the database and use the value for the form.
		*/
		$value = get_post_meta($post->ID, 'client_url', true);

		echo '<label for="gs_logo_slider_url_field">';
		_e('Enter Site URL', 'gslogo');
		echo '</label> ';
		echo '<input type="text" id="gs_logo_slider_url_field" name="gs_logo_slider_url_field" value="' . esc_attr($value) . '" size="25" />';
	}

	public function gs_logo_media_upload($post) {

		if (is_pro_active()) {

			global $content_width, $_wp_additional_image_sizes;
	
			$image_id = get_post_meta($post->ID, '_listing_image_id', true);
	
			$old_content_width = $content_width;
			$content_width = 254;
	
			if ($image_id && get_post($image_id)) {
	
				if (!isset($_wp_additional_image_sizes['post-thumbnail'])) {
					$thumbnail_html = wp_get_attachment_image($image_id, array($content_width, $content_width));
				} else {
					$thumbnail_html = wp_get_attachment_image($image_id, 'post-thumbnail');
				}
	
				if (!empty($thumbnail_html)) {
					$content = $thumbnail_html;
					$content .= '<p class="hide-if-no-js"><a href="javascript:;" id="remove_listing_image_button" >' . esc_html__('Remove Image', 'gslogo') . '</a></p>';
					$content .= '<input type="hidden" id="upload_listing_image" name="_listing_cover_image" value="' . esc_attr($image_id) . '" />';
				}
	
				$content_width = $old_content_width;
			} else {
	
				$content = '<img src="" style="width:' . esc_attr($content_width) . 'px;height:auto;border:0;display:none;" />';
				$content .= '<p class="hide-if-no-js"><a title="' . esc_attr__('Upload Image', 'gslogo') . '" href="javascript:;" id="upload_listing_image_button" id="set-listing-image" data-uploader_title="' . esc_attr__('Choose an image', 'gslogo') . '" data-uploader_button_text="' . esc_attr__('Upload Image', 'gslogo') . '">' . esc_html__('Upload Image', 'gslogo') . '</a></p>';
				$content .= '<input type="hidden" id="upload_listing_image" name="_listing_cover_image" value="" />';
			}
	
			echo $content;

		} else {
	
			echo '<p><a target="_blank" href="https://www.gsplugins.com/product/gs-logo-slider/#pricing"><b>Upgrade to PRO</b></a> to get these advanced features.</p><div class="pro-only" style="pointer-events: none;opacity: .4;"><p class="hide-if-no-js"><a title="' . esc_attr__('Upload Image', 'gslogo') . '" href="javascript:;" id="upload_listing_image_button" id="set-listing-image" data-uploader_title="' . esc_attr__('Choose an image', 'gslogo') . '" data-uploader_button_text="' . esc_attr__('Upload Image', 'gslogo') . '">' . esc_html__('Upload Image', 'gslogo') . '</a></p></div>';

		}

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
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function gs_logo_slider_save_meta_box_data($post_id) {

		/*
		* We need to verify this came from our screen and with proper authorization,
		* because the save_post action can be triggered at other times.
		*/

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

		/* OK, it's safe for us to save the data now. */

		// Make sure that it is set.
		if (!isset($_POST['gs_logo_slider_url_field'])) {
			return;
		}

		// Sanitize user input.
		$gs_logo = sanitize_url($_POST['gs_logo_slider_url_field']);

		// Update the meta field in the database.
		update_post_meta($post_id, 'client_url', $gs_logo);

		// Update Secondary image
		if( is_pro_active() && isset( $_POST['_listing_cover_image'] ) ) {
			$image_id = (int) $_POST['_listing_cover_image'];
			update_post_meta( $post_id, '_listing_image_id', $image_id );
		}
	}
}
