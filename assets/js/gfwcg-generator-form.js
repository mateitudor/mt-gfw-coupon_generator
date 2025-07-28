// Helper to destroy GFWCGSelect for a select (removes custom UI)
function destroyGFWCGSelect(selector) {
    document.querySelectorAll(selector).forEach(select => {
        // Remove custom container if present
        const next = select.nextElementSibling;
        if (next && next.classList.contains('gfwcg-select-container')) {
            next.remove();
        }
        select.style.display = '';
        delete select.dataset.gfwcgSelect;
    });
}

jQuery(document).ready(function($) {
	console.log('GFWCG Generator Form: Document ready');
	
	// Small delay to ensure everything is loaded
	setTimeout(function() {
		// Wait for GFWCGSelect to be available
		if (typeof window.GFWCGSelect === 'undefined') {
			console.error('GFWCGSelect not loaded');
			return;
		}
		
		console.log('GFWCG Generator Form: GFWCGSelect is available');
		
		// Debug: Check if select elements exist
		console.log('GFWCG Generator Form: Checking for select elements...');
		const formSelects = document.querySelectorAll('#form_id, #email_field_id, #name_field_id, #coupon_field_id');
		console.log('GFWCG Generator Form: Found form selects:', formSelects.length);
		formSelects.forEach((select, index) => {
			console.log(`GFWCG Generator Form: Select ${index}:`, select.id, select.tagName);
		});

		// Initialize custom select for form fields
		window.GFWCGSelect.init('#form_id, #email_field_id, #name_field_id, #coupon_field_id', {
			placeholder: gfwcgAdmin.selectFieldText,
			allowClear: true
		});

		// Debug: Check if WooCommerce select elements exist
		console.log('GFWCG Generator Form: Checking for WooCommerce select elements...');
		const wcSelects = document.querySelectorAll('select.wc-product-search, select[name="product_ids[]"], select[name="exclude_product_ids[]"], #product_categories, #exclude_product_categories');
		console.log('GFWCG Generator Form: Found WooCommerce selects:', wcSelects.length);
		wcSelects.forEach((select, index) => {
			console.log(`GFWCG Generator Form: WC Select ${index}:`, select.className, select.name, select.id);
		});

		// Initialize custom select for product fields
		window.GFWCGSelect.init('select.wc-product-search, select[name="product_ids[]"], select[name="exclude_product_ids[]"]', {
			async: true,
			ajax: {
				url: gfwcgAdmin.ajaxUrl,
				action: 'gfwcg_search_products',
				nonce: gfwcgAdmin.nonce,
				minLength: 1,
				preload: true,
				placeholder: 'Search for a product…'
			},
			allowClear: true
		});

		// Initialize custom select for category fields
		window.GFWCGSelect.init('#product_categories, #exclude_product_categories', {
			async: true,
			ajax: {
				url: gfwcgAdmin.ajaxUrl,
				action: 'gfwcg_search_categories',
				nonce: gfwcgAdmin.nonce,
				minLength: 1,
				preload: true,
				placeholder: 'Search for a category…'
			},
			allowClear: true
		});
	}, 100);

    function formatFieldOption(field) {
        if (!field.id) {
            return field.text;
        }

        var $option = $(field.element);
        var type = $option.data('type');
        var isRequired = $option.find('.required').length > 0;
        var $wrapper = $('<span></span>');

        $wrapper.text(field.text);

        if (isRequired) {
            $wrapper.append(' <span class="required">*</span>');
        }

        if (type) {
            $wrapper.append(' <span class="field-type">(' + type + ')</span>');
        }

        return $wrapper;
    }

    function formatFieldSelection(field) {
        if (!field.id) {
            return field.text;
        }

        var $option = $(field.element);
        return $option.text();
    }

    // Handle form field updates
    $('#form_id').on('change', function() {
        var formId = $(this).val();
        var $emailField = $('#email_field_id');
        var $nameField = $('#name_field_id');
        var $couponField = $('#coupon_field_id');

        // Clear and disable fields
        $emailField.val('').trigger('change').prop('disabled', true);
        $nameField.val('').trigger('change').prop('disabled', true);
        $couponField.val('').trigger('change').prop('disabled', true);

        if (!formId) {
            return;
        }

        $.ajax({
            url: gfwcgAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gfwcg_get_form_fields',
                nonce: gfwcgAdmin.nonce,
                form_id: formId
            },
            beforeSend: function() {
                $emailField.addClass('loading');
                $nameField.addClass('loading');
                $couponField.addClass('loading');
            },
            success: function(response) {
                if (response.success) {
                    updateFieldSelects(response.data.fields);
                } else {
                    alert(response.data.message || gfwcgAdmin.errorText);
                }
            },
            error: function() {
                alert(gfwcgAdmin.errorText);
            },
            complete: function() {
                $emailField.removeClass('loading').prop('disabled', false);
                $nameField.removeClass('loading').prop('disabled', false);
                $couponField.removeClass('loading').prop('disabled', false);
            }
        });
    });

    function updateFieldSelects(fields) {
        var $emailField = $('#email_field_id');
        var $nameField = $('#name_field_id');
        var $couponField = $('#coupon_field_id');

        // Destroy previous custom selects
        destroyGFWCGSelect('#email_field_id, #name_field_id, #coupon_field_id');

        // Clear existing options
        $emailField.empty();
        $nameField.empty();
        $couponField.empty();

        // Add default option
        var defaultOption = new Option(gfwcgAdmin.selectFieldText, '', true, true);
        $emailField.append(defaultOption);
        $nameField.append(new Option(gfwcgAdmin.selectFieldText, '', true, true));
        $couponField.append(new Option(gfwcgAdmin.selectFieldText, '', true, true));

        // Add field options
        fields.forEach(function(field) {
            var $option = $('<option></option>')
                .val(field.id)
                .text(field.label)
                .data('type', field.type);

            if (field.required) {
                $option.append('<span class="required">*</span>');
            }

            $emailField.append($option.clone());
            $nameField.append($option.clone());
            $couponField.append($option.clone());
        });

        // Re-initialize custom selects
        setTimeout(function() {
            window.GFWCGSelect.init('#email_field_id, #name_field_id, #coupon_field_id', {
                placeholder: gfwcgAdmin.selectFieldText,
                allowClear: true
            });
        }, 50);
    }

    // Handle form submission
    $('.gfwcg-generator-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        var formData = new FormData(this);

        // Validate required fields
        var $formId = $('#form_id');
        var $emailField = $('#email_field_id');
        
        if (!$formId.val() || !$emailField.val()) {
            alert(gfwcgAdmin.requiredFieldsText);
            return false;
        }

        // Add nonce to form data
        formData.append('action', 'gfwcg_save_generator');
        formData.append('nonce', gfwcgAdmin.nonce);

        // Disable submit button
        $submitButton.prop('disabled', true);

        $.ajax({
            url: gfwcgAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $form.addClass('loading');
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        alert(response.data.message || 'Generator saved successfully.');
                        window.location.reload();
                    }
                } else {
                    alert(response.data.message || gfwcgAdmin.errorText);
                    $submitButton.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                alert(gfwcgAdmin.errorText);
                $submitButton.prop('disabled', false);
            },
            complete: function() {
                $form.removeClass('loading');
            }
        });
    });

    // Handle delete generator button
    const deleteButtons = document.querySelectorAll('.delete-generator');
    deleteButtons.forEach(button => {
        let isConfirming = false;
        const originalText = button.textContent;
        const confirmText = button.dataset.confirmText;
        const deleteText = button.dataset.deleteText;

        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!isConfirming) {
                // First click - show confirmation
                isConfirming = true;
                button.textContent = confirmText || 'Click again to confirm';
                button.classList.add('confirming');
                
                // Reset after 3 seconds
                setTimeout(() => {
                    isConfirming = false;
                    button.textContent = originalText;
                    button.classList.remove('confirming');
                }, 3000);
            } else {
                // Second click - proceed with deletion
                const generatorId = button.dataset.id;
                
                if (!generatorId) {
                    alert('Missing required data for deletion.');
                    return;
                }
                
                // Show loading state
                button.textContent = deleteText || 'Deleting...';
                button.disabled = true;
                
                // Send delete request
                $.ajax({
                    url: gfwcgAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'gfwcg_delete_generator',
                        id: generatorId,
                        nonce: gfwcgAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Remove the generator element from the page
                            const generatorElement = button.closest('.gfwcg-grid-item, tr');
                            if (generatorElement) {
                                generatorElement.remove();
                            }
                            
                            // Show success message
                            alert(response.data.message || 'Generator deleted successfully.');
                            
                            // Reload page if no generators left
                            const remainingGenerators = document.querySelectorAll('.gfwcg-grid-item, .gfwcg-list-item');
                            if (remainingGenerators.length === 0) {
                                window.location.reload();
                            }
                        } else {
                            alert(response.data.message || 'Error deleting generator.');
                            button.disabled = false;
                            button.textContent = originalText;
                        }
                    },
                    error: function() {
                        alert('Error deleting generator.');
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                });
            }
        });
    });

    // Handle coupon type toggle
    $('#coupon_type').on('change', function() {
        var couponType = $(this).val();
        var $couponFieldRow = $('#coupon_field_id_row');
        var $couponField = $('#coupon_field_id');
        
        if (couponType === 'field') {
            $couponFieldRow.show();
            $couponField.prop('required', true);
        } else {
            $couponFieldRow.hide();
            $couponField.prop('required', false);
        }
    });

    // Initialize coupon type toggle on page load
    $('#coupon_type').trigger('change');
}); 