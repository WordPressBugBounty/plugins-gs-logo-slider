<?php

namespace GSLOGO;

/**
 * Protect direct access
 */
if (!defined('ABSPATH')) exit;

class Import_Export {

    private $zip_instance;
    private $zip_file;
    private $upload_dir;

    public function __construct() {
        add_action('wp_ajax_gslogo_export_data', array($this, 'gslogo_export_data'));
        add_action('wp_ajax_gslogo_import_data', array($this, 'gslogo_import_data'));
        add_action('gs_logo_register_sub_menu', array($this, 'register_sub_menu'));
    }

    public function gslogo_export_data() {

        // Check for valid nonce
        check_ajax_referer('_gslogo_import_export_nonce_gs_');

        // Check for valid user permissions
        if (!current_user_can('manage_options')) wp_send_json_error(__('You do not have permission to perform this action', 'gslogo'), 403);

        // Check for required data
        if (empty($export_data = $_REQUEST['export_data'])) wp_send_json_error(__('No export data provided', 'gslogo'), 400);

        // Validate the export data
        $export_logos = wp_validate_boolean($export_data['logos']);
        $export_shortcodes = wp_validate_boolean($export_data['shortcodes']);
        $export_settings = wp_validate_boolean($export_data['settings']);

        // Check for valid export data
        if (!$export_logos && !$export_shortcodes && !$export_settings) wp_send_json_error(__('No export data provided', 'gslogo'), 400);

        // Init the zip archive
        $this->init_zip_file();

        // Init the JSON data
        $json_data = [];

        // Add Posts Data to the zip file
        if ($export_logos) $json_data = $this->export__logos($json_data);

        // Add Shortcodes Data to the zip file
        if ($export_shortcodes) $json_data = $this->export__shortcodes($json_data);

        // Add Settings Data to the zip file
        if ($export_settings) $json_data = $this->export__settings($json_data);

        // Add the JSON data to the zip file
        $this->zip_instance->addFromString('data.json', json_encode($json_data, JSON_PRETTY_PRINT));

        // Send the zip file
        $this->send_zip_file_data();
    }

    function delete_directory($dir) {
        if (!file_exists($dir)) {
            return false;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return @rmdir($dir);
    }

    public function save_imported_file() {

        $import_file     = $_FILES['import_file'];
        $file_tmp_path   = $import_file['tmp_name'];
        $file_name       = $import_file['name'];
        $file_name_cmps  = explode(".", $file_name);
        $file_extension  = strtolower(end($file_name_cmps));

        if ($file_extension != 'zip') wp_send_json_error(__('Invalid file type', 'gslogo'), 400);

        $upload_file_dir = get_temp_dir() . 'gs-plugins/gs-logo-slider';

        if (is_dir($upload_file_dir)) $this->delete_directory($upload_file_dir);

        wp_mkdir_p($upload_file_dir);

        $dest_file_path  = $upload_file_dir . '/' . $file_name;

        if (move_uploaded_file($file_tmp_path, $dest_file_path)) {
            $zip = new \ZipArchive;
            if ($zip->open($dest_file_path) === true) {
                $zip->extractTo($upload_file_dir);
                $zip->close();
                unlink($dest_file_path);
            } else {
                wp_send_json_error(__('File upload failed', 'gslogo'), 400);
            }
        } else {
            wp_send_json_error(__('File upload failed', 'gslogo'), 400);
        }

        return $upload_file_dir;
    }

    public function gslogo_import_data() {

        // Check for valid nonce
        check_ajax_referer('_gslogo_import_export_nonce_gs_');

        // Check for valid user permissions
        if (!current_user_can('manage_options')) wp_send_json_error(__('You do not have permission to perform this action', 'gslogo'), 403);

        // Check for required data
        if (empty($_FILES['import_file'])) wp_send_json_error(__('No import file provided', 'gslogo'), 400);

        // Save the uploaded file
        $this->upload_dir = $this->save_imported_file();

        // Check if the data.json file exists
        $json_import_file = $this->upload_dir . '/data.json';
        if ( !file_exists($json_import_file) ) wp_send_json_error(__('Invalid file', 'gslogo'), 400);

        // Read the JSON data
        $json_data = @file_get_contents($this->upload_dir . '/data.json');
        $json_data = json_decode($json_data, true);

        // Check for valid JSON data
        if (empty($json_data)) wp_send_json_error(__('Invalid file content', 'gslogo'), 400);
        
        // Initiate the Import Process
        $this->import__logo_data( $json_data );

        // Delete the uploaded files
        $this->delete_directory($this->upload_dir);

        // Send the success message
        wp_send_json_success(__('Data imported successfully', 'gslogo'), 200 );
    }

    public function import__logo_data( $json_data ) {

        // Import the Settings Data
        if (!empty($json_data['settings'])) {
            plugin()->builder->_save_shortcode_pref($json_data['settings'], false);
        }

        // Import the Attachments Data
        if (!empty($json_data['attachments'])) {
            $this->import__attachments($json_data['attachments']);
        }

        // Import the Terms Data
        if (!empty($json_data['terms'])) {
            plugin()->cpt->register_taxonomies();
            $this->import__terms($json_data['terms']);
        }

        // Import the Posts Data
        if (!empty($json_data['posts'])) {
            $this->import__posts($json_data['posts']);
        }

        // Import the Shortcodes Data
        if (!empty($json_data['shortcodes'])) {
            $this->import__shortcodes($json_data['shortcodes']);
        }

    }

    // Test Purpose
    public function delete__logo_data() {

        // Delete All the Posts
        $posts = get_posts([
            'posts_per_page'    => -1,
            'post_type'         => 'gs-logo-slider',
        ]);
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }

        // Delete All the Attachments where post_type is 'attachment' && meta_key is '_gs_logo_import_id'
        $attachments = get_posts([
            'posts_per_page'    => -1,
            'post_type'         => 'attachment',
            'meta_key'          => '_gs_logo_import_id'
        ]);
        foreach ($attachments as $attachment) {
            wp_delete_post($attachment->ID, true);
        }

        // Delete All the Terms
        $terms = get_terms([
            'taxonomy' => 'logo-category',
            'hide_empty' => false
        ]);

        foreach ($terms as $term) {
            wp_delete_term($term->term_id, 'logo-category');
        }

        // Delete All the Shortcodes
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}gs_logo_slider");
    }

    public function import__terms($terms) {

        foreach ($terms as $term) {

            $term_data = array(
                'slug' => $term['slug'],
                'description' => $term['description'],
                'parent' => $term['parent']
            );
                
            $inserted_term = wp_insert_term($term['name'], $term['taxonomy'], $term_data);
            
            if (is_wp_error($inserted_term)) continue;

            add_term_meta($inserted_term['term_id'], '_gs_logo_import_id', $term['term_id']);

        }

    }

    public function import__attachments($attachments) {

        require_once(ABSPATH . 'wp-admin/includes/image.php');

        wp_raise_memory_limit('image');

        foreach ($attachments as $attachment) {

            $file = $this->upload_dir . '/attachments/' . $attachment['file_name'];

            if (!file_exists($file)) continue;

            $mirror = wp_upload_bits(basename($file), null, file_get_contents($file));

            if (!empty($mirror['error'])) continue;

            $attachment_data = array(
                'guid'           => $mirror['url'],
                'post_mime_type' => $mirror['type'],
                'post_title'     => $attachment['title'],
                'post_content'   => $attachment['description'],
                'post_status'    => 'inherit',
                'post_excerpt'   => $attachment['caption'],
                'meta_input'     => array(
                    '_wp_attachment_image_alt' => $attachment['alt_text'],
                    '_gs_logo_import_id' => $attachment['ID']
                )
            );

            $attachment_id = wp_insert_attachment($attachment_data, $mirror['file']);

            if (is_wp_error($attachment_id)) continue;

            $attach_data = wp_generate_attachment_metadata($attachment_id, $mirror['file']);

            wp_update_attachment_metadata($attachment_id, $attach_data);

        }

    }

    public function import__posts($posts) {

        foreach ($posts as $post) {

            $meta_input = $post['meta_input'];

            $meta_input = array_map(function($value) {
                if ( !empty($value) ) {
                    if ( is_array($value) ) {
                        return $value[0];
                    } else {
                        return $value;
                    }
                }
                return '';
            }, $meta_input);

            $meta_input['_gs_logo_import_id'] = $post['ID'];

            unset($post['ID']);

            if ( isset( $meta_input['_thumbnail_id'] ) ) {
                $thumbnail_id = $this->get_imported_post_id( $meta_input['_thumbnail_id'] );
                if ($thumbnail_id) {
                    $meta_input['_thumbnail_id'] = $thumbnail_id;
                } else {
                    unset($meta_input['_thumbnail_id']);
                }
            }

            if ( isset( $meta_input['second_featured_img'] ) ) {
                $secondary_thumbnail_id = $this->get_imported_post_id( $meta_input['second_featured_img'] );
                if ($secondary_thumbnail_id) {
                    $meta_input['second_featured_img'] = $secondary_thumbnail_id;
                } else {
                    unset($meta_input['second_featured_img']);
                }
            }

            $post['meta_input'] = $meta_input;

            foreach ($post['tax_input'] as $taxonomy => $terms) {
                
                if ( ! taxonomy_exists( $taxonomy ) ) {
                    unset($post['tax_input'][$taxonomy]);
                    continue;
                }

                foreach ($terms as $key => $term) {
                    $term_id = (int) $this->get_imported_term_id($term);
                    if ($term_id) {
                        $terms[$key] = $term_id;
                    } else {
                        unset($terms[$key]);
                    }
                }

                $post['tax_input'][$taxonomy] = $terms;

            }

            wp_insert_post($post);
        }
        
    }

    public function replace_with_imported_terms($terms) {

        if (empty($terms)) return '';

        $terms = explode(',', $terms);

        $terms = array_map(function($term) {
            $term_id = $this->get_imported_term_id( (int) $term);
            return $term_id ? $term_id : '';
        }, $terms);

        $terms = array_filter($terms);

        return implode(',', $terms);
    }

    public function import__shortcodes($shortcodes) {

        global $wpdb;

        $builder = plugin()->builder;

        foreach ($shortcodes as $shortcode) {

            // Validate the shortcode settings
            $shortcode_settings = json_decode($shortcode['shortcode_settings'], true);
            $shortcode_settings = $builder->validate_shortcode_settings($shortcode_settings);

            // Replace the old term IDs with the new term IDs
            $shortcode_settings['group']         = $this->replace_with_imported_terms($shortcode_settings['group']);

            // Set the new shortcode settings
            $shortcode['shortcode_settings'] = json_encode($shortcode_settings);
            
            // Unset the ID
            unset($shortcode['id']);

            // Insert the shortcode
            $wpdb->insert($wpdb->prefix . 'gs_logo_slider', $shortcode, [
                'shortcode_name'     => '%s',
                'shortcode_settings' => '%s',
                'created_at'         => '%s',
                'updated_at'         => '%s',
            ]);

            // Trigger the shortcode created action
            do_action( 'gs_logo_shortcode_created', $wpdb->insert_id );
            do_action( 'gsp_shortcode_created', $wpdb->insert_id );
        }

        // Delete the shortcode cache
        wp_cache_delete( 'gs_logo_shortcodes', 'gs_logo_slider' );

    }

    public function get_imported_post_id($post_id) {

        global $wpdb;

        $post_id = (int) $post_id;

        if (!$post_id) return false;

        $post_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_gs_logo_import_id' AND meta_value = %d LIMIT 1", $post_id));

        if (!$post_id) return false;

        return $post_id;
    }

    public function get_imported_term_id($term_id) {

        global $wpdb;

        $term_id = (int) $term_id;

        if (!$term_id) return false;

        $term_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM $wpdb->termmeta WHERE meta_key = '_gs_logo_import_id' AND meta_value = %d LIMIT 1", $term_id));

        if (!$term_id) return false;

        return $term_id;
    }

    public function export__logos($json_data = []) {

        $json_data['posts']             = [];
        $json_data['attachments']       = [];
        $json_data['terms']             = [];

        $posts = get_posts([
            'posts_per_page'    => -1,
            'post_type'         => 'gs-logo-slider'
        ]);

        // Add Posts Data to the zip file
        foreach ($posts as $post) {

            extract((array) $post);

            $post_data = compact("ID", "post_date", "post_date_gmt", "post_content", "post_title", "post_excerpt", "post_status", "comment_status", "ping_status", "post_password", "post_name", "post_modified", "post_modified_gmt", "post_parent", "menu_order", "post_type");

            $post_data['meta_input'] = get_post_meta($ID, '', true);

            foreach ($post_data['meta_input'] as $meta_key => $meta_value) {
                foreach ($meta_value as $key => $value) {
                    if (is_serialized($value)) {
                        $meta_value[$key] = maybe_unserialize($value);
                    }
                }
                $post_data['meta_input'][$meta_key] = $meta_value;
            }

            $post_data['tax_input'] = [];
            foreach ( $this->get_taxonomy_list() as $taxonomy ) {
                $post_data['tax_input'][$taxonomy] = wp_get_post_terms($ID, $taxonomy, ['fields' => 'ids']);
            }

            unset($post_data['meta_input']['_edit_last']);
            unset($post_data['meta_input']['_edit_lock']);
            unset($post_data['meta_input']['gslogo-demo_data']);

            $json_data['posts'][] = $post_data;
        }

        // Generate Attachments Data
        foreach ($posts as $post) {

            $thumbnail_id = get_post_thumbnail_id($post->ID);

            if ($thumbnail_id) {
                $thumbnail_data = $this->get_attachment_export_data($thumbnail_id);
                if ( !empty($thumbnail_data) ) {
                    $json_data['attachments'][] = $thumbnail_data;
                }
            }
        }

        // Add Attachments Data to the zip file
        foreach ($json_data['attachments'] as $key => $attachment) {
            $file_name = basename($attachment['file_path']);
            $this->zip_instance->addFile($attachment['file_path'], 'attachments/' . basename($attachment['file_path']));
            $json_data['attachments'][$key]['file_name'] = $file_name;
            unset($json_data['attachments'][$key]['file_path']);
        }

        // Add Terms Data to the zip file
        $json_data['terms'] = get_terms([
            'taxonomy' => $this->get_taxonomy_list(),
            'hide_empty' => false
        ]);

        return $json_data;
    }

    public function export__shortcodes($json_data = []) {

        // Add Shortcodes Data to the zip file
        $json_data['shortcodes'] = $this->get_shortcode_list();

        // Return the generated data
        return $json_data;
    }

    public function export__settings($json_data = []) {

        // Add Shortcodes Data to the zip file
        $json_data['settings'] = plugin()->builder->_get_shortcode_pref( false );

        // Return the generated data
        return $json_data;
    }

    public function get_attachment_export_data($attachment_id) {
        $attachment = get_post($attachment_id);
        
        if ( empty($attachment) || !is_object($attachment) ) return false;
        if ( $attachment->post_type !== 'attachment' ) return false;
        if ( empty( get_attached_file( $attachment_id ) ) ) return false;

        $attachment_data = array(
            'ID' => $attachment->ID,
            'title' => $attachment->post_title,
            'description' => $attachment->post_content,
            'caption' => $attachment->post_excerpt,
            'alt_text' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'file_path' => get_attached_file($attachment_id)
        );
        return $attachment_data;
    }

    public function register_sub_menu() {

        $builder = plugin()->builder;

        add_submenu_page(
            'edit.php?post_type=gs-logo-slider',
            'Import & Export',
            'Import & Export',
            'publish_pages',
            'gs-logo-shortcode#/import-export',
            array($builder, 'view')
        );
    }

    public function get_taxonomy_list() {

        $taxonomies = ['logo-category', 'logo-tag', 'logo-extra-one', 'logo-extra-two', 'logo-extra-three', 'logo-extra-four', 'logo-extra-five'];

        return array_filter( $taxonomies, 'taxonomy_exists' );
    }

    public function get_shortcode_list() {
        global $wpdb;

        $shortcodes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gs_logo_slider", ARRAY_A);
        if (empty($shortcodes)) return [];

        $builder = plugin()->builder;

        foreach ($shortcodes as $shortcode) {
            $shortcode["shortcode_settings"] = json_decode($shortcode["shortcode_settings"], true);
            $shortcode["shortcode_settings"] = $builder->validate_shortcode_settings($shortcode["shortcode_settings"]);
        }

        return $shortcodes;
    }

    public function init_zip_file() {

        // Init the zip archive
        $this->zip_instance = new \ZipArchive();

        // Init the zip file
        $this->zip_file = get_temp_dir() . '/gs-plugins/gs-logo-plugin--export.zip';

        // Delete the zip file if it exists
        if (file_exists($this->zip_file)) unlink($this->zip_file);

        // Create the zip file
        wp_mkdir_p(dirname($this->zip_file));

        // Open the zip file
        $this->zip_instance->open($this->zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    }

    public function send_zip_file_data() {

        // Close the zip file
        $this->zip_instance->close();

        // Send the zip file
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="export.zip"');
        header('Content-Length: ' . filesize($this->zip_file));
        readfile($this->zip_file);

        // Delete the zip file
        if (file_exists($this->zip_file)) unlink($this->zip_file);
        exit;
    }
}