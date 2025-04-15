jQuery(document).ready(function($) {
    // Initialize Select2 for form fields
    function initSelect2() {
        $('#form_id, #email_field_id, #name_field_id').select2({
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

        // Clear and disable fields
        $emailField.val('').trigger('change').prop('disabled', true);
        $nameField.val('').trigger('change').prop('disabled', true);

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
            }
        });
    });

    function updateFieldSelects(fields) {
        var $emailField = $('#email_field_id');
        var $nameField = $('#name_field_id');

        // Clear existing options
        $emailField.empty();
        $nameField.empty();

        // Add default option
        var defaultOption = new Option(gfwcgAdmin.selectFieldText, '', true, true);
        $emailField.append(defaultOption);
        $nameField.append(new Option(gfwcgAdmin.selectFieldText, '', true, true));

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
        });

        // Trigger change to update Select2
        $emailField.trigger('change');
        $nameField.trigger('change');
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

    // Handle delete action
    $('.button-link-delete').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(gfwcgAdmin.confirmDeleteText)) {
            return;
        }

        var $row = $(this).closest('tr');
        var id = $(this).data('id');

        $.ajax({
            url: gfwcgAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gfwcg_delete_generator',
                nonce: gfwcgAdmin.nonce,
                id: id
            },
            beforeSend: function() {
                $row.addClass('loading');
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(400, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || gfwcgAdmin.errorText);
                }
            },
            error: function() {
                alert(gfwcgAdmin.errorText);
            },
            complete: function() {
                $row.removeClass('loading');
            }
        });
    });
}); 