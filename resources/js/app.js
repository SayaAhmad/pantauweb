import './bootstrap';
import Chart from 'chart.js/auto';
import Alpine from 'alpinejs';

window.Chart = Chart;
window.Alpine = Alpine;

Chart.defaults.font.family = "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#64748b';

window.pantauChart = function (canvasId, config) {
    const canvas = document.getElementById(canvasId);

    if (!canvas || !window.Chart) {
        return null;
    }

    return new window.Chart(canvas, config);
};

// Server-rendered validation errors: move focus to the first invalid field so
// keyboard and screen reader users land on the problem instead of the page top.
const firstInvalidField = document.querySelector('[aria-invalid="true"]');

if (firstInvalidField) {
    firstInvalidField.focus();
}

Alpine.start();
