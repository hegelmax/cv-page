// richtext.js — минимальный WYSIWYG для Summary
window.RS = window.RS || {};
RS.rte = {
  areaId: 'f_summary_rte',
  toolbarId: 'rteSummaryToolbar',

  init(){
    const tb = document.getElementById(this.toolbarId);
    const area = document.getElementById(this.areaId);
    if(!tb || !area) return;

    // Клики по кнопкам
    tb.addEventListener('click', (e)=>{
      const btn = e.target.closest('button[data-cmd]');
      if(!btn) return;
      const cmd = btn.dataset.cmd;

      area.focus();

      if(cmd === 'createLink'){
        const sel = window.getSelection();
        if(!sel || sel.toString().trim()===''){
          alert('Выделите текст для ссылки'); return;
        }
        let url = prompt('URL (https://...)', 'https://');
        if(!url) return;
        if(!/^https?:\/\//i.test(url)) url = 'https://' + url;
        document.execCommand('createLink', false, url);
        return;
      }

      if(cmd === 'removeFormat'){
        // убираем формат и ссылки
        document.execCommand('removeFormat');
        document.execCommand('unlink');
        return;
      }

      // стандартные команды
      document.execCommand(cmd, false, null);
    });
  },

  getHTML(){
    const html = document.getElementById(this.areaId).innerHTML || '';
    return RS.rte.sanitize(html);
  },

  setHTML(html){
    document.getElementById(this.areaId).innerHTML = html || '';
  },

  // Простая очистка: оставляем только безопасные теги/атрибуты
  sanitize(html){
    const allowTags = new Set(['B','STRONG','I','EM','U','A','UL','OL','LI','P','BR']);
    const tmp = document.createElement('div'); tmp.innerHTML = html;

    (function walk(node){
      const kids = Array.from(node.childNodes);
      for(const n of kids){
        if(n.nodeType === 1){ // ELEMENT_NODE
          if(!allowTags.has(n.tagName)){
            // разворачиваем содержимое неизвестного тега
            while(n.firstChild) node.insertBefore(n.firstChild, n);
            node.removeChild(n);
            continue;
          }
          // у ссылок оставляем только href (https)
          if(n.tagName === 'A'){
            const href = n.getAttribute('href') || '';
            if(!/^https?:\/\//i.test(href)){ n.removeAttribute('href'); }
            n.removeAttribute('style'); n.removeAttribute('onclick'); n.removeAttribute('target');
          } else {
            // режем инлайновые стили/скрипты
            n.removeAttribute('style'); n.removeAttribute('onclick'); n.removeAttribute('class');
          }
          walk(n);
        } else if(n.nodeType === 8){ // комментарии
          node.removeChild(n);
        }
      }
    })(tmp);

    return tmp.innerHTML
      // нормализуем <div> в параграфы, если просочились
      .replace(/<div>/gi,'<p>').replace(/<\/div>/gi,'</p>');
  }
};
