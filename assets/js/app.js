'use strict';

// Phần lớn tương tác trong file này minh họa giao diện; POS gửi đơn thật qua module goi-mon.
const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
const escapeHtml = value => String(value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money = value => Math.round(value).toLocaleString('vi-VN') + 'đ';
const number = value => Number(String(value).replace(/[^0-9]/g, '')) || 0;
const normalize = value => String(value).toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
const sidebar = $('#sidebar'), menuButton = $('[data-menu-open]');
const modalRoot = $('#modal-root');
let modalContext = null, lastFocus = null, guests = 4, selectedCartIndex = null, selectedCartItemId = null;
const reservations = [
    {id:'RSV-0628', name:'Nguyễn Minh Anh', phone:'0903221884', guests:4, time:'18:30', branch:'Thảo Điền', status:'Chờ xác nhận', table:null},
    {id:'RSV-0629', name:'Trần Hải Yến', phone:'0938450112', guests:6, time:'19:00', branch:'Thảo Điền', status:'Chờ xác nhận', table:null},
    {id:'RSV-0630', name:'Lê Hoàng Nam', phone:'0912773006', guests:2, time:'19:30', branch:'Thảo Điền', status:'Đã xác nhận', table:null},
];
const menu = [
    {name:'Gỏi cuốn tôm thịt',price:89000,category:'Khai vị'},
    {name:'Bò nướng lá lốt',price:149000,category:'Khai vị'},
    {name:'Cá chẽm sốt chanh',price:249000,category:'Món chính'},
    {name:'Bò lúc lắc',price:229000,category:'Món chính'},
    {name:'Cơm chiên hải sản',price:169000,category:'Món chính'},
    {name:'Lẩu nấm đặc biệt',price:329000,category:'Món chính'},
];
let cart = [{name:menu[0].name,qty:2,price:89000},{name:menu[2].name,qty:1,price:249000},{name:'Nước suối',qty:2,price:20000}];
let activeCategory = 'Tất cả', kitchenFilter = 'Tất cả';

function toast(message) {
    const node = document.createElement('div'); node.className = 'toast'; node.textContent = message;
    $('#toast-stack').append(node); setTimeout(() => node.remove(), 6000);
}
function toggleMenu(open) {
    sidebar?.classList.toggle('open', open); document.body.classList.toggle('menu-open', open);
    menuButton?.setAttribute('aria-expanded', String(open));
    (open ? $('[data-menu-close]', sidebar) : menuButton)?.focus();
}
function selectButton(button, selector) {
    $$(selector).forEach(item => {
        const selected = item === button; item.classList.toggle('btn-primary', selected);
        item.classList.toggle('btn-ghost', !selected); item.setAttribute('aria-pressed', String(selected));
    });
}
function closeModal() {
    if (modalContext?.kind === 'Hủy món') selectedCartItemId = null;
    modalRoot.replaceChildren(); modalContext = null; document.body.style.overflow = '';
    if (lastFocus?.isConnected) lastFocus.focus();
}
function showModal(fragment, context) {
    if (!modalRoot.childElementCount) lastFocus = document.activeElement;
    modalRoot.replaceChildren(fragment); modalContext = context;
    document.body.style.overflow = 'hidden';
    const dialog = $('.modal', modalRoot); dialog?.setAttribute('tabindex','-1');
    if (context.kind !== 'Gửi bếp') {
        const help = document.createElement('p'); help.className='modal-help';
        help.textContent='Bản giao diện: thao tác chỉ thay đổi dữ liệu mẫu trên trang hiện tại, chưa ghi MySQL.';
        $('.modal-body', modalRoot)?.append(help);
    }
    ($('input, select, textarea', modalRoot) || dialog)?.focus();
}
function customModal(title, content, action, onSubmit) {
    const template = document.createElement('template');
    template.innerHTML = `<div class="modal-backdrop" data-backdrop><div class="modal" role="dialog" aria-modal="true" aria-label="${escapeHtml(title)}" tabindex="-1"><div class="modal-head"><div><span class="eyebrow">F&B Nodes</span><h2 class="title title-md">${escapeHtml(title)}</h2></div><button type="button" class="btn btn-ghost icon-btn" data-close aria-label="Đóng">×</button></div><form class="modal-form"><div class="modal-body">${content}</div><div class="modal-actions"><button type="button" class="btn btn-secondary" data-close>Hủy bỏ</button><button type="button" class="btn btn-primary" data-modal-submit>${escapeHtml(action)}</button></div></form></div></div>`;
    showModal(template.content.cloneNode(true), {kind:title,onSubmit});
}
function field(label, control) {return `<label class="field"><span class="field-label">${escapeHtml(label)}</span>${control}</label>`;}
function input(name, type='text', value='', required=true) {return `<input class="control" name="${name}" type="${type}" value="${escapeHtml(value)}" ${required?'required':''}>`;}
function modalError(message) {
    let node = $('.modal-error',modalRoot);
    if (!node) {node=document.createElement('p');node.className='modal-error';node.setAttribute('role','alert');$('.modal-body',modalRoot).prepend(node);}
    node.textContent=message;
}
function emptyTables(minSeats=1) {
    return $$('.table-empty').filter(card => number($('small',card)?.textContent) >= minSeats);
}
function tableOptions(cards) {
    return cards.map(card=>{const id=$('.table-surface>span',card).textContent;return `<option value="${escapeHtml(id)}">${escapeHtml(id)} · ${escapeHtml($('small',card).textContent)}</option>`;}).join('');
}
function setTable(id, type, status) {
    const card=$$('.table-card').find(item=>$('.table-surface>span',item)?.textContent===id); if(!card)return;
    card.classList.remove('table-empty','table-reserved','table-serving');card.classList.add('table-'+type);
    $('.table-surface strong',card).textContent=status; updateLegend();
}
function updateLegend() {
    const spans=$$('.actionbar .legend > span');
    ['empty','reserved','serving'].forEach((status,index)=>{if(spans[index])spans[index].lastChild.textContent=['Trống ','Đã đặt ','Đang phục vụ '][index]+$$('.table-'+status).length;});
}
function requestState(name, text, tone) {
    const row=$$('.request').find(item=>$('.request-info>strong',item)?.textContent===name);if(!row)return;
    const actions=$('.request-actions, .request-state',row); actions.className='request-state';
    actions.innerHTML=`<span class="badge badge-${tone}">${escapeHtml(text)}</span>`;
    if(text.startsWith('Đã xác nhận')){
        const cancel=document.createElement('button');cancel.type='button';cancel.className='btn btn-ghost compact danger-text';cancel.dataset.modal='Hủy đặt bàn · '+name;cancel.textContent='Hủy';actions.append(cancel);
    }
}
function openReservationConfirm(name) {
    const reservation=reservations.find(item=>item.name===name); if(!reservation||reservation.status!=='Chờ xác nhận')return;
    const cards=emptyTables(reservation.guests);
    customModal('Xác nhận đặt bàn', `<p class="modal-help">${escapeHtml(name)} · ${reservation.guests} khách · ${reservation.time}</p>`+field('Bàn phù hợp',`<select name="table" class="control" required><option value="">Chọn bàn</option>${tableOptions(cards)}</select>`), 'Xác nhận đặt bàn', form=>{
        const id=form.elements.table.value;
        if(!emptyTables(reservation.guests).some(card=>$('.table-surface>span',card).textContent===id)){modalError('Bàn không còn phù hợp. Vui lòng kiểm tra lại.');return false;}
        reservation.status='Đã xác nhận';reservation.table=id;setTable(id,'reserved','Đã đặt · '+reservation.time);requestState(name,'Đã xác nhận · '+id,'success');return true;
    });
    if(!cards.length)modalError('Hiện không có bàn phù hợp. Đơn vẫn chờ xác nhận.');
}
function createCounterBooking() {
    const options=tableOptions(emptyTables());
    customModal('Tạo đặt bàn tại quầy',field('Chọn bàn trống',`<select class="control" name="table" required><option value="">Chọn bàn</option>${options}</select>`)+field('Ghi chú', '<textarea class="control textarea" name="note" placeholder="Ghi chú phục vụ nếu có"></textarea>'), 'Tạo đơn tại quầy', form=>{
        const id=form.elements.table.value;
        if(!emptyTables().some(card=>$('.table-surface>span',card).textContent===id)){modalError('Bàn không còn trống. Vui lòng chọn bàn khác.');return false;}
        setTable(id,'serving','Đang phục vụ');return true;
    });
}
function openModal(kind) {
    if(kind==='Tạo đặt bàn tại quầy'){createCounterBooking();return;}
    if(kind.startsWith('Xem tất cả')&&document.body.dataset.page==='floor'){
        customModal('Danh sách đặt bàn', `<div class="summary-list">${reservations.map(r=>`<div><span>${escapeHtml(r.name)} · ${r.guests} khách · ${r.time}</span><span class="badge badge-${r.status==='Chờ xác nhận'?'warning':r.status==='Đã hủy'?'danger':'success'}">${escapeHtml(r.status)}</span></div>`).join('')}</div>`,'Đóng',()=>true);return;
    }
    if(kind==='Thêm món ăn'||kind.startsWith('Chi tiết món ăn')){
        const dish=menu.find(d=>kind.endsWith(d.name));
        customModal(kind,field('Tên món',input('name','text',dish?.name||''))+field('Nhóm món','<select name="category" class="control"><option>Khai vị</option><option>Món chính</option><option>Tráng miệng</option><option>Đồ uống</option></select>')+field('Đơn giá',`<input class="control" type="number" min="0" name="price" value="${dish?.price||0}" required>`)+field('Mô tả','<textarea class="control textarea" name="description"></textarea>'),dish?'Lưu thay đổi':'Thêm món',form=>{
            if(!form.elements.name.value.trim()){modalError('Vui lòng nhập tên món.');return false;}
            const card=$$('[data-edit-dish]').find(c=>c.dataset.editDish===dish?.name);
            if(card){$('.menu-info>strong',card).textContent=form.elements.name.value;$('.menu-info>b',card).textContent=money(form.elements.price.value);}
            else if($('.menu-grid')){const newCard=$('.menu-card').cloneNode(true);newCard.dataset.editDish=form.elements.name.value;$('.menu-info>strong',newCard).textContent=form.elements.name.value;$('.menu-info>b',newCard).textContent=money(form.elements.price.value);$('.menu-info>span:not(.badge)',newCard).textContent=form.elements.category.value;$('.menu-grid').append(newCard);}
            return true;
        });return;
    }
    let template=$$('template[data-modal-key]').find(node=>node.dataset.modalKey===kind);
    if(!template&&kind.startsWith('Hủy đặt bàn'))template=$('template[data-modal-key^="Hủy đặt bàn"]');
    if(!template){customModal(kind,'<p class="modal-help">Nội dung này đang ở chế độ xem mẫu. Chức năng xử lý sẽ được nối với PHP và MySQL trong module tương ứng.</p>','Đóng',()=>true);return;}
    showModal(template.content.cloneNode(true),{kind});
    if(kind==='Hủy món'){
        const item=window.posCartForConfirmation?.().find(entry=>entry.id===selectedCartItemId);
        if(!item){closeModal();return;}
        const dialog=$('#modalHuyMon',modalRoot);
        const quantity=$('[name="cancelQuantity"]',modalRoot);
        const reason=$('[name="cancellationReason"]',modalRoot);
        const note=$('[name="cancellationNote"]',modalRoot);
        dialog?.setAttribute('aria-labelledby','modalHuyMonTitle');
        const title=$('#modalHuyMon h2',modalRoot);
        if(title)title.id='modalHuyMonTitle';
        $('.alert-box strong',modalRoot).textContent=`${item.name} × ${item.qty}`;
        if(quantity){quantity.max=String(item.qty);quantity.value=String(item.qty);}
        const updateOtherReason=()=>{
            if(!reason||!note)return;
            const other=reason.value==='Lý do khác...';
            note.required=other;
            note.setCustomValidity(other&&!note.value.trim()?'Vui lòng nêu chi tiết lý do hủy.':'');
        };
        reason?.addEventListener('change',updateOtherReason);
        note?.addEventListener('input',updateOtherReason);
        updateOtherReason();
    } else if(kind.startsWith('Hủy đặt bàn')){
        const name=kind.split('·')[1]?.trim();$('.modal-head h2',modalRoot).textContent='Hủy yêu cầu đặt bàn';
        $('.alert-box strong',modalRoot).textContent=name;
        $('.alert-box span',modalRoot).textContent='Hủy yêu cầu và chỉ giải phóng phần giữ bàn của yêu cầu này.';
    }
    if(kind==='Báo thiếu NVL'){
        const body=$('.modal-body',modalRoot); body.insertAdjacentHTML('afterbegin',field('Món không thể thực hiện','<input class="control" name="dish" placeholder="Tên món" required>'));
    }
}
window.openPosCancellation = itemId => {
    const orderCart=window.posCartForConfirmation?.()??[];
    const index=orderCart.findIndex(item=>item.id===itemId);
    if(index<0)return;
    selectedCartItemId=itemId;
    openModal('Hủy món');
};
async function submitModal() {
    const form=$('form',modalRoot);if(form&&!form.reportValidity())return;
    const {kind,onSubmit}=modalContext||{};
    if(kind==='Gửi bếp'){
        const button=$('[data-modal-submit]',modalRoot);
        if(typeof window.submitPosOrder!=='function'){modalError('Không thể khởi tạo chức năng gửi đơn. Vui lòng tải lại trang.');return;}
        const originalText=button?.textContent;
        if(button){button.disabled=true;button.textContent='Đang gửi...';}
        try{
            const result=await window.submitPosOrder();
            if(!result?.message)throw new Error('Máy chủ không xác nhận đã lưu đơn.');
            closeModal();toast(result.message);return;
        }catch(error){
            modalError(error instanceof Error?error.message:'Không thể gửi đơn xuống bếp. Vui lòng thử lại.');
            return;
        }finally{
            if(button?.isConnected){button.disabled=false;button.textContent=originalText;}
        }
    }
    if(onSubmit){if(onSubmit(form)===false)return;}
    else if(kind?.startsWith('Hủy đặt bàn')){
        const r=reservations.find(item=>item.name===kind.split('·')[1]?.trim());
        if(r){r.status='Đã hủy';if(r.table)setTable(r.table,'empty','Trống');requestState(r.name,'Đã hủy','danger');}
    } else if(kind==='Hủy món'&&selectedCartItemId!==null){
        const quantity=Number(form?.elements.namedItem('cancelQuantity')?.value);
        const reason=form?.elements.namedItem('cancellationReason')?.value.trim()??'';
        const note=form?.elements.namedItem('cancellationNote')?.value.trim()??'';
        if(!reason){modalError('Vui lòng chọn lý do hủy món.');return;}
        if(reason==='Lý do khác...'&&!note){modalError('Vui lòng nêu chi tiết lý do hủy.');return;}
        if(!Number.isInteger(quantity)||quantity<1){modalError('Số lượng hủy không hợp lệ.');return;}
        if(!window.cancelPosCartItem?.(selectedCartItemId,quantity,reason,note)){
            modalError('Món hoặc số lượng cần hủy không còn hợp lệ. Vui lòng kiểm tra lại đơn.');
            return;
        }
    }
    else if(kind==='Lưu định mức'){
        const rows=$$('.ingredient-row');if(!rows.length||rows.some(row=>!$('select',row)?.value||!Number.isFinite(Number($('input',row)?.value))||Number($('input',row)?.value)<=0)){modalError('Định mức phải có ít nhất một nguyên liệu, số lượng lớn hơn 0.');return;}
        const names=rows.map(row=>$('select',row).value);if(new Set(names).size!==names.length){modalError('Nguyên vật liệu không được trùng trong cùng một công thức.');return;}
    } else if(kind==='Thêm nhân viên'){
        const fields=$$('input',form);if(fields.some(input=>!input.value.trim())){modalError('Vui lòng nhập đủ họ tên, CCCD và số điện thoại.');return;}
        if(!/^\d{12}$/.test(fields[1].value)){modalError('CCCD phải có 12 chữ số.');return;}
        const tbody=$('.data-table tbody');if(tbody){const row=$('tr',tbody).cloneNode(true);const cells=$$('td',row);cells[0].textContent='NV-MẪU';cells[1].textContent=fields[0].value;cells[4].textContent=fields[2].value;const btn=$('[data-modal]',row);btn.dataset.modal='Hồ sơ nhân viên · '+fields[0].value;tbody.append(row);}
    } else if(kind==='Xuất bảng công') {exportCsv();}
    closeModal();toast('Đã thực hiện thao tác trên bản giao diện mẫu. Chưa lưu vào MySQL.');
}
function exportCsv(){
    const rows=$$('.data-table tr').map(row=>$$('th,td',row).map(cell=>'"'+cell.textContent.trim().replace(/"/g,'""')+'"').join(','));
    const blob=new Blob(['\ufeff'+rows.join('\r\n')],{type:'text/csv;charset=utf-8'});const link=document.createElement('a');link.href=URL.createObjectURL(blob);link.download='bang-cong-mau.csv';link.click();setTimeout(()=>URL.revokeObjectURL(link.href),1000);
}
function renderCart(){
    const root=$('.cart-items');if(!root)return;
    root.innerHTML=cart.map((item,index)=>`<div class="cart-item"><div class="item-count">${item.qty}</div><div class="item-detail"><strong>${escapeHtml(item.name)}</strong><span>${money(item.qty*item.price)}</span></div><div class="qty"><button class="btn btn-ghost" type="button" data-qty-minus="${index}">−</button><strong>${item.qty}</strong><button class="btn btn-ghost" type="button" data-qty-plus="${index}">+</button></div></div>`).join('');
    const total=cart.reduce((sum,item)=>sum+item.qty*item.price,0);$$('.cart-summary strong').forEach((node,index)=>node.textContent=index===1?'0đ':money(total));
    const send=$('.send-kitchen span');if(send)send.textContent=cart.length+' món · '+money(total);
}
function filterCatalog(){
    const term=normalize($('.catalog-tools input')?.value||'');
    $$('.menu-card').forEach(card=>{const category=$('.menu-info>span:not(.badge)',card)?.textContent;card.hidden=!(activeCategory==='Tất cả'||category===activeCategory)||!normalize(card.textContent).includes(term);});
    showEmpty($('.menu-grid'),$$('.menu-card').some(card=>!card.hidden));
}
function showEmpty(container, any){
    if(!container)return;let message=$('.empty-filter',container);
    if(!any&&!message){message=document.createElement('p');message.className='empty-filter';message.textContent='Không có dữ liệu phù hợp trong mẫu.';container.append(message);}else if(any)message?.remove();
}
function filterTable(){
    const search=$('.data-toolbar input')||$('.toolbar-actions input');const term=normalize(search?.value||'');
    const select=$('.toolbar-actions select');const role=select&&select.options.length>1?select.value:'';
    $$('.data-table tbody tr').forEach(row=>row.hidden=!normalize(row.textContent).includes(term)||(role&&!role.startsWith('Tất cả')&&!row.textContent.includes(role)));
}
function filterTickets(){
    $$('.ticket').forEach(ticket=>{const state=$('.ticket-status .badge',ticket).textContent;ticket.hidden=kitchenFilter!=='Tất cả'&&!state.includes(kitchenFilter==='Đang chế biến'?'đang chế biến':kitchenFilter==='Chờ trả món'?'Chờ trả món':'Chờ tiếp nhận');});
    showEmpty($('.ticket-grid'),$$('.ticket').some(ticket=>!ticket.hidden));
}
function paymentMethod(method){
    $$('.payment-methods .card').forEach((card,index)=>card.classList.toggle('selected',index===(method==='cash'?0:1)));
    const current=$('.qr-box,.cash-box');if(!current)return;
    if(method==='cash'){
        current.className='card cash-box';current.innerHTML=field('Tiền khách đưa','<input class="control" type="number" step="1000" min="0" value="600000" id="cash-received">')+'<div><span>Tiền thừa</span><strong id="cash-change">95.640đ</strong></div>';
    }else{
        current.className='card qr-box';current.innerHTML='<span class="badge badge-info">ĐANG CHỜ THANH TOÁN</span><div class="fake-qr" aria-label="QR minh họa">'+Array.from({length:49},(_,i)=>`<i class="${i%3===0||i%7===0?'fill':''}"></i>`).join('')+'</div><div class="qr-amount"><span>Số tiền chính xác</span><strong>504.360đ</strong></div><p>QR minh họa giao diện · Không dùng để chuyển tiền</p>';
    }
}
function lookupBooking(){
    const input=$('.search-card input');const phone=input.value.replace(/\s/g,'');input.setCustomValidity(/^0\d{9}$/.test(phone)?'':'Nhập số điện thoại gồm 10 chữ số, bắt đầu bằng 0.');
    if(!input.reportValidity())return;
    let result=$('#lookup-result');if(!result){result=document.createElement('div');result.id='lookup-result';$('.empty-lookup')?.replaceWith(result);}
    const matches=reservations.filter(r=>r.phone===phone);
    result.innerHTML='<div class="result-head"><h3 class="title title-sm">Đặt bàn gần đây</h3><span class="muted">'+matches.length+' kết quả mẫu</span></div>'+(matches.length?'<div class="reservation-results">'+matches.map(r=>`<div class="card reservation-card"><div><span class="overline">MÃ ĐẶT BÀN</span><strong>#${r.id}</strong></div><div><span class="overline">CHI NHÁNH</span><strong>Nodes · ${r.branch}</strong></div><div><span class="overline">THỜI GIAN</span><strong>Hôm nay · ${r.time}</strong></div><div><span class="overline">SỐ KHÁCH</span><strong>${r.guests} người</strong></div><span class="badge badge-${r.status==='Chờ xác nhận'?'warning':'success'}">${r.status}</span></div>`).join('')+'</div>':'<div class="empty-lookup"><strong>Không tìm thấy đơn đặt bàn theo số điện thoại đã nhập.</strong></div>');
}
function submitBooking(){
    const wrap=$('.booking-form-wrap');const fields=$$('input',wrap);const date=fields.find(i=>i.type==='date');const name=fields.find(i=>i.placeholder==='Nguyễn Minh Anh');const phone=fields.find(i=>i.placeholder==='0903 123 456');
    name.required=true;phone.required=true;date.required=true;
    name.setCustomValidity(name.value.trim()?'':'Vui lòng nhập họ tên.');phone.setCustomValidity(/^0\d{9}$/.test(phone.value.replace(/\s/g,''))?'':'Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.');
    const selectedTime=$$('select',wrap)[1].value;
    date.setCustomValidity(new Date(date.value+'T'+selectedTime)>new Date()?'':'Ngày giờ đến phải ở tương lai.');
    if([name,phone,date].some(i=>!i.reportValidity()))return;
    let status=$('.success-banner',wrap);if(!status){status=document.createElement('div');status.className='success-banner';$('.form-grid',wrap).before(status);}
    status.innerHTML='<strong>Đã kiểm tra thông tin mẫu</strong><span>Yêu cầu sẽ ở trạng thái Chờ xác nhận, chưa gán hoặc giữ bàn. Chưa gửi tới database.</span>';
    toast('Biểu mẫu hợp lệ. Cần nối PHP/PDO để lưu yêu cầu đặt bàn thật.');
}

document.addEventListener('click',event=>{
    const target=event.target.closest('button,[data-add-dish],[data-edit-dish],[data-method],[data-dish],[data-modal],.table-card');
    if(!target)return;
    if(target.matches('[data-menu-open]')){toggleMenu(true);return;}
    if(target.matches('[data-menu-close]')){toggleMenu(false);return;}
    if(target.matches('[data-close]')){closeModal();return;}
    if(target.matches('[data-modal-submit]')){submitModal();return;}
    if(target.dataset.modal){openModal(target.dataset.modal);return;}
    if(target.dataset.confirmReservation){openReservationConfirm(target.dataset.confirmReservation);return;}
    if(target.dataset.area){
        selectButton(target,'[data-area]');const area=target.dataset.area;
        $$('.table-card').forEach((card,i)=>card.hidden=area==='Ngoài trời'&&i<8);
        const visible=$$('.table-card').filter(card=>!card.hidden);const title=$('.floor-main .title');title.textContent='Sơ đồ khu vực '+area.toLocaleLowerCase('vi');
        $('.floor-main .muted').textContent=visible.length+' bàn · '+visible.reduce((sum,c)=>sum+number($('small',c).textContent),0)+' chỗ ngồi';return;
    }
    if(target.dataset.category){activeCategory=target.dataset.category;selectButton(target,'[data-category]');filterCatalog();return;}
    if(target.dataset.editDish){openModal('Chi tiết món ăn · '+target.dataset.editDish);return;}
    if(target.dataset.addDish){if(target.classList.contains('disabled-card')){openModal('Báo thiếu NVL');return;}const dish=menu.find(d=>d.name===target.dataset.addDish);const existing=cart.find(i=>i.name===dish.name);if(existing)existing.qty++;else cart.push({...dish,qty:1});renderCart();return;}
    if(target.hasAttribute('data-qty-plus')){cart[Number(target.dataset.qtyPlus)].qty++;renderCart();return;}
    if(target.hasAttribute('data-qty-minus')){const index=Number(target.dataset.qtyMinus);if(cart[index].qty<=1){selectedCartIndex=index;openModal('Hủy món');}else{cart[index].qty--;renderCart();}return;}
    if(target.dataset.orderMode){selectButton(target,'[data-order-mode]');$('.cart-head .title').textContent=target.dataset.orderMode==='Mang về'?'Đơn mang về':'Bàn A06';return;}
    if(target.dataset.method){paymentMethod(target.dataset.method);return;}
    if(target.hasAttribute('data-demo-payment')){
        const cash=$('#cash-received');if(cash&&Number(cash.value)<504360){toast('Tiền khách đưa chưa đủ thanh toán.');cash.focus();return;}
        customModal('Xác nhận đã nhận tiền','<p class="modal-help">Tổng tiền mẫu: <strong>504.360đ</strong>. Với QR, nhân viên cần đối chiếu đã nhận đúng số tiền. Bản giao diện không xác nhận giao dịch ngân hàng.</p>','Xem trạng thái mẫu',()=>{
            const button=$('[data-demo-payment]');if(button){const message=document.createElement('div');message.className='payment-success';message.innerHTML='<span class="success-check">✓</span><div><strong>Đã xác nhận trên bản mẫu</strong><span>Chưa ghi nhận thanh toán vào database.</span></div>';button.replaceWith(message);}return true;
        });return;
    }
    if(target.dataset.kdsFilter){kitchenFilter=target.dataset.kdsFilter;selectButton(target,'[data-kds-filter]');filterTickets();return;}
    if(target.dataset.ticket){const ticket=target.closest('.ticket');const badge=$('.ticket-status .badge',ticket);const accepted=badge.textContent.includes('đang chế biến');badge.textContent=accepted?'Hoàn thành · Chờ trả món':'Bếp đang chế biến';badge.className='badge badge-'+(accepted?'success':'info');target.textContent=accepted?'Đã hoàn thành':'Hoàn thành';target.disabled=accepted;filterTickets();return;}
    if(target.dataset.shift){selectButton(target,'[data-shift]');$('.table-footer>span').textContent='Ca '+target.dataset.shift.toLocaleLowerCase('vi')+' · Hiển thị 5 dòng dữ liệu mẫu';return;}
    if(target.dataset.period){selectButton(target,'[data-period]');$('.chart-card .muted').textContent=({Tháng:'30 ngày gần nhất',Quý:'6 tháng gần nhất',Năm:'12 tháng gần nhất'})[target.dataset.period]+' · triệu đồng · dữ liệu mẫu';return;}
    if(target.hasAttribute('data-guests')){guests=Math.max(1,guests+Number(target.dataset.guests));$('.stepper strong').textContent=guests+' người';return;}
    if(target.hasAttribute('data-search-booking')){lookupBooking();return;}
    if(target.hasAttribute('data-submit-booking')){submitBooking();return;}
    if(target.dataset.dish){$$('[data-dish]').forEach(item=>item.classList.toggle('active',item===target));$('.recipe-head .title').textContent=target.dataset.dish;return;}
    if(target.hasAttribute('data-add-ingredient')){const row=$('.ingredient-row').cloneNode(true);$('input',row).value='';$('select',row).innerHTML='<option value="">Chọn nguyên liệu</option><option>Muối biển</option><option>Dầu ăn</option><option>Chanh vàng</option>';$('.ingredients').append(row);updateIngredients();return;}
    if(target.hasAttribute('data-remove-ingredient')){target.closest('.ingredient-row').remove();updateIngredients();return;}
    if(target.matches('.table-card')){customModal('Chi tiết bàn '+$('.table-surface>span',target).textContent,`<div class="detail-metrics"><div><span>Trạng thái</span><strong>${escapeHtml($('.table-surface strong',target).textContent)}</strong></div><div><span>Sức chứa</span><strong>${escapeHtml($('small',target).textContent)}</strong></div></div>`,'Đóng',()=>true);return;}
    const text=target.textContent.replace(/\s+/g,' ').trim();
    if(text.startsWith('Gửi bếp')){const orderCart=window.posCartForConfirmation?.()??cart;if(!orderCart.length){toast('Đơn cần có ít nhất một món.');return;}openModal('Gửi bếp');const summary=$('.order-confirm',modalRoot);if(summary)$('h3',summary).textContent=orderCart.length+' món · '+orderCart.reduce((s,i)=>s+i.qty,0)+' phần';const badge=$('.order-confirm .badge',modalRoot);const pos=$('.pos-layout');if(badge&&pos)badge.textContent='BÀN '+(pos.dataset.tableNumber||pos.dataset.tableId||'');const list=$('.summary-list',modalRoot);if(list)list.innerHTML=orderCart.map(i=>`<div><span>${i.qty}× ${escapeHtml(i.name)}</span><strong>${money(i.qty*i.price)}</strong></div>`).join('');return;}
    if(text.includes('ghi chú')){openModal('Thêm ghi chú cho bếp');return;}
    if(text==='Báo thiếu NVL'){openModal('Báo thiếu NVL');return;}
    if(text==='Lưu định mức'){openModal('Lưu định mức');return;}
    if(text.startsWith('Xem tất cả')){openModal('Xem tất cả');return;}
    if(text==='Xuất bảng công'){openModal(text);return;}
    if(text==='Duyệt'||text==='Duyệt & chốt lương'){openModal(text);return;}
    if(text==='•••'||text==='···'){openModal('•••');return;}
    if(target.closest('.table-footer')){toast('Mẫu hiện có 5 dòng dữ liệu. Phân trang thật sẽ nối với MySQL.');return;}
    if(text==='Chỉnh sửa'){customModal('Thông tin khách tại bàn',field('Tên khách',input('guest','text','Khách lẻ'))+field('Số khách',input('count','number','4')),'Áp dụng',form=>{if(Number(form.elements.count.value)<1){modalError('Số khách phải lớn hơn 0.');return false;}$('.guest-line>span').textContent=form.elements.guest.value+' · '+form.elements.count.value+' khách';return true;});return;}
});
function updateIngredients(){$$('.ingredient-row').forEach((row,i)=>$('.row-index',row).textContent=i+1);const count=$('.recipe-metrics strong');if(count)count.textContent=$$('.ingredient-row').length;}
document.addEventListener('mousedown',event=>{if(event.target.matches('[data-backdrop]'))closeModal();});
document.addEventListener('keydown',event=>{
    if(event.key==='Escape'){if(modalRoot.childElementCount)closeModal();else if(sidebar?.classList.contains('open'))toggleMenu(false);}
    const active=modalRoot.childElementCount?$('.modal',modalRoot):sidebar?.classList.contains('open')?sidebar:null;
    if(event.key==='Tab'&&active){const items=$$('a,button,input,select,textarea,[tabindex="0"]',active).filter(n=>!n.disabled&&n.getClientRects().length);const first=items[0],last=items.at(-1);if(event.shiftKey&&document.activeElement===first){event.preventDefault();last?.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first?.focus();}}
});
document.addEventListener('submit',event=>{if(event.target.closest('#modal-root')){event.preventDefault();submitModal();}});
document.addEventListener('input',event=>{
    event.target.setCustomValidity?.('');
    if(event.target.closest('.catalog-tools'))filterCatalog();
    if(event.target.closest('.data-toolbar,.toolbar-actions'))filterTable();
    if(event.target.closest('.dish-search')){const term=normalize(event.target.value);$$('[data-dish]').forEach(row=>row.hidden=!normalize(row.textContent).includes(term));}
    if(event.target.id==='cash-received')$('#cash-change').textContent=Number(event.target.value)>=504360?money(Number(event.target.value)-504360):'Chưa đủ tiền';
    if(event.target.closest('.actionbar')&&document.body.dataset.page==='floor'){
        const term=normalize(event.target.value);$$('.request').forEach(row=>row.hidden=!normalize(row.textContent).includes(term));
        if(!$$('.request').some(row=>!row.hidden))showEmpty($('.request-panel'),false);else showEmpty($('.request-panel'),true);
    }
});
document.addEventListener('change',event=>{
    if(event.target.closest('.toolbar-actions'))filterTable();
    if(event.target.id==='branch-select'){event.target.form?.requestSubmit();}
});
window.matchMedia('(min-width:901px)').addEventListener('change',event=>{if(event.matches){sidebar?.classList.remove('open');document.body.classList.remove('menu-open');menuButton?.setAttribute('aria-expanded','false');}});

// Khởi tạo dữ liệu tương tác và khả năng sử dụng bàn phím.
$$('[data-method],[data-dish],.menu-card,.table-card').forEach(node=>{node.setAttribute('role','button');node.tabIndex=0;node.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();node.click();}});});
$$('[data-method]').forEach(node=>node.setAttribute('aria-label',node.textContent.trim()));
$$('.ticket').forEach(ticket=>{$('.ticket-actions .btn:last-child',ticket).setAttribute('aria-label',$('.ticket-actions .btn:last-child',ticket).textContent+' '+$('.ticket-number',ticket).textContent);});
const lookup=$('.search-card input');if(lookup){lookup.addEventListener('keydown',event=>{if(event.key==='Enter')lookupBooking();});const hint=document.createElement('p');hint.className='field-hint';hint.textContent='Thử dữ liệu mẫu: 0903 221 884 hoặc 0938 450 112.';$('.search-card').append(hint);}
// Chuyển ghi chú giá trị mẫu thành nhãn rõ ràng, không thay đổi bố cục gốc.
const counts=$$('.kds-tabs button b');if(counts.length===4){[3,2,0,1].forEach((n,i)=>counts[i].textContent=n);}
const fullRequests=$$('.request-panel button').find(b=>b.textContent.startsWith('Xem tất cả'));if(fullRequests)fullRequests.textContent='Xem tất cả yêu cầu';
const inventoryStatus=document.body.dataset.page==='inventory'?$('.toolbar-actions select'):null;
if(inventoryStatus){['Còn hàng','Sắp hết','Gần hết hạn','Hết hàng'].forEach(label=>{const option=document.createElement('option');option.textContent=label;inventoryStatus.append(option);});}
$$('.ticket').forEach(ticket=>{if($('.ticket-status .badge',ticket).textContent.includes('Hoàn thành')){const button=$('.ticket-actions .btn:last-child',ticket);button.textContent='Đã hoàn thành';button.disabled=true;}});

// Chỉ khóa tương tác minh họa; quyền thật vẫn kiểm tra ở PHP qua require_permission().
if (document.body.dataset.canEdit === '0' && !['booking','tracking','accounts','password'].includes(document.body.dataset.page)) {
    const selectors = '[data-confirm-reservation], [data-qty-plus], [data-qty-minus], [data-add-dish], [data-edit-dish], [data-demo-payment], [data-ticket], [data-add-ingredient], [data-remove-ingredient], .send-kitchen, .note-button, .recipe-footer button, .approval button';
    $$(selectors).forEach(node=>{ if(node.tagName==='BUTTON')node.disabled=true; else {node.setAttribute('aria-disabled','true');node.style.pointerEvents='none';} });
    $$('main [data-modal]').forEach(button=>{if(!button.dataset.modal.startsWith('Chi tiết'))button.disabled=true;});
}
