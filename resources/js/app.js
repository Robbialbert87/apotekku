import './medicore/theme';
import './medicore/sidebar';
import './medicore/ui';
import './medicore/actions';
import './medicore/export';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'flatpickr/dist/flatpickr.min.css';
import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';
import './apotek';

document.addEventListener('DOMContentLoaded', () => {
  if (typeof flatpickr === 'function') {
    flatpickr('.date-picker', {
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'd/m/Y',
      locale: Indonesian,
      allowInput: true,
      altInputClass: 'form-control-custom',
    });
  }
});
