<?php
namespace GSLOGO;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Welcome page shown on activation and under the GS Logos menu.
 */
class Welcome {

    const PAGE_SLUG = 'gs-logo-welcome';

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_sub_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    public function register_sub_menu() {
        add_submenu_page(
            'edit.php?post_type=gs-logo-slider', __( 'Welcome to GS Logo Slider', 'gslogo' ), __( 'Welcome', 'gslogo' ), 'manage_options', self::PAGE_SLUG, [ $this, 'view' ]
        );

        if ( ! is_pro_active() || ! is_gs_logo_pro_valid() ) {
            add_submenu_page(
                'edit.php?post_type=gs-logo-slider',
                __( 'Go Pro', 'gslogo' ),
                '<span class="gs-logo-go-pro">' . esc_html__( 'Go Pro', 'gslogo' ) . '</span>',
                'manage_options',
                'gs-logo-shortcode#/go-pro',
                [ plugin()->builder, 'view' ]
            );
        }
    }

    public function enqueue_scripts( $hook ) {

        if ( $hook !== 'gs-logo-slider_page_' . self::PAGE_SLUG ) return;

        wp_enqueue_style( 'gs-logo-welcome', GSL_PLUGIN_URI . 'assets/admin/css/gs-logo-welcome.min.css', [], GSL_VERSION );
        wp_enqueue_script( 'gs-logo-welcome', GSL_PLUGIN_URI . 'assets/admin/js/gs-logo-welcome.min.js', [], GSL_VERSION, true );
    }

    public function view() {
        $changelog = $this->get_changelog();
        include GSL_PLUGIN_DIR . 'includes/welcome/view.php';
    }

    public static function get_page_url() {
        return admin_url( 'edit.php?post_type=gs-logo-slider&page=' . self::PAGE_SLUG );
    }

    /**
     * Parse the latest releases from the Changelog section of readme.txt.
     *
     * Expects the readme format: "= Version 3.8.8 [9th Sep 2026] =" followed by "* [Added]: Text." lines.
     */
    public function get_changelog( $limit = 5 ) {

        $file = GSL_PLUGIN_DIR . 'readme.txt';

        if ( ! is_readable( $file ) ) return [];

        $readme = file_get_contents( $file );
        $start  = strpos( $readme, '== Changelog ==' );

        if ( false === $start ) return [];

        $section = substr( $readme, $start + strlen( '== Changelog ==' ) );
        $end     = strpos( $section, "\n== " );

        if ( false !== $end ) $section = substr( $section, 0, $end );

        $releases = [];

        foreach ( preg_split( '/\r\n|\r|\n/', $section ) as $line ) {

            $line = trim( $line );

            if ( preg_match( '/^=\s*(?:Version\s+)?([^\[=]+?)\s*(?:\[(.+?)\])?\s*=$/i', $line, $match ) ) {

                if ( count( $releases ) >= $limit ) break;

                $releases[] = [
                    'version' => $match[1],
                    'date'    => isset( $match[2] ) ? $match[2] : '',
                    'items'   => [],
                ];

                continue;
            }

            if ( empty( $releases ) || ! preg_match( '/^[\*\-]\s*(?:\[([^\]]+)\]\s*:?\s*)?(.+)$/', $line, $match ) ) continue;

            $releases[ count( $releases ) - 1 ]['items'][] = [
                'tag'  => trim( $match[1] ),
                'text' => trim( $match[2] ),
            ];
        }

        return $releases;
    }

    /**
     * Escape a changelog line and convert the readme's **bold** and *italic* markup.
     */
    public static function format_changelog_text( $text ) {
        $text = esc_html( $text );
        $text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
        $text = preg_replace( '/\*(.+?)\*/', '<em>$1</em>', $text );
        return $text;
    }

}
