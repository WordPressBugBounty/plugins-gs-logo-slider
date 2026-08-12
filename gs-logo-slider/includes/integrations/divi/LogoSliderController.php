<?php
/**
 * Divi 5 REST Controller: GS Logo Slider preview HTML.
 *
 * @package GSLOGO
 */

namespace GSLOGO\Divi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST endpoints used by the Visual Builder edit component.
 */
class LogoSliderController {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'gs-logo/v1';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/divi/render',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ self::class, 'render' ],
					'permission_callback' => [ self::class, 'permissions_check' ],
					'args'                => self::get_endpoint_args(),
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ self::class, 'render' ],
					'permission_callback' => [ self::class, 'permissions_check' ],
					'args'                => self::get_endpoint_args(),
				],
			]
		);
	}

	/**
	 * Shared endpoint args.
	 *
	 * @return array
	 */
	protected static function get_endpoint_args() {
		return [
			'shortcode_id' => [
				'required'          => false,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			],
		];
	}

	/**
	 * Permission check for Visual Builder preview.
	 *
	 * Cookie + X-WP-Nonce auth is handled by WordPress REST core.
	 * Mirror Divi core modules by also accepting Visual Builder capability.
	 *
	 * @return bool|WP_Error
	 */
	public static function permissions_check() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You must be logged in to preview GS Logo shortcodes.', 'gslogo' ),
				[ 'status' => 401 ]
			);
		}

		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		if (
			class_exists( '\ET\Builder\Framework\UserRole\UserRole' )
			&& \ET\Builder\Framework\UserRole\UserRole::can_current_user_use_visual_builder()
		) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to preview GS Logo shortcodes.', 'gslogo' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Return rendered shortcode HTML for VB preview.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function render( WP_REST_Request $request ) {
		$shortcode_id = absint( $request->get_param( 'shortcode_id' ) );

		if ( ! $shortcode_id ) {
			$shortcode_id = absint( LogoSliderModule::get_default_shortcode_id() );
		}

		if ( ! $shortcode_id ) {
			return rest_ensure_response(
				[
					'html'         => sprintf(
						'<div class="gs-logo-divi-empty">%s</div>',
						esc_html__( 'No shortcodes found. Create one in GS Logo first.', 'gslogo' )
					),
					'shortcode_id' => 0,
				]
			);
		}

		try {
			$html = LogoSliderModule::render_shortcode_html( $shortcode_id );
		} catch ( \Throwable $exception ) {
			return new WP_Error(
				'gs_logo_divi_render_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Failed to render shortcode: %s', 'gslogo' ),
					$exception->getMessage()
				),
				[ 'status' => 500 ]
			);
		}

		return rest_ensure_response(
			[
				'html'         => $html,
				'shortcode_id' => $shortcode_id,
			]
		);
	}
}
