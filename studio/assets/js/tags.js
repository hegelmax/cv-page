// tags.js
RS.mountTagInput = (container, values, onChange, placeholder='Type and press Enter')=>{
  container.className = 'tags'; container.innerHTML = '';
  const input = document.createElement('input'); input.placeholder = placeholder;

  function render(){
    container.querySelectorAll('.tag').forEach(t=>t.remove());
    values.forEach((v,i)=>{
      const tag=document.createElement('span'); tag.className='tag';
      tag.innerHTML = `${v}<span class="x" title="Remove">×</span>`;
      tag.querySelector('.x').onclick = ()=>{ values.splice(i,1); onChange(values.slice()); render(); };
      container.insertBefore(tag, input);
    });
  }
  function commit(){
    const raw=input.value.trim(); if(!raw) return;
    raw.split(',').map(s=>s.trim()).filter(Boolean).forEach(v=>{ if(!values.includes(v)) values.push(v); });
    input.value=''; onChange(values.slice()); render();
  }
  input.addEventListener('keydown', e=>{
    if(e.key==='Enter'||e.key===','){ e.preventDefault(); commit(); }
    if(e.key==='Backspace' && !input.value && values.length){ values.pop(); onChange(values.slice()); render(); }
  });
  input.addEventListener('blur', commit);

  container.appendChild(input); render();
  return { get:()=>values.slice(), set:(arr)=>{ values = Array.from(new Set(arr||[])); onChange(values.slice()); render(); } };
};
