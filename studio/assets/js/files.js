// files.js
RS.refreshList = async ()=>{
  const { files } = await RS.api('list');
  const wrap = RS.state.els.files; wrap.innerHTML='';
  files.forEach(f=>{
    const div=document.createElement('div');
    div.className='file'+(f===RS.state.currentFile?' active':'');
    div.innerHTML=`<span>${f}</span><span class="muted">›</span>`;
    div.onclick=()=>RS.openFile(f);
    wrap.appendChild(div);
  });
};

RS.openFile = async (f)=>{
  const data = await RS.api('load', { file: f });
  RS.state.currentFile = f;
  RS.state.els.json.value = data.content;
  RS.state.lastSaved = data.content;
  RS.setUnsaved(false); RS.setStatus('Opened '+f);
  RS.renderForm(); RS.renderCollections();
  RS.state.els.backupPicker.innerHTML = '<option value="">— Backups —</option>' + (data.backups||[]).map(b=>`<option value="${b}">${b}</option>`).join('');
  RS.refreshList();
};

RS.newEmpty = async ()=>{
  const name=prompt('New file name (e.g., resume3.json):','resume'+Math.floor(Math.random()*100)+'.json');
  if(!name) return; await RS.api('new',{file:name,template:'empty'},'POST');
  await RS.refreshList(); await RS.openFile(name);
};
RS.duplicate = async ()=>{
  if(!RS.state.currentFile) return alert('Open a source file first');
  const name=prompt('Duplicate as:', RS.state.currentFile.replace('.json','_copy.json'));
  if(!name) return; await RS.api('copy',{from:RS.state.currentFile,to:name},'POST'); await RS.refreshList();
};
RS.renameFile = async ()=>{
  if(!RS.state.currentFile) return alert('Open a file first');
  const name=prompt('Rename to:', RS.state.currentFile);
  if(!name||name===RS.state.currentFile) return;
  await RS.api('rename',{from:RS.state.currentFile,to:name},'POST');
  RS.state.currentFile=name; await RS.refreshList();
};
RS.delFile = async ()=>{
  if(!RS.state.currentFile) return alert('Open a file first');
  if(!confirm('Delete '+RS.state.currentFile+'?')) return;
  await RS.api('delete',{file:RS.state.currentFile},'POST');
  RS.state.currentFile=''; RS.state.els.json.value=''; await RS.refreshList();
};

RS.download = ()=>{
  const blob=new Blob([RS.state.els.json.value||'{}'],{type:'application/json'});
  const a=document.createElement('a'); a.href=URL.createObjectURL(blob);
  a.download=RS.state.currentFile||'resume.json'; a.click();
};
RS.importJSON = (ev)=>{
  const f=ev.target.files[0]; if(!f) return;
  const r=new FileReader(); r.onload=()=>{ RS.state.els.json.value=r.result; RS.setUnsaved(true); RS.renderForm(); RS.renderCollections(); };
  r.readAsText(f);
};
// mark unsaved
RS.state.els.json?.addEventListener('input', ()=> RS.setUnsaved(RS.state.els.json.value !== RS.state.lastSaved));
