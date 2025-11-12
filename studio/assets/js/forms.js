// forms.js
RS.renderForm = ()=>{
  const o=RS.getObj();
  el('f_name').value=o.name||'';
  el('f_title').value=o.title||'';
  RS.rte.setHTML(o.summary || '');   // <-- заполняем WYSIWYG
  el('f_email').value=o.contact?.email||'';
  el('f_phone').value=o.contact?.phone||'';
  el('f_location').value=o.contact?.location||'';
};

RS.applyForm = ()=>{
  const o=RS.getObj();
  o.name=el('f_name').value;
  o.title=el('f_title').value;
  o.summary = RS.rte.getHTML();      // <-- забираем HTML из редактора
  o.contact=o.contact||{};
  o.contact.email=el('f_email').value;
  o.contact.phone=el('f_phone').value;
  o.contact.location=el('f_location').value;
  RS.setObj(o);
  RS.renderCollections();
};

RS.pullForm = ()=> RS.renderForm();

// инициализация редактора при загрузке модуля
document.addEventListener('DOMContentLoaded', ()=> RS.rte.init());
