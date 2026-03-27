<?php
/**
 * Plugin Name: Market Sentiment
 * Plugin URI: https://adanos.org/
 * Description: Embed self-hosted stock sentiment widgets and shortcodes for WordPress, powered by Adanos.
 * Version: 0.5.4
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Adanos Software
 * Author URI: https://adanos.org/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: adanos-retail-sentiment-insights
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ADANOS_RSI_VERSION', '0.5.4');
define('ADANOS_RSI_OPTION', 'adanos_rsi_options');
define('ADANOS_RSI_CACHE_INDEX_OPTION', 'adanos_rsi_cache_keys');
define('ADANOS_RSI_TRANSIENT_PREFIX', 'adanos_rsi_');
define('ADANOS_RSI_API_BASE_URL', 'https://api.adanos.org');
define('ADANOS_RSI_CACHE_CLEAR_NONCE', 'adanos_rsi_clear_cache');

function adanos_rsi_default_options() {
    return array(
        'api_key' => '',
        'cache_ttl' => 86400,
    );
}

function adanos_rsi_get_options() {
    $saved = get_option(ADANOS_RSI_OPTION, array());

    if (!is_array($saved)) {
        $saved = array();
    }

    return wp_parse_args($saved, adanos_rsi_default_options());
}

function adanos_rsi_activate() {
    update_option(ADANOS_RSI_OPTION, adanos_rsi_get_options());
    update_option(ADANOS_RSI_CACHE_INDEX_OPTION, array());
}

register_activation_hook(__FILE__, 'adanos_rsi_activate');

function adanos_rsi_sanitize_options($input) {
    $defaults = adanos_rsi_default_options();

    return array(
        'api_key' => isset($input['api_key']) ? sanitize_text_field($input['api_key']) : '',
        'cache_ttl' => isset($input['cache_ttl']) ? max(60, min(604800, absint($input['cache_ttl']))) : $defaults['cache_ttl'],
    );
}

function adanos_rsi_register_settings() {
    register_setting(
        'adanos_rsi_settings_group',
        ADANOS_RSI_OPTION,
        array(
            'type' => 'array',
            'sanitize_callback' => 'adanos_rsi_sanitize_options',
            'default' => adanos_rsi_default_options(),
        )
    );

    add_settings_section(
        'adanos_rsi_api_section',
        '',
        function () {
            echo '<p class="adanos-rsi-section-copy">' . esc_html__('Add your Adanos API key once and WordPress will handle the cached requests for every widget and shortcode on the site.', 'adanos-retail-sentiment-insights') . '</p>';
        },
        'adanos-rsi'
    );

    add_settings_field(
        'api_key',
        __('API Key', 'adanos-retail-sentiment-insights'),
        'adanos_rsi_render_api_key_field',
        'adanos-rsi',
        'adanos_rsi_api_section'
    );

    add_settings_field(
        'cache_ttl',
        __('Cache TTL (seconds)', 'adanos-retail-sentiment-insights'),
        'adanos_rsi_render_cache_field',
        'adanos-rsi',
        'adanos_rsi_api_section'
    );
}

add_action('admin_init', 'adanos_rsi_register_settings');

function adanos_rsi_render_api_key_field() {
    $options = adanos_rsi_get_options();
    ?>
    <input type="password" class="regular-text" name="<?php echo esc_attr(ADANOS_RSI_OPTION); ?>[api_key]" value="<?php echo esc_attr($options['api_key']); ?>" autocomplete="off" />
    <p class="description">
        <?php echo esc_html__('Used by the local WordPress proxy for cached requests to the Adanos Finance API.', 'adanos-retail-sentiment-insights'); ?>
        <a href="https://adanos.org/reddit-stock-sentiment#api-form" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Get an API key', 'adanos-retail-sentiment-insights'); ?></a>
    </p>
    <?php
}

function adanos_rsi_render_cache_field() {
    $options = adanos_rsi_get_options();
    ?>
    <input type="number" class="small-text" min="60" max="604800" step="3600" name="<?php echo esc_attr(ADANOS_RSI_OPTION); ?>[cache_ttl]" value="<?php echo esc_attr($options['cache_ttl']); ?>" />
    <p class="description"><?php echo esc_html__('Default is 86400 seconds (24 hours), which keeps daily updates practical on the free 250 requests/month API plan. Responses are cached in WordPress transients, which default to the database when no object cache is active.', 'adanos-retail-sentiment-insights'); ?></p>
    <?php
}

function adanos_rsi_add_settings_page() {
    add_options_page(
        __('Market Sentiment', 'adanos-retail-sentiment-insights'),
        __('Market Sentiment', 'adanos-retail-sentiment-insights'),
        'manage_options',
        'adanos-rsi',
        'adanos_rsi_render_settings_page'
    );
}

add_action('admin_menu', 'adanos_rsi_add_settings_page');

function adanos_rsi_render_settings_page() {
    $cache_keys = adanos_rsi_get_cache_keys();
    ?>
    <div class="wrap">
        <style>
            .adanos-rsi-admin { max-width: 1320px; padding-right: 14px; }
            .adanos-rsi-hero { margin: 18px 0 20px; padding: 20px 24px; background: linear-gradient(135deg, #fcfbf5, #f5f4ea); border: 1px solid #d7d3b2; border-radius: 16px; }
            .adanos-rsi-hero h1 { margin: 0 0 8px; font-size: 30px; line-height: 1.1; display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
            .adanos-rsi-hero-brand { font-size: 15px; font-weight: 500; color: #7a7f87; }
            .adanos-rsi-hero p { margin: 0; max-width: 900px; color: #50575e; font-size: 14px; line-height: 1.55; }
            .adanos-rsi-layout { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 16px; align-items: start; }
            .adanos-rsi-main, .adanos-rsi-sidebar { display: grid; gap: 16px; }
            .adanos-rsi-card { background: #fff; border: 1px solid #dcdcde; border-radius: 14px; padding: 18px; box-shadow: 0 1px 0 rgba(0,0,0,.02); }
            .adanos-rsi-card > :last-child { margin-bottom: 0; }
            .adanos-rsi-card h2 { margin: 0 0 12px; font-size: 19px; line-height: 1.3; }
            .adanos-rsi-card h3 { margin: 0 0 8px; font-size: 15px; line-height: 1.35; }
            .adanos-rsi-card p { margin: 0 0 10px; color: #50575e; line-height: 1.5; }
            .adanos-rsi-muted { color: #646970; }
            .adanos-rsi-grid { display: grid; gap: 12px; }
            .adanos-rsi-grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .adanos-rsi-snippet { display: block; padding: 10px 12px; background: #f6f7f7; border: 1px solid #e5e7eb; border-radius: 8px; overflow-x: auto; white-space: normal; word-break: break-word; line-height: 1.45; }
            .adanos-rsi-shortcode { display: grid; gap: 10px; align-content: start; padding: 14px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fcfcfd; }
            .adanos-rsi-shortcode ul, .adanos-rsi-steps, .adanos-rsi-bullet-list { margin: 0; padding-left: 18px; }
            .adanos-rsi-shortcode ul { padding-left: 0; list-style: none; display: grid; gap: 6px; }
            .adanos-rsi-shortcode li, .adanos-rsi-steps li, .adanos-rsi-bullet-list li { margin: 0 0 6px; line-height: 1.45; }
            .adanos-rsi-shortcode li { margin: 0; }
            .adanos-rsi-bullet-list { list-style: disc; }
            .adanos-rsi-chips { display: flex; flex-wrap: wrap; gap: 8px; }
            .adanos-rsi-chip { display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border-radius: 999px; background: #f6f7f7; border: 1px solid #dcdcde; font-size: 12px; }
            .adanos-rsi-chip code { background: transparent; padding: 0; }
            .adanos-rsi-steps { display: grid; gap: 10px; padding-left: 0; list-style: none; counter-reset: adanos-steps; }
            .adanos-rsi-steps li { display: flex; gap: 12px; align-items: flex-start; margin: 0; }
            .adanos-rsi-steps li::before { counter-increment: adanos-steps; content: counter(adanos-steps); display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 999px; background: #1d4ed8; color: #fff; font-weight: 600; flex: 0 0 26px; }
            .adanos-rsi-stat { padding: 14px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fcfcfd; }
            .adanos-rsi-stat-label { display: block; margin-bottom: 6px; font-size: 12px; letter-spacing: .04em; text-transform: uppercase; color: #646970; }
            .adanos-rsi-stat-value { font-size: 26px; font-weight: 700; line-height: 1.1; }
            .adanos-rsi-section-copy { margin: 0 0 14px; color: #50575e; max-width: 720px; }
            .adanos-rsi-form-wrap form { margin: 0; }
            .adanos-rsi-form-wrap .form-table { margin: 0 0 14px; border-collapse: separate; border-spacing: 0; }
            .adanos-rsi-form-wrap .form-table tbody { display: grid; gap: 14px; }
            .adanos-rsi-form-wrap .form-table tr { display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 18px; align-items: start; padding-top: 14px; border-top: 1px solid #eef0f1; }
            .adanos-rsi-form-wrap .form-table tr:first-child { padding-top: 0; border-top: 0; }
            .adanos-rsi-form-wrap .form-table th,
            .adanos-rsi-form-wrap .form-table td { margin: 0; padding: 0; width: auto; }
            .adanos-rsi-form-wrap .form-table th { font-size: 13px; line-height: 1.5; }
            .adanos-rsi-form-wrap .form-table td .description { margin-top: 6px; line-height: 1.45; }
            .adanos-rsi-form-wrap .regular-text { width: min(100%, 320px); }
            .adanos-rsi-form-wrap .small-text { width: 88px; }
            .adanos-rsi-form-wrap .submit { margin: 0; padding: 2px 0 0; }
            .adanos-rsi-cache-form { margin-top: 2px; }
            .adanos-rsi-faq details { border-top: 1px solid #e5e7eb; padding: 12px 0; }
            .adanos-rsi-faq details:first-child { padding-top: 0; border-top: 0; }
            .adanos-rsi-faq summary { cursor: pointer; font-weight: 600; font-size: 15px; line-height: 1.45; }
            .adanos-rsi-faq details p { margin: 8px 0 0; }
            @media (max-width: 1200px) {
                .adanos-rsi-layout { grid-template-columns: 1fr; }
                .adanos-rsi-grid.cols-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
            @media (max-width: 782px) {
                .adanos-rsi-admin { padding-right: 0; }
                .adanos-rsi-hero { padding: 18px; }
                .adanos-rsi-grid.cols-3 { grid-template-columns: 1fr; }
                .adanos-rsi-form-wrap .form-table tr { grid-template-columns: 1fr; gap: 8px; }
            }
        </style>
        <div class="adanos-rsi-admin">
            <div class="adanos-rsi-hero">
                <h1>
                    <span><?php echo esc_html__('Market Sentiment', 'adanos-retail-sentiment-insights'); ?></span>
                    <span class="adanos-rsi-hero-brand"><?php echo esc_html__('by Adanos', 'adanos-retail-sentiment-insights'); ?></span>
                </h1>
                <p><?php echo esc_html__('Turn market sentiment into publishable content in minutes. Add live stock widgets, inline buzz metrics, and ready-to-use summaries that make finance articles, watchlists, and newsletters more useful for readers.', 'adanos-retail-sentiment-insights'); ?></p>
            </div>

            <div class="adanos-rsi-layout">
                <div class="adanos-rsi-main">
                    <div class="adanos-rsi-card">
                        <h2><?php echo esc_html__('Connect your API key', 'adanos-retail-sentiment-insights'); ?></h2>
                        <div class="adanos-rsi-form-wrap">
                            <form method="post" action="options.php">
                                <?php
                                settings_fields('adanos_rsi_settings_group');
                                do_settings_sections('adanos-rsi');
                                submit_button();
                                ?>
                            </form>
                        </div>
                    </div>

                    <div class="adanos-rsi-card">
                        <h2><?php echo esc_html__('Widget shortcodes', 'adanos-retail-sentiment-insights'); ?></h2>
                        <div class="adanos-rsi-grid cols-3">
                            <div class="adanos-rsi-shortcode">
                                <h3><code>[adanos]</code></h3>
                                <p><?php echo esc_html__('Single-stock sentiment card with buzz, bullish percentage, trend, and source stats.', 'adanos-retail-sentiment-insights'); ?></p>
                                <code class="adanos-rsi-snippet">[adanos symbol="AAPL" source="reddit" width="100%"]</code>
                                <ul>
                                    <li><strong><?php echo esc_html__('Sources:', 'adanos-retail-sentiment-insights'); ?></strong> <code>reddit</code>, <code>x</code>, <code>news</code>, <code>polymarket</code></li>
                                    <li><strong><?php echo esc_html__('Theme:', 'adanos-retail-sentiment-insights'); ?></strong> <code>light</code>, <code>dark</code></li>
                                    <li><strong><?php echo esc_html__('Other options:', 'adanos-retail-sentiment-insights'); ?></strong> <code>show_explanation</code>, <code>days</code>, <code>width</code></li>
                                </ul>
                            </div>
                            <div class="adanos-rsi-shortcode">
                                <h3><code>[adanos_ticker_tape]</code></h3>
                                <p><?php echo esc_html__('Scrolling tape for currently trending stocks from one source.', 'adanos-retail-sentiment-insights'); ?></p>
                                <code class="adanos-rsi-snippet">[adanos_ticker_tape source="x" limit="10" speed="normal" width="100%"]</code>
                                <ul>
                                    <li><strong><?php echo esc_html__('Sources:', 'adanos-retail-sentiment-insights'); ?></strong> <code>reddit</code>, <code>x</code>, <code>news</code>, <code>polymarket</code></li>
                                    <li><strong><?php echo esc_html__('Speed:', 'adanos-retail-sentiment-insights'); ?></strong> <code>slow</code>, <code>normal</code>, <code>fast</code></li>
                                    <li><strong><?php echo esc_html__('Limit:', 'adanos-retail-sentiment-insights'); ?></strong> <?php echo esc_html__('5 to 20 rows', 'adanos-retail-sentiment-insights'); ?></li>
                                </ul>
                            </div>
                            <div class="adanos-rsi-shortcode">
                                <h3><code>[adanos_top_movers]</code></h3>
                                <p><?php echo esc_html__('Table view for the strongest current movers within one source.', 'adanos-retail-sentiment-insights'); ?></p>
                                <code class="adanos-rsi-snippet">[adanos_top_movers source="news" limit="8" period="7" width="100%"]</code>
                                <ul>
                                    <li><strong><?php echo esc_html__('Sources:', 'adanos-retail-sentiment-insights'); ?></strong> <code>reddit</code>, <code>x</code>, <code>news</code>, <code>polymarket</code></li>
                                    <li><strong><?php echo esc_html__('Period:', 'adanos-retail-sentiment-insights'); ?></strong> <?php echo esc_html__('1 to 30 days', 'adanos-retail-sentiment-insights'); ?></li>
                                    <li><strong><?php echo esc_html__('Other options:', 'adanos-retail-sentiment-insights'); ?></strong> <code>show_logos</code>, <code>theme</code>, <code>width</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="adanos-rsi-card">
                        <h2><?php echo esc_html__('Text shortcodes', 'adanos-retail-sentiment-insights'); ?></h2>
                        <div class="adanos-rsi-grid cols-3">
                            <div class="adanos-rsi-shortcode">
                                <h3><code>[adanos_value]</code></h3>
                                <p><?php echo esc_html__('Drop one concrete value directly into a sentence or table cell.', 'adanos-retail-sentiment-insights'); ?></p>
                                <code class="adanos-rsi-snippet">Buzz: [adanos_value symbol="AAPL" source="news" field="buzz"]</code>
                                <ul>
                                    <li><strong><?php echo esc_html__('Fields:', 'adanos-retail-sentiment-insights'); ?></strong> <code>buzz</code>, <code>bullish</code>, <code>trend</code>, <code>mentions</code>, <code>trades</code>, <code>activity</code></li>
                                    <li><strong><?php echo esc_html__('Extra fields:', 'adanos-retail-sentiment-insights'); ?></strong> <code>company</code>, <code>ticker</code>, <code>source_label</code>, <code>summary_value</code>, <code>summary_label</code>, <code>explanation</code></li>
                                    <li><strong><?php echo esc_html__('Wrap text:', 'adanos-retail-sentiment-insights'); ?></strong> <code>prefix</code>, <code>suffix</code></li>
                                </ul>
                            </div>
                            <div class="adanos-rsi-shortcode">
                                <h3><code>[adanos_summary]</code></h3>
                                <p><?php echo esc_html__('Inline one-sentence summary for a stock and source.', 'adanos-retail-sentiment-insights'); ?></p>
                                <code class="adanos-rsi-snippet">[adanos_summary symbol="AAPL" source="x" format="sentence"]</code>
                                <ul>
                                    <li><strong><?php echo esc_html__('Formats:', 'adanos-retail-sentiment-insights'); ?></strong> <code>sentence</code>, <code>brief</code>, <code>explanation</code></li>
                                    <li><strong><?php echo esc_html__('Sources:', 'adanos-retail-sentiment-insights'); ?></strong> <code>reddit</code>, <code>x</code>, <code>news</code>, <code>polymarket</code></li>
                                    <li><strong><?php echo esc_html__('Best for:', 'adanos-retail-sentiment-insights'); ?></strong> <?php echo esc_html__('article intros, stock pages, and newsletter summaries', 'adanos-retail-sentiment-insights'); ?></li>
                                </ul>
                            </div>
                            <div class="adanos-rsi-shortcode">
                                <h3><code>[adanos_trending_text]</code></h3>
                                <p><?php echo esc_html__('Plain-text list or sentence for currently trending stocks.', 'adanos-retail-sentiment-insights'); ?></p>
                                <code class="adanos-rsi-snippet">[adanos_trending_text source="news" limit="3" format="sentence"]</code>
                                <ul>
                                    <li><strong><?php echo esc_html__('Formats:', 'adanos-retail-sentiment-insights'); ?></strong> <code>sentence</code>, <code>list</code>, <code>detailed</code></li>
                                    <li><strong><?php echo esc_html__('Limit:', 'adanos-retail-sentiment-insights'); ?></strong> <?php echo esc_html__('1 to 10 tickers', 'adanos-retail-sentiment-insights'); ?></li>
                                    <li><strong><?php echo esc_html__('Days:', 'adanos-retail-sentiment-insights'); ?></strong> <code>days</code> or <code>period</code>, <?php echo esc_html__('1 to 30', 'adanos-retail-sentiment-insights'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="adanos-rsi-card">
                        <h2><?php echo esc_html__('FAQ', 'adanos-retail-sentiment-insights'); ?></h2>
                        <div class="adanos-rsi-faq">
                            <details open>
                                <summary><?php echo esc_html__('What can I actually publish with it?', 'adanos-retail-sentiment-insights'); ?></summary>
                                <p><?php echo esc_html__('Single-stock sentiment cards, live trending strips, top movers tables, inline buzz values, bullish percentages, and sentence summaries that stay current without manual edits.', 'adanos-retail-sentiment-insights'); ?></p>
                            </details>
                            <details>
                                <summary><?php echo esc_html__('Which shortcode should I use inside articles?', 'adanos-retail-sentiment-insights'); ?></summary>
                                <p><?php echo esc_html__('Use [adanos_value] for one metric, [adanos_summary] for a clean sentence, and [adanos_trending_text] when you want a text-only trending snippet in editorial copy.', 'adanos-retail-sentiment-insights'); ?></p>
                            </details>
                            <details>
                                <summary><?php echo esc_html__('Which sources are supported?', 'adanos-retail-sentiment-insights'); ?></summary>
                                <p><?php echo esc_html__('All shortcodes support Reddit, X.com, News, and Polymarket.', 'adanos-retail-sentiment-insights'); ?></p>
                            </details>
                            <details>
                                <summary><?php echo esc_html__('How does the caching work?', 'adanos-retail-sentiment-insights'); ?></summary>
                                <p><?php echo esc_html__('Requests are fetched server-side through WordPress and stored in transients for the cache TTL you set above. On most standard WordPress installs, that means the cache lives in the database by default.', 'adanos-retail-sentiment-insights'); ?></p>
                            </details>
                            <details>
                                <summary><?php echo esc_html__('Do visitors see my API key?', 'adanos-retail-sentiment-insights'); ?></summary>
                                <p><?php echo esc_html__('No. The API key stays server-side. Visitors only load local plugin assets and responses from your WordPress REST proxy.', 'adanos-retail-sentiment-insights'); ?></p>
                            </details>
                        </div>
                    </div>
                </div>

                <div class="adanos-rsi-sidebar">
                    <div class="adanos-rsi-card">
                        <h2><?php echo esc_html__('Quick start', 'adanos-retail-sentiment-insights'); ?></h2>
                        <ol class="adanos-rsi-steps">
                            <li><span><?php echo esc_html__('Create an API key from the Adanos retail sentiment page.', 'adanos-retail-sentiment-insights'); ?> <a href="https://adanos.org/reddit-stock-sentiment#api-form" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Get an API key', 'adanos-retail-sentiment-insights'); ?></a></span></li>
                            <li><span><?php echo esc_html__('Paste the key into the settings form and save changes.', 'adanos-retail-sentiment-insights'); ?></span></li>
                            <li><span><?php echo esc_html__('Copy one shortcode into a post, page, or reusable block.', 'adanos-retail-sentiment-insights'); ?></span></li>
                        </ol>
                    </div>

                    <div class="adanos-rsi-card">
                        <h2><?php echo esc_html__('Supported sources', 'adanos-retail-sentiment-insights'); ?></h2>
                        <div class="adanos-rsi-chips">
                            <span class="adanos-rsi-chip"><?php echo esc_html__('Reddit', 'adanos-retail-sentiment-insights'); ?></span>
                            <span class="adanos-rsi-chip"><?php echo esc_html__('Finance News', 'adanos-retail-sentiment-insights'); ?></span>
                            <span class="adanos-rsi-chip"><?php echo esc_html__('X.com', 'adanos-retail-sentiment-insights'); ?></span>
                            <span class="adanos-rsi-chip"><?php echo esc_html__('Polymarket', 'adanos-retail-sentiment-insights'); ?></span>
                        </div>
                    </div>

                    <div class="adanos-rsi-card">
                        <h2><?php echo esc_html__('Cache tools', 'adanos-retail-sentiment-insights'); ?></h2>
                        <div class="adanos-rsi-grid">
                            <div class="adanos-rsi-stat">
                                <span class="adanos-rsi-stat-label"><?php echo esc_html__('Tracked cache entries', 'adanos-retail-sentiment-insights'); ?></span>
                                <div class="adanos-rsi-stat-value"><?php echo esc_html(number_format_i18n(count($cache_keys))); ?></div>
                            </div>
                            <form class="adanos-rsi-cache-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="adanos_rsi_clear_cache" />
                                <?php wp_nonce_field(ADANOS_RSI_CACHE_CLEAR_NONCE); ?>
                                <?php submit_button(__('Clear cached API responses', 'adanos-retail-sentiment-insights'), 'secondary', 'submit', false); ?>
                            </form>
                            <p class="adanos-rsi-muted"><?php echo esc_html__('Use this after changing your API key, source mix, or when you want to force a fresh fetch.', 'adanos-retail-sentiment-insights'); ?></p>
                        </div>
                    </div>

                    <div class="adanos-rsi-card">
                        <h2><?php echo esc_html__('Best use cases', 'adanos-retail-sentiment-insights'); ?></h2>
                        <ul class="adanos-rsi-bullet-list">
                            <li><?php echo esc_html__('Stock profile pages with live sentiment context', 'adanos-retail-sentiment-insights'); ?></li>
                            <li><?php echo esc_html__('Earnings preview posts and post-call recap articles', 'adanos-retail-sentiment-insights'); ?></li>
                            <li><?php echo esc_html__('“Why this stock is trending” explainers', 'adanos-retail-sentiment-insights'); ?></li>
                            <li><?php echo esc_html__('Market open / market close summary posts', 'adanos-retail-sentiment-insights'); ?></li>
                            <li><?php echo esc_html__('Newsletter landing pages and daily market briefings', 'adanos-retail-sentiment-insights'); ?></li>
                            <li><?php echo esc_html__('Comparison pieces like NVDA vs AMD or TSLA vs Rivian', 'adanos-retail-sentiment-insights'); ?></li>
                            <li><?php echo esc_html__('Watchlist pages for growth, AI, EV, or meme-stock coverage', 'adanos-retail-sentiment-insights'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function adanos_rsi_admin_notice_missing_key() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && 'settings_page_adanos-rsi' !== $screen->id) {
        return;
    }

    $options = adanos_rsi_get_options();
    if (!empty($options['api_key'])) {
        return;
    }

    echo '<div class="notice notice-warning"><p>' .
        esc_html__('Market Sentiment needs an API key before the widgets can load live data.', 'adanos-retail-sentiment-insights') .
        '</p></div>';
}

add_action('admin_notices', 'adanos_rsi_admin_notice_missing_key');

function adanos_rsi_admin_notice_cache_cleared() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (empty($_GET['page']) || 'adanos-rsi' !== sanitize_key((string) $_GET['page'])) {
        return;
    }

    if (empty($_GET['adanos_cache_cleared'])) {
        return;
    }

    echo '<div class="notice notice-success is-dismissible"><p>' .
        esc_html__('Adanos widget cache cleared successfully.', 'adanos-retail-sentiment-insights') .
        '</p></div>';
}

add_action('admin_notices', 'adanos_rsi_admin_notice_cache_cleared');

function adanos_rsi_api_base_url() {
    return untrailingslashit((string) apply_filters('adanos_rsi_api_base_url', ADANOS_RSI_API_BASE_URL));
}

function adanos_rsi_cache_key($namespace, $payload) {
    return ADANOS_RSI_TRANSIENT_PREFIX . md5($namespace . ':' . wp_json_encode($payload));
}

function adanos_rsi_get_cache_keys() {
    $keys = get_option(ADANOS_RSI_CACHE_INDEX_OPTION, array());

    if (!is_array($keys)) {
        return array();
    }

    return array_values(array_unique(array_filter(array_map('strval', $keys))));
}

function adanos_rsi_register_cache_key($cache_key) {
    $cache_key = (string) $cache_key;
    if ('' === $cache_key) {
        return;
    }

    $keys = adanos_rsi_get_cache_keys();
    if (in_array($cache_key, $keys, true)) {
        return;
    }

    $keys[] = $cache_key;
    update_option(ADANOS_RSI_CACHE_INDEX_OPTION, $keys, false);
}

function adanos_rsi_clear_cache() {
    foreach (adanos_rsi_get_cache_keys() as $cache_key) {
        delete_transient($cache_key);
    }

    update_option(ADANOS_RSI_CACHE_INDEX_OPTION, array(), false);
}

function adanos_rsi_handle_clear_cache() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to clear the Adanos cache.', 'adanos-retail-sentiment-insights'));
    }

    check_admin_referer(ADANOS_RSI_CACHE_CLEAR_NONCE);
    adanos_rsi_clear_cache();

    wp_safe_redirect(
        add_query_arg(
            array(
                'page' => 'adanos-rsi',
                'adanos_cache_cleared' => '1',
            ),
            admin_url('options-general.php')
        )
    );
    exit;
}

add_action('admin_post_adanos_rsi_clear_cache', 'adanos_rsi_handle_clear_cache');

function adanos_rsi_cached_get($namespace, $path, $query = array()) {
    $options = adanos_rsi_get_options();
    $cache_key = adanos_rsi_cache_key($namespace, $query);
    $cached = get_transient($cache_key);

    if (false !== $cached) {
        return $cached;
    }

    if (empty($options['api_key'])) {
        return new WP_Error('adanos_missing_api_key', __('Adanos API key is not configured.', 'adanos-retail-sentiment-insights'));
    }

    $url = add_query_arg($query, adanos_rsi_api_base_url() . $path);
    $response = wp_remote_get(
        $url,
        array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
                'X-API-Key' => $options['api_key'],
            ),
        )
    );

    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code < 200 || $status_code >= 300) {
        return new WP_Error(
            'adanos_http_error',
            sprintf(
                /* translators: %d is the HTTP status code. */
                __('Adanos API request failed with status %d.', 'adanos-retail-sentiment-insights'),
                absint($status_code)
            )
        );
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($body)) {
        return new WP_Error('adanos_invalid_json', __('Adanos API returned an invalid JSON payload.', 'adanos-retail-sentiment-insights'));
    }

    set_transient($cache_key, $body, absint($options['cache_ttl']));
    adanos_rsi_register_cache_key($cache_key);

    return $body;
}

function adanos_rsi_source_specs() {
    return array(
        'reddit' => array(
            'label' => 'Reddit',
            'stock_path' => '/reddit/stocks/v1/stock/%s',
            'explain_path' => '/reddit/stocks/v1/stock/%s/explain',
            'trending_path' => '/reddit/stocks/v1/trending',
            'activity_field' => 'total_mentions',
            'activity_fallback' => 'mentions',
            'activity_label' => __('Mentions', 'adanos-retail-sentiment-insights'),
            'summary_field' => 'subreddit_count',
            'summary_label' => __('Subreddits', 'adanos-retail-sentiment-insights'),
        ),
        'x' => array(
            'label' => 'X.com',
            'stock_path' => '/x/stocks/v1/stock/%s',
            'trending_path' => '/x/stocks/v1/trending',
            'activity_field' => 'mentions',
            'activity_fallback' => 'total_mentions',
            'activity_label' => __('Mentions', 'adanos-retail-sentiment-insights'),
            'summary_field' => 'total_upvotes',
            'summary_label' => __('Likes', 'adanos-retail-sentiment-insights'),
        ),
        'news' => array(
            'label' => 'News',
            'stock_path' => '/news/stocks/v1/stock/%s',
            'explain_path' => '/news/stocks/v1/stock/%s/explain',
            'trending_path' => '/news/stocks/v1/trending',
            'activity_field' => 'mentions',
            'activity_label' => __('Mentions', 'adanos-retail-sentiment-insights'),
            'summary_field' => 'source_count',
            'summary_label' => __('Publishers', 'adanos-retail-sentiment-insights'),
        ),
        'polymarket' => array(
            'label' => 'Polymarket',
            'stock_path' => '/polymarket/stocks/v1/stock/%s',
            'trending_path' => '/polymarket/stocks/v1/trending',
            'activity_field' => 'trade_count',
            'activity_label' => __('Trades', 'adanos-retail-sentiment-insights'),
            'summary_field' => 'total_liquidity',
            'summary_label' => __('Liquidity', 'adanos-retail-sentiment-insights'),
        ),
    );
}

function adanos_rsi_allowed_sources() {
    return array_keys(adanos_rsi_source_specs());
}

function adanos_rsi_sanitize_source($value) {
    $source = sanitize_key($value);

    if (!in_array($source, adanos_rsi_allowed_sources(), true)) {
        return 'reddit';
    }

    return $source;
}

function adanos_rsi_sanitize_theme($value) {
    return 'dark' === sanitize_key($value) ? 'dark' : 'light';
}

function adanos_rsi_sanitize_speed($value) {
    $speed = sanitize_key($value);
    if (!in_array($speed, array('slow', 'normal', 'fast'), true)) {
        return 'normal';
    }

    return $speed;
}

function adanos_rsi_sanitize_bool($value, $default = true) {
    if (null === $value || '' === $value) {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

function adanos_rsi_sanitize_dimension($value, $default = '100%') {
    $value = trim((string) $value);

    if ('' === $value) {
        return $default;
    }

    if (preg_match('/^\d+(?:\.\d+)?(?:px|%|vw|vh|rem|em)$/', $value)) {
        return $value;
    }

    if (in_array($value, array('auto', 'fit-content', 'max-content', 'min-content'), true)) {
        return $value;
    }

    return $default;
}

function adanos_rsi_sanitize_limit($value, $minimum, $maximum, $default) {
    $limit = absint($value);
    if ($limit < $minimum || $limit > $maximum) {
        return $default;
    }

    return $limit;
}

function adanos_rsi_sanitize_period($value) {
    $period = absint($value);
    if ($period < 1 || $period > 30) {
        return 1;
    }

    return $period;
}

function adanos_rsi_extract_activity_value($data, $spec) {
    if (isset($data[$spec['activity_field']])) {
        return $data[$spec['activity_field']];
    }

    if (!empty($spec['activity_fallback']) && isset($data[$spec['activity_fallback']])) {
        return $data[$spec['activity_fallback']];
    }

    return 0;
}

function adanos_rsi_extract_trend_history($data, $source) {
    if (!empty($data['trend_history']) && is_array($data['trend_history'])) {
        return array_values(array_filter(array_map('floatval', $data['trend_history']), 'is_finite'));
    }

    if (empty($data['daily_trend']) || !is_array($data['daily_trend'])) {
        return array();
    }

    $field = 'mentions';
    if ('polymarket' === $source) {
        $field = 'trade_count';
    }

    $history = array();
    foreach ($data['daily_trend'] as $entry) {
        if (isset($entry['buzz_score']) && is_numeric($entry['buzz_score'])) {
            $history[] = (float) $entry['buzz_score'];
            continue;
        }

        if (isset($entry[$field]) && is_numeric($entry[$field])) {
            $history[] = (float) $entry[$field];
        }
    }

    return $history;
}

function adanos_rsi_build_explanation($source, $ticker, $detail, $spec) {
    if (!empty($spec['explain_path'])) {
        $payload = adanos_rsi_cached_get(
            'explain_' . $source,
            sprintf($spec['explain_path'], rawurlencode($ticker))
        );

        if (!is_wp_error($payload) && !empty($payload['explanation'])) {
            return sanitize_text_field($payload['explanation']);
        }
    }

    $trend = !empty($detail['trend']) ? sanitize_text_field($detail['trend']) : __('stable', 'adanos-retail-sentiment-insights');
    $activity = adanos_rsi_extract_activity_value($detail, $spec);
    $bullish_pct = isset($detail['bullish_pct']) ? (float) $detail['bullish_pct'] : null;
    $tone = __('mixed', 'adanos-retail-sentiment-insights');

    if (null !== $bullish_pct) {
        if ($bullish_pct >= 55) {
            $tone = __('bullish', 'adanos-retail-sentiment-insights');
        } elseif ($bullish_pct <= 45) {
            $tone = __('bearish', 'adanos-retail-sentiment-insights');
        } else {
            $tone = __('neutral', 'adanos-retail-sentiment-insights');
        }
    }

    return sprintf(
        /* translators: 1: source label, 2: ticker, 3: tone, 4: activity count, 5: activity label, 6: trend */
        __('%1$s sentiment for %2$s is currently %3$s with %4$s %5$s. Momentum is %6$s in the latest tracking window.', 'adanos-retail-sentiment-insights'),
        $spec['label'],
        $ticker,
        $tone,
        number_format_i18n((float) $activity, adanos_rsi_numeric_decimals($activity)),
        strtolower((string) $spec['activity_label']),
        $trend
    );
}

function adanos_rsi_get_stock_widget_payload($source, $ticker, $days, $show_explanation) {
    $specs = adanos_rsi_source_specs();
    if (!isset($specs[$source])) {
        return new WP_Error('adanos_invalid_source', __('Invalid retail sentiment source.', 'adanos-retail-sentiment-insights'));
    }

    $spec = $specs[$source];
    $detail = adanos_rsi_cached_get(
        'stock_' . $source,
        sprintf($spec['stock_path'], rawurlencode($ticker)),
        array('days' => $days)
    );

    if (is_wp_error($detail)) {
        return $detail;
    }

    $activity_value = adanos_rsi_extract_activity_value($detail, $spec);
    $summary_value = isset($detail[$spec['summary_field']]) ? $detail[$spec['summary_field']] : null;

    return array(
        'ticker' => strtoupper($ticker),
        'company_name' => isset($detail['company_name']) ? sanitize_text_field($detail['company_name']) : '',
        'source' => $source,
        'source_label' => $spec['label'],
        'buzz_score' => isset($detail['buzz_score']) ? round((float) $detail['buzz_score'], 1) : null,
        'bullish_pct' => isset($detail['bullish_pct']) ? round((float) $detail['bullish_pct'], 1) : null,
        'trend' => !empty($detail['trend']) ? sanitize_text_field($detail['trend']) : 'stable',
        'activity_label' => $spec['activity_label'],
        'activity_value' => is_numeric($activity_value) ? $activity_value + 0 : 0,
        'summary_label' => $spec['summary_label'],
        'summary_value' => is_numeric($summary_value) ? $summary_value + 0 : null,
        'trend_history' => adanos_rsi_extract_trend_history($detail, $source),
        'explanation' => $show_explanation ? adanos_rsi_build_explanation($source, strtoupper($ticker), $detail, $spec) : '',
    );
}

function adanos_rsi_get_trending_widget_payload($source, $days, $limit) {
    $specs = adanos_rsi_source_specs();
    if (!isset($specs[$source])) {
        return new WP_Error('adanos_invalid_source', __('Invalid retail sentiment source.', 'adanos-retail-sentiment-insights'));
    }

    $spec = $specs[$source];
    $payload = adanos_rsi_cached_get(
        'trending_' . $source,
        $spec['trending_path'],
        array(
            'days' => $days,
            'limit' => $limit,
            'type' => 'stock',
        )
    );

    if (is_wp_error($payload)) {
        return $payload;
    }

    $rows = array();
    foreach ((array) $payload as $item) {
        $symbol = '';
        if (!empty($item['ticker'])) {
            $symbol = sanitize_text_field($item['ticker']);
        } elseif (!empty($item['symbol'])) {
            $symbol = sanitize_text_field($item['symbol']);
        }

        if ('' === $symbol) {
            continue;
        }

        $activity_value = adanos_rsi_extract_activity_value($item, $spec);

        $rows[] = array(
            'ticker' => $symbol,
            'company_name' => isset($item['company_name']) ? sanitize_text_field($item['company_name']) : '',
            'buzz_score' => isset($item['buzz_score']) ? round((float) $item['buzz_score'], 1) : 0.0,
            'bullish_pct' => isset($item['bullish_pct']) ? round((float) $item['bullish_pct'], 1) : null,
            'trend' => !empty($item['trend']) ? sanitize_text_field($item['trend']) : 'stable',
            'activity_value' => is_numeric($activity_value) ? $activity_value + 0 : 0,
            'trend_history' => !empty($item['trend_history']) && is_array($item['trend_history'])
                ? array_values(array_filter(array_map('floatval', $item['trend_history']), 'is_finite'))
                : array(),
        );
    }

    return array(
        'source' => $source,
        'source_label' => $spec['label'],
        'activity_label' => $spec['activity_label'],
        'rows' => $rows,
    );
}

function adanos_rsi_format_numeric_value($value, $decimals = 0) {
    if (!is_numeric($value)) {
        return '';
    }

    return number_format_i18n((float) $value, $decimals);
}

function adanos_rsi_numeric_decimals($value, $default_decimals = 1) {
    if (!is_numeric($value)) {
        return $default_decimals;
    }

    return floor((float) $value) === (float) $value ? 0 : $default_decimals;
}

function adanos_rsi_normalize_metric_field($field) {
    $field = sanitize_key($field);

    $aliases = array(
        'symbol' => 'ticker',
        'buzz' => 'buzz_score',
        'bullish' => 'bullish_pct',
        'mentions' => 'activity_value',
        'trades' => 'activity_value',
        'activity' => 'activity_value',
        'summary' => 'summary_value',
        'company' => 'company_name',
    );

    return isset($aliases[$field]) ? $aliases[$field] : $field;
}

function adanos_rsi_format_stock_metric($payload, $field) {
    $field = adanos_rsi_normalize_metric_field($field);

    switch ($field) {
        case 'ticker':
            return isset($payload['ticker']) ? (string) $payload['ticker'] : '';
        case 'company_name':
            return isset($payload['company_name']) ? (string) $payload['company_name'] : '';
        case 'source_label':
            return isset($payload['source_label']) ? (string) $payload['source_label'] : '';
        case 'buzz_score':
            return isset($payload['buzz_score']) && null !== $payload['buzz_score']
                ? adanos_rsi_format_numeric_value($payload['buzz_score'], 1) . '/100'
                : '';
        case 'bullish_pct':
            return isset($payload['bullish_pct']) && null !== $payload['bullish_pct']
                ? adanos_rsi_format_numeric_value($payload['bullish_pct'], 1) . '%'
                : '';
        case 'trend':
            return isset($payload['trend']) ? ucfirst((string) $payload['trend']) : '';
        case 'activity_value':
            if (!isset($payload['activity_value'])) {
                return '';
            }

            return adanos_rsi_format_numeric_value($payload['activity_value'], adanos_rsi_numeric_decimals($payload['activity_value']));
        case 'activity_label':
            return isset($payload['activity_label']) ? (string) $payload['activity_label'] : '';
        case 'summary_label':
            return isset($payload['summary_label']) ? (string) $payload['summary_label'] : '';
        case 'summary_value':
            if (!isset($payload['summary_value']) || null === $payload['summary_value']) {
                return '';
            }

            if ('Liquidity' === (string) $payload['summary_label']) {
                return '$' . adanos_rsi_format_numeric_value($payload['summary_value'], 0);
            }

            return adanos_rsi_format_numeric_value($payload['summary_value'], adanos_rsi_numeric_decimals($payload['summary_value']));
        case 'explanation':
            return isset($payload['explanation']) ? (string) $payload['explanation'] : '';
        default:
            return isset($payload[$field]) && is_scalar($payload[$field]) ? (string) $payload[$field] : '';
    }
}

function adanos_rsi_join_text_list($items) {
    $items = array_values(array_filter(array_map('strval', (array) $items)));
    $count = count($items);

    if (0 === $count) {
        return '';
    }

    if (1 === $count) {
        return $items[0];
    }

    if (2 === $count) {
        return $items[0] . ' and ' . $items[1];
    }

    $last = array_pop($items);
    return implode(', ', $items) . ', and ' . $last;
}

function adanos_rsi_build_stock_summary($payload, $format) {
    $ticker = adanos_rsi_format_stock_metric($payload, 'ticker');
    $source_label = adanos_rsi_format_stock_metric($payload, 'source_label');
    $buzz = adanos_rsi_format_stock_metric($payload, 'buzz_score');
    $bullish = adanos_rsi_format_stock_metric($payload, 'bullish_pct');
    $activity = adanos_rsi_format_stock_metric($payload, 'activity_value');
    $activity_label = strtolower(adanos_rsi_format_stock_metric($payload, 'activity_label'));
    $trend = strtolower(adanos_rsi_format_stock_metric($payload, 'trend'));

    if ('explanation' === $format && !empty($payload['explanation'])) {
        return (string) $payload['explanation'];
    }

    if ('brief' === $format) {
        return sprintf(
            /* translators: 1: buzz, 2: bullish percentage, 3: activity count, 4: activity label, 5: trend */
            __('%1$s buzz, %2$s bullish, %3$s %4$s, %5$s trend.', 'adanos-retail-sentiment-insights'),
            $buzz,
            $bullish,
            $activity,
            $activity_label,
            $trend
        );
    }

    return sprintf(
        /* translators: 1: ticker, 2: source label, 3: buzz, 4: bullish percentage, 5: activity count, 6: activity label, 7: trend */
        __('%1$s has a %3$s %2$s buzz score, %4$s bullish sentiment, %5$s %6$s, and a %7$s trend.', 'adanos-retail-sentiment-insights'),
        $ticker,
        $source_label,
        $buzz,
        $bullish,
        $activity,
        $activity_label,
        $trend
    );
}

function adanos_rsi_build_trending_text($payload, $format) {
    $rows = !empty($payload['rows']) && is_array($payload['rows']) ? $payload['rows'] : array();
    if (empty($rows)) {
        return '';
    }

    $source_label = isset($payload['source_label']) ? (string) $payload['source_label'] : __('This source', 'adanos-retail-sentiment-insights');
    $tickers = array();
    $detailed = array();

    foreach ($rows as $row) {
        if (empty($row['ticker'])) {
            continue;
        }

        $tickers[] = (string) $row['ticker'];
        $detailed[] = sprintf(
            /* translators: 1: ticker, 2: buzz */
            __('%1$s (%2$s buzz)', 'adanos-retail-sentiment-insights'),
            $row['ticker'],
            adanos_rsi_format_numeric_value(isset($row['buzz_score']) ? $row['buzz_score'] : 0, 1)
        );
    }

    if (empty($tickers)) {
        return '';
    }

    if ('list' === $format) {
        return implode(', ', $tickers);
    }

    if ('detailed' === $format) {
        return implode(', ', $detailed);
    }

    return sprintf(
        /* translators: 1: source label, 2: ticker list */
        __('%1$s is currently led by %2$s.', 'adanos-retail-sentiment-insights'),
        $source_label,
        adanos_rsi_join_text_list($tickers)
    );
}

function adanos_rsi_rest_response_from_error($error) {
    return new WP_REST_Response(
        array(
            'message' => $error->get_error_message(),
        ),
        500
    );
}

function adanos_rsi_rest_stock_sentiment(WP_REST_Request $request) {
    $source = adanos_rsi_sanitize_source($request->get_param('source'));
    $ticker = strtoupper(sanitize_text_field((string) $request->get_param('ticker')));
    $days = adanos_rsi_sanitize_period($request->get_param('days'));
    $show_explanation = adanos_rsi_sanitize_bool($request->get_param('show_explanation'), true);

    if ('' === $ticker) {
        return new WP_REST_Response(array('message' => __('Missing ticker parameter.', 'adanos-retail-sentiment-insights')), 400);
    }

    $payload = adanos_rsi_get_stock_widget_payload($source, $ticker, $days, $show_explanation);
    if (is_wp_error($payload)) {
        return adanos_rsi_rest_response_from_error($payload);
    }

    return rest_ensure_response($payload);
}

function adanos_rsi_rest_trending(WP_REST_Request $request) {
    $source = adanos_rsi_sanitize_source($request->get_param('source'));
    $days = adanos_rsi_sanitize_period($request->get_param('days'));
    $limit = adanos_rsi_sanitize_limit($request->get_param('limit'), 5, 20, 10);

    $payload = adanos_rsi_get_trending_widget_payload($source, $days, $limit);
    if (is_wp_error($payload)) {
        return adanos_rsi_rest_response_from_error($payload);
    }

    return rest_ensure_response($payload);
}

function adanos_rsi_register_rest_routes() {
    register_rest_route(
        'adanos-rsi/v1',
        '/stock-sentiment',
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'adanos_rsi_rest_stock_sentiment',
            'permission_callback' => '__return_true',
        )
    );

    register_rest_route(
        'adanos-rsi/v1',
        '/trending',
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'adanos_rsi_rest_trending',
            'permission_callback' => '__return_true',
        )
    );
}

add_action('rest_api_init', 'adanos_rsi_register_rest_routes');

function adanos_rsi_register_assets() {
    wp_register_script(
        'adanos-rsi-widgets',
        plugins_url('assets/widgets.js', __FILE__),
        array(),
        ADANOS_RSI_VERSION,
        true
    );

    wp_localize_script(
        'adanos-rsi-widgets',
        'adanosRsiConfig',
        array(
            'restBase' => esc_url_raw(rest_url('adanos-rsi/v1/')),
        )
    );

    wp_register_style(
        'adanos-rsi-styles',
        plugins_url('assets/style.css', __FILE__),
        array(),
        ADANOS_RSI_VERSION
    );
}

add_action('init', 'adanos_rsi_register_assets');

function adanos_rsi_enqueue_assets() {
    wp_enqueue_script('adanos-rsi-widgets');
    wp_enqueue_style('adanos-rsi-styles');
}

function adanos_rsi_render_error($message) {
    adanos_rsi_enqueue_assets();

    return '<div class="adanos-rsi-widget-error"><p>' . esc_html($message) . '</p></div>';
}

function adanos_rsi_render_widget($tag_name, $attributes, $width) {
    adanos_rsi_enqueue_assets();

    $parts = array();
    foreach ($attributes as $name => $value) {
        if ('' === (string) $value) {
            continue;
        }

        $parts[] = sprintf('%s="%s"', esc_attr($name), esc_attr($value));
    }

    return sprintf(
        '<div class="adanos-rsi-widget-wrapper" style="width:%1$s;"><%2$s %3$s></%2$s></div>',
        esc_attr($width),
        $tag_name,
        implode(' ', $parts)
    );
}

function adanos_rsi_shortcode_stock_sentiment($atts) {
    $atts = shortcode_atts(
        array(
            'symbol' => '',
            'ticker' => '',
            'source' => 'reddit',
            'theme' => 'light',
            'width' => '100%',
            'show_explanation' => 'true',
            'days' => 7,
        ),
        $atts,
        'adanos'
    );

    $symbol = !empty($atts['symbol']) ? $atts['symbol'] : $atts['ticker'];
    $symbol = strtoupper(sanitize_text_field($symbol));

    if ('' === $symbol) {
        return adanos_rsi_render_error(__('Please provide a stock symbol via symbol="AAPL".', 'adanos-retail-sentiment-insights'));
    }

    return adanos_rsi_render_widget(
        'adanos-stock-sentiment',
        array(
            'ticker' => $symbol,
            'source' => adanos_rsi_sanitize_source($atts['source']),
            'theme' => adanos_rsi_sanitize_theme($atts['theme']),
            'show-explanation' => adanos_rsi_sanitize_bool($atts['show_explanation']) ? 'true' : 'false',
            'days' => adanos_rsi_sanitize_period($atts['days']),
        ),
        adanos_rsi_sanitize_dimension($atts['width'])
    );
}

function adanos_rsi_shortcode_ticker_tape($atts) {
    $atts = shortcode_atts(
        array(
            'source' => 'reddit',
            'theme' => 'light',
            'width' => '100%',
            'limit' => 10,
            'speed' => 'normal',
        ),
        $atts,
        'adanos_ticker_tape'
    );

    return adanos_rsi_render_widget(
        'adanos-ticker-tape',
        array(
            'source' => adanos_rsi_sanitize_source($atts['source']),
            'theme' => adanos_rsi_sanitize_theme($atts['theme']),
            'limit' => adanos_rsi_sanitize_limit($atts['limit'], 5, 20, 10),
            'speed' => adanos_rsi_sanitize_speed($atts['speed']),
        ),
        adanos_rsi_sanitize_dimension($atts['width'])
    );
}

function adanos_rsi_shortcode_top_movers($atts) {
    $atts = shortcode_atts(
        array(
            'source' => 'reddit',
            'theme' => 'light',
            'width' => '100%',
            'limit' => 10,
            'period' => 1,
            'show_logos' => 'true',
        ),
        $atts,
        'adanos_top_movers'
    );

    return adanos_rsi_render_widget(
        'adanos-top-movers',
        array(
            'source' => adanos_rsi_sanitize_source($atts['source']),
            'theme' => adanos_rsi_sanitize_theme($atts['theme']),
            'limit' => adanos_rsi_sanitize_limit($atts['limit'], 5, 15, 10),
            'period' => adanos_rsi_sanitize_period($atts['period']),
            'show-logos' => adanos_rsi_sanitize_bool($atts['show_logos']) ? 'true' : 'false',
        ),
        adanos_rsi_sanitize_dimension($atts['width'])
    );
}

function adanos_rsi_shortcode_value($atts) {
    $atts = shortcode_atts(
        array(
            'symbol' => '',
            'ticker' => '',
            'source' => 'reddit',
            'field' => 'buzz',
            'days' => 7,
            'show_explanation' => 'false',
            'prefix' => '',
            'suffix' => '',
        ),
        $atts,
        'adanos_value'
    );

    $symbol = !empty($atts['symbol']) ? $atts['symbol'] : $atts['ticker'];
    $symbol = strtoupper(sanitize_text_field($symbol));

    if ('' === $symbol) {
        return esc_html__('Please provide a stock symbol.', 'adanos-retail-sentiment-insights');
    }

    $payload = adanos_rsi_get_stock_widget_payload(
        adanos_rsi_sanitize_source($atts['source']),
        $symbol,
        adanos_rsi_sanitize_period($atts['days']),
        adanos_rsi_sanitize_bool($atts['show_explanation'], false)
    );

    if (is_wp_error($payload)) {
        return esc_html($payload->get_error_message());
    }

    $value = adanos_rsi_format_stock_metric($payload, $atts['field']);
    if ('' === $value) {
        return '';
    }

    return esc_html((string) $atts['prefix'] . $value . (string) $atts['suffix']);
}

function adanos_rsi_shortcode_summary($atts) {
    $atts = shortcode_atts(
        array(
            'symbol' => '',
            'ticker' => '',
            'source' => 'reddit',
            'days' => 7,
            'format' => 'sentence',
            'show_explanation' => 'true',
        ),
        $atts,
        'adanos_summary'
    );

    $symbol = !empty($atts['symbol']) ? $atts['symbol'] : $atts['ticker'];
    $symbol = strtoupper(sanitize_text_field($symbol));

    if ('' === $symbol) {
        return esc_html__('Please provide a stock symbol.', 'adanos-retail-sentiment-insights');
    }

    $format = sanitize_key($atts['format']);
    if (!in_array($format, array('sentence', 'brief', 'explanation'), true)) {
        $format = 'sentence';
    }

    $payload = adanos_rsi_get_stock_widget_payload(
        adanos_rsi_sanitize_source($atts['source']),
        $symbol,
        adanos_rsi_sanitize_period($atts['days']),
        adanos_rsi_sanitize_bool($atts['show_explanation'], true)
    );

    if (is_wp_error($payload)) {
        return esc_html($payload->get_error_message());
    }

    return esc_html(adanos_rsi_build_stock_summary($payload, $format));
}

function adanos_rsi_shortcode_trending_text($atts) {
    $atts = shortcode_atts(
        array(
            'source' => 'reddit',
            'days' => 7,
            'period' => '',
            'limit' => 3,
            'format' => 'sentence',
        ),
        $atts,
        'adanos_trending_text'
    );

    $days = '' !== (string) $atts['period']
        ? adanos_rsi_sanitize_period($atts['period'])
        : adanos_rsi_sanitize_period($atts['days']);

    $format = sanitize_key($atts['format']);
    if (!in_array($format, array('sentence', 'list', 'detailed'), true)) {
        $format = 'sentence';
    }

    $payload = adanos_rsi_get_trending_widget_payload(
        adanos_rsi_sanitize_source($atts['source']),
        $days,
        adanos_rsi_sanitize_limit($atts['limit'], 1, 10, 3)
    );

    if (is_wp_error($payload)) {
        return esc_html($payload->get_error_message());
    }

    return esc_html(adanos_rsi_build_trending_text($payload, $format));
}

function adanos_rsi_register_shortcodes() {
    add_shortcode('adanos', 'adanos_rsi_shortcode_stock_sentiment');
    add_shortcode('adanos_ticker_tape', 'adanos_rsi_shortcode_ticker_tape');
    add_shortcode('adanos_top_movers', 'adanos_rsi_shortcode_top_movers');
    add_shortcode('adanos_value', 'adanos_rsi_shortcode_value');
    add_shortcode('adanos_summary', 'adanos_rsi_shortcode_summary');
    add_shortcode('adanos_trending_text', 'adanos_rsi_shortcode_trending_text');
}

add_action('init', 'adanos_rsi_register_shortcodes');
