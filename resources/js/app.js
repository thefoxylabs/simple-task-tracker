// Livewire ships with Alpine.js. On top of it we add searchable Select2
// dropdowns and our own date / time picker.
import './select2';
import datePicker from './date-picker';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('datePicker', datePicker);
});
