(function () {
    'use strict';

    if (window.__adanosWidgetsLoaded) {
        return;
    }
    window.__adanosWidgetsLoaded = true;

    var REST_BASE = (window.adanosRsiConfig && window.adanosRsiConfig.restBase) || '/wp-json/adanos-rsi/v1/';
    var CACHE_TTL = 5 * 60 * 1000;
    var memoryCache = new Map();

    function cacheGet(key) {
        var entry = memoryCache.get(key);
        if (!entry) {
            return null;
        }
        if (Date.now() - entry.ts > CACHE_TTL) {
            memoryCache.delete(key);
            return null;
        }
        return entry.data;
    }

    function cacheSet(key, data) {
        memoryCache.set(key, { ts: Date.now(), data: data });
    }

    function escapeHtml(value) {
        if (value == null) {
            return '';
        }
        var div = document.createElement('div');
        div.textContent = String(value);
        return div.innerHTML;
    }

    function formatNumber(value, fractionDigits) {
        if (typeof value !== 'number' || !isFinite(value)) {
            return '—';
        }
        return value.toLocaleString('en-US', {
            minimumFractionDigits: fractionDigits,
            maximumFractionDigits: fractionDigits
        });
    }

    function trendInfo(trend) {
        if (trend === 'rising') return { symbol: '↑', cls: 'up', label: 'Rising' };
        if (trend === 'falling') return { symbol: '↓', cls: 'down', label: 'Falling' };
        return { symbol: '→', cls: 'flat', label: 'Stable' };
    }

    function sentimentClass(bullishPct) {
        if (typeof bullishPct !== 'number') {
            return 'neutral';
        }
        if (bullishPct >= 55) {
            return 'positive';
        }
        if (bullishPct <= 45) {
            return 'negative';
        }
        return 'neutral';
    }

    function companyInitials(name) {
        if (!name || typeof name !== 'string') {
            return '';
        }

        var cleaned = name.trim().replace(/[^\p{L}\p{N}\s]/gu, ' ');
        if (!cleaned) {
            return '';
        }

        var parts = cleaned.split(/\s+/).filter(Boolean);
        if (!parts.length) {
            return '';
        }

        return parts.slice(0, 2).map(function (part) {
            return part.charAt(0).toUpperCase();
        }).join('');
    }

    function sparkline(values, width, height) {
        if (!Array.isArray(values) || values.length < 2) {
            return '';
        }

        var points = values.filter(function (value) {
            return typeof value === 'number' && isFinite(value);
        });
        if (points.length < 2) {
            return '';
        }

        var min = Math.min.apply(null, points);
        var max = Math.max.apply(null, points);
        var range = max - min || 1;
        var step = width / (points.length - 1);
        var line = points.map(function (value, index) {
            var x = index * step;
            var y = height - ((value - min) / range) * height;
            return x.toFixed(1) + ',' + y.toFixed(1);
        });

        var trend = points[points.length - 1] - points[0];
        var stroke = trend > 0 ? '#15803d' : trend < 0 ? '#dc2626' : '#6b7280';
        var fill = trend > 0 ? 'rgba(21,128,61,0.12)' : trend < 0 ? 'rgba(220,38,38,0.12)' : 'rgba(107,114,128,0.12)';
        var area = 'M0,' + height + ' L' + line.join(' L') + ' L' + width + ',' + height + ' Z';

        return '<svg width="' + width + '" height="' + height + '" viewBox="0 0 ' + width + ' ' + height + '">' +
            '<path d="' + area + '" fill="' + fill + '"></path>' +
            '<polyline points="' + line.join(' ') + '" fill="none" stroke="' + stroke + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></polyline>' +
            '</svg>';
    }

    function buildRestUrl(path, params) {
        var base = new URL(REST_BASE, window.location.href);
        var route = base.searchParams.get('rest_route');

        if (route) {
            route = route.replace(/\/?$/, '/') + path;
            base.searchParams.set('rest_route', route);
        } else {
            var normalizedPath = base.pathname.replace(/\/?$/, '/') + path;
            base.pathname = normalizedPath;
        }

        Object.keys(params || {}).forEach(function (key) {
            if (params[key] !== undefined && params[key] !== null && params[key] !== '') {
                base.searchParams.set(key, params[key]);
            }
        });

        return base.toString();
    }

    function fetchJson(path, params) {
        var query = new URLSearchParams(params || {});
        var url = buildRestUrl(path, params || {});
        var key = path + '?' + query.toString();
        var cached = cacheGet(key);
        if (cached) {
            return Promise.resolve(cached);
        }

        return fetch(url, {
            method: 'GET',
            headers: { Accept: 'application/json' }
        }).then(function (response) {
            if (!response.ok) {
                return response.json().catch(function () {
                    return { message: 'Failed to load widget data.' };
                }).then(function (payload) {
                    throw new Error(payload.message || 'Failed to load widget data.');
                });
            }
            return response.json();
        }).then(function (data) {
            cacheSet(key, data);
            return data;
        });
    }

    function themeCss(theme) {
        var dark = theme === 'dark';
        return '' +
            ':host{display:block;font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;line-height:1.45;color:' + (dark ? '#f3f4f6' : '#111827') + ';}' +
            '*,*:before,*:after{box-sizing:border-box;}' +
            ':host{--bg:' + (dark ? '#111827' : '#ffffff') + ';--bg-alt:' + (dark ? '#1f2937' : '#f8fafc') + ';--border:' + (dark ? '#374151' : '#e5e7eb') + ';--muted:' + (dark ? '#9ca3af' : '#6b7280') + ';--positive:#15803d;--negative:#dc2626;--neutral:' + (dark ? '#9ca3af' : '#6b7280') + ';--chip:' + (dark ? '#0f172a' : '#f8fafc') + ';}' +
            '.card{background:var(--bg);border:1px solid var(--border);border-radius:14px;overflow:hidden;}' +
            '.status{padding:28px 18px;text-align:center;font-size:13px;color:var(--muted);}' +
            '.status.error{color:var(--negative);}' +
            '.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;}' +
            '.muted{color:var(--muted);}' +
            '.positive{color:var(--positive);}.negative{color:var(--negative);}.neutral{color:var(--neutral);}' +
            '.up{color:var(--positive);}.down{color:var(--negative);}.flat{color:var(--neutral);}';
    }

    function renderShadowContent(shadowRoot, css, html) {
        shadowRoot.innerHTML = html;

        var style = document.createElement('style');
        style.textContent = css;
        shadowRoot.prepend(style);
    }

    function createWidgetClass(observed, render, load) {
        function Widget() {
            return Reflect.construct(HTMLElement, [], Widget);
        }
        Widget.prototype = Object.create(HTMLElement.prototype);
        Widget.prototype.constructor = Widget;
        Widget.observedAttributes = observed;
        Widget.prototype.connectedCallback = function () {
            if (!this.shadowRoot) {
                this.attachShadow({ mode: 'open' });
            }
            this._render();
            this._load();
        };
        Widget.prototype.attributeChangedCallback = function () {
            if (!this.shadowRoot) {
                return;
            }
            this._render();
            this._load();
        };
        Widget.prototype._attr = function (name, fallback) {
            return this.getAttribute(name) || fallback;
        };
        Widget.prototype._render = render;
        Widget.prototype._load = load;
        return Widget;
    }

    var StockSentiment = createWidgetClass(
        ['ticker', 'source', 'theme', 'show-explanation', 'days'],
        function () {
            var theme = this._attr('theme', 'light');
            var source = this._attr('source', 'reddit');
            renderShadowContent(
                this.shadowRoot,
                themeCss(theme) +
                '.header{display:flex;justify-content:space-between;align-items:flex-start;padding:18px 20px;border-bottom:1px solid var(--border);gap:12px;}' +
                '.ticker{font-size:28px;font-weight:700;line-height:1;}' +
                '.company{font-size:14px;margin-top:6px;color:var(--muted);}' +
                '.badge{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);}' +
                '.grid{display:grid;grid-template-columns:1fr 1fr;gap:1px;background:var(--border);}' +
                '.cell{padding:16px 18px;background:var(--bg);}' +
                '.label{display:block;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:6px;}' +
                '.value{font-size:24px;font-weight:700;line-height:1.1;}' +
                '.spark{padding:12px 20px;border-top:1px solid var(--border);background:var(--bg);}' +
                '.summary{padding:16px 20px;border-top:1px solid var(--border);background:var(--bg-alt);font-size:14px;color:var(--muted);line-height:1.6;}' +
                '@media (max-width:520px){.grid{grid-template-columns:1fr;}.ticker{font-size:24px;}}',
                '<div class="card">' +
                '<div class="header"><div><div class="ticker mono">' + escapeHtml(this._attr('ticker', '')) + '</div><div class="company" id="company">Loading…</div></div><div class="badge">' + escapeHtml(source.toUpperCase()) + ' Sentiment</div></div>' +
                '<div id="body"><div class="status">Loading sentiment data…</div></div>' +
                '</div>'
            );
        },
        function () {
            var self = this;
            var ticker = this._attr('ticker', '');
            if (!ticker) {
                this.shadowRoot.getElementById('body').innerHTML = '<div class="status error">No ticker specified.</div>';
                return;
            }

            fetchJson('stock-sentiment', {
                ticker: ticker,
                source: this._attr('source', 'reddit'),
                show_explanation: this._attr('show-explanation', 'true'),
                days: this._attr('days', '7')
            }).then(function (data) {
                var trend = trendInfo(data.trend);
                var sentiment = sentimentClass(data.bullish_pct);
                var spark = sparkline(data.trend_history, 360, 48);
                var summaryValue = data.summary_value == null
                    ? '—'
                    : (data.summary_label === 'Liquidity'
                        ? '$' + formatNumber(Number(data.summary_value), 0)
                        : formatNumber(Number(data.summary_value), 0));

                var body = '' +
                    '<div class="grid">' +
                    '<div class="cell"><span class="label">Buzz score</span><div class="value mono">' + formatNumber(Number(data.buzz_score), 1) + '</div></div>' +
                    '<div class="cell"><span class="label">Bullish</span><div class="value mono ' + sentiment + '">' + formatNumber(Number(data.bullish_pct), 0) + '%</div></div>' +
                    '<div class="cell"><span class="label">' + escapeHtml(data.activity_label) + '</span><div class="value mono">' + formatNumber(Number(data.activity_value), 0) + '</div></div>' +
                    '<div class="cell"><span class="label">Trend</span><div class="value mono ' + trend.cls + '">' + trend.symbol + ' ' + escapeHtml(trend.label) + '</div></div>' +
                    '<div class="cell"><span class="label">' + escapeHtml(data.summary_label) + '</span><div class="value mono">' + escapeHtml(summaryValue) + '</div></div>' +
                    '<div class="cell"><span class="label">Source</span><div class="value mono">' + escapeHtml(data.source_label) + '</div></div>' +
                    '</div>';

                if (spark) {
                    body += '<div class="spark"><span class="label">7-day trend</span>' + spark + '</div>';
                }

                if (data.explanation) {
                    body += '<div class="summary"><span class="label">Trend summary</span>' + escapeHtml(data.explanation) + '</div>';
                }

                self.shadowRoot.getElementById('company').textContent = data.company_name || '—';
                self.shadowRoot.getElementById('body').innerHTML = body;
            }).catch(function (error) {
                self.shadowRoot.getElementById('body').innerHTML = '<div class="status error">' + escapeHtml(error.message || 'Failed to load sentiment data.') + '</div>';
                self.shadowRoot.getElementById('company').textContent = '—';
            });
        }
    );

    var TickerTape = createWidgetClass(
        ['source', 'theme', 'limit', 'speed'],
        function () {
            var theme = this._attr('theme', 'light');
            renderShadowContent(
                this.shadowRoot,
                themeCss(theme) +
                '.tape{overflow:hidden;background:var(--bg);border:1px solid var(--border);border-radius:8px;height:52px;display:flex;align-items:center;}' +
                '.inner{display:flex;gap:28px;align-items:center;padding:0 18px;white-space:nowrap;width:max-content;}' +
                '.item{display:flex;gap:8px;align-items:center;font-size:13px;}' +
                '.sym{font-weight:700;}.buzz{color:var(--muted);}.dot{width:6px;height:6px;border-radius:999px;display:inline-block;}.dot.positive{background:var(--positive);}.dot.negative{background:var(--negative);}.dot.neutral{background:var(--neutral);}' +
                '.sep{width:1px;height:18px;background:var(--border);}' +
                '@keyframes scroll{0%{transform:translateX(0);}100%{transform:translateX(-50%);}}' +
                '@media (prefers-reduced-motion: reduce){.inner{animation:none !important;}}',
                '<div class="tape"><div class="inner status">Loading sentiment data…</div></div>'
            );
        },
        function () {
            var self = this;
            var speedMap = { slow: 42, normal: 26, fast: 16 };

            fetchJson('trending', {
                source: this._attr('source', 'reddit'),
                days: 1,
                limit: this._attr('limit', '10')
            }).then(function (data) {
                if (!data.rows || !data.rows.length) {
                    self.shadowRoot.querySelector('.inner').className = 'inner status';
                    self.shadowRoot.querySelector('.inner').textContent = 'No data available.';
                    return;
                }

                var html = '';
                data.rows.forEach(function (row, index) {
                    var sentiment = sentimentClass(row.bullish_pct);
                    var trend = trendInfo(row.trend);
                    html += '<div class="item">' +
                        '<span class="sym mono">' + escapeHtml(row.ticker) + '</span>' +
                        '<span class="buzz mono">' + formatNumber(Number(row.buzz_score), 1) + '</span>' +
                        '<span class="dot ' + sentiment + '"></span>' +
                        '<span class="' + trend.cls + '">' + trend.symbol + '</span>' +
                        '</div>';
                    if (index < data.rows.length - 1) {
                        html += '<div class="sep"></div>';
                    }
                });

                var inner = self.shadowRoot.querySelector('.inner');
                inner.className = 'inner';
                inner.innerHTML = html + html;
                inner.style.animation = 'scroll ' + (speedMap[self._attr('speed', 'normal')] || speedMap.normal) + 's linear infinite';
            }).catch(function (error) {
                var inner = self.shadowRoot.querySelector('.inner');
                inner.className = 'inner status error';
                inner.textContent = error.message || 'Failed to load tape data.';
            });
        }
    );

    var TopMovers = createWidgetClass(
        ['source', 'theme', 'limit', 'period', 'show-logos'],
        function () {
            var theme = this._attr('theme', 'light');
            var source = this._attr('source', 'reddit');
            renderShadowContent(
                this.shadowRoot,
                themeCss(theme) +
                '.card{background:var(--bg);border:1px solid var(--border);border-radius:14px;overflow:hidden;}' +
                '.header{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);gap:16px;}' +
                '.title{font-size:16px;font-weight:700;}.badge{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);}' +
                'table{width:100%;border-collapse:collapse;}.wrap{overflow-x:auto;}' +
                'th,td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;text-align:left;vertical-align:middle;}' +
                'th{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);}' +
                'tr:last-child td{border-bottom:none;}.rank{width:34px;color:var(--muted);}.ticker{font-weight:700;}.company{color:var(--muted);}' +
                '.sent{display:flex;align-items:center;gap:8px;}.bar{flex:1;max-width:64px;height:4px;background:var(--border);border-radius:999px;overflow:hidden;}.fill{height:100%;}.fill.positive{background:var(--positive);}.fill.negative{background:var(--negative);}.fill.neutral{background:var(--neutral);}' +
                '.spark svg{display:block;}.logo{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:6px;background:var(--chip);border:1px solid var(--border);font-size:10px;margin-right:8px;color:var(--muted);}' +
                '@media (max-width:480px){th:nth-child(3),td:nth-child(3){display:none;}}',
                '<div class="card"><div class="header"><div class="title">Top Movers</div><div class="badge">' + escapeHtml(source.toUpperCase()) + ' Sentiment</div></div><div class="wrap"><table><thead><tr><th>#</th><th>Ticker</th><th>Company</th><th>Buzz</th><th>Bullish</th><th>Trend</th></tr></thead><tbody id="tbody"><tr><td colspan="6" class="status">Loading sentiment data…</td></tr></tbody></table></div></div>'
            );
        },
        function () {
            var self = this;
            var showLogos = this._attr('show-logos', 'true') !== 'false';

            fetchJson('trending', {
                source: this._attr('source', 'reddit'),
                days: this._attr('period', '1'),
                limit: this._attr('limit', '10')
            }).then(function (data) {
                var tbody = self.shadowRoot.getElementById('tbody');
                if (!data.rows || !data.rows.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="status">No data available.</td></tr>';
                    return;
                }

                tbody.innerHTML = data.rows.map(function (row, index) {
                    var cls = sentimentClass(row.bullish_pct);
                    var trend = trendInfo(row.trend);
                    var initials = companyInitials(row.company_name);
                    var logo = showLogos && initials ? '<span class="logo mono" aria-hidden="true">' + escapeHtml(initials) + '</span>' : '';
                    var spark = sparkline(row.trend_history, 56, 20);
                    return '<tr>' +
                        '<td class="rank mono">' + (index + 1) + '</td>' +
                        '<td class="ticker mono">' + logo + escapeHtml(row.ticker) + '</td>' +
                        '<td class="company">' + escapeHtml(row.company_name || '—') + '</td>' +
                        '<td class="mono">' + formatNumber(Number(row.buzz_score), 1) + '</td>' +
                        '<td><div class="sent"><span class="mono ' + cls + '">' + formatNumber(Number(row.bullish_pct), 0) + '%</span><div class="bar"><div class="fill ' + cls + '" style="width:' + Math.max(0, Math.min(100, Number(row.bullish_pct) || 0)) + '%"></div></div></div></td>' +
                        '<td class="spark">' + (spark || ('<span class="' + trend.cls + '">' + trend.symbol + ' ' + escapeHtml(trend.label) + '</span>')) + '</td>' +
                        '</tr>';
                }).join('');
            }).catch(function (error) {
                self.shadowRoot.getElementById('tbody').innerHTML = '<tr><td colspan="6" class="status error">' + escapeHtml(error.message || 'Failed to load movers data.') + '</td></tr>';
            });
        }
    );

    var widgets = {
        'adanos-stock-sentiment': StockSentiment,
        'adanos-ticker-tape': TickerTape,
        'adanos-top-movers': TopMovers
    };

    Object.keys(widgets).forEach(function (tag) {
        if (!customElements.get(tag)) {
            customElements.define(tag, widgets[tag]);
        }
    });
})();
