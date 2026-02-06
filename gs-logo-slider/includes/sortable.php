<?php
namespace GSLOGO;

use function GSLOGO\plugin;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class for making post types sortable.
 * Adds menu item for sorting posts.
 * They are not paged, so better not to add them to a post type
 * that will contain hundreds of items. More like something little used.
 *
 * Paremeters:
 * $location    - define path to js and css in __construct
 * $posttype	- the post type you want to be sortable
 * $title	- The title of the post type
 * $ppp		- the number of recent items to return - defaults to all (-1).
 *
 *		Define page template through:
 *		$sort = new GS_Logo_Sortable($posttype, $title, 10)
 *
 * This class was heavily influenced by http://soulsizzle.com/jquery/create-an-ajax-sorter-for-wordpress-custom-post-types/
 */

if ( ! class_exists('GS_Logo_Sortable') ) {
	class GS_Logo_Sortable {

		var $location = ''; // path to css/js
		var $posttype = 'gs-logo-slider';
		var $title = '';
		var $ppp = '-1'; // postsperpage

		public function __construct( $posttype, $title, $ppp = -1 ) {

			$this->location = get_stylesheet_directory_uri() . '/_inc/functions/'; // path to css/js
			$this->posttype = $posttype;
			$this->title = $title;
			$this->ppp = $ppp;

			add_action( 'admin_init', array( $this, 'gslogo_maybe_add_term_order_column' ) );
			add_action( 'admin_menu' , array( $this, 'gs_logo_enable_sort' ) );
			add_filter( 'posts_orderby', array( $this, 'gs_logo_order_posts' ), 10, 2 );
			add_action('admin_init', [$this, 'maybe_redirect']);
			add_action( 'admin_enqueue_scripts', array( $this, 'gs_logo_sort_scripts' ) );
			add_action( 'wp_ajax_sort_logos', array( $this, 'gs_logo_save_logo_order' ) );
			add_action( 'wp_ajax_sort_categories', array( $this, 'gs_logo_save_logo_category_order' ) );
		}

		/**
		 * Add term_order column to wp_terms table if it doesn't exist
		 * Run once per site
		 * 
		 * @return void
		 */
		function gslogo_maybe_add_term_order_column() {
			// Skip if we already did this
			if ( get_option( 'gslogo_term_order_column_done' ) ) {
				return;
			}

			global $wpdb;

			$table  = $wpdb->terms;         // e.g. wp_terms
			$column = 'term_order';

			// Check if column exists
			$exists = $wpdb->get_var(
				$wpdb->prepare( "SHOW COLUMNS FROM `$table` LIKE %s", $column )
			);

			if ( ! $exists ) {
				// Add column with a sane type for ordering
				$added = $wpdb->query( "ALTER TABLE `$table` ADD `$column` BIGINT(20) NOT NULL DEFAULT 0" );

				if ( false === $added ) {
					error_log( "[GS Logo] Failed to add `$column` to `$table`: {$wpdb->last_error}" );
					return;
				}

				// Add index for faster ORDER BY term_order
				// Ignore errors if it already exists later
				$wpdb->query( "ALTER TABLE `$table` ADD INDEX `{$column}_idx` (`$column`)" );
			}

			// Mark completed
			update_option( 'gslogo_term_order_column_done', 1 );
		}

		/**
		 * Alter the query on front and backend to order posts as desired.
		 */
		public function gs_logo_order_posts( $orderby, $wp_query ) {
			global $wpdb;
			
			if ( ! isset($wp_query) || ! is_main_query() ) return $orderby;
		
			if ( is_post_type_archive( array($this->posttype)) ) {
				$orderby = "{$wpdb->posts}.menu_order, {$wpdb->posts}.post_date DESC";
			}

			return $orderby;
		}

		/**
		 * Get the full URL
		 */
		public function get_full_url() {
			// Get the protocol
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

			// Get the host
			$host = $_SERVER['HTTP_HOST'];

			// Get the request URI
			$uri = $_SERVER['REQUEST_URI'];

			// Combine them to get the full URL
			$full_url = $protocol . $host . $uri;

			return $full_url;
		}

		/**
		 * Get the URL with object type
		 */
		public function get_url_with_object_type( $object = 'gs-logo-slider' ) {
			return add_query_arg( 'object_type', $object, $this->get_full_url() );
		}

		/**
		 * Redirect to the correct page
		 */
		public function maybe_redirect() {
			if ( isset($_GET['post_type']) && $_GET['post_type'] == 'gs-logo-slider' && isset($_GET['page']) && $_GET['page'] === 'sort_gs-logo-slider' && empty($_GET['object_type']) ) {
				wp_redirect( $this->get_url_with_object_type() );
				exit;
			}
		}

		/**
		 * Add Sort menu
		 */
		public function gs_logo_enable_sort() {
			add_submenu_page('edit.php?post_type=' . $this->posttype, 'Sort Order', 'Sort Order', 'edit_posts', 'sort_' . $this->posttype, array( $this, 'sort_order'));
		}

		/**
		 * Display Sort admin page
		 */
		public function sort_order() {

			$object_type = isset($_GET['object_type']) ? $_GET['object_type'] : 'gs-logo-slider';
		
			if ( ! is_pro_active() ) : ?>

			<div class="gs-logo-disable--sort-page">
				<div class="gs-logo-disable--sort-inner">
					<div class="gs-logo-disable--term-message"><a href="https://www.gsplugins.com/product/gs-logo-slider/#pricing">Upgrade to PRO</a></div>
				</div>
			</div>

			<?php endif; ?>

			<div class="wrap">

				<div class="gs-plugins--sort-page">

					<div class="gs-plugins--sort-links">
						<a class="<?php echo $object_type === 'gs-logo-slider' ? 'gs-sort-active' : ''; ?>" href="<?php echo esc_url( $this->get_url_with_object_type('gs-logo-slider') ); ?>">Logos</a>
						<a class="<?php echo $object_type === 'logo-category' ? 'gs-sort-active' : ''; ?>" href="<?php echo esc_url( $this->get_url_with_object_type('logo-category') ); ?>">Categories</a>
					</div>

					<div class="gs-plugins--sort-content">
						
						<?php if ($object_type === 'gs-logo-slider') : ?>

							<?php $this->sort_logos(); ?>

						<?php else : ?>

							<?php $this->sort_categories(); ?>

						<?php endif; ?>
					</div>

				</div>

			</div><!-- #wrap -->
		
			<?php
		}

		/**
		 * Display Sortable Logos
		 */
		public function sort_logos() {
			
			$sortable = new \WP_Query('post_type=' . $this->posttype . '&posts_per_page=' . $this->ppp . '&orderby=menu_order&order=ASC');

			?>

				<div id="icon-edit" class="icon32"></div>
				<h2 class="gs-plugin-title"><span><?php _e('Custom Order for Logos', 'gslogo'); ?></span> <img src="<?php echo GSL_PLUGIN_URI; ?>/assets/img/loader.svg" id="loading-animation" /></h2>

				<div class="gs-logo-sorting-wrap">

					<div class="gs-logo-slider--sort-area">
	
						<?php if ( $sortable->have_posts() ) : ?>
				
							<ul id="sortable-list">
								<?php while ( $sortable->have_posts() ) :
										
									$sortable->the_post();
									$term_obj_list = get_the_terms( get_the_ID(), 'logo-category' );
									$terms_string = '';
			
									if ( is_array($term_obj_list) || is_object($term_obj_list) ) {
										$terms_string = join('</span><span>', wp_list_pluck($term_obj_list, 'name'));
									}
			
									if ( !empty($terms_string) ) $terms_string = '<span>' . $terms_string . '</span>';
								
									?>
									
									<li id="<?php the_id(); ?>">
										<div class="sortable-content sortable-icon"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="28" height="28" viewBox="0 0 28 28"><path d="M28 14c0 0.266-0.109 0.516-0.297 0.703l-4 4c-0.187 0.187-0.438 0.297-0.703 0.297-0.547 0-1-0.453-1-1v-2h-6v6h2c0.547 0 1 0.453 1 1 0 0.266-0.109 0.516-0.297 0.703l-4 4c-0.187 0.187-0.438 0.297-0.703 0.297s-0.516-0.109-0.703-0.297l-4-4c-0.187-0.187-0.297-0.438-0.297-0.703 0-0.547 0.453-1 1-1h2v-6h-6v2c0 0.547-0.453 1-1 1-0.266 0-0.516-0.109-0.703-0.297l-4-4c-0.187-0.187-0.297-0.438-0.297-0.703s0.109-0.516 0.297-0.703l4-4c0.187-0.187 0.438-0.297 0.703-0.297 0.547 0 1 0.453 1 1v2h6v-6h-2c-0.547 0-1-0.453-1-1 0-0.266 0.109-0.516 0.297-0.703l4-4c0.187-0.187 0.438-0.297 0.703-0.297s0.516 0.109 0.703 0.297l4 4c0.187 0.187 0.297 0.438 0.297 0.703 0 0.547-0.453 1-1 1h-2v6h6v-2c0-0.547 0.453-1 1-1 0.266 0 0.516 0.109 0.703 0.297l4 4c0.187 0.187 0.297 0.438 0.297 0.703z"/></svg></div>
										<div class="sortable-content sortable-thumbnail"><span><?php the_post_thumbnail(); ?></span></div>
										<div class="sortable-content sortable-title"><?php the_title(); ?></div>
										<div class="sortable-content sortable-group"><?php echo $terms_string; ?></div>
									</li>
						
								<?php endwhile; ?>
							</ul>
						
						<?php else: ?>
							
							<div class="notice notice-warning">
								<h3><?php _e( 'No Logo Found!', 'gslogo' ); ?></h3>
								<p><?php _e( 'We didn\'t find any logo.</br>Please add some logos to sort them.', 'gslogo' ); ?></p>
								<a href="<?php echo admin_url('post-new.php?post_type=gs-logo-slider'); ?>" class="button button-primary button-large"><?php _e( 'Add Logo', 'gslogo' ); ?></a>
							</div>
			
						<?php endif; ?>
			
						<?php if ( $this->ppp != -1 ) echo '<p>Latest ' . $this->ppp . ' shown</p>'; ?>
					</div>

					<div class="gs-logo-slider--docs-area">
						<h3><?php esc_html_e('Query Settings for Logos', 'gslogo'); ?></h3>

						<div class="gs-logo-slider--docs-area-content">
							
							<ol>
								<li>Create or Edit a Shortcode From <strong>GS Logos > Logo Shortcode</strong>.</li>
								<li>Then proceed to the 3rd tab labeled <strong>Query Settings</strong>.</li>
								<li>Set <strong>Order by</strong> to <strong>Custom Order</strong>.</li>
								<li>Set <strong>Order</strong> to <strong>ASC</strong>.</li>
							</ol>
		
							<ul>
								<li>Follow <a href="https://docs.gsplugins.com/gs-logo-slider/manage-the-logos/sort-order/" target="_blank">Documentation</a> to learn more.</li>
								<li><a href="https://www.gsplugins.com/contact/" target="_blank">Contact us</a> for support.</li>
							</ul>

						</div>
					</div>
				</div>

			<?php
		}

		/**
		 * Display Sortable Categories
		 */
		public function sort_categories() {

			$terms = get_terms( array(
				'taxonomy' => 'logo-category',
				'hide_empty' => false,
				'orderby' => 'term_order',
				'order' => 'ASC',
			) );
			
			?>

				<div id="icon-edit" class="icon32"></div>
				<h2 class="gs-plugin-title"><span><?php _e('Custom Order for Categories', 'gslogo'); ?></span> <img src="<?php echo GSL_PLUGIN_URI; ?>/assets/img/loader.svg" id="loading-animation" /></h2>

				<div class="gs-logo-sorting-wrap">

					<div class="gs-logo-slider--sort-area">
	
						<?php if ( ! empty($terms) ) : ?>
				
							<ul id="sortable-list">
								<?php foreach ( $terms as $term ) :
								
									?>
									
									<li id="<?php esc_attr_e( $term->term_id ); ?>">
										<div class="sortable-content sortable-icon"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="28" height="28" viewBox="0 0 28 28"><path d="M28 14c0 0.266-0.109 0.516-0.297 0.703l-4 4c-0.187 0.187-0.438 0.297-0.703 0.297-0.547 0-1-0.453-1-1v-2h-6v6h2c0.547 0 1 0.453 1 1 0 0.266-0.109 0.516-0.297 0.703l-4 4c-0.187 0.187-0.438 0.297-0.703 0.297s-0.516-0.109-0.703-0.297l-4-4c-0.187-0.187-0.297-0.438-0.297-0.703 0-0.547 0.453-1 1-1h2v-6h-6v2c0 0.547-0.453 1-1 1-0.266 0-0.516-0.109-0.703-0.297l-4-4c-0.187-0.187-0.297-0.438-0.297-0.703s0.109-0.516 0.297-0.703l4-4c0.187-0.187 0.438-0.297 0.703-0.297 0.547 0 1 0.453 1 1v2h6v-6h-2c-0.547 0-1-0.453-1-1 0-0.266 0.109-0.516 0.297-0.703l4-4c0.187-0.187 0.438-0.297 0.703-0.297s0.516 0.109 0.703 0.297l4 4c0.187 0.187 0.297 0.438 0.297 0.703 0 0.547-0.453 1-1 1h-2v6h6v-2c0-0.547 0.453-1 1-1 0.266 0 0.516 0.109 0.703 0.297l4 4c0.187 0.187 0.297 0.438 0.297 0.703z"/></svg></div>
										<div class="sortable-content sortable-title"><?php esc_html_e( $term->name ); ?></div>
										<div class="sortable-content sortable-group"><?php echo '<span>' . esc_html($term->count) . ' Logos</span>'; ?></div>
									</li>
						
								<?php endforeach; ?>
							</ul>
						
						<?php else: ?>
							
							<div class="notice notice-warning">
								<h3><?php _e( 'No Category Found!', 'gslogo' ); ?></h3>
								<p><?php _e( 'We didn\'t find any Category.</br>Please add some categories to sort them.', 'gslogo' ); ?></p>
								<a href="<?php echo admin_url('edit-tags.php?taxonomy=logo-category&post_type=gs-logo-slider'); ?>" class="button button-primary button-large"><?php _e( 'Add Category', 'gslogo' ); ?></a>
							</div>
			
						<?php endif; ?>
			
					</div>

					<div class="gs-logo-slider--docs-area">
						<h3><?php esc_html_e('Query Settings for Categories', 'gslogo'); ?></h3>

						<div class="gs-logo-slider--docs-area-content">
							
							<ol>
								<li>Create or Edit a Shortcode From <strong>GS Logos > Logo Shortcode</strong>.</li>
								<li>Then proceed to the 3rd tab labeled <strong>Query Settings</strong>.</li>
								<li>Set <strong>Category Order by</strong> to <strong>Custom Order</strong>.</li>
								<li>Set <strong>Category Order</strong> to <strong>ASC</strong>.</li>
							</ol>
		
							<ul>
								<li>Follow <a href="https://docs.gsplugins.com/gs-logo-slider/manage-the-logos/sort-order/#reordering-groups-categories" target="_blank">Documentation</a> to learn more.</li>
								<li><a href="https://www.gsplugins.com/contact/" target="_blank">Contact us</a> for support.</li>
							</ul>

						</div>
					</div>
				</div>

			<?php
		}

		/**
		 * Add JS and CSS to admin
		 */
		public function gs_logo_sort_scripts( $hook ) {

			if ( $hook != 'gs-logo-slider_page_sort_gs-logo-slider' ) return;

			wp_enqueue_style('gs-logo-sort', GSL_PLUGIN_URI . 'assets/admin/css/gs-logo-sort.min.css', array(), GSL_VERSION);
			wp_enqueue_script('gs-logo-sort', GSL_PLUGIN_URI . 'assets/admin/js/gs-logo-sort.min.js', array('jquery', 'jquery-ui-sortable'), GSL_VERSION, true);

			if ( empty($_GET['object_type']) || $_GET['object_type'] == 'gs-logo-slider' ) {
				$action = 'sort_logos';
			} else if ( $_GET['object_type'] == 'logo-category' ) {
				$action = 'sort_categories';
			} else {
				$action = 'update_taxonomy_order';
			}

			$data = [
				'nonce' => wp_create_nonce('_gslogo_save_sort_order_gs_'),
				'gs_logo_pro_is_valid' => wp_validate_boolean( is_gs_logo_pro_valid() ),
				'action' => $action
			];

			wp_localize_script('gs-logo-sort', '_gslogo_sort_data', $data);

		}

		/**
		 * Save the sort logo order to database
		 */
		public function gs_logo_save_logo_order() {

			if ( ! is_gs_logo_pro_valid() ) return;

			if ( empty($_POST['_nonce']) || ! wp_verify_nonce( $_POST['_nonce'], '_gslogo_save_sort_order_gs_') ) {
				wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );
			}

			global $wpdb;
		
			$order = explode(',', $_POST['order']);
			$counter = 0;
		
			foreach ($order as $post_id) {
				$wpdb->update($wpdb->posts, array( 'menu_order' => $counter ), array( 'ID' => $post_id) );
				$counter++;
			}

			return wp_send_json_success( __('Logo order saved successfully', 'gslogo'), 200 );

		}

		/**
		 * Save the sort category order to database
		 */
		public function gs_logo_save_logo_category_order() {
			if ( ! is_gs_logo_pro_valid() ) {
				wp_send_json_error( __('License invalid', 'gslogo'), 403 );
			}

			// Capability: adjust if you created a custom capability
			if ( ! current_user_can('manage_categories') ) {
				wp_send_json_error( __('Permission denied', 'gslogo'), 403 );
			}

			if ( empty($_POST['_nonce']) || ! wp_verify_nonce( $_POST['_nonce'], '_gslogo_save_sort_order_gs_') ) {
				wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );
			}

			global $wpdb;
		
			$order = explode(',', sanitize_text_field($_POST['order']));
			$counter = 0;
		
			// Save the category order here
			foreach ($order as $term_id) {
				$wpdb->update( $wpdb->terms, array( 'term_order' => $counter ), array( 'term_id' => (int) $term_id ) );
				$counter++;
			}

			return wp_send_json_success( __('Category order saved successfully', 'gslogo'), 200 );
		}

	}
}

$gs_logo_custom_order = new GS_Logo_Sortable( 'gs-logo-slider', 'GS Logo Slider' );