'use strict';
// Chỉ hỗ trợ điều hướng; mọi thao tác đặt bàn dùng form PHP/PDO thật.
const resSidebar=document.querySelector('#sidebar');
document.querySelectorAll('[data-menu-open]').forEach(b=>b.addEventListener('click',()=>{resSidebar?.classList.add('open');document.body.classList.add('menu-open');b.setAttribute('aria-expanded','true');}));
document.querySelectorAll('[data-menu-close]').forEach(b=>b.addEventListener('click',()=>{resSidebar?.classList.remove('open');document.body.classList.remove('menu-open');document.querySelector('[data-menu-open]')?.setAttribute('aria-expanded','false');}));
document.querySelector('#branch-select')?.addEventListener('change',e=>e.target.form.requestSubmit());
document.addEventListener('keydown',e=>{if(e.key==='Escape'){resSidebar?.classList.remove('open');document.body.classList.remove('menu-open');document.querySelector('[data-menu-open]')?.setAttribute('aria-expanded','false');}});

const resCancelDialog=document.querySelector("#res-cancel-dialog");
document.querySelector("[data-cancel-open]")?.addEventListener("click",()=>resCancelDialog?.showModal());
document.querySelector("[data-cancel-close]")?.addEventListener("click",()=>resCancelDialog?.close());
