/* Website name / tagline (set in Admin -> Settings), applied to every page.
   Elements with  data-site-name / data-site-tagline  are filled in automatically,
   and <html data-page="Login"> makes the tab title "Login - <website name>". */
(function () {
  const KEY = 'site_info';
  let info = { name: 'University Library', tagline: 'Search books, track issues, returns and fines.' };
  try { Object.assign(info, JSON.parse(localStorage.getItem(KEY) || '{}')); } catch (e) {}   // last known name: no flicker

  function apply(i, remember) {
    if (!i || !i.name) return;
    info = { name: i.name, tagline: i.tagline || '' };
    const page = document.documentElement.dataset.page;
    document.title = page ? page + ' - ' + info.name : info.name;
    document.querySelectorAll('[data-site-name]').forEach(e => { e.textContent = info.name; });
    document.querySelectorAll('[data-site-tagline]').forEach(e => { e.textContent = info.tagline; });
    if (remember) { try { localStorage.setItem(KEY, JSON.stringify(info)); } catch (e) {} }
  }
  window.applySite = i => apply(i, true);

  apply(info, false);
  fetch('../backend/index.php?api=site', { cache: 'no-store' })
    .then(r => r.json()).then(j => apply(j, true)).catch(() => {});
})();
