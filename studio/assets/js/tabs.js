// tabs.js
RS.tabs = {
  names: ['Basics','JSON','Collections'],
  active: 'Basics',
  show(name){
    RS.tabs.active = name;
    Array.from(RS.state.els.tabs.querySelectorAll('.tab')).forEach(btn=>{
      btn.classList.toggle('active', btn.dataset.tab===name);
    });
    document.querySelectorAll('.tabview').forEach(v=>{
      v.classList.toggle('hidden', v.getAttribute('data-tab')!==name);
    });
  },
  init(){
    RS.state.els.tabs.innerHTML='';
    RS.tabs.names.forEach((t,i)=>{
      const b=document.createElement('div');
      b.className='tab'+(i===0?' active':''); b.textContent=t; b.dataset.tab=t;
      b.onclick=()=>RS.tabs.show(t); RS.state.els.tabs.appendChild(b);
    });
    RS.tabs.show(RS.tabs.active);
  }
};
