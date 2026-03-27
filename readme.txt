=== Market Sentiment ===
Contributors: adanos
Tags: stocks, finance, investing, sentiment, shortcode, widget
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.5.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed self-hosted stock sentiment widgets and shortcodes for WordPress, powered by Adanos.

== Description ==

Market Sentiment helps finance publishers, bloggers, and investor communities embed self-hosted stock sentiment widgets directly into WordPress content.

The plugin ships the widget code locally inside WordPress and proxies Adanos API requests through WordPress REST endpoints. Responses are cached with WordPress transients, which means the cache lives in the database by default when no external object cache is active. The default cache TTL is 24 hours so the free 250 requests/month API plan can support daily refreshes without constant re-fetching.

The plugin adds widget and text shortcodes:

* `[adanos]` for the stock sentiment card widget
* `[adanos_ticker_tape]` for the live ticker tape widget
* `[adanos_top_movers]` for the top movers widget
* `[adanos_value]` for inline data points like buzz, bullish percentage, mentions, or trend
* `[adanos_summary]` for one-line stock sentiment summaries
* `[adanos_trending_text]` for plain-text trending stock lists and sentences

Supported sources:

* `reddit`
* `x`
* `news`
* `polymarket`

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **Market Sentiment** in the WordPress admin.
3. Open **Settings > Market Sentiment** and add your Adanos Finance API key.
4. If you do not have a key yet, get one at https://adanos.org/reddit-stock-sentiment#api-form
5. Use the shortcodes in posts, pages, or widget areas.

== Screenshots ==

1. Widget and text shortcodes inside a stock-focused WordPress article.
2. Admin settings page with API key, cache tools, and shortcode guidance.

== External services ==

This plugin connects to the Adanos Finance API to fetch retail sentiment data for stocks.

It sends:

* the stock ticker or tickers requested by your shortcode
* the source you selected (`reddit`, `x`, `news`, or `polymarket`)
* the requested lookback window and limit values
* your server IP address as part of the normal HTTPS request
* your Adanos API key in the request header

It does not send visitor-level personal data from the frontend directly to Adanos. Requests are made server-side by WordPress.

Service provider:

* Adanos Software GmbH
* Website: https://adanos.org/
* API docs: https://api.adanos.org/docs
* Privacy Policy: https://adanos.org/privacy-policy
* Terms of Service: https://adanos.org/terms

== Frequently Asked Questions ==

= Do I need an API key? =

Yes. The plugin fetches live sentiment data from the Adanos Finance API through a local WordPress proxy. You can get an API key at https://adanos.org/reddit-stock-sentiment#api-form

= What cache setting should I use on a free API key? =

The default is `86400` seconds (24 hours). That is the recommended starting point for the free 250 requests/month plan if you want sentiment values to refresh roughly once per day.

= Is the widget JavaScript self-hosted? =

Yes. The widget code is bundled inside the plugin and loaded from your WordPress site, not from `adanos.org`.

= What shortcodes are available? =

Single stock card:

`[adanos symbol="AAPL" source="reddit" width="100%"]`

Ticker tape:

`[adanos_ticker_tape source="reddit" limit="10" speed="normal" width="100%"]`

Top movers:

`[adanos_top_movers source="x" limit="8" period="7" width="100%"]`

Inline value:

`[adanos_value symbol="AAPL" source="reddit" field="buzz"]`

Inline sentence:

`[adanos_summary symbol="AAPL" source="x" format="sentence"]`

Trending text:

`[adanos_trending_text source="news" limit="3" format="sentence"]`

= Which widget options are supported? =

* `[adanos]`: `symbol`, `source`, `theme`, `width`, `show_explanation`
* `[adanos_ticker_tape]`: `source`, `theme`, `width`, `limit`, `speed`
* `[adanos_top_movers]`: `source`, `theme`, `width`, `limit`, `period`, `show_logos`
* `[adanos_value]`: `symbol`, `source`, `field`, `days`, `prefix`, `suffix`
* `[adanos_summary]`: `symbol`, `source`, `days`, `format`
* `[adanos_trending_text]`: `source`, `days`, `limit`, `format`

= Which sources can I use? =

All shortcodes support these sources:

* `reddit`
* `x`
* `news`
* `polymarket`

= What values can I use with [adanos_value]? =

Common fields:

* `buzz`
* `bullish`
* `trend`
* `mentions`
* `trades`
* `activity`
* `ticker`
* `company`
* `source_label`

Additional fields:

* `summary`
* `summary_value`
* `summary_label`
* `explanation`

= What formats can I use? =

`[adanos_summary]` supports:

* `sentence`
* `brief`
* `explanation`

`[adanos_trending_text]` supports:

* `sentence`
* `list`
* `detailed`

`[adanos_ticker_tape]` speed supports:

* `slow`
* `normal`
* `fast`

`[adanos]` and widget shortcodes support:

* `theme="light"`
* `theme="dark"`

= What are some ready-to-use examples? =

Inline buzz:

`Buzz score: [adanos_value symbol="AAPL" source="news" field="buzz"]`

Inline bullish percentage:

`Bullish sentiment: [adanos_value symbol="NVDA" source="reddit" field="bullish"]`

Inline sentence summary:

`[adanos_summary symbol="TSLA" source="x" format="sentence"]`

Trending ticker list:

`Currently trending: [adanos_trending_text source="news" limit="5" format="list"]`

Detailed trending text:

`[adanos_trending_text source="reddit" limit="3" format="detailed"]`

== Privacy ==

Market Sentiment stores your Adanos API key in the WordPress options table and uses it only for server-side API requests.

The plugin also stores cached API responses in WordPress transients to reduce repeated requests and improve page performance. On most standard WordPress installs, those cached responses are stored in the database unless an external object cache is active.

The plugin does not track visitors, set its own analytics cookies, or send visitor-entered form data to Adanos.

== Changelog ==

= 0.5.5 =

* Refreshed the WordPress.org banner, icon, and screenshots with cleaner final assets
* Clarified the repository vs. WordPress.org SVN submission structure in the repo docs

= 0.5.4 =

* Raised the default cache TTL to 24 hours so daily refreshes fit the free 250 requests/month API plan better
* Expanded the cache setting guidance in the admin and readme

= 0.5.3 =

* Redesigned the WordPress settings page into a cleaner dashboard layout
* Grouped shortcodes into cards with clearer source, format, and option guidance
* Added a more compact FAQ and sidebar quick-start flow

= 0.5.2 =

* Renamed the visible plugin title to `Market Sentiment`
* Kept Adanos as the publisher and external data provider in the readme and plugin metadata

= 0.5.1 =

* Expanded shortcode documentation in the settings page and readme
* Documented supported sources, formats, and field options more explicitly

= 0.5.0 =

* Added WordPress.org listing assets for banner, icon, and screenshots
* Added clear external-service and privacy disclosures to the readme
* Added a cache tools section and cache-clear button in the WordPress admin
* Indexed cached transient keys so cached Adanos responses can be cleared safely

= 0.4.2 =

* Replaced top movers ticker-prefix bubbles with cleaner company-initial badges
* Removed redundant ticker-derived labels like `GO` before `GOOGL`

= 0.4.1 =

* Expanded the WordPress settings page with a full FAQ and publishing guidance
* Added clearer admin explanations for widget vs text shortcode use cases

= 0.4.0 =

* Added `[adanos_value]` for inline buzz, bullish, activity, and trend values
* Added `[adanos_summary]` for sentence, brief, and explanation-based stock text
* Added `[adanos_trending_text]` for plain-text trending stock snippets
* Kept all text shortcodes on the same cached local WordPress proxy as the widgets

= 0.3.0 =

* Switched to self-hosted widget code inside the plugin
* Added a local WordPress REST proxy for Adanos data requests
* Cached widget responses via WordPress transients
* Restored a simple settings page for API key and cache TTL
* Added `[adanos]`, `[adanos_ticker_tape]`, and `[adanos_top_movers]` shortcodes

= 0.1.0 =

* Initial release
