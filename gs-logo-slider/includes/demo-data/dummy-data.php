<?php
namespace GSLOGO;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'GS_Logo_Slider_Dummy_Data' ) ) {

    final class GS_Logo_Slider_Dummy_Data {

        private static $_instance = null;
        
        public static function get_instance() {

            if ( is_null( self::$_instance ) ) {
                self::$_instance = new GS_Logo_Slider_Dummy_Data();
            }

            return self::$_instance;
            
        }

        public function __construct() {

            if ( ! is_admin() ) return;

            add_action( 'wp_ajax_gslogo_import_logo_data', array($this, 'import_logo_data') );

            add_action( 'wp_ajax_gslogo_remove_logo_data', array($this, 'remove_logo_data') );

            add_action( 'wp_ajax_gslogo_import_shortcode_data', array($this, 'import_shortcode_data') );

            add_action( 'wp_ajax_gslogo_remove_shortcode_data', array($this, 'remove_shortcode_data') );

            add_action( 'wp_ajax_gslogo_import_all_data', array($this, 'import_all_data') );

            add_action( 'wp_ajax_gslogo_remove_all_data', array($this, 'remove_all_data') );

            add_action( 'admin_init', array($this, 'maybe_auto_import_all_data') );

            // Remove dummy indicator
            add_action( 'edit_post_gs_logo_slider', array($this, 'remove_dummy_indicator'), 10 );

            // Import Process
            add_action( 'gslogo_dummy_attachments_process_start', function() {

                // Force delete option if have any
                delete_option( 'gslogo_dummy_logo_data_created' );

                // Force update the process
                set_transient( 'gslogo_dummy_logo_data_creating', 1, 3 * MINUTE_IN_SECONDS );

            });
            
            add_action( 'gslogo_dummy_attachments_process_finished', function() {

                $this->create_dummy_terms();

            });
            
            add_action( 'gslogo_dummy_terms_process_finished', function() {

                $this->create_dummy_logos();

            });
            
            add_action( 'gslogo_dummy_logos_process_finished', function() {

                // clean the record that we have started a process
                delete_transient( 'gslogo_dummy_logo_data_creating' );

                // Add a track so we never duplicate the process
                update_option( 'gslogo_dummy_logo_data_created', 1 );

            });
            
            // Shortcodes
            add_action( 'gslogo_dummy_shortcodes_process_start', function() {

                // Force delete option if have any
                delete_option( 'gslogo_dummy_shortcode_data_created' );

                // Force update the process
                set_transient( 'gslogo_dummy_shortcode_data_creating', 1, 3 * MINUTE_IN_SECONDS );

            });

            add_action( 'gslogo_dummy_shortcodes_process_finished', function() {

                // clean the record that we have started a process
                delete_transient( 'gslogo_dummy_shortcode_data_creating' );

                // Add a track so we never duplicate the process
                update_option( 'gslogo_dummy_shortcode_data_created', 1 );

            });
            
        }

        public function maybe_auto_import_all_data() {

            if ( get_option('gs_logo_autoimport_done', false) == true ) return;

            $logos = get_posts([
                'numberposts' => -1,
                'post_type' => 'gs-logo-slider',
                'fields' => 'ids'
            ]);

            $shortcodes = plugin()->builder->get_shortcodes();

            if ( empty($logos) && empty($shortcodes) ) {

                $this->_import_logo_data( false );
                $this->_import_shortcode_data( false );
            }
            
            update_option( 'gs_logo_autoimport_done', true );
        }

        public function get_taxonomy_list() {

            return ['logo-category', 'logo-tag'];

        }

        public function remove_dummy_indicator( $post_id ) {

            if ( empty( get_post_meta($post_id, 'gslogo-demo_data', true) ) ) return;
            
            $taxonomies = $this->get_taxonomy_list();

            // Remove dummy indicator from texonomies
            $dummy_terms = wp_get_post_terms( $post_id, $taxonomies, [
                'fields' => 'ids',
                'meta_key' => 'gslogo-demo_data',
                'meta_value' => 1,
            ]);

            if ( !empty($dummy_terms) ) {
                foreach( $dummy_terms as $term_id ) {
                    delete_term_meta( $term_id, 'gslogo-demo_data', 1 );
                }
                delete_transient( 'gslogo_dummy_terms' );
            }

            // Remove dummy indicator from attachments
            $thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );
            if ( !empty($thumbnail_id) ) delete_post_meta( $thumbnail_id, 'gslogo-demo_data', 1 );
            delete_transient( 'gslogo_dummy_attachments' );
            
            // Remove dummy indicator from post
            delete_post_meta( $post_id, 'gslogo-demo_data', 1 );
            delete_transient( 'gslogo_dummy_logos' );

        }

        public function import_all_data() {

            // Validate nonce && check permission
            if ( !check_admin_referer('_gslogo_simport_gslogo_demo_gs_') || !current_user_can('manage_options') ) wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );

            $response = [
                'logo' => $this->_import_logo_data( false ),
                'shortcode' => $this->_import_shortcode_data( false )
            ];

            if ( wp_doing_ajax() ) wp_send_json_success( $response, 200 );

            return $response;

        }

        public function remove_all_data() {

            // Validate nonce && check permission
            if ( !check_admin_referer('_gslogo_simport_gslogo_demo_gs_') || !current_user_can('manage_options') ) wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );

            $response = [
                'logo' => $this->_remove_logo_data( false ),
                'shortcode' => $this->_remove_shortcode_data( false )
            ];

            if ( wp_doing_ajax() ) wp_send_json_success( $response, 200 );

            return $response;

        }

        public function import_logo_data() {

            // Validate nonce && check permission
            if ( !check_admin_referer('_gslogo_simport_gslogo_demo_gs_') || !current_user_can('manage_options') ) wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );

            // Start importing
            $this->_import_logo_data();

        }

        public function remove_logo_data() {

            // Validate nonce && check permission
            if ( !check_admin_referer('_gslogo_simport_gslogo_demo_gs_') || !current_user_can('manage_options') ) wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );

            // Remove logo data
            $this->_remove_logo_data();

        }

        public function import_shortcode_data() {

            // Validate nonce && check permission
            if ( !check_admin_referer('_gslogo_simport_gslogo_demo_gs_') || !current_user_can('manage_options') ) wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );

            // Start importing
            $this->_import_shortcode_data();

        }

        public function remove_shortcode_data() {

            // Validate nonce && check permission
            if ( !check_admin_referer('_gslogo_simport_gslogo_demo_gs_') || !current_user_can('manage_options') ) wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );

            // Remove logo data
            $this->_remove_shortcode_data();

        }

        public function _import_logo_data( $is_ajax = null ) {

            if ( $is_ajax === null ) $is_ajax = wp_doing_ajax();

            // Data already imported
            if ( get_option('gslogo_dummy_logo_data_created') !== false || get_transient('gslogo_dummy_logo_data_creating') !== false ) {

                $message_202 = __( 'Dummy Logos already imported', 'gslogo' );

                if ( $is_ajax ) wp_send_json_success( $message_202, 202 );
                
                return [
                    'status' => 202,
                    'message' => $message_202
                ];

            }
            
            // Importing demo data
            $this->create_dummy_attachments();

            $message = __( 'Dummy Logos imported', 'gslogo' );

            if ( $is_ajax ) wp_send_json_success( $message, 200 );

            return [
                'status' => 200,
                'message' => $message
            ];

        }

        public function _remove_logo_data( $is_ajax = null ) {

            if ( $is_ajax === null ) $is_ajax = wp_doing_ajax();

            $this->delete_dummy_attachments();
            $this->delete_dummy_terms();
            $this->delete_dummy_logos();

            delete_option( 'gslogo_dummy_logo_data_created' );
            delete_transient( 'gslogo_dummy_logo_data_creating' );

            $message = __( 'Dummy Logos deleted', 'gslogo' );

            if ( $is_ajax ) wp_send_json_success( $message, 200 );

            return [
                'status' => 200,
                'message' => $message
            ];

        }

        public function _import_shortcode_data( $is_ajax = null ) {

            if ( $is_ajax === null ) $is_ajax = wp_doing_ajax();

            // Data already imported
            if ( get_option('gslogo_dummy_shortcode_data_created') !== false || get_transient('gslogo_dummy_shortcode_data_creating') !== false ) {

                $message_202 = __( 'Dummy Shortcodes already imported', 'gslogo' );

                if ( $is_ajax ) wp_send_json_success( $message_202, 202 );
                
                return [
                    'status' => 202,
                    'message' => $message_202
                ];

            }
            
            // Importing demo shortcodes
            $this->create_dummy_shortcodes();

            $message = __( 'Dummy Shortcodes imported', 'gslogo' );

            if ( $is_ajax ) wp_send_json_success( $message, 200 );

            return [
                'status' => 200,
                'message' => $message
            ];

        }

        public function _remove_shortcode_data( $is_ajax = null ) {

            if ( $is_ajax === null ) $is_ajax = wp_doing_ajax();

            $this->delete_dummy_shortcodes();

            delete_option( 'gslogo_dummy_shortcode_data_created' );
            delete_transient( 'gslogo_dummy_shortcode_data_creating' );

            $message = __( 'Dummy Shortcodes deleted', 'gslogo' );

            if ( $is_ajax ) wp_send_json_success( $message, 200 );

            return [
                'status' => 200,
                'message' => $message
            ];

        }

        public function get_taxonomy_ids_by_slugs( $taxonomy_group, $taxonomy_slugs = [] ) {

            $_terms = $this->get_dummy_terms();

            if ( empty($_terms) ) return [];
            
            $_terms = wp_filter_object_list( $_terms, [ 'taxonomy' => $taxonomy_group ] );
            $_terms = array_values( $_terms );      // reset the keys
            
            if ( empty($_terms) ) return [];
            
            $term_ids = [];
            
            foreach ( $taxonomy_slugs as $slug ) {
                $key = array_search( $slug, array_column($_terms, 'slug') );
                if ( $key !== false ) $term_ids[] = $_terms[$key]['term_id'];
            }

            return $term_ids;

        }

        public function get_attachment_id_by_filename( $filename ) {

            $attachments = $this->get_dummy_attachments();
            
            if ( empty($attachments) ) return '';
            
            $attachments = wp_filter_object_list( $attachments, [ 'post_name' => $filename ] );
            if ( empty($attachments) ) return '';
            
            $attachments = array_values( $attachments );
            
            return $attachments[0]->ID;

        }

        public function get_tax_inputs( $tax_inputs = [] ) {

            if ( empty($tax_inputs) ) return $tax_inputs;

            foreach( $tax_inputs as $tax_input => $tax_params ) {

                $tax_inputs[$tax_input] = $this->get_taxonomy_ids_by_slugs( $tax_input, $tax_params );

            }

            return $tax_inputs;

        }

        public function get_dummy_map_embed( $bbox, $title ) {

            $points = array_map( 'floatval', explode( ',', $bbox ) );
            $lng    = ( $points[0] + $points[2] ) / 2;
            $lat    = ( $points[1] + $points[3] ) / 2;

            $src = add_query_arg(
                [
                    'q'      => $lat . ',' . $lng,
                    'z'      => '15',
                    'hl'     => 'en',
                    'output' => 'embed',
                ],
                'https://maps.google.com/maps'
            );

            return sprintf(
                '<iframe src="%1$s" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="%2$s"></iframe>',
                esc_url( $src ),
                esc_attr( $title )
            );

        }

        public function get_meta_inputs( $meta_inputs = [] ) {

            if ( isset( $meta_inputs['_thumbnail_id'] ) ) {
                $meta_inputs['_thumbnail_id'] = $this->get_attachment_id_by_filename( $meta_inputs['_thumbnail_id'] );
            }

            if ( ! empty( $meta_inputs['gs_logo_life_gallery'] ) && is_array( $meta_inputs['gs_logo_life_gallery'] ) ) {
                $gallery_ids = [];

                foreach ( $meta_inputs['gs_logo_life_gallery'] as $filename ) {
                    $attachment_id = absint( $this->get_attachment_id_by_filename( $filename ) );

                    if ( $attachment_id ) {
                        $gallery_ids[] = $attachment_id;
                    }
                }

                $meta_inputs['gs_logo_life_gallery'] = $gallery_ids;
            }

            if ( ! empty( $meta_inputs['gs_logo_pitch_deck_id'] ) ) {
                $pitch_deck_id = absint( $this->get_attachment_id_by_filename( $meta_inputs['gs_logo_pitch_deck_id'] ) );

                if ( $pitch_deck_id ) {
                    $meta_inputs['gs_logo_pitch_deck_id'] = $pitch_deck_id;
                } else {
                    unset( $meta_inputs['gs_logo_pitch_deck_id'] );
                }
            }

            return $meta_inputs;

        }

        // Logos
        public function create_dummy_logos() {

            do_action( 'gslogo_dummy_logos_process_start' );

            $post_status = 'publish';
            $post_type = 'gs-logo-slider';

            $logos = [];

            $logos[] = array(
                'post_title'   => 'Amara',
                'post_content' => 'Amara is a modern digital solutions company helping businesses create meaningful online experiences through thoughtful design and technology.',
                'post_status'  => $post_status,
                'post_type'    => $post_type,
                'post_date'    => '2026-01-12 09:15:00',
                'tax_input'    => $this->get_tax_inputs([
                    'logo-category' => ['technology'],
                    'logo-tag'      => ['digital', 'modern'],
                ]),
                'meta_input'   => $this->get_meta_inputs([
                    '_thumbnail_id'               => 'gs-logo-slider-1',
                    'client_url'                  => 'https://amara.example/',
                    'gs_logo_contact_email'       => 'hello@amara.example',
                    'gs_logo_address'             => '120 Madison Avenue, New York, NY 10016, USA',
                    'gs_logo_vision'              => '<p>Make digital products feel clear, useful, and human.</p>',
                    'gs_logo_mission'             => '<p>Help businesses turn ideas into online experiences people actually use.</p>',
                    'gs_logo_established_on'      => '2016',
                    'gs_logo_num_employees'       => '48',
                    'gs_logo_formation_structure' => 'llc',
                    'gs_logo_client_since'        => '2019',
                    'gs_logo_map_embed'           => $this->get_dummy_map_embed( '-74.02,40.70,-73.96,40.76', 'Amara office' ),
                    'gs_logo_video_embed_url'     => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
                    'gs_logo_funding_type'        => 'seed',
                    'gs_logo_funding_source'      => 'Northstar Ventures',
                    'gs_logo_social_links'        => [
                        [ 'icon' => 'linkedin', 'name' => 'LinkedIn', 'url' => 'https://linkedin.example/company/amara' ],
                        [ 'icon' => 'instagram', 'name' => 'Instagram', 'url' => 'https://instagram.example/amara' ],
                        [ 'icon' => 'website', 'name' => 'Website', 'url' => 'https://amara.example/' ],
                    ],
                    'gs_logo_life_gallery'        => [ 'gs-logo-life-1', 'gs-logo-life-4', 'gs-logo-life-5', 'gs-logo-life-6' ],
                    'gs_logo_pitch_deck_id'       => 'gs-logo-company-profile',
                ])
            );
            
            $logos[] = array(
                'post_title'   => 'Earth 2.0',
                'post_content' => 'Earth 2.0 develops sustainable technology solutions designed to help organizations reduce environmental impact and build a smarter future.',
                'post_status'  => $post_status,
                'post_type'    => $post_type,
                'post_date'    => '2026-01-18 10:30:00',
                'tax_input'    => $this->get_tax_inputs([
                    'logo-category' => ['technology', 'business'],
                    'logo-tag'      => ['sustainable', 'innovation'],
                ]),
                'meta_input'   => $this->get_meta_inputs([
                    '_thumbnail_id'               => 'gs-logo-slider-2',
                    'client_url'                  => 'https://earth20.example/',
                    'gs_logo_contact_email'       => 'hello@earth20.example',
                    'gs_logo_address'             => '88 Market Street, San Francisco, CA 94105, USA',
                    'gs_logo_vision'              => '<p>Build technology that lowers environmental impact without slowing the work down.</p>',
                    'gs_logo_mission'             => '<p>Give organizations practical tools for a cleaner and smarter operation.</p>',
                    'gs_logo_established_on'      => '2014',
                    'gs_logo_num_employees'       => '120',
                    'gs_logo_formation_structure' => 'corporation',
                    'gs_logo_client_since'        => '2018',
                    'gs_logo_map_embed'           => $this->get_dummy_map_embed( '-122.48,37.74,-122.38,37.81', 'Earth 2.0 office' ),
                    'gs_logo_video_embed_url'     => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                    'gs_logo_funding_type'        => 'series_a',
                    'gs_logo_funding_source'      => 'Greenfield Capital',
                    'gs_logo_social_links'        => [
                        [ 'icon' => 'linkedin', 'name' => 'LinkedIn', 'url' => 'https://linkedin.example/company/earth20' ],
                        [ 'icon' => 'twitter', 'name' => 'X', 'url' => 'https://x.example/earth20' ],
                        [ 'icon' => 'website', 'name' => 'Website', 'url' => 'https://earth20.example/' ],
                    ],
                    'gs_logo_life_gallery'        => [ 'gs-logo-life-6', 'gs-logo-life-5', 'gs-logo-life-1', 'gs-logo-life-4' ],
                    'gs_logo_pitch_deck_id'       => 'gs-logo-company-profile',
                ])
            );
            
            $logos[] = array(
                'post_title'   => 'Fox Hub',
                'post_content' => 'Fox Hub is a creative workspace built for teams that want to collaborate, organize ideas, and turn ambitious concepts into successful projects.',
                'post_status'  => $post_status,
                'post_type'    => $post_type,
                'post_date'    => '2026-01-26 11:20:00',
                'tax_input'    => $this->get_tax_inputs([
                    'logo-category' => ['creative', 'business'],
                    'logo-tag'      => ['workspace', 'collaboration'],
                ]),
                'meta_input'   => $this->get_meta_inputs([
                    '_thumbnail_id'               => 'gs-logo-slider-3',
                    'client_url'                  => 'https://foxhub.example/',
                    'gs_logo_contact_email'       => 'hello@foxhub.example',
                    'gs_logo_address'             => '14 Worship Street, London EC2A 2BA, United Kingdom',
                    'gs_logo_vision'              => '<p>Give teams a shared place to shape ideas and finish the work together.</p>',
                    'gs_logo_mission'             => '<p>Build a creative workspace where collaboration stays simple and visible.</p>',
                    'gs_logo_established_on'      => '2018',
                    'gs_logo_num_employees'       => '32',
                    'gs_logo_formation_structure' => 'partnership',
                    'gs_logo_client_since'        => '2020',
                    'gs_logo_map_embed'           => $this->get_dummy_map_embed( '-0.16,51.49,-0.08,51.53', 'Fox Hub office' ),
                    'gs_logo_video_embed_url'     => 'https://www.youtube.com/watch?v=eRsGyueVLvQ',
                    'gs_logo_funding_type'        => 'bootstrapped',
                    'gs_logo_funding_source'      => 'Founder funded',
                    'gs_logo_social_links'        => [
                        [ 'icon' => 'instagram', 'name' => 'Instagram', 'url' => 'https://instagram.example/foxhub' ],
                        [ 'icon' => 'linkedin', 'name' => 'LinkedIn', 'url' => 'https://linkedin.example/company/foxhub' ],
                        [ 'icon' => 'website', 'name' => 'Website', 'url' => 'https://foxhub.example/' ],
                    ],
                    'gs_logo_life_gallery'        => [ 'gs-logo-life-5', 'gs-logo-life-1', 'gs-logo-life-2', 'gs-logo-life-3' ],
                ])
            );
            
            $logos[] = array(
                'post_title'   => 'Zoo TV',
                'post_content' => 'Zoo TV creates engaging digital entertainment and original video content for audiences across multiple platforms.',
                'post_status'  => $post_status,
                'post_type'    => $post_type,
                'post_date'    => '2026-02-04 08:45:00',
                'tax_input'    => $this->get_tax_inputs([
                    'logo-category' => ['creative'],
                    'logo-tag'      => ['media', 'entertainment'],
                ]),
                'meta_input'   => $this->get_meta_inputs([
                    '_thumbnail_id'               => 'gs-logo-slider-4',
                    'client_url'                  => 'https://zootv.example/',
                    'gs_logo_contact_email'       => 'hello@zootv.example',
                    'gs_logo_address'             => '6400 Sunset Boulevard, Los Angeles, CA 90028, USA',
                    'gs_logo_vision'              => '<p>Make original stories easy to find and worth watching.</p>',
                    'gs_logo_mission'             => '<p>Create digital entertainment that travels well across platforms.</p>',
                    'gs_logo_established_on'      => '2012',
                    'gs_logo_num_employees'       => '75',
                    'gs_logo_formation_structure' => 'llc',
                    'gs_logo_client_since'        => '2017',
                    'gs_logo_map_embed'           => $this->get_dummy_map_embed( '-118.40,34.00,-118.22,34.10', 'Zoo TV office' ),
                    'gs_logo_video_embed_url'     => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
                    'gs_logo_funding_type'        => 'series_b',
                    'gs_logo_funding_source'      => 'Horizon Media Partners',
                    'gs_logo_social_links'        => [
                        [ 'icon' => 'youtube', 'name' => 'YouTube', 'url' => 'https://youtube.example/zootv' ],
                        [ 'icon' => 'instagram', 'name' => 'Instagram', 'url' => 'https://instagram.example/zootv' ],
                        [ 'icon' => 'twitter', 'name' => 'X', 'url' => 'https://x.example/zootv' ],
                    ],
                    'gs_logo_life_gallery'        => [ 'gs-logo-life-2', 'gs-logo-life-3', 'gs-logo-life-5', 'gs-logo-life-1' ],
                ])
            );
            
            $logos[] = array(
                'post_title'   => 'Hex Lab',
                'post_content' => 'Hex Lab is a product innovation studio combining research, strategy, and creative development to build practical solutions for modern businesses.',
                'post_status'  => $post_status,
                'post_type'    => $post_type,
                'post_date'    => '2026-02-11 14:10:00',
                'tax_input'    => $this->get_tax_inputs([
                    'logo-category' => ['creative', 'business'],
                    'logo-tag'      => ['research', 'studio'],
                ]),
                'meta_input'   => $this->get_meta_inputs([
                    '_thumbnail_id'               => 'gs-logo-slider-5',
                    'client_url'                  => 'https://hexlab.example/',
                    'gs_logo_contact_email'       => 'hello@hexlab.example',
                    'gs_logo_address'             => '22 Torstrasse, 10119 Berlin, Germany',
                    'gs_logo_vision'              => '<p>Turn research into products that teams can ship and maintain.</p>',
                    'gs_logo_mission'             => '<p>Combine strategy and creative development into practical business solutions.</p>',
                    'gs_logo_established_on'      => '2015',
                    'gs_logo_num_employees'       => '26',
                    'gs_logo_formation_structure' => 'llc',
                    'gs_logo_client_since'        => '2019',
                    'gs_logo_map_embed'           => $this->get_dummy_map_embed( '13.36,52.49,13.45,52.54', 'Hex Lab office' ),
                    'gs_logo_video_embed_url'     => 'https://www.youtube.com/watch?v=LXb3EKWsInQ',
                    'gs_logo_funding_type'        => 'angel',
                    'gs_logo_funding_source'      => 'Studio Angels',
                    'gs_logo_social_links'        => [
                        [ 'icon' => 'linkedin', 'name' => 'LinkedIn', 'url' => 'https://linkedin.example/company/hexlab' ],
                        [ 'icon' => 'dribbble', 'name' => 'Dribbble', 'url' => 'https://dribbble.example/hexlab' ],
                        [ 'icon' => 'website', 'name' => 'Website', 'url' => 'https://hexlab.example/' ],
                    ],
                    'gs_logo_life_gallery'        => [ 'gs-logo-life-4', 'gs-logo-life-3', 'gs-logo-life-2', 'gs-logo-life-5' ],
                    'gs_logo_pitch_deck_id'       => 'gs-logo-company-profile',
                ])
            );
            
            $logos[] = array(
                'post_title'   => 'Light AI',
                'post_content' => 'Light AI builds intelligent software tools that help teams automate everyday tasks, analyze information, and work more efficiently.',
                'post_status'  => $post_status,
                'post_type'    => $post_type,
                'post_date'    => '2026-02-19 09:40:00',
                'tax_input'    => $this->get_tax_inputs([
                    'logo-category' => ['technology', 'business'],
                    'logo-tag'      => ['ai', 'automation'],
                ]),
                'meta_input'   => $this->get_meta_inputs([
                    '_thumbnail_id'               => 'gs-logo-slider-6',
                    'client_url'                  => 'https://lightai.example/',
                    'gs_logo_contact_email'       => 'hello@lightai.example',
                    'gs_logo_address'             => '410 Congress Avenue, Austin, TX 78701, USA',
                    'gs_logo_vision'              => '<p>Put useful automation in the hands of everyday teams.</p>',
                    'gs_logo_mission'             => '<p>Build software that takes repetitive work off the calendar.</p>',
                    'gs_logo_established_on'      => '2020',
                    'gs_logo_num_employees'       => '64',
                    'gs_logo_formation_structure' => 'corporation',
                    'gs_logo_client_since'        => '2022',
                    'gs_logo_map_embed'           => $this->get_dummy_map_embed( '-97.78,30.25,-97.70,30.31', 'Light AI office' ),
                    'gs_logo_video_embed_url'     => 'https://www.youtube.com/watch?v=C0DPdy98e4c',
                    'gs_logo_funding_type'        => 'series_a',
                    'gs_logo_funding_source'      => 'Lumen Ventures',
                    'gs_logo_social_links'        => [
                        [ 'icon' => 'linkedin', 'name' => 'LinkedIn', 'url' => 'https://linkedin.example/company/lightai' ],
                        [ 'icon' => 'github', 'name' => 'GitHub', 'url' => 'https://github.example/lightai' ],
                        [ 'icon' => 'twitter', 'name' => 'X', 'url' => 'https://x.example/lightai' ],
                    ],
                    'gs_logo_life_gallery'        => [ 'gs-logo-life-4', 'gs-logo-life-1', 'gs-logo-life-5', 'gs-logo-life-6' ],
                    'gs_logo_pitch_deck_id'       => 'gs-logo-company-profile',
                ])
            );
            
            $logos[] = array(
                'post_title'   => 'Muzica',
                'post_content' => 'Muzica is a digital music platform connecting listeners with fresh releases, independent artists, and curated audio experiences.',
                'post_status'  => $post_status,
                'post_type'    => $post_type,
                'post_date'    => '2026-03-02 13:25:00',
                'tax_input'    => $this->get_tax_inputs([
                    'logo-category' => ['creative', 'business'],
                    'logo-tag'      => ['music', 'platform'],
                ]),
                'meta_input'   => $this->get_meta_inputs([
                    '_thumbnail_id'               => 'gs-logo-slider-7',
                    'client_url'                  => 'https://muzica.example/',
                    'gs_logo_contact_email'       => 'hello@muzica.example',
                    'gs_logo_address'             => '1200 Division Street, Nashville, TN 37203, USA',
                    'gs_logo_vision'              => '<p>Connect listeners with music that still feels personal.</p>',
                    'gs_logo_mission'             => '<p>Give independent artists a clear path to a wider audience.</p>',
                    'gs_logo_established_on'      => '2017',
                    'gs_logo_num_employees'       => '41',
                    'gs_logo_formation_structure' => 'llc',
                    'gs_logo_client_since'        => '2021',
                    'gs_logo_map_embed'           => $this->get_dummy_map_embed( '-86.82,36.14,-86.74,36.19', 'Muzica office' ),
                    'gs_logo_video_embed_url'     => 'https://www.youtube.com/watch?v=R6MlUcmOul8',
                    'gs_logo_funding_type'        => 'seed',
                    'gs_logo_funding_source'      => 'Indie Audio Fund',
                    'gs_logo_social_links'        => [
                        [ 'icon' => 'soundcloud', 'name' => 'SoundCloud', 'url' => 'https://soundcloud.example/muzica' ],
                        [ 'icon' => 'instagram', 'name' => 'Instagram', 'url' => 'https://instagram.example/muzica' ],
                        [ 'icon' => 'youtube', 'name' => 'YouTube', 'url' => 'https://youtube.example/muzica' ],
                    ],
                    'gs_logo_life_gallery'        => [ 'gs-logo-life-2', 'gs-logo-life-5', 'gs-logo-life-1', 'gs-logo-life-3' ],
                ])
            );
            
            $logos[] = array(
                'post_title'   => 'Solaytic',
                'post_content' => 'Solaytic provides business intelligence and analytics solutions that transform complex data into clear, actionable insights.',
                'post_status'  => $post_status,
                'post_type'    => $post_type,
                'post_date'    => '2026-03-10 10:05:00',
                'tax_input'    => $this->get_tax_inputs([
                    'logo-category' => ['business'],
                    'logo-tag'      => ['analytics', 'data'],
                ]),
                'meta_input'   => $this->get_meta_inputs([
                    '_thumbnail_id'               => 'gs-logo-slider-8',
                    'client_url'                  => 'https://solaytic.example/',
                    'gs_logo_contact_email'       => 'hello@solaytic.example',
                    'gs_logo_address'             => '233 South Wacker Drive, Chicago, IL 60606, USA',
                    'gs_logo_vision'              => '<p>Make complex business data readable for the people who decide.</p>',
                    'gs_logo_mission'             => '<p>Turn reports into clear actions teams can take the same week.</p>',
                    'gs_logo_established_on'      => '2013',
                    'gs_logo_num_employees'       => '90',
                    'gs_logo_formation_structure' => 'corporation',
                    'gs_logo_client_since'        => '2016',
                    'gs_logo_map_embed'           => $this->get_dummy_map_embed( '-87.68,41.86,-87.60,41.91', 'Solaytic office' ),
                    'gs_logo_video_embed_url'     => 'https://www.youtube.com/watch?v=ScMzIvxBSi4',
                    'gs_logo_funding_type'        => 'series_b',
                    'gs_logo_funding_source'      => 'Insight Partners',
                    'gs_logo_social_links'        => [
                        [ 'icon' => 'linkedin', 'name' => 'LinkedIn', 'url' => 'https://linkedin.example/company/solaytic' ],
                        [ 'icon' => 'twitter', 'name' => 'X', 'url' => 'https://x.example/solaytic' ],
                        [ 'icon' => 'website', 'name' => 'Website', 'url' => 'https://solaytic.example/' ],
                    ],
                    'gs_logo_life_gallery'        => [ 'gs-logo-life-4', 'gs-logo-life-5', 'gs-logo-life-1', 'gs-logo-life-6' ],
                    'gs_logo_pitch_deck_id'       => 'gs-logo-company-profile',
                ])
            );
            
            $logos[] = array(
                'post_title'   => 'Treva',
                'post_content' => 'Treva helps growing companies bring technology, creative strategy, and business operations together through flexible digital services.',
                'post_status'  => $post_status,
                'post_type'    => $post_type,
                'post_date'    => '2026-03-18 15:35:00',
                'tax_input'    => $this->get_tax_inputs([
                    'logo-category' => ['technology', 'creative', 'business'],
                    'logo-tag'      => ['strategy', 'growth'],
                ]),
                'meta_input'   => $this->get_meta_inputs([
                    '_thumbnail_id'               => 'gs-logo-slider-9',
                    'client_url'                  => 'https://treva.example/',
                    'gs_logo_contact_email'       => 'hello@treva.example',
                    'gs_logo_address'             => '100 King Street West, Toronto, ON M5X 1A9, Canada',
                    'gs_logo_vision'              => '<p>Help growing companies keep technology, creative work, and operations in one plan.</p>',
                    'gs_logo_mission'             => '<p>Offer flexible digital services that match the stage a company is in.</p>',
                    'gs_logo_established_on'      => '2011',
                    'gs_logo_num_employees'       => '150',
                    'gs_logo_formation_structure' => 'llc',
                    'gs_logo_client_since'        => '2015',
                    'gs_logo_map_embed'           => $this->get_dummy_map_embed( '-79.42,43.64,-79.35,43.68', 'Treva office' ),
                    'gs_logo_video_embed_url'     => 'https://www.youtube.com/watch?v=Rok8b75pDsQ',
                    'gs_logo_funding_type'        => 'private_equity',
                    'gs_logo_funding_source'      => 'Growth Bridge',
                    'gs_logo_social_links'        => [
                        [ 'icon' => 'linkedin', 'name' => 'LinkedIn', 'url' => 'https://linkedin.example/company/treva' ],
                        [ 'icon' => 'facebook', 'name' => 'Facebook', 'url' => 'https://facebook.example/treva' ],
                        [ 'icon' => 'website', 'name' => 'Website', 'url' => 'https://treva.example/' ],
                    ],
                    'gs_logo_life_gallery'        => [ 'gs-logo-life-5', 'gs-logo-life-6', 'gs-logo-life-3', 'gs-logo-life-1' ],
                    'gs_logo_pitch_deck_id'       => 'gs-logo-company-profile',
                ])
            );
            
            $logos[] = array(
                'post_title'   => 'Velocity 9',
                'post_content' => 'Velocity 9 is a digital performance agency focused on fast, reliable web experiences and creative strategies that help brands grow.',
                'post_status'  => $post_status,
                'post_type'    => $post_type,
                'post_date'    => '2026-03-27 11:50:00',
                'tax_input'    => $this->get_tax_inputs([
                    'logo-category' => ['technology', 'creative'],
                    'logo-tag'      => ['performance', 'agency'],
                ]),
                'meta_input'   => $this->get_meta_inputs([
                    '_thumbnail_id'               => 'gs-logo-slider-10',
                    'client_url'                  => 'https://velocity9.example/',
                    'gs_logo_contact_email'       => 'hello@velocity9.example',
                    'gs_logo_address'             => '1201 Second Avenue, Seattle, WA 98101, USA',
                    'gs_logo_vision'              => '<p>Make brand websites fast enough that the work never feels in the way.</p>',
                    'gs_logo_mission'             => '<p>Pair performance with creative strategy so brands can grow with confidence.</p>',
                    'gs_logo_established_on'      => '2019',
                    'gs_logo_num_employees'       => '22',
                    'gs_logo_formation_structure' => 'corporation',
                    'gs_logo_client_since'        => '2021',
                    'gs_logo_map_embed'           => $this->get_dummy_map_embed( '-122.36,47.59,-122.30,47.63', 'Velocity 9 office' ),
                    'gs_logo_video_embed_url'     => 'https://www.youtube.com/watch?v=QH2-TGUlwu4',
                    'gs_logo_funding_type'        => 'bootstrapped',
                    'gs_logo_funding_source'      => 'Agency revenue',
                    'gs_logo_social_links'        => [
                        [ 'icon' => 'linkedin', 'name' => 'LinkedIn', 'url' => 'https://linkedin.example/company/velocity9' ],
                        [ 'icon' => 'dribbble', 'name' => 'Dribbble', 'url' => 'https://dribbble.example/velocity9' ],
                        [ 'icon' => 'instagram', 'name' => 'Instagram', 'url' => 'https://instagram.example/velocity9' ],
                    ],
                    'gs_logo_life_gallery'        => [ 'gs-logo-life-4', 'gs-logo-life-5', 'gs-logo-life-3', 'gs-logo-life-1' ],
                ])
            );
            
            $logos[] = array(
                'post_title'   => 'Aven',
                'post_content' => 'Aven partners with ambitious brands to create memorable identities, digital experiences, and practical strategies for sustainable growth.',
                'post_status'  => $post_status,
                'post_type'    => $post_type,
                'post_date'    => '2026-04-06 09:55:00',
                'tax_input'    => $this->get_tax_inputs([
                    'logo-category' => ['creative', 'business'],
                    'logo-tag'      => ['branding', 'design'],
                ]),
                'meta_input'   => $this->get_meta_inputs([
                    '_thumbnail_id'               => 'gs-logo-slider-11',
                    'client_url'                  => 'https://aven.example/',
                    'gs_logo_contact_email'       => 'hello@aven.example',
                    'gs_logo_address'             => '18 Rue de Rivoli, 75004 Paris, France',
                    'gs_logo_vision'              => '<p>Give ambitious brands an identity people remember and a plan they can follow.</p>',
                    'gs_logo_mission'             => '<p>Create brand systems and digital experiences that support lasting growth.</p>',
                    'gs_logo_established_on'      => '2009',
                    'gs_logo_num_employees'       => '36',
                    'gs_logo_formation_structure' => 'llc',
                    'gs_logo_client_since'        => '2014',
                    'gs_logo_map_embed'           => $this->get_dummy_map_embed( '2.30,48.84,2.38,48.88', 'Aven office' ),
                    'gs_logo_video_embed_url'     => 'https://www.youtube.com/watch?v=ZbZSe6N_BXs',
                    'gs_logo_funding_type'        => 'angel',
                    'gs_logo_funding_source'      => 'Brand Founders Circle',
                    'gs_logo_social_links'        => [
                        [ 'icon' => 'instagram', 'name' => 'Instagram', 'url' => 'https://instagram.example/aven' ],
                        [ 'icon' => 'behance', 'name' => 'Behance', 'url' => 'https://behance.example/aven' ],
                        [ 'icon' => 'linkedin', 'name' => 'LinkedIn', 'url' => 'https://linkedin.example/company/aven' ],
                    ],
                    'gs_logo_life_gallery'        => [ 'gs-logo-life-3', 'gs-logo-life-5', 'gs-logo-life-1', 'gs-logo-life-2' ],
                ])
            );
            
            $logos[] = array(
                'post_title'   => 'Circle',
                'post_content' => 'Circle creates connected digital solutions that simplify collaboration, communication, and day-to-day operations for modern organizations.',
                'post_status'  => $post_status,
                'post_type'    => $post_type,
                'post_date'    => '2026-04-15 12:20:00',
                'tax_input'    => $this->get_tax_inputs([
                    'logo-category' => ['technology', 'business'],
                    'logo-tag'      => ['communication', 'productivity'],
                ]),
                'meta_input'   => $this->get_meta_inputs([
                    '_thumbnail_id'               => 'gs-logo-slider-12',
                    'client_url'                  => 'https://circle.example/',
                    'gs_logo_contact_email'       => 'hello@circle.example',
                    'gs_logo_address'             => '8 Marina Boulevard, Singapore 018981',
                    'gs_logo_vision'              => '<p>Make everyday collaboration feel connected instead of scattered.</p>',
                    'gs_logo_mission'             => '<p>Simplify communication and operations for modern organizations.</p>',
                    'gs_logo_established_on'      => '2018',
                    'gs_logo_num_employees'       => '210',
                    'gs_logo_formation_structure' => 'corporation',
                    'gs_logo_client_since'        => '2020',
                    'gs_logo_map_embed'           => $this->get_dummy_map_embed( '103.82,1.27,103.87,1.31', 'Circle office' ),
                    'gs_logo_video_embed_url'     => 'https://www.youtube.com/watch?v=JGwWNGJdvx8',
                    'gs_logo_funding_type'        => 'series_c',
                    'gs_logo_funding_source'      => 'Orbit Capital',
                    'gs_logo_social_links'        => [
                        [ 'icon' => 'linkedin', 'name' => 'LinkedIn', 'url' => 'https://linkedin.example/company/circle' ],
                        [ 'icon' => 'twitter', 'name' => 'X', 'url' => 'https://x.example/circle' ],
                        [ 'icon' => 'website', 'name' => 'Website', 'url' => 'https://circle.example/' ],
                    ],
                    'gs_logo_life_gallery'        => [ 'gs-logo-life-1', 'gs-logo-life-5', 'gs-logo-life-4', 'gs-logo-life-6' ],
                ])
            );

            foreach ( $logos as $logo ) {
                // Insert the post into the database
                $post_id = wp_insert_post( $logo );
                // Add meta value for demo
                if ( $post_id ) add_post_meta( $post_id, 'gslogo-demo_data', 1 );
            }

            do_action( 'gslogo_dummy_logos_process_finished' );

        }

        public function delete_dummy_logos() {
            
            $logos = $this->get_dummy_logos();

            if ( empty($logos) ) return;

            foreach ($logos as $logo) {
                wp_delete_post( $logo->ID, true );
            }

            delete_transient( 'gslogo_dummy_logos' );

        }

        public function get_dummy_logos() {

            $logos = get_transient( 'gslogo_dummy_logos' );

            if ( false !== $logos ) return $logos;

            $logos = get_posts( array(
                'numberposts' => -1,
                'post_type'   => 'gs-logo-slider',
                'meta_key' => 'gslogo-demo_data',
                'meta_value' => 1,
            ));
            
            if ( is_wp_error($logos) || empty($logos) ) {
                delete_transient( 'gslogo_dummy_logos' );
                return [];
            }
            
            set_transient( 'gslogo_dummy_logos', $logos, 3 * MINUTE_IN_SECONDS );

            return $logos;

        }

        public function http_request_args( $args ) {
            
            $args['sslverify'] = false;

            return $args;

        }

        // Attachments
        public function create_dummy_attachments() {

            do_action( 'gslogo_dummy_attachments_process_start' );

            require_once( ABSPATH . 'wp-admin/includes/image.php' );

            $attachment_files = [
                'gs-logo-slider-1.png',
                'gs-logo-slider-2.png',
                'gs-logo-slider-3.png',
                'gs-logo-slider-4.png',
                'gs-logo-slider-5.png',
                'gs-logo-slider-6.png',
                'gs-logo-slider-7.png',
                'gs-logo-slider-8.png',
                'gs-logo-slider-9.png',
                'gs-logo-slider-10.png',
                'gs-logo-slider-11.png',
                'gs-logo-slider-12.png',
                'gs-logo-life-1.jpg',
                'gs-logo-life-2.jpg',
                'gs-logo-life-3.jpg',
                'gs-logo-life-4.jpg',
                'gs-logo-life-5.jpg',
                'gs-logo-life-6.jpg',
                'gs-logo-company-profile.pdf',
            ];

            add_filter( 'http_request_args', [ $this, 'http_request_args' ] );

            wp_raise_memory_limit( 'image' );

            foreach ( $attachment_files as $file ) {

                $file = GSL_PLUGIN_URI . 'assets/img/dummy-data/' . $file;

                $filename = basename($file);
                $attachment_name = preg_replace( '/\.[^.]+$/', '', $filename );

                $get = wp_remote_get( $file );
                $type = wp_remote_retrieve_header( $get, 'content-type' );
                $filetype = wp_check_filetype( $filename );

                if ( ! empty( $filetype['type'] ) ) {
                    $type = $filetype['type'];
                }

                $mirror = wp_upload_bits( $filename, null, wp_remote_retrieve_body( $get ) );
                
                // Prepare an array of post data for the attachment.
                $attachment = array(
                    'guid'           => $mirror['url'],
                    'post_mime_type' => $type,
                    'post_title'     => 'gs-logo-company-profile' === $attachment_name ? 'Company Profile' : $attachment_name,
                    'post_name'      => $attachment_name,
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );
                
                // Insert the attachment.
                $attach_id = wp_insert_attachment( $attachment, $mirror['file'] );
                
                // Generate the metadata for the attachment, and update the database record.
                $attach_data = wp_generate_attachment_metadata( $attach_id, $mirror['file'] );
                wp_update_attachment_metadata( $attach_id, $attach_data );

                add_post_meta( $attach_id, 'gslogo-demo_data', 1 );

            }

            remove_filter( 'http_request_args', [ $this, 'http_request_args' ] );

            do_action( 'gslogo_dummy_attachments_process_finished' );

        }

        public function delete_dummy_attachments() {
            
            $attachments = $this->get_dummy_attachments();

            if ( empty($attachments) ) return;

            foreach ($attachments as $attachment) {
                wp_delete_attachment( $attachment->ID, true );
            }

            delete_transient( 'gslogo_dummy_attachments' );

        }

        public function get_dummy_attachments() {

            $attachments = get_transient( 'gslogo_dummy_attachments' );

            if ( false !== $attachments ) return $attachments;

            $attachments = get_posts( array(
                'numberposts' => -1,
                'post_type'   => 'attachment',
                'post_status' => 'inherit',
                'meta_key' => 'gslogo-demo_data',
                'meta_value' => 1,
            ));
            
            if ( is_wp_error($attachments) || empty($attachments) ) {
                delete_transient( 'gslogo_dummy_attachments' );
                return [];
            }
            
            set_transient( 'gslogo_dummy_attachments', $attachments, 3 * MINUTE_IN_SECONDS );

            return $attachments;

        }
        
        // Terms
        public function create_dummy_terms() {

            do_action( 'gslogo_dummy_terms_process_start' );
            
            $terms = [

                // Logo Categories
                [
                    'name'  => 'Technology',
                    'slug'  => 'technology',
                    'group' => 'logo-category',
                ],
                [
                    'name'  => 'Creative',
                    'slug'  => 'creative',
                    'group' => 'logo-category',
                ],
                [
                    'name'  => 'Business',
                    'slug'  => 'business',
                    'group' => 'logo-category',
                ],

                // Logo Tags
                [
                    'name'  => 'Digital',
                    'slug'  => 'digital',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Modern',
                    'slug'  => 'modern',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Sustainable',
                    'slug'  => 'sustainable',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Innovation',
                    'slug'  => 'innovation',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Workspace',
                    'slug'  => 'workspace',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Collaboration',
                    'slug'  => 'collaboration',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Media',
                    'slug'  => 'media',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Entertainment',
                    'slug'  => 'entertainment',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Research',
                    'slug'  => 'research',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Studio',
                    'slug'  => 'studio',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'AI',
                    'slug'  => 'ai',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Automation',
                    'slug'  => 'automation',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Music',
                    'slug'  => 'music',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Platform',
                    'slug'  => 'platform',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Analytics',
                    'slug'  => 'analytics',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Data',
                    'slug'  => 'data',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Strategy',
                    'slug'  => 'strategy',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Growth',
                    'slug'  => 'growth',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Performance',
                    'slug'  => 'performance',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Agency',
                    'slug'  => 'agency',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Branding',
                    'slug'  => 'branding',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Design',
                    'slug'  => 'design',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Communication',
                    'slug'  => 'communication',
                    'group' => 'logo-tag',
                ],
                [
                    'name'  => 'Productivity',
                    'slug'  => 'productivity',
                    'group' => 'logo-tag',
                ],

            ];

            foreach ( $terms as $term ) {

                $response = wp_insert_term(
                    $term['name'],
                    $term['group'],
                    [
                        'slug' => $term['slug'],
                    ]
                );

                if ( ! is_wp_error( $response ) ) {
                    add_term_meta( $response['term_id'], 'gslogo-demo_data', 1 );
                }

            }

            do_action( 'gslogo_dummy_terms_process_finished' );

        }
        
        public function delete_dummy_terms() {
            
            $terms = $this->get_dummy_terms();

            if ( empty($terms) ) return;
    
            foreach ( $terms as $term ) {
                wp_delete_term( $term['term_id'], $term['taxonomy'] );
            }

            delete_transient( 'gslogo_dummy_terms' );

        }

        public function get_dummy_terms() {

            $terms = get_transient( 'gslogo_dummy_terms' );

            if ( false !== $terms ) return $terms;

            $taxonomies = $this->get_taxonomy_list();

            $terms = get_terms( array(
                'taxonomy' => $taxonomies,
                'hide_empty' => false,
                'meta_key' => 'gslogo-demo_data',
                'meta_value' => 1,
            ));

            $terms = json_decode( json_encode( $terms ), true ); // Object to Array
            
            if ( is_wp_error($terms) || empty($terms) ) {
                delete_transient( 'gslogo_dummy_terms' );
                return [];
            }

            set_transient( 'gslogo_dummy_terms', $terms, 3 * MINUTE_IN_SECONDS );

            return $terms;

        }

        // Shortcode
        public function create_dummy_shortcodes() {

            do_action( 'gslogo_dummy_shortcodes_process_start' );

            plugin()->builder->create_dummy_shortcodes();

            do_action( 'gslogo_dummy_shortcodes_process_finished' );

        }

        public function delete_dummy_shortcodes() {
            
            plugin()->builder->delete_dummy_shortcodes();

        }

    }

}

GS_Logo_Slider_Dummy_Data::get_instance();