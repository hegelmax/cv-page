/* studio.js — loader for /studio/assets/js modules */

(function(){
  const VERSION = ''; // optional cache-bust string
  // теперь модули лежат в /studio/assets/js/
  const FILES = [
    'core.js',
    'api.js',
    'files.js',
    'tabs.js',
    'tags.js',
    'richtext.js',
    'forms.js',
    'collections.js',
    'modal.js',
    'main.js'
  ];

  const now = ()=>new Date().toISOString();

  function errorOverlay(msg){
    const el=document.createElement('div');
    el.style.cssText=`position:fixed;inset:0;background:rgba(0,0,0,.75);color:#fff;z-index:99999;
      display:flex;align-items:center;justify-content:center;padding:24px;font-family:system-ui,sans-serif;`;
    el.innerHTML=`<div style="max-width:800px;background:#1f2937;border:1px solid #374151;border-radius:12px;padding:16px;">
      <h3 style="margin:0 0 8px 0;">Resume Studio loader error</h3>
      <pre style="white-space:pre-wrap;font:13px/1.4 ui-monospace,Menlo,Consolas,monospace;">${msg}</pre></div>`;
    document.body.appendChild(el);
  }

  function getBasePath(){
    // определяем путь к /studio/assets/
    const self = document.querySelector('script[data-rs-loader]') ||
      Array.from(document.scripts).find(s => (s.src||'').includes('/studio/assets/studio.js'));
    if(!self) return '/studio/assets/';
    const src = new URL(self.getAttribute('src') || '/studio/assets/studio.js', location.origin);
    const path = src.pathname.slice(0, src.pathname.lastIndexOf('/') + 1);
    return path; // заканчивается на /studio/assets/
  }

  function withCacheBust(path){
    if(!VERSION) return path;
    const u = new URL(path, location.origin);
    u.searchParams.set('v', VERSION);
    return u.pathname + u.search;
  }

  function loadScriptSequential(urls){
    const loaded = new Set(Array.from(document.scripts).map(s=>s.src));
    return urls.reduce((p,url)=>p.then(()=>new Promise((res,rej)=>{
      if(loaded.has(new URL(url,location.origin).href)) return res();
      const s=document.createElement('script');
      s.src=url; s.async=false;
      s.onload=res;
      s.onerror=()=>rej(new Error('Failed to load '+url));
      document.head.appendChild(s);
    })),Promise.resolve());
  }

  function start(){
    const base = getBasePath() + 'js/'; // теперь добавляем поддиректорию js
    const urls = FILES.map(f=>withCacheBust(base+f));
    loadScriptSequential(urls).catch(err=>errorOverlay('Load error @ '+now()+'\n\n'+err));
  }

  if(document.readyState==='loading')
    document.addEventListener('DOMContentLoaded', start);
  else start();
})();
