(function () {
  console.log("AJAX switcher loaded");

  const root = document.getElementById("app");
  if (!root) {
    console.warn("#app not found");
    return;
  }

  const cache = new Map();
  let isAnimating = false;
  const REDUCED = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const FADE_MS = REDUCED ? 0 : 180; // animation duration (ms)

  // ---------------------------
  // Helpers
  // ---------------------------
  function keyFromURL(url) {
    try {
      const u = new URL(url, window.location.origin);
      // ✅ Only handle same-origin links; external URLs should NOT be intercepted
      if (u.origin !== window.location.origin) return null;
      const p = (u.pathname || "/").replace(/\/+$/, "").toLowerCase() || "/";
      if (p === "/") return "chooser";
      if (p === "/developer") return "developer";
      if (p === "/analyst") return "analyst";
      return null;
    } catch { return null; }
  }

  function currentKey() {
    return keyFromURL(window.location.href) || "chooser";
  }

  function fetchPartialFor(key) {
    const url = new URL(window.location.origin);
    url.pathname = key === "chooser" ? "/" : `/${key}`;
    return fetch(url.toString(), {
      headers: { "X-Requested-With": "fetch-partial" },
      cache: "no-store",
      credentials: "same-origin",
    }).then((r) => {
      if (!r.ok) throw new Error("Failed to load partial");
      return r.text();
    });
  }

  function wireCopyButton() {
    const btn = document.getElementById("copyLinkBtn");
    if (!btn) return;
    btn.addEventListener("click", () => {
      navigator.clipboard.writeText(window.location.href);
      const prev = btn.innerHTML;
      btn.innerHTML = "✅ Copied!";
      setTimeout(() => (btn.innerHTML = prev), 1200);
    });
  }

  // Fade-out → swap → fade-in
  function animateSwap(html) {
    return new Promise((resolve) => {
      if (FADE_MS === 0) {
        root.innerHTML = html;
        wireCopyButton();
        resolve();
        return;
      }

      // 1) Fade out
      root.style.willChange = "opacity";
      root.style.transition = `opacity ${FADE_MS}ms ease`;
      // гарантируем начальное состояние
      root.style.opacity = "1";
      // принудительный reflow
      void root.offsetHeight;
      // затемнение
      root.style.opacity = "0";

      const onFadeOut = () => {
        root.removeEventListener("transitionend", onFadeOut);
        // 2) Swap HTML
        root.innerHTML = html;
        wireCopyButton();
        // 3) Fade in
        // сбросим transition на новый reflow
        root.style.transition = `opacity ${FADE_MS}ms ease`;
        // reflow
        void root.offsetHeight;
        root.style.opacity = "1";

        const onFadeIn = () => {
          root.removeEventListener("transitionend", onFadeIn);
          // cleanup
          root.style.transition = "";
          root.style.willChange = "";
          resolve();
        };
        root.addEventListener("transitionend", onFadeIn, { once: true });
      };

      root.addEventListener("transitionend", onFadeOut, { once: true });
    });
  }

  function swapTo(key, html, { push = true } = {}) {
    if (isAnimating) return;
    isAnimating = true;

    animateSwap(html).then(() => {
      const url = new URL(window.location.origin);
      url.pathname = key === "chooser" ? "/" : `/${key}`;
      if (push) history.pushState({ track: key }, "", url.toString());
      else history.replaceState({ track: key }, "", url.toString());
      isAnimating = false;
    });
  }

  function ensureAndSwap(key, opts) {
    if (cache.has(key)) {
      swapTo(key, cache.get(key), opts);
      return;
    }
    // быстро показать загрузку (без мигания, если мгновенный кэш)
    if (!isAnimating) root.innerHTML = `<div class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</div>`;
    fetchPartialFor(key)
      .then((html) => {
        cache.set(key, html);
        swapTo(key, html, opts);
      })
      .catch((err) => {
        console.error(err);
        // fallback — обычная навигация
        window.location.href = key === "chooser" ? "/" : `/${key}`;
      });
  }

  // ---------------------------
  // Link interception
  // ---------------------------
  document.addEventListener("click", (e) => {
    const a = e.target.closest('a[href]');
    if (!a) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
  
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
  
    const key = keyFromURL(a.href);
    if (!key) return; // external or unknown path → let browser handle it
  
    e.preventDefault();
    ensureAndSwap(key, { push: true });
  }, true);

  // ---------------------------
  // Init + Back/Forward
  // ---------------------------
  const initKey = currentKey();
  cache.set(initKey, root.innerHTML);
  history.replaceState({ track: initKey }, "", window.location.href);
  wireCopyButton();

  window.addEventListener("popstate", (e) => {
    const st = e.state;
    const key = st ? st.track : currentKey();
    ensureAndSwap(key, { push: false });
  });
  
  const other = initKey === 'developer' ? 'analyst' : 'developer';
  if (other !== 'chooser' && !cache.has(other)) {
    fetchPartialFor(other).then(html => cache.set(other, html)).catch(()=>{});
  }
})();
