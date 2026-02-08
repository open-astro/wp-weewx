# Changelog
All notable changes to WPWeeWX will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## 0.5.1

- Add SQM Time field (sqmTime / sqm_time) to labels and SQM Latest tab; show SQM Time row when available.
- SQM Latest tab: fallback to last SQM row from daily data when no current reading ($sqm_latest); use for metric display and to show tab when only historical SQM data exists.
- Chart value format: support `fixed:N` (e.g. `fixed:2`) for axis and tooltip; Solar Alt, Lunar Alt, Lunar Phase use fixed:2.
- SQM daily charts: prefer sqmTime for x-axis labels when present, else timestamp_epoch.

## 0.5.0

- Add SQM (sky quality) support: optional display of SQM metrics and charts when data is available.
- Add admin setting "Show SQM Data" (checkbox) and `wpweewx_show_sqm` option with boolean sanitization.
- Add dashboard SQM metrics: SQM, SQM Temp, NELM, cd/m², NSU, Solar Alt, Lunar Alt, Lunar Phase (labels, series, charts).
- Add path-aware number formatting: higher precision and scientific notation for cd/m² (dark sky) values.
- Improve JSON normalization in fetcher: handle missing scalars, strip trailing commas instead of inserting null.
- Add `extract_daily_series_fallback` and `build_summary_series_set` for dashboard chart data.
- Chart options: support `value_format: sci` and series labels; Chart.js axis uses scientific format when set.
- Dashboard chart layout: chart-series wrapper, responsive grid (2-col at 1024px, 1-col at 768px), stacked header on small screens.
- Chart.js: visible points on line charts (pointRadius 2, hit radius 10), scientific tick formatter when needed.
- Rename "Wind Dir" to "Wind Direction" in LCD label map.

## 0.4.0

- Redesign admin settings page with card-based layout and professional styling.
- Rename URL settings: Simple/Main/LCD JSON URL → Conditions Current / Summary / Dataset URL.
- Add dedicated admin CSS (wpweewx-admin.css) with sections, responsive layout, and dark admin support.
- Group settings into Data sources, Dataset options, Caching & network, and Display cards.
- Update Default Source and Test Fetch labels to match (Conditions Current/Summary/Dataset).
- Add OK/Failed status badge and clearer result table for connection tests.

## 0.3.0

- Add temperature unit support: site setting (F/C) and per-visitor cookie override.
- Add F/C unit toggle in header on current, summary, and dashboard views.
- Convert all temperature displays (current, summary, dashboard, LCD) to selected unit.
- Add LCD extra temp 1/2/3 configurable labels in admin settings.
- Add Chart.js-based dashboard charts (LCD daily/summary series) with theme-aware colors.
- Add `wpweewx-charts.js` for chart rendering and unit-toggle cookie handling.
- Add chart series CSS variables and header-actions/unit-toggle styles.
- Expand LCD dashboard: dew point/wind chill labels, field label helper, series fallbacks, wind direction bins, epoch label formatting.

## 0.2.0

- Add LCD datasheet source, settings, and test fetch support.
- Fix LCD JSON parsing with missing values and provide better errors.
- Add LCD dashboard charts/tabs with inline SVG sparklines and axis labels.
- Fix LCD timestamp to use WordPress timezone.
- Add LCD altitude mapping and dashboard-only LCD view behavior.
- Add Simple/Main/LCD ordering in admin settings.
- Improve chart layout and styles.

## 0.1.1

- Refresh JSON on each page load and add cache-busting for forced refreshes.
- Improve dashboard layout, tighten spacing, and add tabbed extremes.
- Add reload button in the header.
- Update README with preview image, license info, and usage notes.
- Add GPLv3 license file and .gitignore for macOS metadata.

## 0.1.0

- Initial release with settings, shortcode, templates, and styling.
