// core.js
window.RS = window.RS || {};
const el = id => document.getElementById(id);

RS.state = {
  currentUser: '', currentFile: '', lastSaved: '',
  els: {
    user: el('user'), files: el('files'), json: el('json'),
    validation: el('validation'), statusPill: el('status-pill'),
    unsaved: el('unsaved'), backupPicker: el('backupPicker'),
    tabs: el('tabs')
  }
};

RS.setUnsaved = flag => RS.state.els.unsaved.style.display = flag ? 'flex' : 'none';
RS.setStatus  = text => RS.state.els.statusPill.textContent = text;

RS.getObj = () => { try { return JSON.parse(RS.state.els.json.value||'{}'); } catch { return {}; } };
RS.setObj = o => { RS.state.els.json.value = JSON.stringify(o, null, 2); RS.setUnsaved(true); };

RS.readPath = (path) => { let o=RS.getObj(); for(const k of path){ if(o==null) return; o=o[k]; } return o; };
RS.writePath = (path, value) => {
  const o=RS.getObj(); let cur=o;
  for(let i=0;i<path.length-1;i++){ const k=path[i]; if(cur[k]==null) cur[k]=(typeof path[i+1]==='number'?[]:{}); cur=cur[k]; }
  cur[path[path.length-1]] = value; RS.setObj(o);
};
RS.removePath = (path) => {
  const o=RS.getObj(); let cur=o; for(let i=0;i<path.length-1;i++) cur=cur[path[i]];
  const last=path[path.length-1]; if(Array.isArray(cur)) cur.splice(last,1); else delete cur[last];
  RS.setObj(o);
};
RS.movePath = (path, d) => {
  const o=RS.getObj(); let cur=o; for(let i=0;i<path.length-1;i++) cur=cur[path[i]];
  const i=path[path.length-1], j=i+d; if(!Array.isArray(cur) || j<0 || j>=cur.length) return;
  [cur[i],cur[j]]=[cur[j],cur[i]]; RS.setObj(o);
};
RS.ensureArrays = () => {
  const o=RS.getObj();
  o.experience = Array.isArray(o.experience)?o.experience:[];
  o.education  = Array.isArray(o.education)?o.education:[];
  o.projects   = Array.isArray(o.projects)?o.projects:[];
  o.awards     = Array.isArray(o.awards)?o.awards:[];
  o.skills = o.skills || {};
  if(!Array.isArray(o.skills.list)) o.skills.list=[];
  if(!Array.isArray(o.skills.full)) o.skills.full=[];
  RS.setObj(o);
};
