'use strict';
// Chỉ tăng tiện ích giao diện; các form vẫn gửi PHP/POST, không tự ghi dữ liệu.
const staffPanel=document.querySelector('#staff-login');
function openStaffPanel(){
  if(!staffPanel)return;
  staffPanel.open=true;
  staffPanel.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'center'});
  staffPanel.querySelector('input[name="username"]')?.focus({preventScroll:true});
}
document.querySelectorAll('[data-open-staff]').forEach(link=>link.addEventListener('click',event=>{if(staffPanel){event.preventDefault();openStaffPanel();}}));
if(location.hash==='#staff-login')openStaffPanel();

const arrivalInput=document.querySelector('[data-arrival-hours]');
if(arrivalInput){
  function validateArrivalHour(){
    const time=arrivalInput.value.split('T')[1];
    arrivalInput.setCustomValidity(time&&(time<'08:00'||time>='22:00')?'Vui lòng chọn giờ đến từ 08:00 đến trước 22:00.':'');
  }
  arrivalInput.addEventListener('input',validateArrivalHour);arrivalInput.addEventListener('change',validateArrivalHour);validateArrivalHour();
}
document.querySelectorAll('[data-guest-form]').forEach(form=>form.addEventListener('submit',()=>{
  if(!form.checkValidity())return;
  const button=form.querySelector('button[type="submit"]');
  if(button){button.dataset.idleLabel=button.innerHTML;button.disabled=true;button.textContent=button.dataset.loadingLabel||'Đang xử lý…';form.setAttribute('aria-busy','true');}
}));
window.addEventListener('pageshow',()=>{
  document.querySelectorAll('[data-guest-form]').forEach(form=>form.removeAttribute('aria-busy'));
  document.querySelectorAll('button[data-idle-label]').forEach(button=>{button.innerHTML=button.dataset.idleLabel;button.disabled=false;});
});
document.querySelectorAll('[data-copy-booking]').forEach(button=>button.addEventListener('click',async()=>{
  const feedback=document.querySelector('[data-copy-feedback]');
  try{await navigator.clipboard.writeText(button.dataset.copyBooking);if(feedback)feedback.textContent='Đã sao chép mã đặt bàn.';}
  catch{if(feedback)feedback.textContent='Bạn có thể chọn mã đặt bàn phía trên để sao chép.';}
}));
const errorNotice=document.querySelector('[data-guest-error] .res-notice.error');
if(errorNotice){errorNotice.setAttribute('tabindex','-1');errorNotice.focus({preventScroll:true});}

const staffErrorNotice=document.querySelector('.entry-staff .guest-notice.error');
if(staffErrorNotice){staffErrorNotice.setAttribute('tabindex','-1');staffErrorNotice.focus();}
