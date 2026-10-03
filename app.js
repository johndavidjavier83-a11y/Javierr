const CATS = {quiz:'Quiz', long_quiz:'Long Quiz', midterms:'Midterms', finals:'Finals', activity:'Activity', project:'Project'};
let cur = 'quiz', isAdmin = false;
const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

async function api(action, body, form) {
  const opt = form ? {method:'POST', body:form} : body ? {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body)} : {};
  const r = await fetch('api.php?action=' + action + (action === 'list' ? '&category=' + cur : ''), opt);
  const d = await r.json();
  if (!r.ok) throw new Error(d.error || 'Error');
  return d;
}
function tabs() {
  $('tabs').innerHTML = Object.entries(CATS).map(([k, v]) => `<button data-k="${k}" class="${k === cur ? 'active' : ''}">${v}</button>`).join('');
  $('tabs').querySelectorAll('button').forEach(b => b.onclick = () => { cur = b.dataset.k; load(); });
}
async function load() {
  tabs();
  const d = await api('list');
  isAdmin = d.admin;
  $('catName').textContent = CATS[cur];
  $('adminPanel').hidden = !isAdmin;
  $('authBox').innerHTML = isAdmin ? '<button id="logoutBtn">Logout</button>' : '<button id="loginBtn">Owner Login</button>';
  if (isAdmin) $('logoutBtn').onclick = async () => { await api('logout', {}); load(); };
  else $('loginBtn').onclick = () => { $('loginModal').hidden = false; };
  $('grid').innerHTML = d.works.length ? d.works.map(card).join('') : `<div class="empty">No ${CATS[cur]} uploaded yet.</div>`;
  document.querySelectorAll('.card img').forEach(i => i.onclick = () => { $('bigImg').src = i.src; $('viewer').hidden = false; });
  document.querySelectorAll('[data-edit]').forEach(b => b.onclick = () => edit(d.works.find(w => w.id == b.dataset.edit)));
  document.querySelectorAll('[data-del]').forEach(b => b.onclick = async () => { if (confirm('Delete this item?')) { await api('delete', {id: +b.dataset.del}); load(); } });
}
function card(w) {
  const f = w.filename ? 'uploads/' + w.filename : '';
  const media = w.is_image == 1 ? `<img src="${f}" alt="${esc(w.title)}">` : `<div class="ph">${f ? '📄' : '📝'}</div>`;
  const dl = f ? `<a href="${f}" download="${esc(w.original)}" target="_blank">⬇ ${esc(w.original)}</a>` : '';
  const ad = isAdmin ? `<button data-edit="${w.id}" class="ghost">Edit</button><button data-del="${w.id}" style="background:#ef4444">Delete</button>` : '';
  return `<div class="card">${media}<div class="body"><h3>${esc(w.title)}</h3><p>${esc(w.description)}</p><small>${esc(w.created_at.slice(0, 10))}</small></div><div class="acts">${dl}${ad}</div></div>`;
}
async function edit(w) {
  const title = prompt('Title:', w.title); if (title === null) return;
  const description = prompt('Description:', w.description); if (description === null) return;
  await api('update', {id: w.id, title, description}); load();
}
$('doLogin').onclick = async () => {
  try { await api('login', {email: $('email').value, password: $('pass').value}); $('loginModal').hidden = true; $('pass').value = ''; $('err').textContent = ''; load(); }
  catch (e) { $('err').textContent = e.message; }
};
$('closeLogin').onclick = () => $('loginModal').hidden = true;
$('viewer').onclick = () => $('viewer').hidden = true;
$('uploadBtn').onclick = async () => {
  const fd = new FormData();
  fd.append('category', cur); fd.append('title', $('title').value); fd.append('description', $('desc').value);
  if ($('file').files[0]) fd.append('file', $('file').files[0]);
  try { await api('upload', null, fd); $('title').value = $('desc').value = $('file').value = ''; load(); }
  catch (e) { alert(e.message); }
};
$('pwBtn').onclick = async () => {
  const email = prompt('New owner email:'); if (!email) return;
  const password = prompt('New password (8+ characters):'); if (!password) return;
  try { await api('password', {email, password}); alert('Updated!'); } catch (e) { alert(e.message); }
};
load();
