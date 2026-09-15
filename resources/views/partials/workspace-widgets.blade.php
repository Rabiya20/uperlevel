<div class="workspace-widgets" id="workspaceWidgets">
    <button type="button" class="icon-btn workspace-widgets-trigger" aria-label="Open workspace widgets" aria-expanded="false" title="Workspace widgets">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    </button>

    <section class="workspace-widgets-panel" aria-label="Workspace widgets" hidden>
        <div class="workspace-widgets-heading">
            <div>
                <div class="workspace-widgets-kicker">Quick tools</div>
                <h2>Workspace widgets</h2>
            </div>
            <button type="button" class="workspace-widgets-close" aria-label="Close workspace widgets">&times;</button>
        </div>

        <div class="workspace-widget-tabs" role="tablist" aria-label="Workspace widgets">
            <button type="button" class="workspace-widget-tab is-active" role="tab" aria-selected="true" aria-controls="widgetCalculator" data-widget-tab="calculator">Calculator</button>
            <button type="button" class="workspace-widget-tab" role="tab" aria-selected="false" aria-controls="widgetNotepad" data-widget-tab="notepad">Notepad</button>
        </div>

        <div class="workspace-widget-view is-active" id="widgetCalculator" role="tabpanel" data-widget-view="calculator">
            <div class="calculator-mode" role="tablist" aria-label="Calculator mode">
                <button type="button" class="calculator-mode-btn is-active" data-calculator-mode="math">Math</button>
                <button type="button" class="calculator-mode-btn" data-calculator-mode="salary">Salary &amp; tax</button>
            </div>

            <div class="calculator-pane is-active" data-calculator-pane="math">
                <input class="calculator-display" data-calculator-display value="" inputmode="decimal" aria-label="Calculator expression" placeholder="0">
                <div class="calculator-keypad">
                    <button type="button" data-calculator-action="clear">AC</button>
                    <button type="button" data-calculator-value="(">(</button>
                    <button type="button" data-calculator-value=")">)</button>
                    <button type="button" class="calculator-key-operator" data-calculator-value="/">/</button>
                    <button type="button" data-calculator-value="7">7</button>
                    <button type="button" data-calculator-value="8">8</button>
                    <button type="button" data-calculator-value="9">9</button>
                    <button type="button" class="calculator-key-operator" data-calculator-value="*">x</button>
                    <button type="button" data-calculator-value="4">4</button>
                    <button type="button" data-calculator-value="5">5</button>
                    <button type="button" data-calculator-value="6">6</button>
                    <button type="button" class="calculator-key-operator" data-calculator-value="-">-</button>
                    <button type="button" data-calculator-value="1">1</button>
                    <button type="button" data-calculator-value="2">2</button>
                    <button type="button" data-calculator-value="3">3</button>
                    <button type="button" class="calculator-key-operator" data-calculator-value="+">+</button>
                    <button type="button" class="calculator-key-wide" data-calculator-value="0">0</button>
                    <button type="button" data-calculator-value=".">.</button>
                    <button type="button" class="calculator-key-equals" data-calculator-action="calculate">=</button>
                </div>
                <p class="calculator-hint">Supports parentheses, percentages, powers (^), and decimal values.</p>
            </div>

            <form class="salary-calculator calculator-pane" data-calculator-pane="salary">
                <div class="calculator-field-grid">
                    <label>Gross monthly<input type="number" min="0" step="0.01" value="5000" data-salary="gross"></label>
                    <label>Tax rate %<input type="number" min="0" max="100" step="0.01" value="20" data-salary="tax"></label>
                    <label>Pension rate %<input type="number" min="0" max="100" step="0.01" value="5" data-salary="pension"></label>
                    <label>Other deductions<input type="number" min="0" step="0.01" value="0" data-salary="other"></label>
                </div>
                <button type="submit" class="salary-calculate">Calculate take-home</button>
                <div class="salary-results" aria-live="polite" data-salary-results>
                    <div><span>Annual gross</span><strong data-salary-result="gross">0.00</strong></div>
                    <div><span>Monthly tax</span><strong data-salary-result="tax">0.00</strong></div>
                    <div><span>Monthly pension</span><strong data-salary-result="pension">0.00</strong></div>
                    <div><span>Monthly net</span><strong class="is-highlight" data-salary-result="net">0.00</strong></div>
                </div>
                <p class="calculator-hint">A planning estimate only. Confirm local tax rules with payroll or finance.</p>
            </form>
        </div>

        <div class="workspace-widget-view" id="widgetNotepad" role="tabpanel" data-widget-view="notepad" hidden>
            <label class="notepad-label" for="workspaceNotepad">Private note</label>
            <textarea id="workspaceNotepad" class="workspace-notepad" data-notepad placeholder="Write a quick note..." spellcheck="true"></textarea>
            <div class="notepad-footer"><span data-notepad-status>Saved locally</span><button type="button" class="notepad-clear" data-notepad-clear>Clear note</button></div>
        </div>
    </section>
</div>

<script>
(function () {
    const root = document.getElementById('workspaceWidgets');
    if (!root) return;

    const trigger = root.querySelector('.workspace-widgets-trigger');
    const panel = root.querySelector('.workspace-widgets-panel');
    const note = root.querySelector('[data-notepad]');
    const noteStatus = root.querySelector('[data-notepad-status]');
    const noteKey = 'uperlevel-notepad-{{ auth()->id() }}';

    try { note.value = localStorage.getItem(noteKey) || ''; } catch (error) {}

    const setOpen = (open) => {
        panel.hidden = !open;
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) root.querySelector('.workspace-widget-tab.is-active').focus();
    };

    trigger.addEventListener('click', () => setOpen(panel.hidden));
    root.querySelector('.workspace-widgets-close').addEventListener('click', () => setOpen(false));

    root.querySelectorAll('[data-widget-tab]').forEach((tab) => tab.addEventListener('click', () => {
        const target = tab.dataset.widgetTab;
        root.querySelectorAll('[data-widget-tab]').forEach((item) => {
            const active = item === tab;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        root.querySelectorAll('[data-widget-view]').forEach((view) => {
            const active = view.dataset.widgetView === target;
            view.classList.toggle('is-active', active);
            view.hidden = !active;
        });
    }));

    root.querySelectorAll('[data-calculator-mode]').forEach((button) => button.addEventListener('click', () => {
        const target = button.dataset.calculatorMode;
        root.querySelectorAll('[data-calculator-mode]').forEach((item) => item.classList.toggle('is-active', item === button));
        root.querySelectorAll('[data-calculator-pane]').forEach((pane) => pane.classList.toggle('is-active', pane.dataset.calculatorPane === target));
    }));

    const display = root.querySelector('[data-calculator-display]');
    root.querySelectorAll('[data-calculator-value]').forEach((button) => button.addEventListener('click', () => {
        display.value += button.dataset.calculatorValue;
        display.focus();
    }));
    root.querySelector('[data-calculator-action="clear"]').addEventListener('click', () => { display.value = ''; });
    root.querySelector('[data-calculator-action="calculate"]').addEventListener('click', () => {
        try { display.value = formatNumber(evaluate(display.value)); } catch (error) { display.value = 'Error'; }
    });
    display.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            root.querySelector('[data-calculator-action="calculate"]').click();
        }
    });

    root.querySelector('.salary-calculator').addEventListener('submit', (event) => {
        event.preventDefault();
        const value = (name) => Math.max(0, Number(root.querySelector(`[data-salary="${name}"]`).value) || 0);
        const gross = value('gross');
        const tax = gross * value('tax') / 100;
        const pension = gross * value('pension') / 100;
        const other = value('other');
        const result = { gross: gross * 12, tax, pension, net: Math.max(0, gross - tax - pension - other) };
        Object.keys(result).forEach((key) => { root.querySelector(`[data-salary-result="${key}"]`).textContent = formatNumber(result[key]); });
    });
    root.querySelector('.salary-calculator').dispatchEvent(new Event('submit'));

    let saveTimer;
    note.addEventListener('input', () => {
        noteStatus.textContent = 'Saving...';
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            try { localStorage.setItem(noteKey, note.value); } catch (error) {}
            noteStatus.textContent = 'Saved locally';
        }, 250);
    });
    root.querySelector('[data-notepad-clear]').addEventListener('click', () => {
        note.value = '';
        note.dispatchEvent(new Event('input'));
        note.focus();
    });

    document.addEventListener('click', (event) => { if (!event.target.closest('#workspaceWidgets')) setOpen(false); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') setOpen(false); });

    function formatNumber(number) { return Number(number).toLocaleString(undefined, { maximumFractionDigits: 2 }); }
    function evaluate(expression) {
        const tokens = expression.replace(/\s+/g, '').match(/(?:\d+(?:\.\d*)?|\.\d+)|[()+\-*/%^]/g);
        if (!tokens || tokens.join('') !== expression.replace(/\s+/g, '') || !tokens.length) throw new Error('Invalid expression');
        let index = 0;
        const parseExpression = () => { let result = parseTerm(); while (['+', '-'].includes(tokens[index])) { const operator = tokens[index++]; const value = parseTerm(); result = operator === '+' ? result + value : result - value; } return result; };
        const parseTerm = () => { let result = parsePower(); while (['*', '/', '%'].includes(tokens[index])) { const operator = tokens[index++]; const value = parsePower(); if (operator === '*') result *= value; else if (operator === '/') result /= value; else result %= value; } return result; };
        const parsePower = () => { let result = parsePrimary(); if (tokens[index] === '^') { index++; result = Math.pow(result, parsePower()); } return result; };
        const parsePrimary = () => { if (tokens[index] === '-') { index++; return -parsePrimary(); } if (tokens[index] === '(') { index++; const result = parseExpression(); if (tokens[index++] !== ')') throw new Error('Missing parenthesis'); return result; } const result = Number(tokens[index++]); if (!Number.isFinite(result)) throw new Error('Invalid number'); return result; };
        const result = parseExpression();
        if (index !== tokens.length || !Number.isFinite(result)) throw new Error('Invalid expression');
        return result;
    }
})();
</script>
