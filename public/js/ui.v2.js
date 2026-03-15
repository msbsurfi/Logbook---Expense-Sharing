(function(window, document){
  'use strict';

  const themeKey = 'halkhata_theme';
  function applyTheme(mode){
    if(mode === 'dark'){
      document.documentElement.setAttribute('data-theme','dark');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
  }
  function getSavedTheme(){
    const saved = localStorage.getItem(themeKey);
    if(saved) return saved;
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    return prefersDark ? 'dark' : 'light';
  }
  function toggleTheme(){
    const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    localStorage.setItem(themeKey, next);
  }

  const toastRoot = (() => {
    let el = document.querySelector('.toast-stack');
    if(!el){
      el = document.createElement('div');
      el.className = 'toast-stack';
      document.body.appendChild(el);
    }
    return el;
  })();

  function showToast(title, msg, type='success', ttl=6000){
    const box = document.createElement('div');
    box.className = `toast ${type}`;
    box.innerHTML = `<div style="width:40px;display:grid;place-items:center;">
      ${type==='success'
        ? '<i class="fa-solid fa-circle-check" style="color:#16a34a;font-size:20px;"></i>'
        : '<i class="fa-solid fa-circle-exclamation" style="color:#dc2626;font-size:20px;"></i>'}
      </div>
      <div style="flex:1">
        <p class="title">${title}</p>
        <p class="msg">${msg}</p>
      </div>
      <button aria-label="Close" style="background:none;border:none;color:inherit;cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>`;
    const closeBtn = box.querySelector('button');
    closeBtn.addEventListener('click', () => { box.remove(); });
    toastRoot.appendChild(box);
    setTimeout(() => {
      box.style.opacity='0';
      box.style.transform='translateY(8px)';
      setTimeout(()=> box.remove(), 350);
    }, ttl);
  }

  function initDisableOnClick(){
    document.addEventListener('click', function(e){
      const btn = e.target.closest('.disable-on-click');
      if(!btn) return;
      if(btn.dataset.disabled === '1'){ e.preventDefault(); return; }
      btn.dataset.disabled = '1';
      btn.setAttribute('disabled','disabled');
      const spinner = document.createElement('span');
      spinner.className = 'spinner';
      btn.appendChild(spinner);
    }, true);
  }

  function initMobileNav(){
    const toggle = document.getElementById('mobile-nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    if(!toggle || !navLinks) return;
    toggle.addEventListener('click', () => {
      navLinks.classList.toggle('open');
    });
  }

  function initDataTables(){
    if(typeof $ === 'undefined' || !$.fn.DataTable) return;
    document.querySelectorAll('table.datatable').forEach(tbl => {
      if($(tbl).hasClass('dt-inited')) return;
      $(tbl).DataTable({
        responsive:true,
        pageLength:10,
        lengthMenu:[5,10,25,50],
        order: [],
        language:{ searchPlaceholder:'Search...', search:'', lengthMenu:'Show _MENU_ entries', info:'Showing _START_ to _END_ of _TOTAL_' },
        dom:"<'row'<'col-sm-6'l><'col-sm-6'f>>" + "rt" + "<'row'<'col-sm-6'i><'col-sm-6'p>>"
      });
      $(tbl).addClass('dt-inited');
    });
  }

  function pollNotifications(){
    const btn = document.getElementById('notifications-btn');
    if(!btn) return;
    fetch('/notifications/unread-count')
      .then(r => r.json())
      .then(data => {
        if(data.unread && data.unread > 0){
          btn.innerHTML = `<i class="fa-solid fa-bell"></i><span style="position:absolute;top:4px;right:4px;background:#dc2626;color:#fff;font-size:10px;padding:2px 6px;border-radius:12px;">${data.unread}</span>`;
        } else {
          btn.innerHTML = `<i class="fa-solid fa-bell"></i>`;
        }
      }).catch(()=>{});
  }

  function showGlobalLoading(on=true){
    const overlay = document.getElementById('global-loading-overlay');
    if(!overlay) return;
    overlay.classList.toggle('active', !!on);
  }

  document.addEventListener('DOMContentLoaded', function(){
    applyTheme(getSavedTheme());
    const themeToggle = document.getElementById('theme-toggle-btn');
    if(themeToggle){
      themeToggle.addEventListener('click', () => {
        toggleTheme();
        themeToggle.classList.add('pulse');
        setTimeout(()=>themeToggle.classList.remove('pulse'), 400);
      });
    }
    initDisableOnClick();
    initMobileNav();
    initDataTables();
    pollNotifications();
    setInterval(pollNotifications, 15000);
  });

  window.HalkhataUI = {
    showToast,
    toggleTheme,
    showGlobalLoading,
    initDataTables
  };

})(window, document);