// api.js
const API_URL = window.__RESUME_STUDIO_API__ || '/studio/api.php';

RS.api = async (action, params={}, method='GET') => {
  const url = new URL(API_URL, location.origin);
  url.searchParams.set('action', action);
  url.searchParams.set('user', RS.state.currentUser);
  const opts = { method };
  if(method==='POST'){
    const fd = new FormData(); for(const [k,v] of Object.entries(params)) fd.append(k,v);
    opts.body = fd;
  } else { for(const [k,v] of Object.entries(params)) url.searchParams.set(k,v); }
  const res = await fetch(url.toString(), opts);
  const data = await res.json();
  if(!data.ok) throw new Error(data.error||'API error');
  return data;
};

RS.pretty = () => {
  try { const obj=JSON.parse(RS.state.els.json.value);
    RS.state.els.json.value = JSON.stringify(obj, null, 2);
    RS.state.els.validation.innerHTML = `<span class="badge ok">Valid & prettified</span>`;
  } catch(e){ RS.state.els.validation.innerHTML = `<span class="badge err">${e.message}</span>`; }
};
RS.validate = () => {
  try { JSON.parse(RS.state.els.json.value);
    RS.state.els.validation.innerHTML = `<span class="badge ok">Valid JSON</span>`;
  } catch(e){ RS.state.els.validation.innerHTML = `<span class="badge err">${e.message}</span>`; }
};
RS.save = async () => {
  if(!RS.state.currentFile) return alert('Open or create a file first');
  try { JSON.parse(RS.state.els.json.value); } catch(e){ return alert('Fix JSON: '+e.message); }
  const r=await RS.api('save',{file:RS.state.currentFile,content:RS.state.els.json.value},'POST');
  RS.state.lastSaved = RS.state.els.json.value; RS.setUnsaved(false);
  RS.setStatus('Saved '+RS.state.currentFile+' (backup created)');
  RS.state.els.backupPicker.innerHTML = '<option value="">— Backups —</option>' + (r.backups||[]).map(b=>`<option value="${b}">${b}</option>`).join('');
  await RS.refreshList();
};
RS.restoreBackup = async () => {
  const b=RS.state.els.backupPicker.value; if(!b) return alert('Choose a backup first');
  const r=await RS.api('restore',{file:RS.state.currentFile,backup:b},'POST');
  RS.state.els.json.value=r.content; RS.state.lastSaved=r.content; RS.setUnsaved(false);
  RS.renderForm(); RS.renderCollections(); RS.setStatus('Restored '+b);
};
