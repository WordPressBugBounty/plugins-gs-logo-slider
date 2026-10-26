<?php
namespace GSLOGO;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** @var array $changelog Releases parsed from readme.txt by Welcome::get_changelog(). */

$docs_url  = 'https://docs.gsplugins.com/gs-logo-slider/';
$video_id  = '6C8fc4dOd1E';
$pro_ready = is_pro_active() && function_exists( 'gs_logo_pro_is_valid' ) && gs_logo_pro_is_valid();

$faqs = [
    [
        'q' => __( 'How do I add a new logo?', 'gslogo' ),
        'a' => '<p>' . __( 'To add a new logo, go to <b>GS Logos &gt; Add New</b>:', 'gslogo' ) . '</p>'
            . '<ul>'
            . '<li>' . __( 'Add the company name in the Title input box.', 'gslogo' ) . '</li>'
            . '<li>' . __( 'Upload the logo image.', 'gslogo' ) . '</li>'
            . '<li>' . __( 'Write the company\'s website URL with <i>http://</i> or <i>https://</i>. Keep it blank if no website is available.', 'gslogo' ) . '</li>'
            . '<li>' . __( 'Logo Categories (optional): categorize your logos by creating your own category or selecting an existing one.', 'gslogo' ) . '</li>'
            . '<li>' . __( 'Finally, publish it.', 'gslogo' ) . '</li>'
            . '</ul>'
            . '<p>' . sprintf(
                /* translators: %s: documentation link */
                __( 'Check out the %s for an in-depth guide on adding a new logo.', 'gslogo' ),
                '<a href="https://docs.gsplugins.com/gs-logo-slider/manage-the-logos/add-new-logo/" target="_blank" rel="noopener">' . __( 'documentation', 'gslogo' ) . '</a>'
            ) . '</p>',
    ],
    [
        'q' => __( 'How to use shortcodes inside page templates?', 'gslogo' ),
        'a' => '<p>' . __( 'The capabilities of WordPress are amazing, and one of its finest features is <b>do_shortcode()</b>, which allows you to utilize shortcodes in your theme files. To output Logos in a Theme file, for example, use this code:', 'gslogo' ) . '</p>'
            . '<p><code>' . esc_html( "<?php echo do_shortcode('[gslogo id=1]'); ?>" ) . '</code></p>'
            . '<p>' . __( 'This will allow you to display using the shortcode and the desired theme in your page templates.', 'gslogo' ) . '</p>',
    ],
    [
        'q' => __( 'What will be the logo image dimension?', 'gslogo' ),
        'a' => '<p>' . __( '200 pixels X 200 pixels is the default dimension but from the <i>General Settings</i> tab, you can choose other image sizes.', 'gslogo' ) . '</p>',
    ],
    [
        'q' => __( 'How many logos can I add?', 'gslogo' ),
        'a' => '<p>' . __( 'GS Logo Slider\'s Pro version allows you to add unlimited logos. In the free version, up to 20 logos can be presented at once. You can add as many logos as necessary and sort them into categories. The number of logos on your page will not have an impact on your page loading speed.', 'gslogo' ) . '</p>',
    ],
    [
        'q' => __( 'Is there any shortcode of the plugin?', 'gslogo' ),
        'a' => '<p>' . __( 'Yes, there are shortcodes available for the plugin. The default shortcode is:', 'gslogo' ) . '</p>'
            . '<p><code>[gslogo id=#]</code></p>'
            . '<p>' . __( 'First, install the plugin on your WordPress website. Then add the Shortcode where you wish to showcase the slider. You can add the shortcode to display logos anywhere at your site.', 'gslogo' ) . '</p>',
    ],
    [
        'q' => __( 'Can I use an external link in the logo of my client\'s website on the plugin?', 'gslogo' ),
        'a' => '<p>' . __( 'Yes, you can. Add the company\'s website address by starting it with <b>https://</b> in the company URL field. Logo will automatically link with the URL. You can set the link to open in a <i>new tab / same window</i>.', 'gslogo' ) . '</p>',
    ],
    [
        'q' => __( 'What version of WordPress do I need to use this plugin?', 'gslogo' ),
        'a' => '<p>' . __( 'Minimum WordPress Version: 5.8 or higher is required for using the plugin. We regularly update the plugin to make it compatible with the latest WordPress versions.', 'gslogo' ) . '</p>',
    ],
    [
        'q' => __( 'What image formats can I use for the logos?', 'gslogo' ),
        'a' => '<p>' . __( 'For the logos, you can use JPG, JPEG, PNG, GIF and other image formats.', 'gslogo' ) . '</p>',
    ],
    [
        'q' => __( 'Can I get a custom-developed plugin for my website?', 'gslogo' ),
        'a' => '<p>' . sprintf(
                /* translators: %s: contact link */
                __( 'Yes, we do provide custom development services. In case of a personal or a business need, you can %s for custom support.', 'gslogo' ),
                '<a href="https://www.gsplugins.com/contact/" target="_blank" rel="noopener">' . __( 'contact us', 'gslogo' ) . '</a>'
            ) . '</p>',
    ],
    [
        'q' => __( 'What makes our plugin stand out from other WordPress plugins?', 'gslogo' ),
        'a' => '<p>' . __( 'Our plugin is user-friendly, feature-rich, and optimized for performance, ensuring your website runs smoothly. We offer robust support, regular updates, and extensive customization options. Plus, our positive user feedback highlights its reliability and effectiveness.', 'gslogo' ) . '</p>',
    ],
    [
        'q' => __( 'Is there any Page Builder Support available for the GS Logo Slider?', 'gslogo' ),
        'a' => '<p>' . __( 'Yes, Page Builder Support is available for the GS Logo Slider. Currently supported builders include:', 'gslogo' ) . '</p>'
            . '<ul><li>Gutenberg</li><li>Elementor</li><li>WPBakery</li><li>Divi Builders</li><li>Oxygen Builder</li><li>UX Builder – Flatsome</li><li>TagDiv – Newspaper</li></ul>'
            . '<p>' . __( 'There are plans to add support for additional builders in future updates.', 'gslogo' ) . '</p>',
    ],
];

$tag_classes = [ 'added', 'improved', 'fixed', 'updated', 'removed' ];

?>
<div class="gslogo-welcome">

    <header class="gslogo-welcome__head">
        <div class="gslogo-welcome__head-inner">
            <img class="gslogo-welcome__logo" src="<?php echo esc_url( GSL_PLUGIN_URI . 'assets/img/icon-colored.svg' ); ?>" width="40" height="40" alt="<?php esc_attr_e( 'GS Logo Slider', 'gslogo' ); ?>">
            <h1><?php esc_html_e( 'Welcome to GS Logo Slider', 'gslogo' ); ?></h1>
            <span class="gslogo-welcome__ver">v<?php echo esc_html( GSL_VERSION ); ?></span>
            <?php if ( $pro_ready && defined( 'GSL_PRO_VERSION' ) ) : ?>
                <span class="gslogo-welcome__ver is-pro"><?php echo esc_html( sprintf( 'Pro v%s', GSL_PRO_VERSION ) ); ?></span>
            <?php endif; ?>
        </div>
    </header>

    <nav class="gslogo-welcome__tabs" aria-label="<?php esc_attr_e( 'Welcome page sections', 'gslogo' ); ?>">
        <div class="tabs">
            <a class="tab current" href="#overview" aria-current="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                <?php esc_html_e( 'Overview', 'gslogo' ); ?>
            </a>
            <a class="tab" href="#usage">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11"/><path d="M4 6h.01M4 12h.01M4 18h.01"/></svg>
                <?php esc_html_e( 'Usage', 'gslogo' ); ?>
            </a>
            <a class="tab" href="#faq">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6v.6"/><path d="M12 17h.01"/></svg>
                <?php esc_html_e( 'FAQ', 'gslogo' ); ?>
            </a>
            <a class="tab" href="#changelog">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                <?php esc_html_e( 'Changelog', 'gslogo' ); ?>
            </a>
            <span class="tabs-indicator"></span>
        </div>
    </nav>

    <div class="gslogo-welcome__content">

        <section id="overview" class="welcome-hero">
            <div class="welcome-copy">
                <p class="kicker"><?php esc_html_e( 'Thanks for choosing GS Logo Slider', 'gslogo' ); ?></p>
                <h2><?php esc_html_e( 'Show off the clients, partners and sponsors who trust your business.', 'gslogo' ); ?></h2>
                <p><?php esc_html_e( 'Add your logos once, then display them as a slider, ticker, grid, list, table or filterable gallery. Build each layout in the shortcode generator with a live preview, and drop it into any page or page builder.', 'gslogo' ); ?></p>
                <div class="welcome-stats">
                    <div><b>30k+</b><?php esc_html_e( 'active installs', 'gslogo' ); ?></div>
                    <div><b>4.8 / 5</b><?php esc_html_e( 'from 250 reviews', 'gslogo' ); ?></div>
                    <div><b>33</b><?php esc_html_e( 'layout themes included', 'gslogo' ); ?></div>
                </div>
                <div class="welcome-ctas">
                    <a class="btn btn-brand" href="#usage"><?php esc_html_e( 'Get started', 'gslogo' ); ?></a>
                    <a class="btn btn-outline" href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Read the docs', 'gslogo' ); ?></a>
                </div>
            </div>

            <div class="video-frame" style="--gslogo-video-thumb: url('<?php echo esc_url( 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg' ); ?>');">
                <div class="browser-chrome"><span></span><span></span><span></span></div>
                <button type="button" class="play-btn" aria-label="<?php esc_attr_e( 'Play video', 'gslogo' ); ?>" data-video="<?php echo esc_attr( $video_id ); ?>" data-title="<?php esc_attr_e( 'GS Logo Slider video tutorial', 'gslogo' ); ?>"></button>
                <div class="frame-title"><?php esc_html_e( 'Install Logo Slider & import demo data', 'gslogo' ); ?></div>
                <div class="frame-sub"><?php esc_html_e( 'Video tutorial · GS Plugins Tutorials', 'gslogo' ); ?></div>
                <a class="yt-watch" href="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . $video_id ); ?>" target="_blank" rel="noopener">▶ <?php esc_html_e( 'Watch on YouTube', 'gslogo' ); ?></a>
            </div>
        </section>

        <section id="usage" class="block">
            <div class="block-head">
                <p class="kicker"><?php esc_html_e( 'Usage', 'gslogo' ); ?></p>
                <h3><?php esc_html_e( 'Your first logo slider in four steps', 'gslogo' ); ?></h3>
                <p><?php echo wp_kses_post( __( 'Everything lives under <b>GS Logos</b> in your dashboard menu. Come back here anytime you need a refresher.', 'gslogo' ) ); ?></p>
            </div>
            <div class="usage-steps">
                <div class="usage-step">
                    <div class="num">1</div>
                    <h4><?php esc_html_e( 'Add your logos', 'gslogo' ); ?></h4>
                    <p><?php echo wp_kses_post( sprintf(
                        /* translators: %s: "GS Logos → Add New Logo" link */
                        __( 'Go to %s and add the company name, logo image and website URL. Want to try things first? Use <b>Install Demo</b>.', 'gslogo' ),
                        '<a href="' . esc_url( admin_url( 'post-new.php?post_type=gs-logo-slider' ) ) . '">' . __( 'GS Logos → Add New Logo', 'gslogo' ) . '</a>'
                    ) ); ?></p>
                </div>
                <div class="usage-step">
                    <div class="num">2</div>
                    <h4><?php esc_html_e( 'Create a shortcode', 'gslogo' ); ?></h4>
                    <p><?php echo wp_kses_post( sprintf(
                        /* translators: %s: "GS Logos → Logo Shortcode" link */
                        __( 'Open %s, click <b>Create New</b> and pick a theme. The live preview updates as you change settings.', 'gslogo' ),
                        '<a href="' . esc_url( admin_url( 'edit.php?post_type=gs-logo-slider&page=gs-logo-shortcode' ) ) . '">' . __( 'GS Logos → Logo Shortcode', 'gslogo' ) . '</a>'
                    ) ); ?></p>
                </div>
                <div class="usage-step">
                    <div class="num">3</div>
                    <h4><?php esc_html_e( 'Fine-tune and save', 'gslogo' ); ?></h4>
                    <p><?php echo wp_kses_post( __( 'Set logos per device and spacing in the <b>Style</b> tab, choose categories and order in <b>Query</b>, then save to get a shortcode like <code>[gslogo id=1]</code>.', 'gslogo' ) ); ?></p>
                </div>
                <div class="usage-step">
                    <div class="num">4</div>
                    <h4><?php esc_html_e( 'Place it anywhere', 'gslogo' ); ?></h4>
                    <p><?php echo wp_kses_post( __( 'Paste the shortcode into any page or widget, or use the <b>GS Logo Slider Builder</b> block, the Elementor widget or the Divi module.', 'gslogo' ) ); ?></p>
                </div>
            </div>
        </section>

        <section id="faq" class="block">
            <div class="block-head">
                <p class="kicker"><?php esc_html_e( 'FAQ', 'gslogo' ); ?></p>
                <h3><?php esc_html_e( 'Before you write in to support', 'gslogo' ); ?></h3>
                <p><?php esc_html_e( 'Answers to the questions we hear most often.', 'gslogo' ); ?></p>
            </div>
            <div class="faq">
                <?php foreach ( $faqs as $index => $faq ) : ?>
                    <div class="faq-item<?php echo 0 === $index ? ' open' : ''; ?>">
                        <button type="button" class="faq-q" aria-expanded="<?php echo 0 === $index ? 'true' : 'false'; ?>">
                            <span><?php echo esc_html( $faq['q'] ); ?></span><span class="chev" aria-hidden="true">▾</span>
                        </button>
                        <div class="faq-a"><?php echo wp_kses_post( $faq['a'] ); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="docs-band">
                <div>
                    <h4><?php esc_html_e( 'Didn\'t find your answer?', 'gslogo' ); ?></h4>
                    <p><?php esc_html_e( 'The full documentation covers every setting, template override and page-builder integration.', 'gslogo' ); ?></p>
                </div>
                <a class="btn btn-brand" href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open full documentation', 'gslogo' ); ?> ↗</a>
            </div>
        </section>

        <section id="changelog" class="block">
            <div class="block-head">
                <p class="kicker"><?php esc_html_e( 'Changelog', 'gslogo' ); ?></p>
                <h3><?php esc_html_e( 'What\'s new in GS Logo Slider', 'gslogo' ); ?></h3>
                <p><?php esc_html_e( 'The latest releases at a glance.', 'gslogo' ); ?></p>
            </div>
            <div class="changelog">
                <?php foreach ( $changelog as $index => $release ) : ?>
                    <article class="release">
                        <div class="release-head">
                            <h4><?php echo esc_html( sprintf( __( 'Version %s', 'gslogo' ), $release['version'] ) ); ?></h4>
                            <?php if ( 0 === $index ) : ?>
                                <span class="latest"><?php esc_html_e( 'Latest', 'gslogo' ); ?></span>
                            <?php endif; ?>
                            <?php if ( $release['date'] ) : ?>
                                <time><?php echo esc_html( $release['date'] ); ?></time>
                            <?php endif; ?>
                        </div>
                        <ul>
                            <?php foreach ( $release['items'] as $item ) :
                                $tag_class = strtolower( $item['tag'] );
                                $tag_class = in_array( $tag_class, $tag_classes, true ) ? $tag_class : 'other';
                                ?>
                                <li>
                                    <?php if ( $item['tag'] ) : ?>
                                        <span class="tag <?php echo esc_attr( $tag_class ); ?>"><?php echo esc_html( $item['tag'] ); ?></span>
                                    <?php endif; ?>
                                    <span><?php echo wp_kses_post( Welcome::format_changelog_text( $item['text'] ) ); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="changelog-more"><a href="https://wordpress.org/plugins/gs-logo-slider/#developers" target="_blank" rel="noopener"><?php esc_html_e( 'View full changelog', 'gslogo' ); ?> ↗</a></p>
        </section>

    </div>
</div>
