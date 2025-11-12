// modal.js
const modalBack = el('modalBack'), modalBody = el('modalBody'),
      modalTitle = el('modalTitle'), modalSave = el('modalSave'),
      modalCancel = el('modalCancel'); el('closeModal').onclick=close; modalCancel.onclick=close;

function close(){ modalBack.style.display='none'; RS.modalCtx=null; }
RS.openEditor = (kind, index)=>{
  RS.modalCtx = {kind, index};
  modalTitle.textContent = `Edit ${kind[0].toUpperCase()+kind.slice(1)}`;
  const o=RS.getObj(); let data;
  if(kind==='skills'){ data = Array.isArray(o.skills?.list)? o.skills.list[index] : ''; }
  else if(kind==='skillgroup'){ data = Array.isArray(o.skills?.full)? (o.skills.full[index]||{name:'',color:'',skills:[]}) : {name:'',color:'',skills:[]}; }
  else if(kind==='exp_project'){ const [i,j]=String(index).split(':').map(n=>parseInt(n,10)); data = (((o.experience||[])[i]||{}).projects||[])[j] || {name:'',description:'',technologies:[]}; }
  else { data = RS.readPath([kind,index]) || {}; }

  modalBody.innerHTML = RS.renderFormHTML(kind, data);

  // Подключаем тег-инпуты в нужных формах
  if(kind==='skillgroup'){
    const holder = modalBody.querySelector('#skillgroup_tags');
    RS.mountTagInput(holder, Array.isArray(data.skills)? data.skills.slice(): [], ()=>{}, 'Add skill and Enter');
  }
  if(kind==='exp_project'){
    const holder = modalBody.querySelector('#exp_proj_tags');
    RS.mountTagInput(holder, Array.isArray(data.technologies)? data.technologies.slice(): [], ()=>{}, 'Add tech and Enter');
  }
  modalBack.style.display='flex';
};

RS.renderFormHTML = (kind, data)=>{
  const esc=s=>String(s||'').replace(/[&<>\"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));
  if(kind==='experience') return `
    <form>
      <div class="field"><label>Title/Role</label><input name="title" type="text" value="${esc(data.title)}"></div>
      <div class="two">
        <div class="field"><label>Company</label><input name="company" type="text" value="${esc(data?.companies?.[0]?.company||'')}"></div>
        <div class="field"><label>Location</label><input name="location" type="text" value="${esc(data?.companies?.[0]?.location||'')}"></div>
      </div>
      <div class="field"><label>Period</label><input name="period" type="text" value="${esc(data?.companies?.[0]?.period||'')}"></div>
      <div class="field"><label>Highlights (one per line)</label><textarea name="highlights">${esc((data.highlights||[]).join('\n'))}</textarea></div>
    </form>`;
  if(kind==='education') return `
    <form>
      <div class="field"><label>Degree</label><input name="degree" type="text" value="${esc(data.degree)}"></div>
      <div class="field"><label>Institution</label><input name="institution" type="text" value="${esc(data.institution)}"></div>
      <div class="two">
        <div class="field"><label>Period</label><input name="period" type="text" value="${esc(data.period)}"></div>
        <div class="field"><label>Details</label><input name="details" type="text" value="${esc(data.details||'')}"></div>
      </div>
    </form>`;
  if(kind==='projects') return `
    <form>
      <div class="field"><label>Name</label><input name="name" type="text" value="${esc(data.name)}"></div>
      <div class="field"><label>Description</label><textarea name="description">${esc(data.description||'')}</textarea></div>
      <div class="two">
        <div class="field"><label>Technologies (comma separated)</label><input name="technologies" type="text" value="${esc((data.technologies||[]).join(', '))}"></div>
        <div class="field"><label>URL</label><input name="url" type="text" value="${esc(data.url||'')}"></div>
      </div>
    </form>`;
  if(kind==='awards') return `
    <form>
      <div class="two">
        <div class="field"><label>Award</label><input name="what" type="text" value="${esc(data.what)}"></div>
        <div class="field"><label>Where</label><input name="where" type="text" value="${esc(data.where||'')}"></div>
      </div>
      <div class="two">
        <div class="field"><label>When</label><input name="when" type="text" value="${esc(data.when||'')}"></div>
        <div class="field"><label>Description</label><input name="description" type="text" value="${esc(data.description||'')}"></div>
      </div>
    </form>`;
  if(kind==='skills'){
    const v=(typeof data==='string')?data:(data?.name||'');
    return `<form><div class="field"><label>Skill</label><input name="skill" type="text" value="${esc(v)}"></div></form>`;
  }
  if(kind==='skillgroup') return `
    <form>
      <div class="field"><label>Group name</label><input name="name" type="text" value="${esc(data.name)}"></div>
      <div class="field"><label>Color (optional, e.g. bg-gray-200)</label><input name="color" type="text" value="${esc(data.color||'')}"></div>
      <div class="field"><label>Skills</label><div id="skillgroup_tags" class="tags"></div>
        <input type="hidden" name="skills_fallback" value="${esc((data.skills||[]).join(', '))}">
      </div>
    </form>`;
  if(kind==='exp_project') return `
    <form>
      <div class="field"><label>Name</label><input name="name" type="text" value="${esc(data.name)}"></div>
      <div class="field"><label>Description</label><textarea name="description">${esc(data.description||'')}</textarea></div>
      <div class="field"><label>Technologies</label><div id="exp_proj_tags" class="tags"></div>
        <input type="hidden" name="technologies_fallback" value="${esc((data.technologies||[]).join(', '))}">
      </div>
    </form>`;
  return '<form></form>';
};

modalSave.onclick = ()=>{
  const ctx=RS.modalCtx; if(!ctx) return;
  const fd=new FormData(modalBody.querySelector('form'));
  let updated;
  if(ctx.kind==='experience'){
    updated={
      title:fd.get('title')||'',
      companies:[{company:fd.get('company')||'',location:fd.get('location')||'',period:fd.get('period')||''}],
      highlights:(fd.get('highlights')||'').split('\n').map(s=>s.trim()).filter(Boolean),
      projects: RS.readPath(['experience',ctx.index,'projects'])||[]
    };
    RS.writePath(['experience',ctx.index],updated);
  } else if(ctx.kind==='education'){
    RS.writePath(['education',ctx.index],{degree:fd.get('degree')||'',institution:fd.get('institution')||'',period:fd.get('period')||'',details:fd.get('details')||''});
  } else if(ctx.kind==='projects'){
    RS.writePath(['projects',ctx.index],{name:fd.get('name')||'',description:fd.get('description')||'',url:fd.get('url')||'',technologies:(fd.get('technologies')||'').split(',').map(s=>s.trim()).filter(Boolean)});
  } else if(ctx.kind==='awards'){
    RS.writePath(['awards',ctx.index],{what:fd.get('what')||'',where:fd.get('where')||'',when:fd.get('when')||'',description:fd.get('description')||''});
  } else if(ctx.kind==='skills'){
    const o=RS.getObj(); if(Array.isArray(o.skills?.list)){ o.skills.list[ctx.index]=fd.get('skill')||''; RS.setObj(o); }
  } else if(ctx.kind==='skillgroup'){
    const tags = Array.from(modalBody.querySelectorAll('#skillgroup_tags .tag')).map(t=>t.firstChild.textContent.trim());
    RS.writePath(['skills','full',ctx.index],{name:fd.get('name')||'',color:fd.get('color')||'',skills:tags.length?tags:(fd.get('skills_fallback')||'').split(',').map(s=>s.trim()).filter(Boolean)});
  } else if(ctx.kind==='exp_project'){
    const [i,j]=String(ctx.index).split(':').map(n=>parseInt(n,10));
    const tags = Array.from(modalBody.querySelectorAll('#exp_proj_tags .tag')).map(t=>t.firstChild.textContent.trim());
    RS.writePath(['experience',i,'projects',j],{name:fd.get('name')||'',description:fd.get('description')||'',technologies:tags.length?tags:(fd.get('technologies_fallback')||'').split(',').map(s=>s.trim()).filter(Boolean)});
  }
  RS.renderCollections(); close();
};
