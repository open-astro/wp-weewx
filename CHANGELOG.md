# Changelog

All notable changes to this project are documented in this file.

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
