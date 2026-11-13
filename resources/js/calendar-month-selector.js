const fallbackMonths = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
];

const monthExpression = /(?:calendarMonths\s*\[\s*calendarMonth\s*\]|monthNames\s*\[\s*currentMonth\s*\])/;

function registerMonthHeaders(root) {
    if (!(root instanceof Element)) return;

    const candidates = [];
    if (root.matches('[x-text]')) candidates.push(root);
    candidates.push(...root.querySelectorAll('[x-text]'));

    candidates.forEach((element) => {
        if (!monthExpression.test(element.getAttribute('x-text') || '') || element.dataset.monthSelectorReady) return;

        element.dataset.monthSelectorReady = 'true';
        element.tabIndex = 0;
        element.setAttribute('role', 'button');
        element.setAttribute('aria-haspopup', 'dialog');
        element.setAttribute('aria-label', 'Selecionar mês e ano');
        element.title = 'Selecionar mês e ano';
        element.style.cursor = 'pointer';
        element.style.textDecoration = 'underline dotted';
        element.style.textUnderlineOffset = '3px';

        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-chevron-down';
        icon.setAttribute('aria-hidden', 'true');
        icon.style.cssText = 'font-size:0.65em;margin-left:0.4rem;opacity:0.65;pointer-events:none;';
        element.after(icon);
    });
}

function getCalendarState(trigger) {
    if (!window.Alpine?.$data) return null;

    const component = trigger.closest('[x-data]');
    if (!component) return null;

    try {
        const state = window.Alpine.$data(component);
        if ('calendarMonth' in state && 'calendarYear' in state) {
            return {
                state,
                monthKey: 'calendarMonth',
                yearKey: 'calendarYear',
                months: state.calendarMonths || fallbackMonths,
            };
        }
        if ('currentMonth' in state && 'currentYear' in state) {
            return {
                state,
                monthKey: 'currentMonth',
                yearKey: 'currentYear',
                months: state.monthNames || fallbackMonths,
            };
        }
    } catch (error) {
        return null;
    }

    return null;
}

function openMonthSelector(trigger) {
    const calendar = getCalendarState(trigger);
    if (!calendar) return;

    document.getElementById('calendar-month-selector')?.remove();
    const isDark = document.documentElement.classList.contains('dark');

    const month = document.createElement('select');
    month.setAttribute('aria-label', 'Mês');
    month.style.cssText = `width:100%;min-height:44px;padding:0.5rem;border:1px solid ${isDark ? '#374151' : '#d1d5db'};border-radius:0.75rem;background:${isDark ? '#1f2937' : '#fff'};font-size:16px;color:${isDark ? '#f9fafb' : '#111827'};`;
    calendar.months.forEach((name, index) => {
        const option = document.createElement('option');
        option.value = String(index);
        option.textContent = name;
        month.append(option);
    });
    month.value = String(calendar.state[calendar.monthKey]);

    const year = document.createElement('input');
    year.type = 'number';
    year.min = '1';
    year.max = '9999';
    year.step = '1';
    year.inputMode = 'numeric';
    year.setAttribute('aria-label', 'Ano');
    year.value = String(calendar.state[calendar.yearKey]);
    year.style.cssText = `width:100%;min-height:44px;padding:0.5rem;border:1px solid ${isDark ? '#374151' : '#d1d5db'};border-radius:0.75rem;background:${isDark ? '#1f2937' : '#fff'};font-size:16px;color:${isDark ? '#f9fafb' : '#111827'};`;

    const panel = document.createElement('div');
    panel.id = 'calendar-month-selector';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Selecionar mês e ano');
    panel.style.cssText = `position:fixed;z-index:2147483000;width:min(330px,calc(100vw - 24px));padding:1rem;background:${isDark ? '#111827' : '#fff'};border:1px solid ${isDark ? '#374151' : '#e5e7eb'};border-radius:1rem;box-shadow:0 16px 40px rgba(0,0,0,.2);font-family:inherit;`;
    panel.addEventListener('click', (event) => event.stopPropagation());

    const heading = document.createElement('div');
    heading.textContent = 'Selecionar mês e ano';
    heading.style.cssText = `margin-bottom:0.75rem;font-size:0.875rem;font-weight:700;color:${isDark ? '#f9fafb' : '#111827'};`;

    const fields = document.createElement('div');
    fields.style.cssText = 'display:grid;grid-template-columns:minmax(0,1fr) 110px;gap:0.5rem;';

    const monthLabel = document.createElement('label');
    monthLabel.textContent = 'Mês';
    monthLabel.style.cssText = `display:grid;gap:0.25rem;font-size:0.75rem;font-weight:600;color:${isDark ? '#d1d5db' : '#4b5563'};`;
    monthLabel.append(month);

    const yearLabel = document.createElement('label');
    yearLabel.textContent = 'Ano';
    yearLabel.style.cssText = `display:grid;gap:0.25rem;font-size:0.75rem;font-weight:600;color:${isDark ? '#d1d5db' : '#4b5563'};`;
    yearLabel.append(year);
    fields.append(monthLabel, yearLabel);

    const actions = document.createElement('div');
    actions.style.cssText = 'display:flex;justify-content:flex-end;gap:0.5rem;margin-top:0.75rem;';

    const makeButton = (label, primary = false) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.style.cssText = `min-height:44px;padding:0.5rem 0.9rem;border:1px solid ${primary ? '#0f766e' : (isDark ? '#4b5563' : '#d1d5db')};border-radius:0.75rem;background:${primary ? '#0f766e' : (isDark ? '#1f2937' : '#fff')};color:${primary ? '#fff' : (isDark ? '#f3f4f6' : '#374151')};font-size:0.875rem;font-weight:700;`;
        return button;
    };

    const cancel = makeButton('Cancelar');
    cancel.addEventListener('click', () => panel.remove());

    const apply = makeButton('Aplicar', true);
    apply.addEventListener('click', () => {
        const selectedYear = Number(year.value);
        if (!Number.isInteger(selectedYear) || selectedYear < 1 || selectedYear > 9999) {
            year.focus();
            return;
        }

        calendar.state[calendar.monthKey] = Number(month.value);
        calendar.state[calendar.yearKey] = selectedYear;
        panel.remove();
        trigger.focus();
    });

    actions.append(cancel, apply);
    panel.append(heading, fields, actions);

    document.body.append(panel);

    const rect = trigger.getBoundingClientRect();
    const panelWidth = Math.min(330, window.innerWidth - 24);
    const left = Math.max(12, Math.min(rect.left, window.innerWidth - panelWidth - 12));
    const top = Math.max(12, Math.min(rect.bottom + 8, window.innerHeight - 190));
    panel.style.left = `${left}px`;
    panel.style.top = `${top}px`;
    month.focus();
}

function handleClick(event) {
    if (event.target.closest('#calendar-month-selector')) return;

    const trigger = event.target.closest('[data-month-selector-ready="true"]');
    if (trigger) {
        event.preventDefault();
        openMonthSelector(trigger);
        return;
    }

    document.getElementById('calendar-month-selector')?.remove();
}

function handleKeydown(event) {
    const trigger = event.target.closest('[data-month-selector-ready="true"]');
    if (trigger && (event.key === 'Enter' || event.key === ' ')) {
        event.preventDefault();
        openMonthSelector(trigger);
        return;
    }

    if (event.key === 'Escape') {
        document.getElementById('calendar-month-selector')?.remove();
    }
}

function initializeCalendarMonthSelector() {
    registerMonthHeaders(document.body);
    new MutationObserver((records) => {
        records.forEach((record) => {
            record.addedNodes.forEach(registerMonthHeaders);
        });
    }).observe(document.body, { childList: true, subtree: true });

    document.addEventListener('click', handleClick);
    document.addEventListener('keydown', handleKeydown);
    window.addEventListener('resize', () => {
        document.getElementById('calendar-month-selector')?.remove();
    });
    window.addEventListener('scroll', () => {
        document.getElementById('calendar-month-selector')?.remove();
    }, true);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCalendarMonthSelector, { once: true });
} else {
    initializeCalendarMonthSelector();
}
