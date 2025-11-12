# MyCV — Dynamic Resume & Portfolio

A lightweight, self-hosted resume/portfolio app built with **PHP + JSON + AJAX**.  
It’s fast, privacy-friendly, and includes a secure built-in **Analytics Dashboard** (SQLite + Chart.js) and with integrated editing studio.

Now supports **multiple users** — each with their own resume set and analytics stats.

Demo data (no personal info): **John Doe** for both tracks.  
🔗 Demo: [https://cv.hgl.mx/demo](https://cv.hgl.mx/demo)

---

## ✨ Highlights

- ⚙️ **PHP + JSON** templating — no frameworks or dependencies  
- ⚡ **AJAX navigation** with History API and fade transitions  
- 🌓 **Light/Dark theme** toggle (auto-saved; print always light)  
- 🧩 **Smart caching** — auto-rebuild on JSON/template change  
- 📊 **Built-in Analytics** — local SQLite logger with charts  
- 🧠 **Multi-user support** — each user has their own resumes and stats  
- 🔐 **Secure web login + setup wizard** for analytics  
- 🧱 **No third-party trackers** or external databases  
- 📦 **Demo fallback**: loads default or demo JSONs if real data absent  
- 🎨 **Resume Studio** - New `/studio/` section with modular JS structure and basic formatting tools  

---

## 🗂 Project Structure

```
/
├─ index.php # Router and cache logic
├── lib/
│ ├── render.php # Template + cache rendering
│ ├── router.php → centralized routing
│ ├── functions.php→ helper utilities
│ └── init.php → global includes
├─ analytics/ # Built-in dashboard
│ ├─ setup.php # First-time setup (login+password)
│ ├─ login.php, logout.php, auth.php
│ ├─ index.php # Chart.js dashboard (now with user filter)
│ ├─ track.php # Beacon collector (logs per-user hits)
│ ├─ bootstrap.php # SQLite migrations + helpers
│ ├─ sql/
│ │ ├─ 001_base_schema.sql # Base schema (visits, rate, indexes)
│ │ └─ (future migrations…) # Additional .sql files auto-applied
│ ├─ config.php # Credentials (auto-generated)
│ └─ cleanup.php # Optional rotation/VACUUM
├─ data/
│ ├─ default/ # Default (public) user resumes
│ ├─ user1/ # Example user 1
│ ├─ user2/ # Example user 2
│ └─ demo/ # Demo fallback
├── studio/ → resume editor (SPA)
│ ├── assets/ → CSS and JS modules
│ ├── api.php → api for studio module
│ └── index.php → main script
├─ templates/
│ ├─ layout.html
│ ├─ chooser.html
│ ├─ main.template.html
│ └─ topbar.html
├─ assets/
│ ├─ main.ssr.css
│ ├─ switcher.ajax.js
│ ├─ analytics.js
│ └─ theme.js
├─ cache/ # Auto-generated inner HTML
├─ .htaccess # Routing + CSP headers
└─ CHANGELOG.md
```


---

## 🧠 Multi-User Routing

### URL Patterns
| Path | Action |
|------|-----------|
| `/analytics/` | Loads analytics module |
| `/studio/` or `/studio/api` | Loads Resume Studio or API handler |
| `/` | Default user chooser page (if multiple resumes) |
| `/resume` | Loads resume from `data/default/resume.json` |
| `/user_name` | If user has one resume, opens it directly |
| `/user_name/resume` | Loads `data/user_name/resume.json` |

### Auto-Detection
Each visit is automatically tagged with its **user** based on the URL path.  
This value is stored in the analytics database (`visits.user`).

---

## 📊 Analytics Overview

### Client (Browser)
**File:** `assets/analytics.js`  
Sends: URL, referrer, UTM, language, timezone, DPR, viewport, theme, perf metrics.  
Respects: Do-Not-Track, localhost, and `an_ignore` cookie.

### Server (Collector)
**File:** `analytics/track.php`  
Now supports **multi-user tracking**:
- Automatically detects `user` from request path.
- Writes hits to `analytics/analytics.db` with `user` column.
- Rate-limited (1 hit / 300 ms per IP).
- Rejects cross-origin and `/analytics/*` requests.

### Dashboard
**File:** `analytics/index.php`
- Filter by **days**, **path**, **country**, or **user**  
- Tables: Top Users, Top Paths, Referrers, Countries, Recent Hits  
- Charts: Visits by day, referrers, countries  
- Auto-excludes admin (via `an_ignore=1` cookie)

---

## 🧩 Database & Migrations

The app now uses **versioned SQL migrations** under `/analytics/sql/`.

- **`001_base_schema.sql`** — base structure for `visits`, `rate`, and indexes  
- New migrations can be added as `002_*.sql`, `003_*.sql`, etc.  
- Each migration runs **once** and is tracked in `schema_migrations`.  
- Existing databases are automatically upgraded (adds missing `user` column).  

No manual SQL needed — migrations apply on first access.

---

## 🧾 CHANGELOG

See [CHANGELOG.md](./CHANGELOG.md) for the full version history.  
Latest release: **v1.2.0 — Multi-User Resume Analytics**

---

## 🔧 Requirements

- PHP 8.1+ with SQLite3  
- Apache or Nginx with rewrites  
- HTTPS recommended (for secure cookies)

---

## 🚀 Quick Start

```bash
git clone https://github.com/hegelmax/cv-page.git
cd cv-page
```

Then open `/analytics/` in browser — the setup wizard will guide you.  
If `/analytics/config.php` exists, just sign in.

---

## 🧩 JSON Format Example

```json
{
  "version": "1.0.8",
  "name": "John Doe",
  "title": "Software Engineer",
  "summary": "Brief overview",
  "contact": { "email": "...", "linkedin": "...", "location": "..." },
  "experience": [{ "title": "Engineer", "company": "TechCorp", "period": "2022–Present" }],
  "education": [{ "degree": "B.Sc. Computer Science", "institution": "MIT" }],
  "skills": { "list": ["PHP", "JavaScript", "SQL"] },
  "languages": [{ "name": "English", "level": "Native" }]
}
```

Include a `"version"` key to trigger automatic rebuilds when changed.

---

## ⚙️ Security & Performance

- **Headers:** CSP, Referrer-Policy, Permissions-Policy, no sniff  
- **Sessions:** strict mode, HttpOnly, SameSite=Lax, Secure (HTTPS)  
- **Rate limiting** for analytics login and tracking  
- **Cache invalidation** based on JSON/template mtime or version  
- **Automatic DB migrations** (no manual SQL)  
- **Print optimization**: Light theme, clean layout  

---

## 🧾 License

MIT — you can use and modify freely (keep attribution).

---

## 🧑‍💻 Author

Created by **Maxim Hegel** — built for speed, privacy, and elegant simplicity.
