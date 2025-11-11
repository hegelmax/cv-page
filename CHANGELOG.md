# Changelog

All notable changes to **MyCV** project.

## [v1.2.0] — Multi-User Resume Analytics

### 🚀 Added
- **Multi-user resume tracking:**  
  Each visit is now automatically linked to a logical `user` (resume owner) detected from the URL path.  
  - `/` and `/resume_slug` → `default`  
  - `/user_name` or `/user_name/resume_slug` → corresponding user (if `data/user_name` folder exists)  
- **New `user` column** in the `visits` table with automatic migration support.  
- **Dynamic user detection** built into `track.php` using the `detect_user_from_path()` function.  
- **User filter** in the analytics dashboard to view statistics per user.  
- **“Top users” table** displaying most viewed users and their visit counts.  
- **User column** added to the “Last 50 hits” table for better visibility of per-user traffic.  
- **Automatic schema migrations** from `./analytics/sql/` folder.  
  - Each migration file is executed once and tracked in `schema_migrations`.  
  - Base schema moved to `001_base_schema.sql`.

### ✨ Changed
- `bootstrap.php` refactored:  
  - All SQL schema creation and migrations are now externalized to separate `.sql` files in `/analytics/sql/`.  
  - Automatic detection and upgrade for existing databases (adds `user` column if missing).  
- Improved database initialization with transactional migration system.  
- Cleaner and modular architecture: migration logic decoupled from runtime code.

### 🧹 Removed
- Hard-coded SQL definitions from `bootstrap.php`.  
- Direct table-creation logic inline with analytics logic — now replaced by migration files.

### 💡 Impact
- The analytics system now supports multiple resume owners with independent statistics.  
- Safer schema evolution and easier maintenance.  
- Database updates happen automatically on first run — no manual SQL execution required.  
- Backward compatibility preserved for existing analytics data.

---

_Release date: 2025-11-11_


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