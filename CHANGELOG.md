# Changelog

All notable changes to **MyCV** project.

## [1.0.8] – 2025‑10‑28
### Added
- Redirect to analytics after first-time setup completion
- Demo data auto-fallback if user JSONs not found
- Automatic cache directory creation on startup (`init.php`)
- Improved partial transition fade animation (fix for mobile)
- Enhanced security headers in `.htaccess` (CSP, Permissions-Policy)
- Login rate limit, cookie flags, and redirect after successful auth

### Improved
- Cleaner analytics dashboard layout (dark mode only)
- Optimized Chart.js rendering performance
- Fixed persistent auth cookie issue
- Unified analytics setup wizard with CSRF protection

### Fixed
- Stats no longer count authenticated analytics users
- Setup now correctly redirects to dashboard upon success

---

## [1.0.4] – 2025‑10‑27
### Added
- Web-based analytics login system (sessions, CSRF, rate limit)
- CSP & Permissions-Policy headers
- Full revision & security hardening pass
- Print-safe theme isolation

### Improved
- Optimized CSS (removed duplicates, unified print rules)
- Revised routing (AJAX transitions + History API)
- Canonical URLs now generated in PHP

---

## [1.0.3] – 2025‑10‑27
### Added
- Dark theme and switcher (☀️/🌙)
- Smooth fade animation during page switch
- Improved caching of partials in JS

---

## [1.0.2] – 2025‑10‑27
### Added
- Server-side HTML caching (template + JSON)
- PHP fallback render if cache outdated
- Versioned pre-rendering system

### Fixed
- Back button behavior in SPA navigation

---

## [1.0.1] – 2025‑10‑27
### Added second template
- Resume generator using JSON + PHP templates
- AJAX navigation (`switcher.ajax.js`)
- Light theme, static analytics tracker placeholder

---

## [1.0.0] – 2024‑02‑11
### Initial release
- Main CV Template created