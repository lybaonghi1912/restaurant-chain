(() => {
  const initializePosCart = () => {
    const pos = document.querySelector('.pos-layout');
    if (!pos) return;

    const cartRoot = pos.querySelector('.cart-items');
    const sendButton = pos.querySelector('.send-kitchen');
    const searchInput = pos.querySelector('#posSearchDish');
    const categoryButtons = [...pos.querySelectorAll('.category-tabs button')];

    // Dữ liệu món đã gửi bếp từ CSDL
    let sentItems = JSON.parse(pos.dataset.sentItems || '[]');

    let cart = [];
    let activeCategory = 'Tất cả';
    let sendingOrder = false;

    // Modal Hủy món
    const modalHuy = document.getElementById('modalHuyMon');
    const modalTenMonHuy = document.getElementById('modalTenMonHuy');
    const selectLyDoHuy = document.getElementById('selectLyDoHuy');
    const inputLyDoKhac = document.getElementById('inputLyDoKhac');
    const btnDongModalHuy = document.getElementById('btnDongModalHuy');
    const btnXacNhanHuyMon = document.getElementById('btnXacNhanHuyMon');
    let currentCancellingLineId = null;

    const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
    })[char]);

    const normalize = value => String(value)
      .toLocaleLowerCase('vi')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/đ/g, 'd');

    const formatCurrency = value => `${Math.round(value).toLocaleString('vi-VN')}đ`;

    const getDishDetails = card => {
      const name = card.querySelector('.menu-info strong')?.textContent.trim() ?? '';
      const priceText = card.querySelector('.menu-info b')?.textContent ?? '';
      const category = card.dataset.dishCategory
        ?? card.querySelector('.menu-info span:not(.badge)')?.textContent.trim()
        ?? '';

      return {
        id: card.dataset.dishId || name,
        name,
        price: Number(priceText.replace(/[^\d]/g, '')) || 0,
        category,
      };
    };

    function renderCart() {
      if (!cartRoot) return;

      // Render danh sách các món mới chọn; món đã gửi được render trực tiếp từ PHP.
      if (cart.length === 0) {
        cartRoot.innerHTML = `
          ${sentItems.length ? '<h3 class="title title-sm">Món mới chọn</h3>' : ''}
          <p class="cart-empty">Chưa có món mới nào. Nhấp dấu (+) trên thực đơn để gọi thêm.</p>
        `;
      } else {
        cartRoot.innerHTML = `
          <h3 class="title title-sm">Món mới chọn</h3>
          ${cart.map((item, index) => `
            <div class="cart-item" data-id="${escapeHtml(item.id)}">
              <div class="item-count">${item.qty}</div>
              <div class="item-detail">
                <strong>${escapeHtml(item.name)}</strong>
                <span>${formatCurrency(item.price * item.qty)}</span>
                ${item.note ? `<small>${escapeHtml(item.note)}</small>` : ''}
                <button type="button" class="btn btn-ghost btn-edit-item-note" data-edit-item-note="${index}">
                  ${item.note ? 'Sửa ghi chú' : 'Thêm ghi chú'}
                </button>
              </div>
              <div class="qty">
                <button type="button" class="btn btn-ghost btn-qty-minus" data-qty-minus="${index}" aria-label="Giảm">−</button>
                <strong>${item.qty}</strong>
                <button type="button" class="btn btn-ghost btn-qty-plus" data-qty-plus="${index}" aria-label="Tăng">+</button>
                <button type="button" class="btn btn-ghost btn-cancel-item" data-cancel-item="${escapeHtml(item.id)}">Xóa</button>
              </div>
            </div>
          `).join('')}
        `;
      }

      // 3. Tính toán tổng tiền
      const sentSubtotal = sentItems.reduce((sum, item) => sum + item.price * item.qty, 0);
      const pendingSubtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
      const summaryValues = pos.querySelectorAll('.cart-summary strong');
      if (summaryValues[0]) summaryValues[0].textContent = formatCurrency(sentSubtotal);
      if (summaryValues[1]) summaryValues[1].textContent = formatCurrency(pendingSubtotal);
      if (summaryValues[2]) summaryValues[2].textContent = formatCurrency(sentSubtotal + pendingSubtotal);

      // 4. Cập nhật nút Gửi bếp
      const itemCount = cart.reduce((count, item) => count + item.qty, 0);
      const actionLabel = sendButton?.querySelector('[data-send-action]');
      const summaryLabel = sendButton?.querySelector('[data-send-summary]');

      if (actionLabel) {
        actionLabel.textContent = sendingOrder
          ? 'Đang gửi sang bếp...'
          : (pos.dataset.orderId ? 'Gửi thêm món vào bếp' : 'Gửi bếp');
      }
      if (summaryLabel) {
        summaryLabel.textContent = `${itemCount} món · ${formatCurrency(pendingSubtotal)}`;
      }
      if (sendButton) {
        sendButton.disabled = (itemCount === 0 || !pos.dataset.tableId || sendingOrder);
      }
    }

    function filterMenu() {
      const searchTerm = normalize(searchInput?.value ?? '');
      pos.querySelectorAll('.menu-card').forEach(card => {
        const dish = getDishDetails(card);
        const matchesCategory = activeCategory === 'Tất cả'
          || normalize(dish.category) === normalize(activeCategory);
        const matchesSearch = normalize(dish.name).includes(searchTerm);
        card.hidden = !matchesCategory || !matchesSearch;
      });
    }

    // LẮNG NGHE SỰ KIỆN CLICK TOÀN CỤC TRÊN MÀN HÌNH POS
    pos.addEventListener('click', event => {
      const target = event.target;
      if (!(target instanceof Element)) return;

      // Tab chuyển danh mục
      const categoryButton = target.closest('.category-tabs button[data-category]');
      if (categoryButton && pos.contains(categoryButton)) {
        event.stopPropagation();
        activeCategory = categoryButton.dataset.category ?? 'Tất cả';
        categoryButtons.forEach(button => {
          const active = button === categoryButton;
          button.classList.toggle('btn-primary', active);
          button.classList.toggle('btn-ghost', !active);
        });
        filterMenu();
        return;
      }

      // Nút bấm tăng / giảm số lượng món mới
      const quantityButton = target.closest('[data-qty-plus], [data-qty-minus]');
      if (quantityButton && pos.contains(quantityButton)) {
        event.stopPropagation();
        if (sendingOrder) return;
        const index = Number(quantityButton.dataset.qtyPlus ?? quantityButton.dataset.qtyMinus);
        if (!Number.isInteger(index) || !cart[index]) return;

        if (quantityButton.hasAttribute('data-qty-plus')) {
          if (cart[index].qty >= 999) {
            alert('Mỗi dòng món chỉ được gọi tối đa 999 phần.');
            return;
          }
          cart[index].qty += 1;
        } else if (cart[index].qty <= 1) {
          cart.splice(index, 1);
        } else {
          cart[index].qty -= 1;
        }
        renderCart();
        return;
      }

      // Xóa món mới chọn
      const cancelButton = target.closest('[data-cancel-item]');
      if (cancelButton && pos.contains(cancelButton)) {
        event.stopPropagation();
        if (sendingOrder) return;
        const itemId = cancelButton.dataset.cancelItem;
        cart = cart.filter(item => item.id !== itemId);
        renderCart();
        return;
      }

      const editNoteButton = target.closest('[data-edit-item-note]');
      if (editNoteButton && pos.contains(editNoteButton)) {
        event.stopPropagation();
        if (sendingOrder) return;

        const index = Number(editNoteButton.dataset.editItemNote);
        const item = cart[index];
        if (!item) return;

        const note = window.prompt(`Ghi chú cho ${item.name} (tối đa 255 ký tự):`, item.note);
        if (note === null) return;

        const normalizedNote = note.trim();
        if (normalizedNote.length > 255) {
          alert('Ghi chú không được vượt quá 255 ký tự.');
          return;
        }

        item.note = normalizedNote;
        const matchingItem = cart.find((entry, entryIndex) =>
          entryIndex !== index
          && entry.id === item.id
          && entry.note === normalizedNote
          && entry.qty + item.qty <= 999
        );
        if (matchingItem) {
          matchingItem.qty += item.qty;
          cart.splice(index, 1);
        }
        renderCart();
        return;
      }

      // Hủy món đã gửi bếp khi món vẫn đang chờ tiếp nhận (SD-27)
      const cancelSentBtn = target.closest('.btn-cancel-sent-dish');
      if (cancelSentBtn) {
        event.stopPropagation();
        currentCancellingLineId = cancelSentBtn.dataset.lineId;
        const dishName = cancelSentBtn.dataset.dishName;

        modalTenMonHuy.textContent = dishName;
        selectLyDoHuy.value = 'Khách đổi ý không dùng nữa';
        inputLyDoKhac.style.display = 'none';
        inputLyDoKhac.value = '';
        modalHuy.style.display = 'flex';
        return;
      }

      // Bấm vào thẻ món ăn để thêm vào giỏ
      const card = target.closest('.menu-card');
      if (!card || !pos.contains(card) || !card.hasAttribute('data-add-dish')) return;
      if (sendingOrder || card.classList.contains('disabled-card')) return;

      event.stopPropagation();
      const dish = getDishDetails(card);
      if (!dish.name) return;

      const existing = cart.find(item => item.id === dish.id && item.note === '');
      if (existing) {
        if (existing.qty >= 999) {
          alert('Mỗi dòng món chỉ được gọi tối đa 999 phần.');
          return;
        }
        existing.qty += 1;
      } else {
        cart.push({ id: dish.id, name: dish.name, price: dish.price, qty: 1, note: '' });
      }
      renderCart();
    });

    // Thay đổi dropdown chọn bàn
    pos.querySelector('[data-pos-table-select]')?.addEventListener('change', event => {
      const tableId = event.target.value;
      if (!tableId) return;
      const url = new URL(window.location.href);
      url.searchParams.set('page', 'pos');
      url.searchParams.set('ban', tableId);
      window.location.assign(url);
    });

    // Tìm kiếm món
    searchInput?.addEventListener('input', () => filterMenu());

    // XỬ LÝ HỘP THOẠI MODAL HỦY MÓN
    selectLyDoHuy.addEventListener('change', () => {
      inputLyDoKhac.style.display = selectLyDoHuy.value === 'other' ? 'block' : 'none';
      if (selectLyDoHuy.value === 'other') inputLyDoKhac.focus();
    });

    btnDongModalHuy.addEventListener('click', () => {
      modalHuy.style.display = 'none';
      currentCancellingLineId = null;
    });

    btnXacNhanHuyMon.addEventListener('click', async () => {
      if (!currentCancellingLineId) return;

      let finalReason = selectLyDoHuy.value;
      if (finalReason === 'other') {
        finalReason = inputLyDoKhac.value.trim();
        if (!finalReason) {
          alert('Vui lòng nhập lý do hủy cụ thể!');
          inputLyDoKhac.focus();
          return;
        }
      }

      btnXacNhanHuyMon.disabled = true;
      btnXacNhanHuyMon.textContent = 'Đang xử lý...';

      try {
        const formData = new URLSearchParams({
          action: 'cancel_order_item',
          csrf_token: pos.dataset.csrfToken ?? '',
          maDon: pos.dataset.orderId ?? '',
          maCTDon: currentCancellingLineId,
          lyDoHuy: finalReason
        });

        const res = await fetch(window.location.href, {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          body: formData
        });
        const result = await res.json();

        if (result.success) {
          alert(result.message);
          window.location.reload();
        } else {
          alert('Lỗi: ' + result.message);
        }
      } catch (err) {
        alert('Lỗi kết nối máy chủ khi hủy món!');
      } finally {
        btnXacNhanHuyMon.disabled = false;
        btnXacNhanHuyMon.textContent = 'Xác nhận hủy món';
        modalHuy.style.display = 'none';
      }
    });

    // GỬI ĐƠN/MÓN XUỐNG BẾP (SD-25/26)
    sendButton?.addEventListener('click', async () => {
      if (sendButton.disabled || sendingOrder || cart.length === 0) return;

      const isAdd = Boolean(pos.dataset.orderId);
      const orderTarget = `Bàn ${pos.dataset.tableNumber}`;
      const confirmMsg = isAdd
        ? `Gửi thêm ${cart.reduce((c, i) => c + i.qty, 0)} món mới vào đơn ${orderTarget}?`
        : `Gửi đơn gồm ${cart.reduce((c, i) => c + i.qty, 0)} món xuống bếp cho ${orderTarget}?`;

      if (!window.confirm(confirmMsg)) return;

      sendingOrder = true;
      renderCart();

      try {
        const body = new URLSearchParams({
          action: 'send_kitchen',
          csrf_token: pos.dataset.csrfToken ?? '',
          maBan: pos.dataset.tableId,
          items: JSON.stringify(cart.map(item => ({
            id: item.id,
            qty: item.qty,
            note: item.note ?? '',
          }))),
        });

        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          body: body,
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Không thể gửi đơn sang bếp.');
        }

        alert(result.message);
        window.location.reload();
      } catch (err) {
        alert(err.message);
      } finally {
        sendingOrder = false;
        renderCart();
      }
    });

    renderCart();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePosCart, { once: true });
  } else {
    initializePosCart();
  }
})();
