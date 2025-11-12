// main.js
(() =>{
  // Tabs
  RS.tabs.init();

  // Buttons
  el('loadUser').onclick = async ()=>{ RS.state.currentUser = RS.state.els.user.value.trim(); if(!RS.state.currentUser) return alert('Enter user folder'); await RS.refreshList(); };
  el('newEmpty').onclick = RS.newEmpty;
  el('duplicate').onclick = RS.duplicate;
  el('rename').onclick = RS.renameFile;
  el('delete').onclick = RS.delFile;
  el('download').onclick = RS.download;
  el('importFile').addEventListener('change', RS.importJSON);

  el('pretty').onclick = RS.pretty;
  el('validate').onclick = RS.validate;
  el('save').onclick = RS.save;
  el('restoreBackup').onclick = RS.restoreBackup;

  el('applyForm').onclick = RS.applyForm;
  el('pullForm').onclick  = RS.pullForm;

  document.querySelectorAll('[data-add]').forEach(btn=> btn.onclick = ()=> RS.addWizard(btn.dataset.add));

  // Deep-link
  const qs = new URLSearchParams(location.search);
  const u=qs.get('user'), f=qs.get('file'), tab=qs.get('tab');
  if(u){ RS.state.els.user.value=u; RS.state.currentUser=u; RS.refreshList().then(()=>{ if(f) RS.openFile(f); }); }
  if(tab && RS.tabs.names.includes(tab)) RS.tabs.show(tab);
})();
