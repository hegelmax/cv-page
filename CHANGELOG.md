# Changelog

All notable changes to **MyCV** project.

## [1.0.3] – 2025‑10‑27
### Added
- Web‑based analytics login system (sessions, CSRF, rate limit)
- CSP & Permissions‑Policy headers
- Full revision & security hardening pass
- Print‑safe theme isolation

### Improved
- Optimized CSS (removed duplicates, unified print rules)
- Revised routing (AJAX transitions + History API)
- Canonical URLs now generated in PHP

## [1.0.2] – 2025‑10‑27
### Added
- Dark theme and switcher (`☀️/🌙`)
- Smooth content fade animation during page switch
- Improved caching of partials in JS

## [1.0.1] – 2025‑10‑27
### Added
- Server‑side HTML caching (template + JSON)
- PHP fallback render if cache outdated
- Versioned pre‑rendering system

### Fixed
- Back button behavior in SPA navigation

## [1.0.0] – 2025‑10‑27
### Initial release
- Resume generator using JSON + PHP templates
- AJAX navigation (`switcher.ajax.js`)
- Light theme, static analytics tracker placeholder
