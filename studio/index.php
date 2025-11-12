<?php
/* Resume Studio — index (UI shell) */
?><!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Resume Studio</title>
    <link rel="stylesheet" href="/studio/assets/studio.css">
  </head>
  <body>
    <header>
      <h1>📄 Resume Studio</h1>
      <span class="pill" id="status-pill">Ready</span>
      <div class="status" id="unsaved" style="display:none">
        <span class="badge warn">Unsaved changes</span>
      </div>
    </header>
    <div class="wrap">
      <aside>
        <div class="field">
          <label>User folder (under /data)</label>
          <div class="row">
            <input id="user" type="text" placeholder="user_name" />
            <button id="loadUser">Open</button>
          </div>
          <small class="muted">Files live at <span class="kbd">/data/&lt;user&gt;/resumeX.json</span></small>
        </div>
    
        <div class="row" style="gap:6px; flex-wrap:wrap">
          <button id="newEmpty">＋ New Empty</button>
          <button id="duplicate">⧉ Duplicate</button>
          <button id="rename">✎ Rename</button>
          <button id="delete" class="danger">🗑 Delete</button>
        </div>
    
        <div class="row" style="margin-top:6px; gap:6px; flex-wrap:wrap">
          <button id="download">⬇ Export</button>
          <label class="button ghost" style="display:inline-flex; align-items:center; gap:8px; cursor:pointer">
            <input id="importFile" type="file" accept="application/json" style="display:none" />
            <span class="kbd">Import JSON…</span>
          </label>
        </div>
    
        <div class="files" id="files"></div>
      </aside>
    
      <main>
        <div class="tabs" id="tabs"></div>
    
        <!-- BASICS -->
        <div class="split tabview" data-tab="Basics">
          <section class="panel">
            <div class="grid2">
              <div>
                <div class="field"><label>Name</label><input id="f_name" type="text"></div>
                <div class="field"><label>Title</label><input id="f_title" type="text"></div>
                <div class="field">
                  <label>Summary (HTML allowed)</label>
                  <div class="rte-toolbar" id="rteSummaryToolbar">
                    <button type="button" data-cmd="bold"><b>B</b></button>
                    <button type="button" data-cmd="italic"><i>I</i></button>
                    <button type="button" data-cmd="underline"><u>U</u></button>
                    <span class="sep"></span>
                    <button type="button" data-cmd="createLink">Link</button>
                    <span class="sep"></span>
                    <button type="button" data-cmd="insertUnorderedList">• List</button>
                    <button type="button" data-cmd="insertOrderedList">1. List</button>
                    <span class="sep"></span>
                    <button type="button" data-cmd="removeFormat">Clear</button>
                  </div>
                  <div id="f_summary_rte" class="rte-area" contenteditable="true" spellcheck="false"></div>
                </div>
              </div>
              <div>
                <div class="field"><label>Email</label><input id="f_email" type="text"></div>
                <div class="field"><label>Phone</label><input id="f_phone" type="text"></div>
                <div class="field"><label>Location</label><input id="f_location" type="text"></div>
              </div>
            </div>
    
            <div style="display:flex; gap:8px; margin-top:8px">
              <button class="primary" id="applyForm">Apply to JSON</button>
              <button class="ghost" id="pullForm">Pull from JSON</button>
              <div style="margin-left:auto" class="row">
                <select id="backupPicker"></select>
                <button id="restoreBackup" class="warn">Restore backup</button>
              </div>
            </div>
          </section>
        </div>
    
        <!-- JSON -->
        <div class="tabview" data-tab="JSON">
          <section class="panel">
            <div class="row" style="justify-content:space-between; align-items:center; margin-bottom:8px">
              <strong>Raw JSON</strong>
              <div class="row" style="gap:6px">
                <button id="pretty">Pretty</button>
                <button id="validate">Validate</button>
                <button class="primary" id="save">Save</button>
              </div>
            </div>
            <textarea id="json" spellcheck="false"></textarea>
            <div style="margin-top:8px" id="validation"></div>
          </section>
        </div>
    
        <!-- COLLECTIONS -->
        <div class="tabview" data-tab="Collections">
          <section class="panel" style="margin-top:12px">
            <h3 style="margin:0 0 8px 0">Collections</h3>
            <div class="grid3">
              <!-- Experience -->
              <div>
                <div class="row" style="justify-content:space-between; align-items:center">
                  <h4 style="margin:0">Experience</h4>
                  <button data-add="experience">＋ Add</button>
                </div>
                <div id="list_experience" class="list"></div>
              </div>
    
              <!-- Education -->
              <div>
                <div class="row" style="justify-content:space-between; align-items:center">
                  <h4 style="margin:0">Education</h4>
                  <button data-add="education">＋ Add</button>
                </div>
                <div id="list_education" class="list"></div>
              </div>
    
              <!-- Projects -->
              <div>
                <div class="row" style="justify-content:space-between; align-items:center">
                  <h4 style="margin:0">Projects</h4>
                  <button data-add="projects">＋ Add</button>
                </div>
                <div id="list_projects" class="list"></div>
              </div>
    
              <!-- Awards -->
              <div>
                <div class="row" style="justify-content:space-between; align-items:center">
                  <h4 style="margin:0">Awards</h4>
                  <button data-add="awards">＋ Add</button>
                </div>
                <div id="list_awards" class="list"></div>
              </div>
    
              <!-- Skills: flat list -->
              <div>
                <div class="row" style="justify-content:space-between; align-items:center">
                  <h4 style="margin:0">Skills (List)</h4>
                  <button data-add="skills">＋ Add</button>
                </div>
                <div id="list_skills" class="list"></div>
              </div>
    
              <!-- Skills: groups -->
              <div>
                <div class="row" style="justify-content:space-between; align-items:center">
                  <h4 style="margin:0">Skills (Groups)</h4>
                  <button data-add="skillgroup">＋ Add Group</button>
                </div>
                <div id="list_skillgroups" class="list"></div>
              </div>
            </div>
          </section>
        </div>
      </main>
    </div>
    
    <!-- Modal -->
    <div class="modal-backdrop" id="modalBack">
      <div class="modal">
        <header class="row" style="justify-content:space-between; align-items:center">
          <h3 id="modalTitle" style="margin:0">Edit</h3>
          <button class="ghost" id="closeModal">✕</button>
        </header>
        <div id="modalBody"></div>
        <footer style="margin-top:10px">
          <button id="modalSave" class="primary">Save</button>
          <button id="modalCancel" class="ghost">Cancel</button>
        </footer>
      </div>
    </div>
    
    <script>window.__RESUME_STUDIO_API__ = '/studio/api/';</script>
    <script src="/studio/assets/studio.js" data-rs-loader="true"></script>
  </body>
</html>
