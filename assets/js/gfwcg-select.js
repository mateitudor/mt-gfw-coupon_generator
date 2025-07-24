// GFWCG Custom Select Component
// Usage: GFWCGSelect.init(selector, options)

const GFWCGSelect = (() => {
    /**
     * Create the custom dropdown UI and attach all event listeners
     * @param {HTMLSelectElement} select - The select element to enhance
     * @param {Object} options - Configuration options
     */
    function createDropdown(select, options) {
        if (select.dataset.gfwcgSelect) return; // Prevent double init
        select.style.display = 'none';
        select.dataset.gfwcgSelect = '1';
        console.log('[GFWCGSelect] Initializing for', select);

        // Container
        const container = document.createElement('div');
        container.className = 'gfwcg-select-container';
        container.tabIndex = 0;
        container.setAttribute('role', 'combobox');
        container.setAttribute('aria-haspopup', 'listbox');
        container.setAttribute('aria-expanded', 'false');
        container.setAttribute('aria-owns', select.id + '-dropdown');
        select.parentNode.insertBefore(container, select.nextSibling);
        console.log('[GFWCGSelect] Created container for', select.id);

        // Display selected value(s)
        const display = document.createElement('div');
        display.className = 'gfwcg-select-display';
        renderSelectedDisplay(select, display);
        container.appendChild(display);

        // Dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'gfwcg-select-dropdown';
        dropdown.id = select.id + '-dropdown';
        dropdown.setAttribute('role', 'listbox');
        dropdown.setAttribute('tabindex', '-1');
        dropdown.style.display = 'none';
        container.appendChild(dropdown);

        // Search box
        const search = document.createElement('input');
        search.type = 'text';
        search.className = 'gfwcg-select-search';
        search.setAttribute('aria-label', 'Search');
        dropdown.appendChild(search);

        // Option list
        const optionList = document.createElement('div');
        optionList.className = 'gfwcg-select-options';
        dropdown.appendChild(optionList);

        // Loading indicator
        const loading = document.createElement('div');
        loading.className = 'gfwcg-select-loading';
        loading.textContent = 'Loading...';
        loading.style.display = 'none';
        dropdown.appendChild(loading);

        // Debounce helper
        let debounceTimeout;
        function debounce(fn, delay) {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(fn, delay);
        }

        // Render options (sync or async)
        function renderOptions(filter = '') {
            optionList.innerHTML = '';
            loading.style.display = 'none';
            const isMultiple = select.multiple;
            let found = false;
            // Async mode
            if (options.async && options.ajax && filter.length >= (options.ajax.minLength || 1)) {
                optionList.innerHTML = '';
                loading.style.display = 'block';
                debounce(() => {
                    fetchOptions(filter).then(results => {
                        loading.style.display = 'none';
                        // Always show currently selected options at the top, even if not in results
                        const selectedOptions = Array.from(select.options).filter(o => o.selected);
                        const renderedIds = new Set();
                        selectedOptions.forEach(opt => {
                            const item = document.createElement('div');
                            item.className = 'gfwcg-select-option';
                            item.setAttribute('role', 'option');
                            item.setAttribute('data-value', opt.value);
                            item.setAttribute('aria-selected', 'true');
                            item.tabIndex = -1;
                            item.textContent = opt.text;
                            item.classList.add('selected');
                            item.addEventListener('click', e => {
                                if (isMultiple) {
                                    opt.selected = !opt.selected;
                                    item.classList.toggle('selected', opt.selected);
                                } else {
                                    Array.from(select.options).forEach(o => o.selected = false);
                                    opt.selected = true;
                                    closeDropdown();
                                }
                                renderSelectedDisplay(select, display);
                                select.dispatchEvent(new Event('change', { bubbles: true }));
                            });
                            optionList.appendChild(item);
                            renderedIds.add(opt.value);
                        });
                        // Render AJAX results, skipping already rendered selected
                        if (!results.length && !selectedOptions.length) {
                            const noRes = document.createElement('div');
                            noRes.className = 'gfwcg-select-no-results';
                            noRes.textContent = 'No results';
                            optionList.appendChild(noRes);
                        } else {
                            results.forEach(opt => {
                                if (renderedIds.has(opt.id)) return;
                                const item = document.createElement('div');
                                item.className = 'gfwcg-select-option';
                                item.setAttribute('role', 'option');
                                item.setAttribute('data-value', opt.id);
                                item.setAttribute('aria-selected', isSelected(opt.id) ? 'true' : 'false');
                                item.tabIndex = -1;
                                item.textContent = opt.text;
                                if (isSelected(opt.id)) item.classList.add('selected');
                                item.addEventListener('click', e => {
                                    if (isMultiple) {
                                        toggleMulti(opt.id, opt.text);
                                        item.classList.toggle('selected', isSelected(opt.id));
                                    } else {
                                        setSingle(opt.id, opt.text);
                                        closeDropdown();
                                    }
                                    renderSelectedDisplay(select, display);
                                    select.dispatchEvent(new Event('change', { bubbles: true }));
                                });
                                optionList.appendChild(item);
                            });
                        }
                    });
                }, 250);
                return;
            }
            // Sync mode
            Array.from(select.options).forEach((opt, idx) => {
                if (filter && !opt.text.toLowerCase().includes(filter.toLowerCase())) return;
                found = true;
                const item = document.createElement('div');
                item.className = 'gfwcg-select-option';
                item.setAttribute('role', 'option');
                item.setAttribute('data-value', opt.value);
                item.setAttribute('aria-selected', opt.selected ? 'true' : 'false');
                item.tabIndex = -1;
                item.textContent = opt.text;
                if (opt.selected) item.classList.add('selected');
                item.addEventListener('click', e => {
                    if (isMultiple) {
                        opt.selected = !opt.selected;
                        item.classList.toggle('selected', opt.selected);
                    } else {
                        Array.from(select.options).forEach(o => o.selected = false);
                        opt.selected = true;
                        closeDropdown();
                    }
                    renderSelectedDisplay(select, display);
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
                optionList.appendChild(item);
            });
            if (!found) {
                const noRes = document.createElement('div');
                noRes.className = 'gfwcg-select-no-results';
                noRes.textContent = 'No results';
                optionList.appendChild(noRes);
            }
        }

        // Helpers for async
        function isSelected(id) {
            return Array.from(select.options).some(o => o.value == id && o.selected);
        }
        function toggleMulti(id, text) {
            let opt = Array.from(select.options).find(o => o.value == id);
            if (!opt) {
                opt = new Option(text, id, true, true);
                select.appendChild(opt);
            }
            opt.selected = !opt.selected;
        }
        function setSingle(id, text) {
            let opt = Array.from(select.options).find(o => o.value == id);
            if (!opt) {
                opt = new Option(text, id, true, true);
                select.appendChild(opt);
            }
            Array.from(select.options).forEach(o => o.selected = false);
            opt.selected = true;
        }
        async function fetchOptions(term) {
            const ajax = options.ajax;
            const params = new URLSearchParams({
                action: ajax.action,
                term,
                security: ajax.nonce
            });
            const res = await fetch(ajax.url + '?' + params.toString(), { credentials: 'same-origin' });
            const data = await res.json();
            // Convert {id: text, ...} to [{id, text}, ...]
            if (Array.isArray(data)) {
                return Object.entries(data).map(([id, text]) => ({ id, text }));
            } else if (typeof data === 'object') {
                return Object.entries(data).map(([id, text]) => ({ id, text }));
            }
            return [];
        }

        // Open/close dropdown
        function openDropdown() {
            dropdown.style.display = 'block';
            container.setAttribute('aria-expanded', 'true');
            search.focus();
            renderOptions(search.value);
        }
        function closeDropdown() {
            dropdown.style.display = 'none';
            container.setAttribute('aria-expanded', 'false');
        }

        // Display click (always open dropdown)
        display.addEventListener('click', e => {
            if (dropdown.style.display === 'block') {
                closeDropdown();
            } else {
                openDropdown();
            }
        });

        // Keyboard navigation
        container.addEventListener('keydown', e => {
            if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openDropdown();
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });
        search.addEventListener('keydown', e => {
            const items = optionList.querySelectorAll('.gfwcg-select-option');
            let idx = Array.from(items).findIndex(i => i === document.activeElement);
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (idx < items.length - 1) items[idx + 1].focus();
                else if (items.length) items[0].focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (idx > 0) items[idx - 1].focus();
                else if (items.length) items[items.length - 1].focus();
            } else if (e.key === 'Escape') {
                closeDropdown();
                container.focus();
            }
        });

        // Search
        search.addEventListener('input', e => {
            renderOptions(search.value);
        });

        // Click outside to close
        document.addEventListener('mousedown', e => {
            if (!container.contains(e.target)) closeDropdown();
        });

        // Update display on select change
        select.addEventListener('change', () => {
            renderSelectedDisplay(select, display);
        });

        // Initial render
        renderOptions();
    }

    function renderSelectedDisplay(select, display) {
        const selected = Array.from(select.selectedOptions);
        display.innerHTML = '';
        console.log('[GFWCGSelect] renderSelectedDisplay for', select, 'selected:', selected.map(o => o.value));
        if (!selected.length) {
            display.textContent = select.getAttribute('data-placeholder') || 'Select...';
            return;
        }
        if (select.multiple) {
            selected.forEach(opt => {
                const chip = document.createElement('span');
                chip.className = 'gfwcg-select-chip';
                chip.textContent = opt.text;
                const remove = document.createElement('span');
                remove.className = 'gfwcg-select-chip-remove';
                remove.textContent = '×';
                remove.tabIndex = 0;
                remove.setAttribute('aria-label', 'Remove ' + opt.text);
                remove.addEventListener('click', e => {
                    opt.selected = false;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
                chip.appendChild(remove);
                display.appendChild(chip);
            });
        } else {
            display.textContent = selected[0].text;
        }
    }

    /**
     * Initialize the custom select component on all matching elements
     * @param {string|NodeList|Array} selector - CSS selector or NodeList/Array of select elements
     * @param {Object} opts - Configuration options
     */
    function init(selector, opts = {}) {
        console.log('[GFWCGSelect] Initializing with selector:', selector);
        const selects = typeof selector === 'string' ? document.querySelectorAll(selector) : selector;
        console.log('[GFWCGSelect] Found elements:', selects.length);
        selects.forEach(select => {
            if (!select || select.tagName.toLowerCase() !== 'select') {
                console.log('[GFWCGSelect] Skipping non-select element:', select);
                return;
            }
            console.log('[GFWCGSelect] Creating dropdown for:', select);
            createDropdown(select, opts);
        });
    }

    return { init };
})();

window.GFWCGSelect = GFWCGSelect;
console.log('[GFWCGSelect] Component loaded and available globally'); 