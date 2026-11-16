/* Public pages: login */
const API = '../backend/index.php?api=';
let loginType = 'staff';
const $ = s => document.querySelector(s);

function toast(m, err) {
  const t = $('#toast');
  t.textContent = m;
  t.style.background = err ? 'var(--er)' : 'var(--ok)';
  t.style.display = 'block';
  clearTimeout(t._t);
  t._t = setTimeout(() => t.style.display = 'none', 3000);
}
async function api(action, data) {
  const r = await fetch(API + action, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data || {}) });
  let j;
  try { j = await r.json(); } catch (e) { toast('Cannot reach the server. Is the backend running?', 1); throw e; }
  if (j.error) { toast(j.error, 1); throw new Error(j.error); }
  return j;
}

$('#tStaff').onclick = () => setType('staff');
$('#tStu').onclick = () => setType('student');
const EYE = '<svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';
const EYE_OFF = '<svg viewBox="0 0 24 24"><path d="M3 3l18 18"/><path d="M10.6 6.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.2M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 4.2-.9"/></svg>';
$('#pwt').onclick = () => { const i = $('#lpw'), show = i.type === 'password'; i.type = show ? 'text' : 'password'; $('#pwt').innerHTML = show ? EYE_OFF : EYE; };
function setType(t) {
  loginType = t;
  $('#tStaff').classList.toggle('on', t === 'staff');
  $('#tStu').classList.toggle('on', t === 'student');
  $('#lu').firstChild.textContent = t === 'staff' ? 'Username' : 'Student ID';
}
$('#lf').onsubmit = async e => {
  e.preventDefault();
  try { await api('login', { type: loginType, username: $('#lun').value, password: $('#lpw').value }); location.href = 'app.html'; } catch (x) {}
};

/* already logged in? go straight to the dashboard */
(async () => { try { const r = await api('me'); if (r.role) location.href = 'app.html'; } catch (e) {} })();
