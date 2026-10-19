/**
 * Date, date-time and time picker used by <x-date-picker>.
 *
 * Values use the same formats as native inputs so the server doesn't need
 * to know about the picker:  date "2026-10-21",  datetime "2026-10-21T15:00",
 * time "15:00".  Bind it with wire:model on the component (x-modelable).
 */
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const pad = (n) => String(n).padStart(2, '0');
const toKey = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const fromKey = (key) => {
    const [y, m, d] = key.split('-').map(Number);
    return new Date(y, m - 1, d);
};
const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
const formatTime = (h, m) => `${h % 12 || 12}:${pad(m)} ${h < 12 ? 'AM' : 'PM'}`;

export default function datePicker({ mode = 'date', defaultTime = '17:00', minuteStep = 5, placeholder = '' } = {}) {
    const [defaultHour, defaultMinute] = defaultTime.split(':').map(Number);

    return {
        mode,
        placeholder,
        value: '',
        open: false,
        view: 'days', // days | months | years
        date: '', // selected day, "Y-m-d"
        hour: defaultHour,
        minute: defaultMinute,
        month: new Date().getMonth(), // month being displayed
        year: new Date().getFullYear(),
        focusedDay: '',
        mobile: false,
        style: '',
        weekdays: WEEKDAYS,
        months: MONTHS,

        init() {
            this.read();
            this.$watch('value', () => this.read());

            // Keep the panel attached to its field while any container scrolls.
            this.onScroll = () => this.open && this.position();
            window.addEventListener('scroll', this.onScroll, true);
        },

        destroy() {
            window.removeEventListener('scroll', this.onScroll, true);
        },

        get hasDate() {
            return this.mode !== 'time';
        },

        get hasTime() {
            return this.mode !== 'date';
        },

        // ----- Reading and writing the bound value --------------------------------

        read() {
            const value = (this.value ?? '').toString().trim();
            let datePart = '';
            let timePart = '';

            if (this.mode === 'time') {
                timePart = value;
            } else {
                [datePart, timePart = ''] = value.split(/[T ]/);
            }

            this.date = /^\d{4}-\d{2}-\d{2}$/.test(datePart) ? datePart : '';

            const time = timePart.match(/^(\d{1,2}):(\d{2})/);
            if (time) {
                this.hour = Number(time[1]);
                this.minute = Number(time[2]);
            }

            if (this.date) {
                const d = fromKey(this.date);
                this.month = d.getMonth();
                this.year = d.getFullYear();
            }
        },

        write() {
            const time = `${pad(this.hour)}:${pad(this.minute)}`;

            if (this.mode === 'time') {
                this.value = time;
            } else if (this.mode === 'date') {
                this.value = this.date;
            } else {
                this.value = this.date ? `${this.date}T${time}` : '';
            }
        },

        get label() {
            if (this.mode === 'time') {
                return this.value ? formatTime(this.hour, this.minute) : '';
            }

            if (! this.date) {
                return '';
            }

            const d = fromKey(this.date);
            const day = `${WEEKDAYS[(d.getDay() + 6) % 7]}, ${d.getDate()} ${MONTHS[d.getMonth()].slice(0, 3)} ${d.getFullYear()}`;

            return this.mode === 'datetime' ? `${day} · ${formatTime(this.hour, this.minute)}` : day;
        },

        get relative() {
            if (! this.date) {
                return '';
            }

            const diff = Math.round((fromKey(this.date) - fromKey(toKey(new Date()))) / 86400000);

            if (diff === 0) return 'Today';
            if (diff === 1) return 'Tomorrow';
            if (diff === -1) return 'Yesterday';
            if (diff > 1 && diff < 7) return `In ${diff} days`;
            if (diff < -1 && diff > -7) return `${-diff} days ago`;

            return '';
        },

        // ----- Opening, closing and positioning -----------------------------------

        toggle() {
            this.open ? this.close() : this.show();
        },

        show() {
            if (this.open) return;

            this.read();
            if (! this.date) {
                const today = new Date();
                this.month = today.getMonth();
                this.year = today.getFullYear();
            }
            this.view = 'days';
            this.focusedDay = this.date || toKey(new Date());
            this.mobile = window.innerWidth < 640;
            this.open = true;

            this.$nextTick(() => {
                this.position();
                // Measure again once the calendar and time lists have rendered.
                requestAnimationFrame(() => this.position());
                this.scrollTimeIntoView();
                this.$refs.panel?.focus({ preventScroll: true });
            });
        },

        close(refocus = true) {
            if (! this.open) return;

            this.open = false;
            if (refocus) this.$refs.trigger?.focus({ preventScroll: true });
        },

        position() {
            if (! this.open || this.mobile || ! this.$refs.panel) {
                this.style = '';
                return;
            }

            const trigger = this.$refs.trigger.getBoundingClientRect();
            const panel = this.$refs.panel;
            const width = panel.offsetWidth;
            const height = panel.scrollHeight;
            const gap = 6;
            const margin = 8;
            const below = window.innerHeight - trigger.bottom - gap - margin;
            const above = trigger.top - gap - margin;

            let left = Math.min(trigger.left, window.innerWidth - width - margin);
            left = Math.max(margin, left);

            // Open below if it fits, otherwise above, otherwise on the roomier
            // side with a scrollable panel.
            let top;
            let maxHeight = null;

            if (height <= below) {
                top = trigger.bottom + gap;
            } else if (height <= above) {
                top = trigger.top - gap - height;
            } else if (below >= above) {
                top = trigger.bottom + gap;
                maxHeight = below;
            } else {
                top = margin;
                maxHeight = above;
            }

            this.style = `top: ${Math.round(top)}px; left: ${Math.round(left)}px;`
                + (maxHeight ? ` max-height: ${Math.round(maxHeight)}px; overflow-y: auto;` : '');
        },

        // ----- Calendar -------------------------------------------------------------

        get title() {
            if (this.view === 'years') {
                const start = this.year - (this.year % 12);
                return `${start} – ${start + 11}`;
            }

            return this.view === 'months' ? String(this.year) : `${MONTHS[this.month]} ${this.year}`;
        },

        get days() {
            const first = new Date(this.year, this.month, 1);
            const start = addDays(first, -((first.getDay() + 6) % 7));
            const today = toKey(new Date());

            return Array.from({ length: 42 }, (_, i) => {
                const d = addDays(start, i);
                const key = toKey(d);

                return {
                    key,
                    day: d.getDate(),
                    outside: d.getMonth() !== this.month,
                    today: key === today,
                    selected: key === this.date,
                    weekend: d.getDay() === 0 || d.getDay() === 6,
                    label: `${WEEKDAYS[(d.getDay() + 6) % 7]} ${d.getDate()} ${MONTHS[d.getMonth()]} ${d.getFullYear()}`,
                };
            });
        },

        get years() {
            const start = this.year - (this.year % 12);
            return Array.from({ length: 12 }, (_, i) => start + i);
        },

        previous() {
            if (this.view === 'years') this.year -= 12;
            else if (this.view === 'months') this.year -= 1;
            else this.shiftMonth(-1);
        },

        next() {
            if (this.view === 'years') this.year += 12;
            else if (this.view === 'months') this.year += 1;
            else this.shiftMonth(1);
        },

        shiftMonth(n) {
            const d = new Date(this.year, this.month + n, 1);
            this.month = d.getMonth();
            this.year = d.getFullYear();
        },

        zoomOut() {
            this.view = this.view === 'days' ? 'months' : 'years';
        },

        pickMonth(m) {
            this.month = m;
            this.view = 'days';
        },

        pickYear(y) {
            this.year = y;
            this.view = 'months';
        },

        select(key) {
            this.date = key;
            this.focusedDay = key;

            const d = fromKey(key);
            this.month = d.getMonth();
            this.year = d.getFullYear();
            this.write();

            // Dates are done after one click; date-times still need a time.
            if (this.mode === 'date') {
                this.close();
            }
        },

        shortcut(days) {
            this.select(toKey(addDays(new Date(), days)));
        },

        nextWeekday(weekday) {
            const today = new Date();
            const diff = ((weekday - today.getDay() + 7) % 7) || 7;
            this.shortcut(diff);
        },

        moveFocus(days) {
            if (this.view !== 'days') return;

            const d = addDays(fromKey(this.focusedDay || toKey(new Date())), days);
            this.focusedDay = toKey(d);
            this.month = d.getMonth();
            this.year = d.getFullYear();
        },

        // ----- Time -----------------------------------------------------------------

        get hours() {
            return [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
        },

        get minutes() {
            const list = [];
            for (let m = 0; m < 60; m += minuteStep) list.push(m);
            if (! list.includes(this.minute)) list.push(this.minute);

            return list.sort((a, b) => a - b);
        },

        get meridiem() {
            return this.hour < 12 ? 'AM' : 'PM';
        },

        get hour12() {
            return this.hour % 12 || 12;
        },

        get presets() {
            return [[9, 0], [12, 0], [15, 0], [17, 0]].map(([h, m]) => ({ h, m, label: formatTime(h, m) }));
        },

        setHour(h12) {
            this.hour = (h12 % 12) + (this.meridiem === 'PM' ? 12 : 0);
            this.timeChanged();
        },

        setMinute(m) {
            this.minute = m;
            this.timeChanged();
        },

        setMeridiem(value) {
            if (value === this.meridiem) return;
            this.hour = (this.hour + 12) % 24;
            this.timeChanged();
        },

        setTime(h, m) {
            this.hour = h;
            this.minute = m;
            this.timeChanged();
            this.$nextTick(() => this.scrollTimeIntoView());
        },

        timeChanged() {
            // Picking a time first defaults the date to today.
            if (this.mode === 'datetime' && ! this.date) {
                this.date = toKey(new Date());
            }
            this.write();
        },

        scrollTimeIntoView() {
            this.$root.querySelectorAll('[data-time-column]').forEach((column) => {
                const selected = column.querySelector('[aria-selected="true"]');
                if (selected) {
                    column.scrollTop = selected.offsetTop - column.clientHeight / 2 + selected.offsetHeight / 2;
                }
            });
        },

        clear() {
            this.date = '';
            this.hour = defaultHour;
            this.minute = defaultMinute;
            this.value = '';
            this.close();
        },

        // ----- Keyboard -------------------------------------------------------------

        onKeydown(event) {
            const keys = {
                ArrowLeft: () => this.moveFocus(-1),
                ArrowRight: () => this.moveFocus(1),
                ArrowUp: () => this.moveFocus(-7),
                ArrowDown: () => this.moveFocus(7),
                PageUp: () => this.shiftMonth(-1),
                PageDown: () => this.shiftMonth(1),
                Enter: () => this.hasDate && this.view === 'days' && this.select(this.focusedDay),
            };

            if (keys[event.key] && event.target === this.$refs.panel) {
                event.preventDefault();
                keys[event.key]();
            }
        },
    };
}
