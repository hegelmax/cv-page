// /assets/analytics.js
(function () {
  if (document.cookie.includes('an_ignore=1')) return;
  if (document.visibilityState === 'hidden') return;
  
  // respect private browsing (DNT) and local dev
  if (navigator.doNotTrack === "1" || window.location.hostname === "localhost") return;

  // collect privacy-friendly client data
  const nav = window.navigation || performance.getEntriesByType?.("navigation")?.[0];
  const data = {
    ts: Date.now(),
    url: location.href,
    path: location.pathname,
    ref: document.referrer || null,
    utm: Object.fromEntries([...new URLSearchParams(location.search)].filter(([k]) => /^utm_/i.test(k))),
    lang: navigator.language || null,
    languages: navigator.languages || [],
    tz: Intl.DateTimeFormat().resolvedOptions().timeZone || null,
    dpr: window.devicePixelRatio || 1,
    vp: { w: window.innerWidth, h: window.innerHeight },
    scr: { w: screen.width, h: screen.height },
    theme: document.documentElement.getAttribute("data-theme") || "light",
    navType: nav?.type || null,
    // basic perf
    perf: (function(){
      if (!nav || !nav.responseStart) return null;
      return {
        ttfb: Math.max(0, nav.responseStart - nav.requestStart),
        domInteractive: nav.domInteractive || null,
        domContentLoaded: nav.domContentLoadedEventEnd || null,
        load: nav.loadEventEnd || null
      };
    })(),
  };

  // send beacon helper
  function send(payload) {
    try {
      navigator.sendBeacon?.("/analytics/track.php", new Blob([JSON.stringify(payload)], { type: "application/json" }))
        || fetch("/analytics/track.php", {
          method: "POST", credentials: "include",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });
    } catch (e) { /* ignore */ }
  }

  // initial pageview
  send({type:"visit", ...data});

  // SPA: track virtual pageviews on pushState/replaceState/popstate
  (function(){
    const _ps = history.pushState, _rs = history.replaceState;
    function fire() { send({type:"virtual", ...data, ts: Date.now(), url: location.href, path: location.pathname, ref: null}); }
    history.pushState = function(){ _ps.apply(this, arguments); setTimeout(fire, 0); };
    history.replaceState = function(){ _rs.apply(this, arguments); setTimeout(fire, 0); };
    window.addEventListener("popstate", fire);
  })();
})();
