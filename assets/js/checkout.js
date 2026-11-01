(() => {
  const checkout = document.querySelector('.checkout-layout');
  if (!checkout) return;

  const methodInput = document.querySelector('[data-payment-method-value]');
  const cashInput = document.querySelector('[data-cash-input]');
  const cashPanel = document.querySelector('[data-cash-panel]');
  const qrPanel = document.querySelector('[data-qr-panel]');
  const changeOutput = document.querySelector('[data-cash-change]');
  const total = Number(checkout.dataset.checkoutTotal || 0);

  const updateChange = () => {
    const change = Math.max(0, Number(cashInput?.value || 0) - total);
    if (changeOutput) changeOutput.textContent = `${Math.floor(change).toLocaleString('vi-VN')}đ`;
  };
  document.querySelectorAll('[data-checkout-method]').forEach(button => {
    button.addEventListener('click', () => {
      const method = button.dataset.checkoutMethod;
      methodInput.value = method;
      document.querySelectorAll('[data-checkout-method]').forEach(option => {
        const selected = option === button;
        option.classList.toggle('selected', selected);
        option.setAttribute('aria-pressed', String(selected));
      });
      cashPanel.hidden = method !== 'cash';
      qrPanel.hidden = method !== 'qr';
      cashInput.required = method === 'cash';
      updateChange();
    });
  });
  cashInput?.addEventListener('input', updateChange);
  updateChange();
})();
