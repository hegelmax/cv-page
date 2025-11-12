// collections.js
RS.addItemCard = (listId, title, subtitle, onEdit, onRemove, onUp, onDown)=>{
  const c=document.createElement('div'); c.className='item';
  c.innerHTML=`
    <div class="item-head">
      <div><strong>${title}</strong><div class="muted">${subtitle||''}</div></div>
      <div class="row">
        <button class="ghost">↑</button>
        <button class="ghost">↓</button>
        <button>✎</button>
        <button class="danger">🗑</button>
      </div>
    </div>`;
  const [upBtn,downBtn,editBtn,delBtn]=c.querySelectorAll('button');
  upBtn.onclick=onUp; downBtn.onclick=onDown; editBtn.onclick=onEdit; delBtn.onclick=onRemove;
  el(listId).appendChild(c); return c;
};
const clearList = id => el(id).innerHTML='';

RS.addWizard = (kind)=>{
  RS.ensureArrays();
  const o=RS.getObj(); let idx=0;
  if(kind==='experience'){ idx=o.experience.push({title:'',companies:[{company:'',location:'',period:''}],highlights:[],projects:[]})-1; }
  else if(kind==='education'){ idx=o.education.push({degree:'',institution:'',period:'',details:''})-1; }
  else if(kind==='projects'){ idx=o.projects.push({name:'',description:'',technologies:[],url:''})-1; }
  else if(kind==='awards'){ idx=o.awards.push({what:'',where:'',when:'',description:''})-1; }
  else if(kind==='skills'){ idx=o.skills.list.push('')-1; }
  else if(kind==='skillgroup'){ idx=o.skills.full.push({name:'', color:'', skills:[]})-1; }
  RS.setObj(o); RS.renderCollections(); RS.openEditor(kind, idx);
};

RS.renderCollections = ()=>{
  const o=RS.getObj();
  const exp=Array.isArray(o.experience)?o.experience:[], edu=Array.isArray(o.education)?o.education:[],
        proj=Array.isArray(o.projects)?o.projects:[], awd=Array.isArray(o.awards)?o.awards:[],
        groups=Array.isArray(o.skills?.full)?o.skills.full:[];

  // Experience + nested Projects
  clearList('list_experience');
  exp.forEach((e,i)=>{
    const card = RS.addItemCard('list_experience',
      e.title||e.name||'Untitled',
      (e.companies?.[0]?.company||'')+' '+(e.companies?.[0]?.period||''),
      ()=>RS.openEditor('experience',i),
      ()=>{ RS.removePath(['experience',i]); RS.renderCollections(); },
      ()=>{ RS.movePath(['experience',i],-1); RS.renderCollections(); },
      ()=>{ RS.movePath(['experience',i],+1); RS.renderCollections(); }
    );
    // sub-projects
    const sub=document.createElement('div'); sub.className='sublist';
    sub.innerHTML=`<h5>Projects</h5><div id="exp_proj_${i}" class="list"></div><button class="ghost" id="add_proj_${i}">＋ Project</button>`;
    card.appendChild(sub);
    const lst=sub.querySelector(`#exp_proj_${i}`);
    (Array.isArray(e.projects)?e.projects:[]).forEach((p,j)=>{
      RS.addItemCard(lst.id, p.name||'Project', (p.technologies||[]).join(', '),
        ()=>RS.openEditor('exp_project', `${i}:${j}`),
        ()=>{ RS.removePath(['experience',i,'projects',j]); RS.renderCollections(); },
        ()=>{ RS.movePath(['experience',i,'projects',j],-1); RS.renderCollections(); },
        ()=>{ RS.movePath(['experience',i,'projects',j],+1); RS.renderCollections(); }
      );
    });
    sub.querySelector(`#add_proj_${i}`).onclick = ()=>{
      const obj=RS.getObj();
      obj.experience[i].projects = Array.isArray(obj.experience[i].projects)? obj.experience[i].projects : [];
      const j = obj.experience[i].projects.push({name:'',description:'',technologies:[]})-1;
      RS.setObj(obj); RS.renderCollections(); RS.openEditor('exp_project', `${i}:${j}`);
    };
  });

  // Education
  clearList('list_education');
  edu.forEach((e,i)=>RS.addItemCard('list_education', e.degree||'Untitled', e.institution||'',
    ()=>RS.openEditor('education',i),
    ()=>{ RS.removePath(['education',i]); RS.renderCollections(); },
    ()=>{ RS.movePath(['education',i],-1); RS.renderCollections(); },
    ()=>{ RS.movePath(['education',i],+1); RS.renderCollections(); }
  ));

  // Projects (global)
  clearList('list_projects');
  proj.forEach((p,i)=>RS.addItemCard('list_projects', p.name||'Untitled', p.description||'',
    ()=>RS.openEditor('projects',i),
    ()=>{ RS.removePath(['projects',i]); RS.renderCollections(); },
    ()=>{ RS.movePath(['projects',i],-1); RS.renderCollections(); },
    ()=>{ RS.movePath(['projects',i],+1); RS.renderCollections(); }
  ));

  // Awards
  clearList('list_awards');
  awd.forEach((a,i)=>RS.addItemCard('list_awards', a.what||a.name||'Untitled', (a.where||'')+' '+(a.when||''),
    ()=>RS.openEditor('awards',i),
    ()=>{ RS.removePath(['awards',i]); RS.renderCollections(); },
    ()=>{ RS.movePath(['awards',i],-1); RS.renderCollections(); },
    ()=>{ RS.movePath(['awards',i],+1); RS.renderCollections(); }
  ));

  // Skills (List) — tag input
  const skillsListHost = el('list_skills'); skillsListHost.innerHTML='';
  const tagBox = document.createElement('div'); skillsListHost.appendChild(tagBox);
  const initial = Array.isArray(o.skills?.list) ? o.skills.list.slice() : [];
  RS.mountTagInput(tagBox, initial, (arr)=>{ const obj=RS.getObj(); obj.skills=obj.skills||{}; obj.skills.list=arr; RS.setObj(obj); });

  // Skills (Groups)
  clearList('list_skillgroups');
  groups.forEach((g,i)=>RS.addItemCard('list_skillgroups', g.name||'Group',
    `${(g.skills||[]).length} skill(s)`+(g.color?` • ${g.color}`:''),
    ()=>RS.openEditor('skillgroup',i),
    ()=>{ RS.removePath(['skills','full',i]); RS.renderCollections(); },
    ()=>{ RS.movePath(['skills','full',i],-1); RS.renderCollections(); },
    ()=>{ RS.movePath(['skills','full',i],+1); RS.renderCollections(); }
  ));
};
