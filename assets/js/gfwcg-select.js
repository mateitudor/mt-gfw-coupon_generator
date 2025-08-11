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
		display.setAttribute('data-placeholder', select.getAttribute('data-placeholder') || 'Select...');
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
		search.setAttribute('placeholder', 'Search...');
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
			console.log('[GFWCGSelect] renderOptions called with filter:', filter);
			optionList.innerHTML = '';
			loading.style.display = 'none';
			const isMultiple = select.multiple;
			let found = false;

			// Async mode
			if (options.async && options.ajax) {
				const minLength = options.ajax.minLength || 1;
				const shouldSearch = filter.length >= minLength;
				const shouldPreload = !filter && options.ajax.preload;
				
				// Always show loading for initial load or when searching
				if (shouldSearch || shouldPreload) {
					console.log('[GFWCGSelect] Async mode - fetching options...');
					optionList.innerHTML = '';
					loading.style.display = 'block';
					
					debounce(() => {
						fetchOptions(filter).then(results => {
							console.log('[GFWCGSelect] Fetch completed, rendering results...');
							loading.style.display = 'none';
							
							// Clear the option list first
							optionList.innerHTML = '';
							
							// Always show currently selected options at the top, even if not in results
							const selectedOptions = Array.from(select.options).filter(o => o.selected);
							const renderedIds = new Set();
							
							console.log('[GFWCGSelect] Selected options:', selectedOptions.map(o => ({ value: o.value, text: o.text })));
							
							selectedOptions.forEach(opt => {
								const item = createOptionElement(opt.value, opt.text, true, isMultiple, isSelected);
								optionList.appendChild(item);
								renderedIds.add(opt.value);
							});
							
							// Render AJAX results, skipping already rendered selected
							if (!results.length && !selectedOptions.length) {
								const noRes = document.createElement('div');
								noRes.className = 'gfwcg-select-no-results';
								noRes.textContent = 'No results found';
								optionList.appendChild(noRes);
							} else {
								console.log('[GFWCGSelect] Rendering', results.length, 'results');
								results.forEach(opt => {
									if (renderedIds.has(opt.id)) {
										console.log('[GFWCGSelect] Skipping already rendered option:', opt.id);
										return;
									}
									const item = createOptionElement(opt.id, opt.text, isSelected(opt.id), isMultiple, isSelected);
									optionList.appendChild(item);
								});
							}
						}).catch(error => {
							console.error('[GFWCGSelect] Error fetching options:', error);
							loading.style.display = 'none';
							const errorMsg = document.createElement('div');
							errorMsg.className = 'gfwcg-select-no-results';
							errorMsg.textContent = 'Error loading results';
							optionList.appendChild(errorMsg);
						});
					}, 250);
					return;
				}
			}

			// Sync mode
			console.log('[GFWCGSelect] Sync mode - rendering options...');
			Array.from(select.options).forEach((opt, idx) => {
				if (filter && !opt.text.toLowerCase().includes(filter.toLowerCase())) return;
				found = true;
				const item = createOptionElement(opt.value, opt.text, opt.selected, isMultiple, isSelected);
				optionList.appendChild(item);
			});
			
			if (!found) {
				const noRes = document.createElement('div');
				noRes.className = 'gfwcg-select-no-results';
				noRes.textContent = 'No results found';
				optionList.appendChild(noRes);
			}
		}

		// Create option element with proper event handling
		function createOptionElement(value, text, isSelected, isMultiple, isSelectedChecker) {
			console.log('[GFWCGSelect] Creating option element:', { value, text, isSelected, isMultiple });
			const item = document.createElement('div');
			item.className = 'gfwcg-select-option';
			item.setAttribute('role', 'option');
			item.setAttribute('data-value', value);
			item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
			item.tabIndex = -1;
			item.textContent = text;
			
			if (isSelected) {
				item.classList.add('selected');
			}

			console.log('[GFWCGSelect] Created option element:', item);

			// Single click handler with proper event management
			item.addEventListener('click', function(e) {
				console.log('[GFWCGSelect] Click event triggered!');
				console.log('[GFWCGSelect] Event target:', e.target);
				console.log('[GFWCGSelect] Event currentTarget:', e.currentTarget);
				
				// Don't prevent default or stop propagation - let the event bubble
				// e.preventDefault();
				// e.stopPropagation();
				
				console.log('[GFWCGSelect] Option clicked:', { value, text, isMultiple });
				
				if (isMultiple) {
					console.log('[GFWCGSelect] Processing multi-select...');
					toggleMulti(value, text);
					// Update the visual state based on the actual selection
					const isCurrentlySelected = isSelectedChecker ? isSelectedChecker(value) : isSelected;
					item.classList.toggle('selected', isCurrentlySelected);
					item.setAttribute('aria-selected', isCurrentlySelected ? 'true' : 'false');
					console.log('[GFWCGSelect] Toggled multi-select option:', { value, text, selected: isCurrentlySelected });
				} else {
					console.log('[GFWCGSelect] Processing single-select...');
					setSingle(value, text);
					closeDropdown();
					console.log('[GFWCGSelect] Set single-select option:', { value, text });
				}
				
				console.log('[GFWCGSelect] Calling renderSelectedDisplay...');
				renderSelectedDisplay(select, display);
				console.log('[GFWCGSelect] Dispatching change event...');
				select.dispatchEvent(new Event('change', { bubbles: true }));
				console.log('[GFWCGSelect] Click handler completed');
			});

			// Also add a mousedown event to ensure it's clickable
			item.addEventListener('mousedown', function(e) {
				console.log('[GFWCGSelect] Mouse down on option:', { value, text });
			});

			return item;
		}

		// Helpers for async
		function isSelected(id) {
			return Array.from(select.options).some(o => o.value == id && o.selected);
		}
		function toggleMulti(id, text) {
			console.log('[GFWCGSelect] toggleMulti called with:', { id, text });
			let opt = Array.from(select.options).find(o => o.value == id);
			if (!opt) {
				console.log('[GFWCGSelect] Creating new option for:', { id, text });
				opt = new Option(text, id, true, true);
				select.appendChild(opt);
				// New option is already selected, no need to toggle
			} else {
				// If option already exists, toggle its selected state
				opt.selected = !opt.selected;
			}
			console.log('[GFWCGSelect] Option selected state:', { id, text, selected: opt.selected });
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
			console.log('[GFWCGSelect] fetchOptions called with term:', term);
			const ajax = options.ajax;
			const params = new URLSearchParams({
				action: ajax.action,
				term: term || '',
				security: ajax.nonce
			});
			
			const url = ajax.url + '?' + params.toString();
			console.log('[GFWCGSelect] Fetching from URL:', url);
			
			const res = await fetch(url, { 
				credentials: 'same-origin',
				headers: {
					'X-Requested-With': 'XMLHttpRequest'
				}
			});
			
			if (!res.ok) {
				throw new Error(`HTTP error! status: ${res.status}`);
			}
			
			const data = await res.json();
			console.log('[GFWCGSelect] Received data:', data);
			
			// Convert {id: text, ...} to [{id, text}, ...]
			if (Array.isArray(data)) {
				const result = data.map(item => ({ id: item.id || item.value, text: item.text || item.label }));
				console.log('[GFWCGSelect] Converted array data:', result);
				return result;
			} else if (typeof data === 'object') {
				const result = Object.entries(data).map(([id, text]) => ({ id, text }));
				console.log('[GFWCGSelect] Converted object data:', result);
				return result;
			}
			console.log('[GFWCGSelect] No data to return');
			return [];
		}

		// Track if dropdown is open to prevent double-clicks
		let isDropdownOpen = false;

		// Open/close dropdown
		function openDropdown() {
			if (isDropdownOpen) return;
			
			dropdown.style.display = 'block';
			container.setAttribute('aria-expanded', 'true');
			isDropdownOpen = true;
			
			// Focus search and render options
			setTimeout(() => {
				search.focus();
				renderOptions(search.value);
			}, 10);
		}
		
		function closeDropdown() {
			if (!isDropdownOpen) return;
			
			dropdown.style.display = 'none';
			container.setAttribute('aria-expanded', 'false');
			isDropdownOpen = false;
			search.value = '';
		}

		// Display click handler
		display.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			if (isDropdownOpen) {
				closeDropdown();
			} else {
				openDropdown();
			}
		});

		// Keyboard navigation
		container.addEventListener('keydown', function(e) {
			if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				if (!isDropdownOpen) {
					openDropdown();
				}
			} else if (e.key === 'Escape') {
				closeDropdown();
				container.focus();
			}
		});
		
		search.addEventListener('keydown', function(e) {
			const items = optionList.querySelectorAll('.gfwcg-select-option');
			let idx = Array.from(items).findIndex(i => i === document.activeElement);
			
			if (e.key === 'ArrowDown') {
				e.preventDefault();
				if (idx < items.length - 1) {
					items[idx + 1].focus();
				} else if (items.length) {
					items[0].focus();
				}
			} else if (e.key === 'ArrowUp') {
				e.preventDefault();
				if (idx > 0) {
					items[idx - 1].focus();
				} else if (items.length) {
					items[items.length - 1].focus();
				}
			} else if (e.key === 'Escape') {
				closeDropdown();
				container.focus();
			} else if (e.key === 'Enter') {
				e.preventDefault();
				const focusedItem = document.activeElement;
				if (focusedItem && focusedItem.classList.contains('gfwcg-select-option')) {
					focusedItem.click();
				}
			}
		});

		// Search input handler
		search.addEventListener('input', function(e) {
			renderOptions(e.target.value);
		});

		// Click outside to close
		document.addEventListener('mousedown', function(e) {
			console.log('[GFWCGSelect] Document mousedown, target:', e.target);
			if (!container.contains(e.target)) {
				console.log('[GFWCGSelect] Click outside container, closing dropdown');
				closeDropdown();
			} else {
				console.log('[GFWCGSelect] Click inside container, keeping dropdown open');
			}
		});

		// Update display on select change
		select.addEventListener('change', function() {
			renderSelectedDisplay(select, display);
		});

		// Initial render for sync mode or preload for async
		if (options.async && options.ajax && options.ajax.preload) {
			console.log('[GFWCGSelect] Preloading options for:', select.id);
			renderOptions();
		} else if (!options.async) {
			console.log('[GFWCGSelect] Rendering sync options for:', select.id);
			renderOptions();
		}
	}

	function renderSelectedDisplay(select, display) {
		const selected = Array.from(select.selectedOptions);
		display.innerHTML = '';
		console.log('[GFWCGSelect] renderSelectedDisplay for', select.id, 'selected:', selected.map(o => ({ value: o.value, text: o.text })));
		
		if (!selected.length) {
			display.textContent = '';
			console.log('[GFWCGSelect] No selected options, clearing display');
			return;
		}
		
		if (select.multiple) {
			console.log('[GFWCGSelect] Creating chips for multiple select');
			selected.forEach(opt => {
				const chip = document.createElement('span');
				chip.className = 'gfwcg-select-chip';
				chip.textContent = opt.text;
				
				const remove = document.createElement('span');
				remove.className = 'gfwcg-select-chip-remove';
				remove.textContent = '×';
				remove.tabIndex = 0;
				remove.setAttribute('aria-label', 'Remove ' + opt.text);
				
				remove.addEventListener('click', function(e) {
					e.preventDefault();
					e.stopPropagation();
					opt.selected = false;
					select.dispatchEvent(new Event('change', { bubbles: true }));
				});
				
				remove.addEventListener('keydown', function(e) {
					if (e.key === 'Enter' || e.key === ' ') {
						e.preventDefault();
						opt.selected = false;
						select.dispatchEvent(new Event('change', { bubbles: true }));
					}
				});
				
				chip.appendChild(remove);
				display.appendChild(chip);
				console.log('[GFWCGSelect] Created chip for:', opt.text);
			});
		} else {
			display.textContent = selected[0].text;
			console.log('[GFWCGSelect] Set single select display to:', selected[0].text);
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
