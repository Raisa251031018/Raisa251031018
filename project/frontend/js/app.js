/* Dashboard application (admin / librarian / student) */
const API = '../backend/index.php?api=';
const BACKEND = '../backend/';
let U = null;
const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const asset = p => BACKEND + p;   // book covers live in backend/uploads/

function toast(m, err) {
  const t = $('#toast');
  t.textContent = m;
  t.style.background = err ? 'var(--er)' : 'var(--ok)';
  t.style.display = 'block';
  clearTimeout(t._t);
  t._t = setTimeout(() => t.style.display = 'none', 3000);
}

/** Calls the API. isForm = true with a FormData body (used for book_save with a file). */
async function api(action, data, isForm) {
  const opts = isForm
    ? { method: 'POST', body: data }
    : { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data || {}) };
  const r = await fetch(API + action, opts);
  let j;
  try { j = await r.json(); } catch (e) { toast('Server error. Is the backend running?', 1); throw e; }
  if (j.error) { toast(j.error, 1); throw new Error(j.error); }
  return j;
}

const fmtD = s => s || '-';
const addDays = n => { const d = new Date(); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10); };
const stBadge = i => { const od = i.status === 'issued' && i.late > 0; return `<span class="badge ${i.status === 'returned' ? 'b-ok' : od ? 'b-er' : 'b-wr'}">${i.status === 'returned' ? 'Returned' : od ? 'Overdue' : 'Issued'}</span>`; };
const days = n => n + (n == 1 ? ' day' : ' days');
const coverThumb = url => url ? `<img src="${esc(asset(url))}" class="thumb" alt="Cover" loading="lazy" decoding="async">` : `<span class="no-cover">—</span>`;

$('#out').onclick = async () => { await api('logout'); location.href = 'login.html'; };
$('#menuBtn').onclick = () => $('#side').classList.toggle('open');

/* ---------- Modal (now supports a 'file' field type for cover images) ---------- */
function fieldHtml(f, vals) {
  if (f.t === 'select') {
    return `<label>${f.l}<select name="${f.k}">${f.o.map(o => `<option value="${esc(o[0])}" ${String(vals[f.k] ?? '') === String(o[0]) ? 'selected' : ''}>${esc(o[1])}</option>`).join('')}</select></label>`;
  }
  if (f.t === 'file') {
    const has = !!vals[f.k];
    return `<label>${f.l}
      ${has ? `<img src="${esc(asset(vals[f.k]))}" class="cover-preview" id="prev_${f.k}">` : ''}
      <input name="${f.k}" type="file" accept="image/png,image/jpeg,image/webp" onchange="previewFile(this,'prev_${f.k}')">
      ${has ? `<label class="chk"><input type="checkbox" name="remove_cover" value="1"> Remove current image</label>` : ''}
    </label>`;
  }
  return `<label>${f.l}<input name="${f.k}" type="${f.t || 'text'}" value="${esc(f.t === 'password' ? '' : vals[f.k])}" ${f.r ? 'required' : ''} ${f.min ? 'min="' + f.min + '"' : ''}></label>`;
}
window.previewFile = (input, id) => {
  const file = input.files[0];
  if (!file) return;
  let img = document.getElementById(id);
  if (!img) {
    img = document.createElement('img');
    img.id = id;
    img.className = 'cover-preview';
    input.insertAdjacentElement('beforebegin', img);
  }
  img.src = URL.createObjectURL(file);
};
function modal(title, fields, vals, save) {
  vals = vals || {};
  const hasFile = fields.some(f => f.t === 'file');
  const m = document.createElement('div');
  m.className = 'modal';
  m.innerHTML = `<form class="box">${hasFile ? '' : ''}<h3>${title}</h3>${fields.map(f => fieldHtml(f, vals)).join('')}
    <div class="row"><button class="btn">Save</button><button type="button" class="btn gray">Cancel</button></div></form>`;
  document.body.appendChild(m);
  m.querySelector('.gray').onclick = () => m.remove();
  m.querySelector('form').onsubmit = async e => {
    e.preventDefault();
    try {
      if (hasFile) {
        const fd = new FormData(e.target);
        fd.set('id', vals.id || 0);
        await save(fd, true);
      } else {
        const d = Object.fromEntries(new FormData(e.target));
        d.id = vals.id || 0;
        await save(d, false);
      }
      m.remove();
    } catch (x) {}
  };
}
function table(heads, rows) {
  return `<div class="tw"><table><thead><tr>${heads.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>${rows.length ? rows.join('') : `<tr><td colspan="${heads.length}" class="empty">No records found</td></tr>`}</tbody></table></div>`;
}

/* ---------- Navigation ---------- */
const IC={dash:'<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',books:'<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"/>',cats:'<path d="M20 12l-8 8-9-9V3h8z"/><circle cx="7.5" cy="7.5" r="1.2"/>',students:'<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11.5V16c0 1.5 3 3 6 3s6-1.5 6-3v-4.5"/>',issue:'<path d="M12 19V5"/><path d="M5 12l7-7 7 7"/>',issues:'<path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/>',libs:'<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',hist:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',settings:'<path d="M4 6h8"/><circle cx="15" cy="6" r="2"/><path d="M17 6h3"/><path d="M4 12h2"/><circle cx="9" cy="12" r="2"/><path d="M11 12h9"/><path d="M4 18h9"/><circle cx="16" cy="18" r="2"/><path d="M18 18h2"/>',account:'<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'};
const ic=k=>`<svg viewBox="0 0 24 24">${IC[k]||''}</svg>`;

const MENU = {
  staff: [['dash', 'Dashboard'], ['books', 'Books'], ['cats', 'Categories'], ['students', 'Students'], ['issue', 'Issue Book'], ['issues', 'Return & Fines']],
  student: [['books', 'Search Books'], ['hist', 'My History']]
};
let PAGE = '';
function start(r) {
  U = r;
  $('#app').style.display = 'block';
  $('#who').innerHTML = `<span>${esc(r.name)} &middot; ${esc(r.role)}</span><b class="av" style="margin:0">${esc(r.name[0] || '?').toUpperCase()}</b>`;
  const ACCOUNT = ['account', 'Change Password'];
  const m = r.role === 'student' ? MENU.student.concat([ACCOUNT])
    : MENU.staff.concat(r.role === 'admin' ? [['libs', 'Librarians'], ['settings', 'Settings']] : [ACCOUNT]);
  $('#nav').innerHTML = m.map(x => `<a data-p="${x[0]}">${ic(x[0])}<span>${x[1]}</span></a>`).join('');
  $('#nav').onclick = e => { const a = e.target.closest('a'); if (a) go(a.dataset.p); };
  go(m[0][0]);
}
function go(p) {
  PAGE = p;
  $('#side').classList.remove('open');
  document.querySelectorAll('#nav a').forEach(a => a.classList.toggle('on', a.dataset.p === p));
  $('#title').textContent = document.querySelector('#nav a.on').textContent;
  $('#main').innerHTML = '<div class="skel"></div>';
  P[p]();
}

/* ---------- Pages ---------- */
const P = {};

P.dash = async () => {
  const r = await api('reports');
  const S = (v, l, c) => `<div class="stat" style="--c1:${c || 'var(--p)'}"><i></i><b>${v}</b><span>${l}</span></div>`;
  const used = r.total_books ? Math.round((r.total_books - r.available) / r.total_books * 100) : 0;
  $('#main').innerHTML = `<div class="grid">
    ${S(r.titles, 'Total Book Titles')}${S(r.total_books, 'Total Books (Copies)')}${S(r.available, 'Available Books', 'var(--ok)')}
    ${S(r.issued_now, 'Currently Issued', 'var(--wr)')}${S(r.total_issued, 'Total Issued (All Time)')}${S(r.returned, 'Returned Books', 'var(--ok)')}
    ${S(r.overdue, 'Overdue Books', 'var(--er)')}${S(r.students, 'Total Students')}
    ${S('৳' + r.fine_collected, 'Fines Collected', 'var(--ok)')}${S('৳' + r.fine_pending, 'Fines Pending (Running)', 'var(--er)')}
   </div><div class="card usage"><b>Library usage</b> <span style="color:var(--m)">&mdash; ${used}% of all copies are currently issued</span><div class="meter"><div style="width:${used}%"></div></div></div><p style="color:var(--m);font-size:13px;margin-top:12px">Fine rate: ৳${r.fine_rate} per day</p>
   <div class="card" style="margin-top:14px"><h3 style="margin-top:0">Overdue List</h3><div id="od"></div></div>`;
  const o = await api('issues', { filter: 'overdue' });
  $('#od').innerHTML = table(['Student', 'ID', 'Book', 'Due Date', 'Late By', 'Fine'],
    o.map(i => `<tr><td>${esc(i.sname)}</td><td>${esc(i.sid)}</td><td>${esc(i.title)}</td><td>${i.due_date}</td><td>${days(i.late)}</td><td>৳${i.fine}</td></tr>`));
};

P.books = async () => {
  const staff = U.role !== 'student';
  const cats = await api('cats');
  $('#main').innerHTML = `<div class="bar">
    <input id="bq" placeholder="Search by title, author or ISBN...">
    <select id="bc"><option value="">All Categories</option>${cats.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('')}</select>
    <select id="bav"><option value="">All Books</option><option value="1">Available Only</option></select>
    ${staff ? '<button class="btn" id="badd">+ Add Book</button>' : ''}</div><div class="card" id="bl"></div>`;
  const load = async () => {
    const r = await api('books', { q: $('#bq').value, cat: $('#bc').value, avail: $('#bav').value });
    $('#bl').innerHTML = table(['Cover', '#', 'Title', 'Author', 'ISBN', 'Category', 'Total', 'Available', 'Status'].concat(staff ? ['Actions'] : []),
      r.map(b => `<tr><td>${coverThumb(b.cover_image)}</td><td>${b.id}</td><td>${esc(b.title)}</td><td>${esc(b.author)}</td><td>${esc(b.isbn)}</td><td>${esc(b.cat || '-')}</td><td>${b.total_copies}</td><td>${b.available_copies}</td>
       <td><span class="badge ${b.available_copies > 0 ? 'b-ok' : 'b-er'}">${b.available_copies > 0 ? 'Available' : 'Not Available'}</span></td>
       ${staff ? `<td><button class="btn sm" onclick='editBook(${esc(JSON.stringify(b))})'>Edit</button> <button class="btn sm red" onclick="delBook(${b.id})">Delete</button></td>` : ''}</tr>`));
  };
  let tm; $('#bq').oninput = () => { clearTimeout(tm); tm = setTimeout(load, 250); };
  $('#bc').onchange = load; $('#bav').onchange = load;
  if (staff) $('#badd').onclick = () => editBook({});
  window.editBook = b => modal(b.id ? 'Edit Book' : 'Add New Book', [
    { k: 'title', l: 'Book Title', r: 1 }, { k: 'author', l: 'Author', r: 1 }, { k: 'isbn', l: 'ISBN' },
    { k: 'category_id', l: 'Category', t: 'select', o: [['', '-- Select --']].concat(cats.map(c => [c.id, c.name])) },
    { k: 'total_copies', l: 'Total Copies', t: 'number', r: 1, min: 1 },
    { k: 'cover_image', l: 'Cover Image (JPG / PNG / WEBP, max 2MB)', t: 'file' }
  ], Object.assign({ total_copies: 1 }, b), async (d, isForm) => { await api('book_save', d, isForm); toast('Saved successfully'); load(); });
  window.delBook = async id => { if (confirm('Delete this book?')) { await api('book_del', { id }); toast('Deleted'); load(); } };
  load();
};

P.cats = async () => {
  const r = await api('cats');
  $('#main').innerHTML = `<div class="bar"><button class="btn" id="cadd">+ Add Category</button></div><div class="card">${table(['#', 'Name', 'Books', 'Actions'],
    r.map(c => `<tr><td>${c.id}</td><td>${esc(c.name)}</td><td>${c.cnt}</td><td><button class="btn sm" onclick='editCat(${esc(JSON.stringify(c))})'>Edit</button> <button class="btn sm red" onclick="delCat(${c.id})">Delete</button></td></tr>`))}</div>`;
  window.editCat = c => modal(c.id ? 'Edit Category' : 'Add Category', [{ k: 'name', l: 'Category Name', r: 1 }], c, async d => { await api('cat_save', d); toast('Saved successfully'); P.cats(); });
  window.delCat = async id => { if (confirm('Delete this category?')) { await api('cat_del', { id }); P.cats(); } };
  $('#cadd').onclick = () => editCat({});
};

P.students = async () => {
  $('#main').innerHTML = `<div class="bar"><input id="sq" placeholder="Search by name, ID or department..."><button class="btn" id="sadd">+ Add Student</button></div><div class="card" id="sl"></div>`;
  const load = async () => {
    const r = await api('students', { q: $('#sq').value });
    $('#sl').innerHTML = table(['Student ID', 'Name', 'Department', 'Phone', 'Email', 'Actions'],
      r.map(s => `<tr><td>${esc(s.student_id)}</td><td><span class="av">${esc((s.name||'?')[0]).toUpperCase()}</span>${esc(s.name)}</td><td>${esc(s.department)}</td><td>${esc(s.phone)}</td><td>${esc(s.email)}</td>
       <td><button class="btn sm" onclick='editStu(${esc(JSON.stringify(s))})'>Edit</button> <button class="btn sm red" onclick="delStu(${s.id})">Delete</button></td></tr>`));
  };
  let tm; $('#sq').oninput = () => { clearTimeout(tm); tm = setTimeout(load, 250); };
  window.editStu = s => modal(s.id ? 'Edit Student' : 'Add New Student', [
    { k: 'student_id', l: 'Student ID', r: 1 }, { k: 'name', l: 'Full Name', r: 1 }, { k: 'department', l: 'Department' }, { k: 'phone', l: 'Phone' }, { k: 'email', l: 'Email', t: 'email' },
    { k: 'password', l: s.id ? 'New Password (leave blank to keep)' : 'Password (blank = Student ID)', t: 'password' }], s, async d => { await api('student_save', d); toast('Saved successfully'); load(); });
  window.delStu = async id => { if (confirm('Delete this student?')) { await api('student_del', { id }); load(); } };
  $('#sadd').onclick = () => editStu({});
  load();
};

P.issue = async () => {
  const [st, bk] = await Promise.all([api('students', { q: '' }), api('books', { avail: 1 })]);
  $('#main').innerHTML = `<div class="card" style="max-width:520px"><form id="isf">
    <label>Student<select name="student_id" required><option value="">-- Select student --</option>${st.map(s => `<option value="${s.id}">${esc(s.student_id)} - ${esc(s.name)}</option>`).join('')}</select></label>
    <label>Book (available only)<select name="book_id" required><option value="">-- Select book --</option>${bk.map(b => `<option value="${b.id}">${esc(b.title)} — ${esc(b.author)} (${b.available_copies} left)</option>`).join('')}</select></label>
    <label>Due Date (return by)<input type="date" name="due_date" value="${addDays(14)}" min="${addDays(0)}" required></label>
    <button class="btn">Issue Book</button></form></div>`;
  $('#isf').onsubmit = async e => {
    e.preventDefault();
    try { await api('issue', Object.fromEntries(new FormData(e.target))); toast('Book issued successfully'); P.issue(); } catch (x) {}
  };
};

P.issues = async () => {
  $('#main').innerHTML = `<div class="bar"><select id="ifl"><option value="">All Records</option><option value="issued">Currently Issued</option><option value="overdue">Overdue</option><option value="returned">Returned</option></select></div><div class="card" id="il"></div>`;
  const load = async () => {
    const r = await api('issues', { filter: $('#ifl').value });
    $('#il').innerHTML = table(['Student', 'ID', 'Book', 'Issued', 'Due', 'Returned', 'Late By', 'Fine', 'Status', 'Action'],
      r.map(i => `<tr><td>${esc(i.sname)}</td><td>${esc(i.sid)}</td><td>${esc(i.title)}</td><td>${i.issue_date}</td><td>${i.due_date}</td><td>${fmtD(i.return_date)}</td>
        <td>${days(i.late)}</td><td>৳${i.fine}</td><td>${stBadge(i)}</td>
        <td>${i.status === 'issued' ? `<button class="btn sm green" onclick="ret(${i.id},${i.fine})">Return</button>` : ''}</td></tr>`));
  };
  window.ret = async (id, fine) => {
    if (!confirm('Mark this book as returned?' + (fine > 0 ? '\nFine due: ৳' + fine : ''))) return;
    const r = await api('return', { id });
    toast(r.fine > 0 ? `Returned. Fine ৳${r.fine} (${days(r.late)} late)` : 'Returned successfully');
    load();
  };
  $('#ifl').onchange = load; load();
};

P.libs = async () => {
  const r = await api('libs');
  $('#main').innerHTML = `<div class="bar"><button class="btn" id="ladd">+ Add Librarian</button></div><div class="card">${table(['Name', 'Username', 'Role', 'Phone', 'Email', 'Actions'],
    r.map(l => `<tr><td>${esc(l.name)}</td><td>${esc(l.username)}</td><td>${l.role}</td><td>${esc(l.phone)}</td><td>${esc(l.email)}</td>
     <td><button class="btn sm" onclick='editLib(${esc(JSON.stringify(l))})'>Edit</button> ${l.role === 'admin' ? '' : `<button class="btn sm red" onclick="delLib(${l.id})">Delete</button>`}</td></tr>`))}</div>`;
  window.editLib = l => modal(l.id ? 'Edit Librarian' : 'Add Librarian', [
    { k: 'name', l: 'Full Name', r: 1 }, { k: 'username', l: 'Username', r: 1 }, { k: 'phone', l: 'Phone' }, { k: 'email', l: 'Email', t: 'email' },
    { k: 'password', l: l.id ? 'New Password (leave blank to keep)' : 'Password', t: 'password', r: !l.id }], l, async d => { await api('lib_save', d); toast('Saved successfully'); P.libs(); });
  window.delLib = async id => { if (confirm('Delete this librarian?')) { await api('lib_del', { id }); P.libs(); } };
  $('#ladd').onclick = () => editLib({});
};

/* ---------- Settings (admin) & Change password (everyone) ---------- */
function passwordCard(sel) {
  $(sel).innerHTML = `<h3 style="margin-top:0">Change Password</h3><form id="pf">
    <label>Current Password<input type="password" name="current_password" required autocomplete="current-password"></label>
    <label>New Password (at least 6 characters)<input type="password" name="new_password" required minlength="6" autocomplete="new-password"></label>
    <label>Confirm New Password<input type="password" name="confirm_password" required minlength="6" autocomplete="new-password"></label>
    <button class="btn">Update Password</button></form>`;
  $('#pf').onsubmit = async e => {
    e.preventDefault();
    try { await api('password_save', Object.fromEntries(new FormData(e.target))); toast('Password updated'); e.target.reset(); } catch (x) {}
  };
}

P.account = async () => {
  $('#main').innerHTML = '<div class="card" id="pwcard" style="max-width:520px"></div>';
  passwordCard('#pwcard');
};

P.settings = async () => {
  const s = await api('site');
  $('#main').innerHTML = `<div class="card" style="max-width:520px"><h3 style="margin-top:0">Website Settings</h3><form id="sf">
    <label>Website Name<input name="site_name" value="${esc(s.name)}" maxlength="80" required></label>
    <label>Tagline (shown on the home page)<input name="site_tagline" value="${esc(s.tagline)}" maxlength="160"></label>
    <button class="btn">Save Settings</button></form></div>
    <div class="card" id="pwcard" style="max-width:520px;margin-top:16px"></div>`;
  $('#sf').onsubmit = async e => {
    e.preventDefault();
    try {
      const r = await api('settings_save', Object.fromEntries(new FormData(e.target)));
      window.applySite({ name: r.name, tagline: r.tagline });   // update sidebar + tab title right away
      toast('Settings saved');
    } catch (x) {}
  };
  passwordCard('#pwcard');
};

P.hist = async () => {
  const r = await api('my_history');
  $('#main').innerHTML = `<div class="card">${table(['Book', 'Issued', 'Due Date', 'Returned', 'Late By', 'Fine', 'Status'],
    r.map(i => `<tr><td>${esc(i.title)}</td><td>${i.issue_date}</td><td>${i.due_date}</td><td>${fmtD(i.return_date)}</td><td>${days(i.late)}</td><td>৳${i.fine}</td><td>${stBadge(i)}</td></tr>`))}</div>`;
};


/* ---------- Boot: must be logged in, otherwise go to login page ---------- */
(async () => {
  try { const r = await api('me'); if (r.role) start(r); else location.href = 'login.html'; }
  catch (e) { location.href = 'login.html'; }
})();
