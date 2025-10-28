// /assets/theme.js
(function () {
  const KEY = "cv.theme"; // 'light' | 'dark'
  const html = document.documentElement;
  const btn = document.getElementById("themeToggle");

  function setIcon(theme) {
    if (!btn) return;
    btn.innerHTML = theme === "dark"
      ? '<i class="fa-solid fa-sun"></i>'
      : '<i class="fa-solid fa-moon"></i>';
    btn.setAttribute("aria-pressed", theme === "dark" ? "true" : "false");
    btn.title = theme === "dark" ? "Switch to light mode" : "Switch to dark mode";
  }

  function apply(theme) {
    html.setAttribute("data-theme", theme);
    try { localStorage.setItem(KEY, theme); } catch {}
    setIcon(theme);
  }

  function systemPrefersDark() {
    return window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
  }

  // init
  let saved = null;
  try { saved = localStorage.getItem(KEY); } catch {}
  const initial = saved === "light" || saved === "dark"
    ? saved
    : (systemPrefersDark() ? "dark" : "light");
  apply(initial);

  // React to system theme changes unless the user explicitly chose a theme.
  // If a manual preference is saved — keep it.
  if (!saved) {
    const mq = window.matchMedia("(prefers-color-scheme: dark)");
    mq.addEventListener?.("change", () => apply(mq.matches ? "dark" : "light"));
  }

  // click handler
  if (btn) {
    btn.addEventListener("click", () => {
      const next = (html.getAttribute("data-theme") === "dark") ? "light" : "dark";
      apply(next);
    });
  }

  // export if you need to pull manually
  window.__cvTheme = { apply };
})();
