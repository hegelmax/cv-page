# MyCV — Dynamic Resume & Portfolio

A lightweight, self‑hosted resume/portfolio site built with **PHP + JSON + AJAX**.  
It renders fast, works with clean URLs, supports a **Light/Dark** theme, and ships with a **privacy‑friendly analytics** dashboard (SQLite + Chart.js).

Demo data (no personal info) is included: **John Doe** for both tracks.

You can find how it looks like here [https://cv.hgl.mx](https://cv.hgl.mx)

---

## ✨ Features

- 🧩 **Server-side rendering** with JSON data (no frameworks required)
- ⚡ **Instant navigation** without reloads (AJAX + History API)
- 🌓 **Theme toggle** (Light/Dark). Print is always light.
- 📦 **Smart caching**: pre-renders HTML and rebuilds when JSON/templates change
- 🌐 **Clean URLs**: `/`, `/developer`, `/analyst`
- 📊 **Built-in analytics**: local SQLite log (referrer, UTM, device, theme, tz, DPR, perf)
- 🔐 **Secure web login** for analytics (sessions, CSRF, rate limit)
- 🧱 **No third-party trackers**; optional Cloudflare country support

---

## 🗂 Project Structure

```
/
├─ index.php                 # Router: resolves track & serves full/partial HTML
├─ lib/
│   ├─ render.php            # Template injection + cache engine
│   └─ utils.php             # Helpers (safe HTML, template memoization, etc.)
├─ data/
│   ├─ demo/
│   │   ├─ john_doe_prog.json
│   │   └─ john_doe_analyst.json
│   └─ README.md            # (optional) describe your own JSON schema here
├─ templates/
│   ├─ layout.html           # Page chrome (head, theme button, scripts)
│   ├─ chooser.html          # Track selector (cards for Developer/Analyst)
│   ├─ main.template.html    # Resume template (placeholders like ##PAGE_TITLE##)
│   └─ partials/             # Reusable blocks (header, sections, etc.)
├─ assets/
│   ├─ main.ssr.css          # Styles (light/dark + print)
│   ├─ switcher.ajax.js      # Intercepts links, swaps content, caches partials
│   ├─ theme.js              # Theme persistence & toggle button
│   └─ analytics.js          # Client-side metrics collector
└─ analytics/
    ├─ bootstrap.php         # SQLite setup (schema, PRAGMAs)
    ├─ track.php             # POST endpoint for beacons
    ├─ index.php             # Web dashboard (charts + filters)
    ├─ auth.php              # Sessions, CSRF, require_auth()
    ├─ login.php             # Web login form (rate-limited)
    ├─ logout.php            # Session destroy
    ├─ config.php            # Login and password hash (edit this)
    └─ cleanup.php           # Optional rotation/VACUUM
```

---

## 🔧 Requirements

- **PHP 8.1+** with **SQLite3** enabled
- Apache/Nginx with URL rewriting (examples below)
- HTTPS recommended (for secure cookies in analytics)

---

## 🚀 Quick Start

1) **Clone** and go to the project
```bash
git clone https://github.com/hegelmax/cv-page.git
cd mycv
```

2) **Demo data** (already provided):
```
data/demo/john_doe_prog.json
data/demo/john_doe_analyst.json
```

3) **Configure analytics login** (`/analytics/config.php`)
```php
<?php
declare(strict_types=1);
const ANALYTICS_LOGIN = 'admin';
const ANALYTICS_PASS_HASH = 'REPLACE_ME_WITH_password_hash'; // run the PHP one-liner below
```
Generate a password hash (copy the output into `ANALYTICS_PASS_HASH`):
```bash
php -r "echo password_hash('YourStrongPass', PASSWORD_DEFAULT), PHP_EOL;"
```

4) **Permissions**: allow write for cache & analytics DB
```bash
mkdir -p cache analytics
chmod -R 775 cache analytics
```

5) **.htaccess (Apache)** — put in repo root:
```apache
RewriteEngine On
RewriteBase /

# 1) Allow static & analytics
RewriteCond %{REQUEST_URI} ^/(assets|analytics)/ [NC]
RewriteRule ^ - [L]

# 2) Existing files/dirs
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# 3) Pretty routes
RewriteRule ^$ index.php [L,QSA]
RewriteRule ^(developer|analyst)/?$ index.php [L,QSA]
```

6) **Nginx example** (location block within your server):
```nginx
location ~ ^/(assets|analytics)/ { try_files $uri =404; }
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
```

7) Open your site:
- `/` → choose track
- `/developer`  → Developer/Engineer resume (John Doe)
- `/analyst`    → Analyst/Management resume (John Doe)
- `/analytics/` → dashboard (requires login)

---

## 🧩 JSON Schema (minimal)

Your resume JSON should provide fields used by `main.template.html`.  
Demo files are good starting points.

Common fields:
```json
{
  "name": "John Doe",
  "title": "Software Engineer",
  "contact": { "email": "...", "phone": "...", "location": "...", "website": "...", "linkedin": "..." },
  "summary": "Short HTML or plain text",
  "achievements": [{ "name": "Title", "desc": "Details" }],
  "experience": [
    {
      "title": "Role",
      "companies": [{ "company": "Org", "location": "City", "period": "2022 – Present" }],
      "highlights": ["Bullet line", "…"]
    }
  ],
  "education": [{ "degree": "B.Sc. ...", "institution": "University" }],
  "skills": { "list": ["Python", "SQL", "..."] },
  "languages": [{ "name": "English", "level": "Native" }]
}
```

Placeholders like `##PAGE_TITLE##` in `templates/main.template.html` are replaced by PHP using your JSON.

---

## 🌓 Theme

- Toggle button (☀️/🌙) persists choice in `localStorage`.
- Print stylesheet forces **light** theme for better paper readability.
- Button is outside `#app` so it survives AJAX swaps.

---

## ⚡ AJAX Navigation

- `assets/switcher.ajax.js` intercepts clicks for `/`, `/developer`, `/analyst`.
- Loads partial HTML via `fetch` with `X-Requested-With: fetch-partial`.
- Caches responses (no repeated server hits).
- Updates History API & handles Back/Forward.
- Smooth fade animation with `prefers-reduced-motion` support.

---

## 📊 Built-in Analytics

### Client
`assets/analytics.js` collects:
- URL/path, referrer, UTM params
- language(s), time zone
- devicePixelRatio (DPR), viewport & screen size
- current theme (light/dark)
- basic performance timing (TTFB, DOM, DCL, Load)
- SPA transitions (virtual hits)

Respects `Do Not Track`, localhost, and your special cookie `an_ignore=1` (set after analytics login).

### Server
- SQLite DB at `analytics/analytics.db` (auto-created)
- `analytics/track.php` stores hits (with simple rate limiting)
- `analytics/index.php` renders charts/tables (requires web login)
- `analytics/cleanup.php` rotates old records (optional, via cron)

### Security
- Web login at `/analytics/login.php` (session, CSRF, rate limit)
- Cookies: `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS
- Optional CSP/Permissions-Policy headers via web server config

---

## 🛠 Customization

- Add a new track: create a new JSON file and extend routing if needed.
- Override styles: put your tweaks in a new CSS file and include after `main.ssr.css`.
- Extend analytics: add new columns & update `track.php` accordingly.
- SEO: set canonical/OG meta in PHP (`render_layout_page`) using current path and JSON title/summary.

---

## 🧪 Development Notes

- When JSON or templates change, server cache invalidates automatically.
- SPA fallback: if AJAX fails, links degrade to full page reload.
- Accessibility: focus management & scroll restoration hooks are present in the switcher file.

---

## 🧾 License

**MIT** — use freely. Please keep attribution if you publish a fork.

---

## 🙌 Credits

Created for a fast, private, and elegant resume/portfolio workflow.  
Includes demo data for **John Doe** so you can publish safely without personal details.
