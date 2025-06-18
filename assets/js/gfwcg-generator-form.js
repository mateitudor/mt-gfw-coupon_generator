jQuery(document).ready(function($) {
    // Initialize Select2 for form fields
    function initSelect2() {
        $('#form_id, #email_field_id, #name_field_id, #coupon_field_id').select2({
            width: '100%',
            placeholder: gfwcgAdmin.selectFieldText,
            allowClear: true,
            templateResult: formatFieldOption,
            templateSelection: formatFieldSelection
        });
    }
    initSelect2();

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

        // Trigger change to update Select2
        $emailField.trigger('change');
        $nameField.trigger('change');
        $couponField.trigger('change');
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
                button.textContent = confirmText;
                button.classList.add('button-link-delete');
                return;
            }

            // Second click - proceed with deletion
            const generatorId = button.dataset.id;
            const nonce = button.dataset.nonce;
            
            button.disabled = true;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'gfwcg_delete_generator',
                    generator_id: generatorId,
                    nonce: nonce
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Check if we're in single view
                    const isSingleView = document.querySelector('.gfwcg-generator-form') !== null;
                    
                    if (isSingleView) {
                        // Redirect to the generators list page
                        window.location.href = window.location.origin + '/wp-admin/admin.php?page=gfwcg-generators';
                    } else {
                        // Find the closest row or grid item
                        const container = button.closest('tr, .gfwcg-grid-item');
                        if (container) {
                            // Add fade out animation
                            container.style.transition = 'opacity 0.3s';
                            container.style.opacity = '0';
                            
                            // Remove after animation
                            setTimeout(() => {
                                container.remove();
                                
                                // Check if we need to show empty message
                                const remainingItems = document.querySelectorAll('.gfwcg-grid-item, .wp-list-table tbody tr');
                                if (remainingItems.length === 0) {
                                    const grid = document.querySelector('.gfwcg-grid');
                                    const tableBody = document.querySelector('.wp-list-table tbody');
                                    
                                    if (grid) {
                                        grid.innerHTML = `<div class="gfwcg-grid-empty">${data.data}</div>`;
                                    } else if (tableBody) {
                                        tableBody.innerHTML = `<tr><td colspan="8">${data.data}</td></tr>`;
                                    }
                                }
                            }, 300);
                        }
                    }
                } else {
                    throw new Error(data.data || 'Failed to delete generator');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'An error occurred while deleting the generator.');
            })
            .finally(() => {
                button.disabled = false;
                isConfirming = false;
                button.textContent = originalText;
                button.classList.remove('button-link-delete');
            });
        });

        // Reset button state if clicking outside
        document.addEventListener('click', function(e) {
            if (!button.contains(e.target)) {
                isConfirming = false;
                button.textContent = originalText;
                button.classList.remove('button-link-delete');
            }
        });
    });
}); 