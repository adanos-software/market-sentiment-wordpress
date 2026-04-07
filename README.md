# Adanos Stock Sentiment Widgets for WordPress

`Adanos Stock Sentiment Widgets` is a WordPress plugin by [Adanos](https://adanos.org/) for finance publishers, investor blogs, and stock-focused communities.

It lets you embed live stock sentiment cards, ticker tapes, top movers tables, and inline text snippets directly inside WordPress posts and pages.

The plugin keeps the frontend widget code self-hosted inside WordPress, sends requests server-side, and caches API responses in WordPress transients so normal sites can stay within modest API limits.

## What it adds

- Single-stock sentiment widgets with buzz, bullish percentage, trend, and activity
- Trending ticker tape widgets for Reddit, Finance News, X.com, and Polymarket
- Top movers tables for current sentiment leaders
- Text shortcodes for inline buzz values, bullish percentages, and publish-ready summaries
- Cached responses with a one-click cache clear tool in the WordPress admin

## Good fits

- Stock profile pages
- Earnings preview and recap posts
- "Why this stock is trending" explainers
- Market open / market close summaries
- Newsletter landing pages
- Comparison pieces such as `NVDA vs AMD` or `TSLA vs Rivian`
- Watchlist pages for AI, growth, EV, or meme-stock coverage

## Installation

1. Upload the plugin files to `wp-content/plugins/adanos-stock-sentiment-widgets`.
2. Activate `Adanos Stock Sentiment Widgets` in WordPress admin.
3. Open `Settings > Adanos Stock Sentiment Widgets`.
4. Add your API key from [adanos.org/reddit-stock-sentiment#api-form](https://adanos.org/reddit-stock-sentiment#api-form).
5. Start with the default cache TTL of `86400` seconds for daily updates on the free plan.
6. Insert shortcodes into posts, pages, or blocks.

## Shortcodes

### Widgets

```text
[adanos symbol="AAPL" source="reddit" width="100%"]
[adanos_ticker_tape source="x" limit="10" speed="normal" width="100%"]
[adanos_top_movers source="news" limit="8" period="7" width="100%"]
```

### Text snippets

```text
[adanos_value symbol="AAPL" source="reddit" field="buzz"]
[adanos_summary symbol="AAPL" source="x" format="sentence"]
[adanos_trending_text source="news" limit="3" format="sentence"]
```

### Sources

- `reddit`
- `x`
- `news`
- `polymarket`

## Security and privacy

- The API key stays server-side in WordPress options.
- Visitors do not receive the API key in widget markup or browser requests.
- Requests are proxied through WordPress REST endpoints.
- Responses are cached in WordPress transients, which usually means database-backed caching on standard installs.
- The plugin ships its own frontend widget JavaScript. It does not hotlink runtime widget code from `adanos.org`.

For full WordPress.org-style disclosure, see [readme.txt](./readme.txt).

## External service

This plugin uses the Adanos Finance API:

- API docs: [api.adanos.org/docs](https://api.adanos.org/docs)
- Key signup: [adanos.org/reddit-stock-sentiment#api-form](https://adanos.org/reddit-stock-sentiment#api-form)
- Privacy policy: [adanos.org/privacy-policy](https://adanos.org/privacy-policy)
- Terms: [adanos.org/terms](https://adanos.org/terms)

## Repository contents

- `adanos-retail-sentiment-insights.php` — main WordPress plugin file
- `assets/` — self-hosted widget JS and CSS
- `readme.txt` — WordPress.org plugin readme
- `wordpress-org-assets/` — listing banners, icons, and screenshots
- `uninstall.php` — cleanup on uninstall

## Validation

Validated locally before publishing:

- targeted plugin tests in the main workspace: `python3 -m pytest tests/test_wordpress_retail_sentiment_plugin.py -q`
- PHP syntax lint in `wordpress:cli-php8.2`

Note: the parent monorepo currently has unrelated failing tests during full collection, so this repo documents the plugin-specific checks that were run for this package.
