# Changelog

All notable changes to **MyCV** project.

## [v1.1.0] — Responsive Topbar Redesign

### 🚀 Added
- Unified **topbar layout** supporting both **button** and **dropdown** track switchers.
- Automatic detection of the number of resume tracks:
  - **1 track:** no switcher shown.
  - **2–3 tracks:** buttons on desktop, dropdown on mobile.
  - **4+ tracks:** dropdown only.
- Responsive behavior using CSS media queries for seamless transitions.
- Dark theme styling for the new `.seg-select` dropdown.
- English-only documentation comments for clarity.

### ✨ Changed
- Fully refactored and consolidated **topbar-related CSS** into one section.
- Replaced duplicate selectors (`.seg-select`, `.seg-buttons`, etc.) with a unified style group.
- Improved alignment, padding, and border-radius consistency across buttons and dropdowns.
- Enhanced small-screen layout spacing and readability.
- Updated **print rules** to hide topbar and switcher elements for clean export.

### 🧹 Removed
- Legacy duplicate CSS rules for `.seg-select` and `.seg-buttons`.
- Outdated media queries and redundant dark theme overrides.

### 💡 Impact
- Consistent visual behavior across all screen sizes.
- Cleaner, more maintainable CSS structure.
- Simplified future customization of the topbar and switcher UI.

## [1.0.9] – 2025‑11-11
- Added logic to hide entire sections (Experience, Education, Achievements, Skills, etc.)
  when corresponding data arrays are empty in JSON.
- Updated `blocks_experience()` to show “Projects:” only when a job actually contains projects.
- Introduced helper `section_if_not_empty()` and refactored `build_mapping()` 
  to generate *_SECTION placeholders combining title + content dynamically.
- Updated `main.template.html` to use new placeholders (##*_SECTION##) instead of static headers.
- Added conditional rendering of Image (ex. QR) code via `build_image()` and new placeholder ##IMAGE_BLOCK##.
  Image is now displayed only when the "image" field exists in the JSON.
- Minor code cleanup and consistent formatting of HTML blocks.

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