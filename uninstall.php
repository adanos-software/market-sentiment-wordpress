<?php
/**
 * Uninstall Adanos Retail Sentiment Insights.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('adanos_rsi_options');
delete_option('adanos_rsi_cache_keys');
