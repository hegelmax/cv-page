# MyCV — Dynamic Resume & Portfolio

A lightweight, self-hosted resume/portfolio app built with **PHP + JSON + AJAX**.  
It’s fast, privacy-friendly, and includes a secure built-in **Analytics Dashboard** (SQLite + Chart.js).

Demo data (no personal info): **John Doe** for both tracks.  
🔗 Demo: [https://cv.hgl.mx](https://cv.hgl.mx)

---

## ✨ Highlights

- ⚙️ **PHP + JSON** templating — no frameworks or dependencies  
- ⚡ **AJAX navigation** with History API and fade transitions  
- 🌓 **Light/Dark theme** toggle (auto-saved; print always light)  
- 🧩 **Smart caching** — auto-rebuild on JSON/template change  
- 📊 **Built-in Analytics** — local SQLite logger with charts  
- 🔐 **Secure web login + setup wizard** for analytics  
- 🧱 **No third-party trackers** or external databases  
- 📦 **Demo fallback**: loads John Doe JSONs if real data absent

---

## 🗂 Project Structure

```
/
├─ index.php              # Router and cache logic
├─ init.php               # Ensures /cache directory exists
├─ lib/render.php         # Template + cache rendering
├─ analytics/             # Built-in dashboard
│   ├─ setup.php          # First-time setup (login+password)
│   ├─ login.php, logout.php, auth.php
│   ├─ index.php          # Chart.js dashboard
│   ├─ track.php          # Beacon collector (rate-limited)
│   ├─ bootstrap.php      # SQLite schema + helpers
│   ├─ config.php         # Credentials (auto-generated)
│   └─ cleanup.php        # Optional rotation/VACUUM
├─ data/
│   ├─ user_prog.json
│   ├─ user_analyst.json
│   └─ demo/
│       ├─ john_doe_prog.json
│       └─ john_doe_analyst.json
├─ templates/
│   ├─ layout.html
│   ├─ chooser.html
│   ├─ main.template.html
│   └─ topbar.html
├─ assets/
│   ├─ main.ssr.css
│   ├─ switcher.ajax.js
│   ├─ analytics.js
│   └─ theme.js
├─ cache/                 # Auto-generated inner HTML
├─ .htaccess              # Routing + CSP headers
└─ CHANGELOG.md
```

---

## 🧠 How It Works

### Resume Tracks
- `/developer` → loads `data/user_prog.json` (fallback to demo)  
- `/analyst` → loads `data/user_analyst.json` (fallback to demo)  
- `/` → track chooser page  

Server rebuilds cached HTML (`cache/*.inner.html`) when JSON or template changes, or when version field differs.

### Partial Rendering
`switcher.ajax.js` intercepts links, fetches partial HTML with  
`X-Requested-With: fetch-partial`, animates fade, updates History.

### Analytics Setup Flow
1. Go to `/analytics/` → if config missing → redirects to setup wizard  
2. Enter login + password → config.php auto-generated  
3. After setup, user redirected back to analytics dashboard  
4. Subsequent logins handled via `/analytics/login.php`  
5. Logged-in users are excluded from stats via cookie `an_ignore=1`

---

## 🔧 Requirements

- PHP 8.1 or higher with SQLite3  
- Apache (with `.htaccess`) or Nginx rewrite  
- HTTPS recommended (secure cookies)

---

## 🚀 Quick Start

```bash
git clone https://github.com/hegelmax/cv-page.git
cd cv-page
```

Then open `/analytics/` in browser — the setup wizard will guide you.  
If `/analytics/config.php` exists, just sign in.

Default public pages:
- `/` → track chooser  
- `/developer` → Developer resume (John Doe demo)  
- `/analyst` → Analyst resume (John Doe demo)  
- `/analytics/` → Dashboard (after login)

---

## 📊 Analytics Details

**Client:** `assets/analytics.js`
- Sends minimal info: URL, referrer, UTM, language, timezone, DPR, viewport, theme, basic perf.  
- Respects Do-Not-Track, localhost, and `an_ignore` cookie.  

**Server:** `analytics/track.php`
- Inserts records into SQLite DB (`analytics/analytics.db`)  
- Limits rate (≤ 1 hit/300 ms per IP)  
- Rejects cross-origin requests  
- Excludes `/analytics/*` from tracking  

**Dashboard:** `analytics/index.php`
- Filter by days, path, or country  
- Charts for visits, referrers, countries, and recent hits  
- Automatic redirection after first-time setup  

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
- **Print optimization**: Light theme, clean layout  

---

## 🧾 License

MIT — you can use and modify freely (keep attribution).

---

## 🧑‍💻 Author

Created by **Maxim Hegel** — built for speed, privacy, and elegant simplicity.
