/* ==========================================================================
   APOTEK BILLING — interaksi khusus aplikasi.
   Bergantung pada helper global dari resources/js/medicore (theme/ui/sidebar).
   ========================================================================== */

import $ from 'jquery';
window.$ = window.jQuery = $;
import 'datatables.net';

(function () {
  'use strict';

  /* ---- Format angka & Rupiah --------------------------------------------- */
  const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  });

  function formatRupiah(value) {
    return rupiah.format(Number(value) || 0);
  }

  /**
   * Membaca angka dari input yang bisa berupa format ribuan ("1.500.000")
   * atau desimal dari server ("1500000.00").
   */
  function parseRupiah(value) {
    let clean = String(value ?? '').replace(/[^0-9,.\-]/g, '');
    const negative = clean.startsWith('-');
    clean = clean.replace(/^-/, '');

    if (clean === '') {
      return 0;
    }

    if (clean.includes(',')) {
      clean = clean.replace(/\./g, '').replace(',', '.');
    } else if ((clean.match(/\./g) || []).length > 1) {
      clean = clean.replace(/\./g, '');
    } else if (!/\.\d{1,2}$/.test(clean)) {
      clean = clean.replace(/\./g, '');
    }

    const amount = Math.round(Number(clean) || 0);

    return negative ? -amount : amount;
  }

  function formatThousands(value) {
    return parseRupiah(value).toLocaleString('id-ID');
  }

  /* ---- Input mata uang ---------------------------------------------------- */
  function maxOf(input) {
    const raw = input.getAttribute('data-max');
    return raw === null || raw === '' ? null : Number(raw);
  }

  function clampToMax(input) {
    const max = maxOf(input);
    if (max === null) {
      return;
    }

    if (parseRupiah(input.value) > max) {
      input.value = formatThousands(max);
    }
  }

  function initCurrencyInputs(root = document) {
    root.querySelectorAll('[data-currency-input]').forEach((input) => {
      if (input.dataset.currencyReady === '1') {
        return;
      }
      input.dataset.currencyReady = '1';

      if (input.value !== '') {
        input.value = formatThousands(input.value);
      }

      input.addEventListener('input', () => {
        const caretAtEnd = input.selectionStart === input.value.length;
        input.value = formatThousands(input.value);
        clampToMax(input);

        if (caretAtEnd) {
          input.setSelectionRange(input.value.length, input.value.length);
        }

        updatePaymentSummary();
      });

      input.addEventListener('blur', () => {
        clampToMax(input);
        updatePaymentSummary();
      });
    });
  }

  /* ---- Ringkasan alokasi pembayaran -------------------------------------- */
  function updatePaymentSummary() {
    const totalInput = document.querySelector('[data-payment-total]');
    if (!totalInput) {
      return;
    }

    const total = parseRupiah(totalInput.value);
    let allocated = 0;

    document.querySelectorAll('[data-allocation]').forEach((input) => {
      allocated += parseRupiah(input.value);
    });

    const setText = (selector, value) => {
      const el = document.querySelector(selector);
      if (el) {
        el.textContent = formatRupiah(value);
        el.classList.toggle('money-negative', value < 0);
      }
    };

    setText('[data-summary-total]', total);
    setText('[data-summary-allocated]', allocated);
    setText('[data-summary-remaining]', total - allocated);

    const hint = document.querySelector('[data-allocation-hint]');
    if (hint) {
      const remaining = total - allocated;
      hint.textContent = total === 0
        ? 'Isi total pembayaran, lalu alokasikan ke invoice.'
        : remaining === 0
          ? 'Alokasi sudah sesuai dengan total pembayaran.'
          : remaining > 0
            ? `Masih ada ${formatRupiah(remaining)} yang belum dialokasikan.`
            : `Alokasi melebihi total pembayaran sebesar ${formatRupiah(Math.abs(remaining))}.`;
    }
  }

  function parseAmount(value) {
    return Number(String(value || '').replace(/\D/g, '')) || 0;
  }

  function syncAllocationButton(row) {
    if (!row) {
      return;
    }

    const input = row.querySelector('[data-allocation]');
    const button = row.querySelector('[data-fill-remaining]');
    if (!input || !button) {
      return;
    }

    const fullAmount = parseRupiah(button.getAttribute('data-fill-remaining'));
    const currentValue = parseRupiah(input.value);
    const isActive = fullAmount > 0 && currentValue === fullAmount;

    button.classList.toggle('btn-success-custom', isActive);
    button.classList.toggle('btn-ghost-custom', !isActive);
  }

  function initPaymentForm() {
    const form = document.querySelector('[data-payment-form]');
    if (!form) {
      return;
    }

    form.querySelectorAll('[data-fill-remaining]').forEach((button) => {
      const row = button.closest('.allocation-row');

      button.addEventListener('click', () => {
        const input = row ? row.querySelector('[data-allocation]') : null;
        if (!input) {
          return;
        }

        const fullAmount = parseRupiah(button.getAttribute('data-fill-remaining'));
        const currentValue = parseRupiah(input.value);

        if (fullAmount > 0 && currentValue === fullAmount) {
          input.value = '';
        } else {
          input.value = formatThousands(fullAmount);
        }

        syncAllocationButton(row);
        updatePaymentSummary();
      });

      if (row) {
        const input = row.querySelector('[data-allocation]');
        if (input) {
          input.addEventListener('input', () => {
            syncAllocationButton(row);
          });
        }

        syncAllocationButton(row);
      }
    });

    const supplierSelect = form.querySelector('[data-supplier-select]');
    if (supplierSelect) {
      supplierSelect.addEventListener('change', () => {
        const url = new URL(window.location.href);

        if (supplierSelect.value) {
          url.searchParams.set('supplier_id', supplierSelect.value);
        } else {
          url.searchParams.delete('supplier_id');
        }

        url.searchParams.delete('invoice_id');
        url.searchParams.set('payment_modal', '1');
        window.location.assign(url.toString());
      });
    }

    updatePaymentSummary();
  }

  /* ---- Baris rincian obat (invoice) -------------------------------------- */
  function reindexItemRows(container) {
    container.querySelectorAll('[data-item-row]').forEach((row, index) => {
      row.querySelectorAll('input[name^="items["]').forEach((input) => {
        const field = input.name.match(/\[([a-z_]+)\]$/i);
        if (field) {
          input.name = `items[${index}][${field[1]}]`;
        }
      });
    });
  }

  function initInvoiceItems() {
    const container = document.querySelector('[data-item-rows]');
    const addButton = document.querySelector('[data-add-item]');
    if (!container) {
      return;
    }

    const firstRow = container.querySelector('[data-item-row]');

    if (addButton && firstRow) {
      addButton.addEventListener('click', () => {
        if (container.querySelectorAll('[data-item-row]').length >= 100) {
          return;
        }

        const row = firstRow.cloneNode(true);
        row.querySelectorAll('input').forEach((input) => {
          input.value = '';
        });
        container.appendChild(row);
        reindexItemRows(container);

        const firstInput = row.querySelector('.item-name');
        if (firstInput) {
          firstInput.focus();
        }
      });
    }

    container.addEventListener('click', (event) => {
      const remove = event.target.closest('[data-remove-item]');
      if (!remove) {
        return;
      }

      const rows = container.querySelectorAll('[data-item-row]');
      const row = remove.closest('[data-item-row]');

      if (!row) {
        return;
      }

      if (rows.length > 1) {
        row.remove();
      } else {
        row.querySelectorAll('input').forEach((input) => {
          input.value = '';
        });
      }

      reindexItemRows(container);
    });
  }

  /* ---- Dialog konfirmasi -------------------------------------------------- */
  function initConfirmations() {
    const modal = document.getElementById('modalConfirm');
    const message = document.getElementById('modalConfirmMessage');
    const okButton = document.getElementById('modalConfirmOk');
    const cancelButton = document.getElementById('modalConfirmCancel');
    if (!modal || !message || !okButton || !cancelButton) {
      // Fallback to native confirm if modal is unavailable.
      document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.confirm === undefined) {
          return;
        }
        if (!window.confirm(form.dataset.confirm)) {
          event.preventDefault();
        }
      });
      return;
    }

    let pendingForm = null;

    function closeModal() {
      modal.classList.remove('is-open');
      pendingForm = null;
    }

    // Clear pending form when the confirmation modal is closed via X, overlay, or Escape.
    modal.addEventListener('click', (event) => {
      if (event.target === modal || event.target.closest('[data-modal-close]')) {
        pendingForm = null;
      }
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        pendingForm = null;
      }
    });

    document.addEventListener('submit', (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) {
        return;
      }
      if (form.dataset.confirmed === 'true') {
        delete form.dataset.confirmed;
        return;
      }

      event.preventDefault();
      pendingForm = form;
      message.textContent = form.dataset.confirm;
      modal.classList.add('is-open');
    });

    okButton.addEventListener('click', () => {
      if (pendingForm) {
        pendingForm.querySelectorAll('.date-id-input').forEach((input) => {
          const normalized = normalizeDateIdValue(input.value);
          if (normalized !== input.value) {
            input.value = normalized;
          }
        });
        pendingForm.dataset.confirmed = 'true';
        pendingForm.submit();
      }
      closeModal();
    });

    cancelButton.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }
    });
  }

  /* ---- Input tanggal dd/mm/yyyy ------------------------------------------ */
  function normalizeDateIdValue(value) {
    const match = String(value || '').trim().match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!match) {
      return value;
    }
    return match[3] + '-' + match[2] + '-' + match[1];
  }

  function initDateIdInputs() {
    document.querySelectorAll('.date-id-input').forEach((input) => {
      input.addEventListener('input', () => {
        const digits = input.value.replace(/\D/g, '').slice(0, 8);
        let formatted = digits;
        if (digits.length > 2) {
          formatted = digits.slice(0, 2) + '/' + digits.slice(2);
        }
        if (digits.length > 4) {
          formatted = formatted.slice(0, 5) + '/' + digits.slice(4);
        }
        input.value = formatted;
      });
    });

    document.addEventListener('submit', (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) {
        return;
      }
      form.querySelectorAll('.date-id-input').forEach((input) => {
        const normalized = normalizeDateIdValue(input.value);
        if (normalized !== input.value) {
          input.value = normalized;
        }
      });
    }, true);
  }

  /* ---- Tombol Batal di dalam modal ---------------------------------------- */
  function initModalCancelButtons() {
    document.addEventListener('click', (event) => {
      const cancel = event.target.closest('.modal-overlay .form-actions a, .modal-overlay .form-actions button[type="button"]');
      if (!cancel) {
        return;
      }

      const modal = cancel.closest('.modal-overlay');
      if (!modal) {
        return;
      }

      // Prevent the cancel link/button from submitting or navigating.
      event.preventDefault();
      modal.classList.remove('is-open');
    });
  }

  /* ---- Pilih izin peran --------------------------------------------------- */
  function initPermissionToggles() {
    document.querySelectorAll('[data-permission-toggle]').forEach((button) => {
      button.addEventListener('click', () => {
        const mode = button.getAttribute('data-permission-toggle');

        if (mode === 'all' || mode === 'none') {
          const checked = mode === 'all';
          document.querySelectorAll('[data-permission-checkbox]').forEach((checkbox) => {
            checkbox.checked = checked;
          });
          return;
        }

        if (mode === 'group') {
          const scope = button.getAttribute('data-permission-scope');
          const group = document.querySelector(`[data-permission-group="${scope}"]`);
          if (!group) {
            return;
          }

          const checkboxes = Array.from(group.querySelectorAll('[data-permission-checkbox]'));
          const checkAll = checkboxes.some((checkbox) => !checkbox.checked);
          checkboxes.forEach((checkbox) => {
            checkbox.checked = checkAll;
          });
        }
      });
    });
  }

  /* ---- Grafik dashboard --------------------------------------------------- */
  function cssVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  }

  function initDashboardCharts() {
    const payload = document.getElementById('dashboard-data');
    if (!payload) {
      return;
    }

    let data;
    try {
      data = JSON.parse(payload.textContent || '{}');
    } catch (error) {
      return;
    }

    import('chart.js/auto').then(({ default: Chart }) => {
      const gridColor = cssVar('--color-border') || 'rgba(148, 163, 184, 0.25)';
      const textColor = cssVar('--color-text-muted') || '#64748b';
      const fontFamily = cssVar('--font-family-base') || 'Inter, system-ui, sans-serif';

      const monthlyCanvas = document.getElementById('chartMonthly');
      if (monthlyCanvas && data.monthly) {
        new Chart(monthlyCanvas, {
          type: 'bar',
          data: {
            labels: data.monthly.labels || [],
            datasets: [
              {
                label: 'Tagihan',
                data: (data.monthly.billed || []).map(Number),
                backgroundColor: cssVar('--color-primary') || '#2563eb',
                borderRadius: 6,
                maxBarThickness: 26,
              },
              {
                label: 'Pembayaran',
                data: (data.monthly.paid || []).map(Number),
                backgroundColor: cssVar('--color-success') || '#22c55e',
                borderRadius: 6,
                maxBarThickness: 26,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: (context) => `${context.dataset.label}: ${formatRupiah(context.parsed.y)}`,
                },
              },
            },
            scales: {
              x: {
                grid: { display: false },
                ticks: { color: textColor, font: { family: fontFamily } },
              },
              y: {
                beginAtZero: true,
                grid: { color: gridColor },
                ticks: {
                  color: textColor,
                  font: { family: fontFamily },
                  callback: (value) => {
                    if (Math.abs(Number(value)) >= 1000000) {
                      return `${Number(value) / 1000000} jt`;
                    }
                    if (Math.abs(Number(value)) >= 1000) {
                      return `${Number(value) / 1000} rb`;
                    }
                    return value;
                  },
                },
              },
            },
          },
        });
      }

      const statusCanvas = document.getElementById('chartStatus');
      if (statusCanvas && data.status) {
        new Chart(statusCanvas, {
          type: 'doughnut',
          data: {
            labels: data.status.labels || [],
            datasets: [
              {
                data: (data.status.data || []).map(Number),
                backgroundColor: data.status.colors || ['#ef4444', '#f59e0b', '#22c55e'],
                borderWidth: 0,
                hoverOffset: 4,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: (context) => `${context.label}: ${context.parsed} invoice`,
                },
              },
            },
          },
        });
      }
    });
  }

  function initDataTables() {
    if (!$ || !$.fn || !$.fn.DataTable) {
      return;
    }
    $('.datatable').each(function () {
      if ($(this).hasClass('dataTable')) {
        return;
      }

      var $table = $(this);
      var headerCount = $table.find('thead th').length;
      var hasInvalidRow = $table.find('tbody tr').toArray().some(function (tr) {
        return tr.cells.length !== headerCount;
      });

      if (hasInvalidRow) {
        return;
      }

      var table = $table.DataTable({
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        layout: {
          topStart: null,
          topEnd: 'pageLength',
          bottomStart: 'info',
          bottomEnd: 'paging',
        },
        language: {
          lengthMenu: 'Tampilkan _MENU_ data',
          info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
          paginate: { first: 'Awal', last: 'Akhir', next: '›', previous: '‹' },
          zeroRecords: 'Data tidak ditemukan',
        },
      });

      if (this.id === 'table-invoices') {
        $('#invoice-search').on('input', function () {
          table.search(this.value).draw();
        });
      }

      if (this.id === 'table-payments') {
        $('#payment-search').on('input', function () {
          table.search(this.value).draw();
        });
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    initCurrencyInputs();
    initPaymentForm();
    initInvoiceItems();
    initConfirmations();
    initModalCancelButtons();
    initDateIdInputs();
    initDataTables();
    initPermissionToggles();
    initDashboardCharts();
  });
})();
