import './bootstrap';

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import { Japanese } from 'flatpickr/dist/l10n/ja.js';
// Chart.jsのインポート
import Chart from 'chart.js/auto';
window.Chart = Chart;

window.Alpine = Alpine;
window.flatpickr = flatpickr;

flatpickr.localize(Japanese);

Alpine.start();
