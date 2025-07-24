jQuery(document).ready(function($) {
    'use strict';
    
    const ReserveMateAdmin = {
        config: {
            currentOpenDetails: null,
            currentOpenSummaryRow: null,
            fieldCounter: 0
        },
        
        init: function() {
            this.initGlobalFeatures();
            this.initPageSpecificFeatures();
        },
        
        initGlobalFeatures: function() {
            this.setupTabNavigation();
            this.setupCalendarSettingsToggle();
        },
        
        initPageSpecificFeatures: function() {
            const urlParams = window.location.search;
            
            if (urlParams.includes('reserve-mate-services')) {
                this.initServicesPage();
            } else if (urlParams.includes('page=reserve-mate-settings')) {
                this.initSettingsPage();
            } else if (urlParams.includes('page=reserve-mate-payments')) {
                this.initPaymentsPage();
            } else if (urlParams.includes('reserve-mate-bookings')) {
                this.initServiceBookingsPage();
            } else if (urlParams.includes('reserve-mate-staff')) {
                this.initStaffSettingsPage();
            }
        },
        
        setupDetailsToggle: function(toggleClass, dataIdPrefix) {
            const self = this;
            
            $(toggleClass).each(function() {
                const $button = $(this);
                
                $button.off('click touchstart').on('click touchstart', function(event) {
                    if (event.type === 'touchstart') {
                        event.preventDefault();
                    }
                    
                    self.handleDetailsToggle($button, dataIdPrefix);
                });
            });
        },
        
        handleDetailsToggle: function($button, dataIdPrefix) {
            const entityId = $button.attr(`data-${dataIdPrefix}-id`);
            const $detailsRow = $('#details-' + entityId);
            const $summaryRow = $button.closest('tr');
            
            if (!this.config.currentOpenDetails || this.config.currentOpenDetails[0] !== $detailsRow[0]) {
                this.openNewDetails($button, $detailsRow, $summaryRow, dataIdPrefix);
            } else {
                this.closeDetails($button, $detailsRow, $summaryRow);
            }
        },
        
        openNewDetails: function($button, $detailsRow, $summaryRow, dataIdPrefix) {
            const self = this;
            
            const openDetails = function() {
                $detailsRow.show();
                $detailsRow.find('.table-details-container').hide().slideDown();
                $button.html('<span class="dashicons dashicons-arrow-up-alt"></span>');
                self.config.currentOpenDetails = $detailsRow;
                
                $summaryRow.addClass('details-row-active');
                $detailsRow.addClass('details-section-active');
                self.config.currentOpenSummaryRow = $summaryRow;
            };
            
            if (this.config.currentOpenDetails) {
                this.closeCurrentDetails(dataIdPrefix, openDetails);
            } else {
                openDetails();
            }
        },
        
        closeCurrentDetails: function(dataIdPrefix, callback) {
            const toggleClass = dataIdPrefix === 'service' ? '.toggle-details-service' : '.toggle-details-booking';
            const $previousButton = $(toggleClass + `[data-${dataIdPrefix}-id="${this.config.currentOpenDetails.attr('id').replace('details-', '')}"]`);
            
            $previousButton.html('<span class="dashicons dashicons-arrow-down-alt"></span>');
            
            if (this.config.currentOpenSummaryRow) {
                this.config.currentOpenSummaryRow.removeClass('details-row-active');
                this.config.currentOpenDetails.removeClass('details-section-active');
            }
            
            this.config.currentOpenDetails.find('.table-details-container').slideUp(callback);
        },
        
        closeDetails: function($button, $detailsRow, $summaryRow) {
            $detailsRow.find('.table-details-container').slideUp(function() {
                $detailsRow.hide();
            });
            $button.html('<span class="dashicons dashicons-arrow-down-alt"></span>');
            $summaryRow.removeClass('details-row-active');
            $detailsRow.removeClass('details-section-active');
            this.config.currentOpenDetails = null;
            this.config.currentOpenSummaryRow = null;
        },
        
        setupTabNavigation: function() {
            $('.tab-button').off('click').on('click', function() {
                $('.tab-button, .tab-content').removeClass('active');
                
                $(this).addClass('active');
                const target = $(this).attr('data-target');
                $(target).addClass('active');
                
                if (window.location.search.includes('reserve-mate-bookings')) {
                    try {
                        localStorage.setItem('activeTab', target);
                    } catch (e) {
                        console.warn('localStorage not available:', e);
                    }
                }
            });
        },

        initTabManagement: function(storageKey, defaultTab) {
            let activeTab = defaultTab;
            
            try {
                activeTab = localStorage.getItem(storageKey) || defaultTab;
            } catch (e) {
                console.warn('localStorage not available:', e);
            }
            
            // Hide all forms except the active one
            $('.nav-tab').removeClass('nav-tab-active');
            $('.tab-content').removeClass('active').hide();
            
            // Show the active tab and its corresponding form
            $(`a[data-tab="${activeTab}"]`).addClass('nav-tab-active');
            $(`#${activeTab}`).addClass('active').show();
            
            $('.nav-tab').off('click').on('click', function(e) {
                e.preventDefault();
                
                const tabId = $(this).data('tab');
                
                // Hide all forms
                $('.nav-tab').removeClass('nav-tab-active');
                $('.tab-content').removeClass('active').hide();
                
                // Show the selected tab's form
                $(this).addClass('nav-tab-active');
                $(`#${tabId}`).addClass('active').show();
                
                try {
                    localStorage.setItem(storageKey, tabId);
                } catch (e) {
                    console.warn('localStorage not available:', e);
                }
            });
        },
        
        setupCalendarSettingsToggle: function() {
            const toggleCalendarSettings = function() {
                const $checkbox = $('#save_to_google_calendar');
                const fields = ['calendar_api_key', 'calendar_id', 'calendar_timezones'];
                
                fields.forEach(function(fieldId) {
                    $(`[name="rm_google_calendar_options[${fieldId}]"]`).prop('disabled', !$checkbox.prop('checked'));
                });
            };
            
            $('#save_to_google_calendar').off('click').on('click', function(e) {
                e.preventDefault();
                toggleCalendarSettings();
            });
        },
        
        initServicesPage: function() {
            this.setupDetailsToggle('.toggle-details-service', 'service');
            this.setupServiceFormToggle();
            this.setupBulkActions("service");
        },
        
        setupServiceFormToggle: function() {
            $('#toggle-form-btn').off('click').on('click', function() {
                const $form = $('#service-form');
                const isHidden = $form.is(':hidden');
                
                $form.slideToggle(500, function() {
                    $(this).closest('.form-section').toggleClass('form-expanded', !isHidden);
                });
                $(this).text(isHidden ? 'Hide Form' : 'Add New Service');
            });
        },
        
        initSettingsPage: function() {
            this.initTabManagement('rm_booking_options_active_tab', 'general-tab');
            this.setupTimeValidation();
            this.setupColorPickers();
            this.setupDisabledDates();
            this.setupFormFields();
            this.setupBookingModeToggle();
            this.initDateTimePickers();
        },
        
        setupTimeValidation: function() {
            $('#booking_min_time, #booking_max_time').off('change').on('change', function() {
                const minTime = $('#booking_min_time').val();
                const maxTime = $('#booking_max_time').val();
                if (minTime > maxTime) {
                    $('#booking_max_time').val(minTime);
                }
            });
        },
        
        setupColorPickers: function() {
            $('.color-field').each(function() {
                if ($(this).hasClass('wp-color-picker')) {
                    return;
                }
                
                $(this).wpColorPicker({
                    defaultColor: $(this).data('default-color'),
                    change: function(event, ui) {
                        // Handle color changes if needed
                    },
                    clear: function() {
                        // Handle clearing if needed
                    }
                });
            });
        },
        
        setupDisabledDates: function() {
            this.setupDisabledDatesToggle();
            this.setupDisabledDateRules();
        },
        
        setupDisabledDatesToggle: function() {
            $('#disabled-dates-btn').off('click').on('click', function() {
                const $icon = $(this).find('.dashicons');
                const $container = $('.disabled-dates-container');
                
                $icon.toggleClass('dashicons-arrow-down-alt dashicons-arrow-up-alt');
                
                if ($container.is(':hidden')) {
                    $container.slideDown(700);
                } else {
                    $container.slideUp(700);
                }
            });
        },
        
        setupDisabledDateRules: function() {
            const self = this;
            
            $('#add-disabled-date-rule').off('click').on('click', function() {
                const index = Date.now();
                
                $.ajax({
                    url: reserve_mate_admin.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_disabled_date_rule',
                        index: index,
                        security: reserve_mate_admin.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            $('#disabled-dates-rules').append(response.data);
                            self.initDateTimePickers();
                        } else {
                            console.error("Invalid response format", response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                    }
                });
            });
            
            $(document).off('click', '.remove-disabled-date-rule').on('click', '.remove-disabled-date-rule', function() {
                $(this).closest('.disabled-date-rule').remove();
            });
            
            $(document).off('change', '.disabled-date-type').on('change', '.disabled-date-type', function() {
                const $container = $(this).closest('.disabled-date-rule').find('.disabled-date-options');
                $container.find('> div').hide();
                $container.find('.disabled-date-' + $(this).val()).show();
            });
            
            $(document).off('change', '.disabled-date-time input[name$="[date]"]').on('change', '.disabled-date-time input[name$="[date]"]', function() {
                const $weeklyOptions = $(this).closest('.disabled-date-time').find('.weekly-options');
                if ($(this).val() !== '') {
                    $weeklyOptions.hide();
                } else {
                    $weeklyOptions.show();
                }
            });
        },
        
        initDateTimePickers: function() {
            this.initTimePickers();
            this.initDatePickers();
        },
        
        initTimePickers: function() {
            if ($.fn.timepicker) {
                $('.timepicker').timepicker({
                    timeFormat: 'HH:mm',
                    interval: 30,
                    minTime: '00:00',
                    maxTime: '23:30',
                    dynamic: false,
                    dropdown: true,
                    scrollbar: true
                });
            } else if (typeof flatpickr === 'function') {
                flatpickr('.timepicker', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true
                });
            }
        },
        
        initDatePickers: function() {
            if ($.fn.datepicker) {
                $('.datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0
                });
            }
            
            if (typeof flatpickr === 'function') {
                flatpickr('.datepicker', {
                    dateFormat: 'Y-m-d',
                    minDate: 'today'
                });
            }
        },
        
        setupFormFields: function() {
            this.config.fieldCounter = parseInt($('#field-count').val(), 10) || 0;
            this.setupFormFieldToggle();
            this.setupFormFieldRemoval();
            this.setupFormFieldOptions();
            this.setupAddNewField();
            this.setupFormFieldSorting();
            this.setupFormFieldIdUpdates();
        },
        
        setupFormFieldToggle: function() {
            $('.form-fields-list').off('click', '.edit-field').on('click', '.edit-field', function() {
                $(this).closest('.form-field-item').find('.field-details').slideToggle();
            });
        },
        
        setupFormFieldOptions: function() {
            $('.form-fields-list').off('change', 'select[name*="[type]"]').on('change', 'select[name*="[type]"]', function() {
                const type = $(this).val();
                const optionsRow = $(this).closest('table').find('.field-options-row');
                
                if (type === 'select' || type === 'radio' || type === 'checkbox') {
                    optionsRow.show();
                } else {
                    optionsRow.hide();
                }
            });
        },
        
        setupAddNewField: function() {
            const self = this;
            
            $('.add-new-field').off('click').on('click', function() {
                const template = $('#field-template').html();
                const existingFields = $('.form-fields-list .form-field-item').length;
                const newIndex = existingFields;
                
                let lastOrder = 0;
                $('.form-fields-list input[name*="[order]"]').each(function() {
                    const order = parseInt($(this).val(), 10);
                    if (order > lastOrder) {
                        lastOrder = order;
                    }
                });
                
                const nextOrder = lastOrder + 1;
                const processedTemplate = template
                    .replace(/\{\{index\}\}/g, newIndex)
                    .replace(/\{\{order\}\}/g, nextOrder);
                
                $('.form-fields-list').append(processedTemplate);
                
                self.reindexFields();
            });
        },
        
        reindexFields: function() {
            $('.form-fields-list .form-field-item').each(function(index) {
                const $item = $(this);
                $item.attr('data-index', index);
                
                $item.find('input, select, textarea').each(function() {
                    const $input = $(this);
                    const name = $input.attr('name');
                    if (name && name.includes('[form_fields]')) {
                        const newName = name.replace(/\[form_fields\]\[\d+\]/, '[form_fields][' + index + ']');
                        $input.attr('name', newName);
                    }
                });
                
                $item.find('.description').each(function() {
                    const $desc = $(this);
                    const text = $desc.text();
                    if (text.includes('Use as variable:')) {
                        const fieldId = $item.find('input[name*="[id]"]').val() || 'field_' + index;
                        $desc.html('Unique identifier for the field (no spaces). Use as variable: {' + fieldId + '}');
                    }
                });
            });
        },
        
        setupFormFieldRemoval: function() {
            const self = this;
            $('.form-fields-list').off('click', '.remove-field').on('click', '.remove-field', function() {
                if (confirm('Are you sure you want to remove this field?')) {
                    $(this).closest('.form-field-item').remove();
                    self.reindexFields();
                }
            });
        },
        
        setupFormFieldSorting: function() {
            if ($.fn.sortable) {
                $('.form-fields-list').sortable({
                    handle: '.field-drag-handle',
                    update: function(event, ui) {
                        $('.form-fields-list .form-field-item').each(function(index) {
                            $(this).find('input[name*="[order]"]').val(index + 1);
                        });
                    }
                });
            }
        },
        
        setupFormFieldIdUpdates: function() {
            $('.form-fields-list').off('input', '.field-id-input').on('input', '.field-id-input', function() {
                const $input = $(this);
                const fieldId = $input.val();
                const $description = $input.siblings('.description');
                $description.html('Unique identifier for the field (no spaces). Use as variable: {' + fieldId + '}');
            });
            
            $('.form-fields-list').off('input', '.field-label-input').on('input', '.field-label-input', function() {
                const $input = $(this);
                const label = $input.val();
                const $fieldItem = $input.closest('.form-field-item');
                $fieldItem.find('.field-title').text(label);
            });
        },
        
        setupBookingModeToggle: function() {
            const $bookingMode = $('input[name="rm_booking_options[booking_mode]"]');
            const $maxDateContainer = $('#fixed-date-field');
            const $maxDaysContainer = $('#rolling-days-field');
            const $maxDateInput = $maxDateContainer.find('input');
            const $maxDaysInput = $maxDaysContainer.find('input');
            
            const toggleBookingModeFields = function() {
                if ($bookingMode.filter(':checked').val() === 'fixed') {
                    $maxDateContainer.show();
                    $maxDaysContainer.hide();
                    // Enable the visible field and disable the hidden one
                    $maxDateInput.prop('disabled', false);
                    $maxDaysInput.prop('disabled', true);
                } else {
                    $maxDateContainer.hide();
                    $maxDaysContainer.show();
                    // Enable the visible field and disable the hidden one
                    $maxDateInput.prop('disabled', true);
                    $maxDaysInput.prop('disabled', false);
                }
            };
            
            toggleBookingModeFields();
            $bookingMode.off('change').on('change', toggleBookingModeFields);
        },
        
        initPaymentsPage: function() {
            this.initTabManagement('rm_payment_options_active_tab', 'online-tab');
        },
        
        initServiceBookingsPage: function() {
            this.setupBookingStatusStyles();
            this.addDataLabels();
            this.setupDetailsToggle('.toggle-details-booking', 'booking');
            this.setupServiceSelect();
            this.setupBookingFormToggle();
            this.setupDateTimePickers();
            this.setupCostCalculation();
            this.setupBulkActions("booking");
        },
        
        setupBookingStatusStyles: function() {
            $('button.status-toggle-button').each(function() {
                const $button = $(this);
                $button
                    .toggleClass('confirmed-style', $button.find('.booking-status.confirmed').length > 0)
                    .toggleClass('pending-style', $button.find('.booking-status.pending').length > 0);
            });
        },
        
        addDataLabels: function() {
            if (window.innerWidth > 600) {
                return;
            }
            
            const headers = Array.from(document.querySelectorAll('.existing-bookings-table thead th'))
                .map(th => th.textContent.trim());
            
            document.querySelectorAll('.existing-bookings-table tbody tr:not(.table-details)').forEach(row => {
                const cells = row.querySelectorAll('td');
                
                cells.forEach((cell, index) => {
                    if (!headers[index] || index === cells.length - 1) return;
                    
                    const label = document.createElement('span');
                    label.className = 'mobile-label';
                    label.textContent = headers[index];
                    
                    cell.insertBefore(label, cell.firstChild);
                });
            });
        },
        
        setupServiceSelect: function() {
            const $serviceSelect = $("#services");
            
            if ($.fn.select2) {
                $serviceSelect.select2({
                    placeholder: "Select service(s)",
                    multiple: true,
                    allowClear: true,
                });
            }
            
            $('.status-toggle-button').off('click').on('click', function(e) {
                if (!confirm('Are you sure you want to change the status?')) {
                    e.preventDefault();
                    return false;
                }
            });
        },
        
        setupBookingFormToggle: function() {
            $('#toggle-form-btn').off('click').on('click', function() {
                const $form = $('#booking-form');
                const isHidden = $form.is(':hidden');
                
                $form.slideToggle(500, function() {
                    $(this).closest('.form-section').toggleClass('form-expanded', !isHidden);
                });
                $(this).text(isHidden ? 'Hide Form' : 'Add New Booking');
            });
        },
        
        setupDateTimePickers: function() {
            if ($.fn.datetimepicker) {
                $('#start_datetime, #end_datetime').datetimepicker({
                    format: 'Y-m-d H:i',
                    step: 15,
                    onShow: function(ct) {
                        this.setOptions({
                            minDate: new Date()
                        });
                    }
                });
                
                $('#end_datetime').datetimepicker({
                    onShow: function(ct) {
                        const startDate = $('#start_datetime').val() ? 
                            $('#start_datetime').datetimepicker('getValue') : new Date();
                        this.setOptions({
                            minDate: startDate
                        });
                    }
                });
            }
        },
        
        setupCostCalculation: function() {
            const self = this;
            
            $('#services, #guests').off('change').on('change', function() {
                const selectedServices = $("#services").select2('data');
                self.calculateTotalCost(selectedServices);
            });
        },
        
        calculateTotalCost: function(selectedServices) {
            let totalCost = 0;
            selectedServices.forEach(service => {
                const servicePrice = parseFloat($(`#services option[value="${service.id}"]`).data('price'));
                if (!isNaN(servicePrice)) {
                    totalCost += servicePrice;
                }
            });
            $("#total_cost").val(totalCost);
            return totalCost;
        },
        
        setupBulkActions: function(context) {
            const checkboxes = `input[name="${context}_ids[]"]`;
            const formId = `${context}s-bulk-form`;
            const selectAllTop = `#cb-select-all-${context}-1`;
            const selectAllBottom = `#cb-select-all-${context}-2`;
            
            // Select all functionality for both top and bottom checkboxes
            $(`${selectAllTop}, ${selectAllBottom}`).off('change').on('change', function() {
                const isChecked = $(this).prop('checked');
                $(checkboxes).prop('checked', isChecked);
                
                // Sync both select-all checkboxes
                if (this.id === `cb-select-all-${context}-1`) {
                    $(selectAllBottom).prop('checked', isChecked);
                } else {
                    $(selectAllTop).prop('checked', isChecked);
                }
            });
            
            // Form submission handling
            $(`#${formId}`).off('submit').on('submit', function(e) {
                const action = $('#bulk-action-selector-top').val();
                if (action === 'delete') {
                    const checkedCount = $(`${checkboxes}:checked`).length;
                    if (checkedCount === 0) {
                        alert(`Please select at least one ${context} to delete.`);
                        e.preventDefault();
                        return false;
                    }
                    
                    if (!confirm(`Are you sure you want to delete the selected ${context}s?`)) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        },
        
        initStaffSettingsPage: function() {
            this.setupStaffFormToggle();
            this.setupWorkingHours();
            this.setupImageUpload();
            this.setupBulkActions("staff");
        },
        
        setupStaffFormToggle: function() {
            $('#toggle-form-btn').off('click').on('click', function() {
                const $form = $('#staff-form');
                const isHidden = $form.is(':hidden');
                
                $form.slideToggle(500, function() {
                    $(this).closest('.form-section').toggleClass('form-expanded', !isHidden);
                });
                $(this).text(isHidden ? 'Hide Form' : 'Add New Staff Member');
            });
        },
        
        setupWorkingHours: function() {
            const self = this;
            const days = [
                {formIndex: 0, name: 'Sunday'},
                {formIndex: 1, name: 'Monday'},
                {formIndex: 2, name: 'Tuesday'},
                {formIndex: 3, name: 'Wednesday'},
                {formIndex: 4, name: 'Thursday'},
                {formIndex: 5, name: 'Friday'},
                {formIndex: 6, name: 'Saturday'}
            ];
            
            days.forEach(day => {
                $(`.day-enabled[data-day="${day.formIndex}"]`).off('change').on('change', function() {
                    $(`.time-periods[data-day="${day.formIndex}"]`).toggle(this.checked);
                });
                
                $(`.add-period[data-day="${day.formIndex}"]`).off('click').on('click', function() {
                    const periodContainer = $(this).closest('.time-periods');
                    const dayIndex = periodContainer.data('day');
                    const periodCount = periodContainer.find('.time-period').length;
                    
                    const newPeriod = $(`
                        <div class="time-period">
                            <select name="working_hours[${dayIndex}][${periodCount}][start]" class="time-select">
                                ${self.generateTimeOptions()}
                            </select>
                            to
                            <select name="working_hours[${dayIndex}][${periodCount}][end]" class="time-select">
                                ${self.generateTimeOptions()}
                            </select>
                            <button type="button" class="remove-period button-secondary">Remove</button>
                        </div>
                    `);
                    
                    periodContainer.append(newPeriod);
                });
            });
            
            $(document).off('click', '.remove-period').on('click', '.remove-period', function() {
                $(this).closest('.time-period').remove();
            });
        },
        
        generateTimeOptions: function() {
            let options = '';
            for (let hour = 0; hour < 24; hour++) {
                for (let min = 0; min < 60; min += 30) {
                    const timeStr = `${String(hour).padStart(2, '0')}:${String(min).padStart(2, '0')}`;
                    options += `<option value="${timeStr}">${timeStr}</option>`;
                }
            }
            return options;
        },
        
        setupImageUpload: function() {
            $('.select-image-btn').off('click').on('click', function(e) {
                e.preventDefault();
                
                const imageField = $('#profile_image');
                const imagePreview = $(this).siblings('.image-preview');
                
                if (typeof wp !== 'undefined' && wp.media) {
                    const mediaFrame = wp.media({
                        title: 'Select Profile Image',
                        library: {
                            type: 'image'
                        },
                        multiple: false
                    });
                    
                    mediaFrame.on('select', function() {
                        const attachment = mediaFrame.state().get('selection').first().toJSON();
                        imageField.val(attachment.id);
                        imagePreview.html('<img src="' + attachment.url + '" style="max-width: 150px;">');
                        $('.remove-image-btn').show();
                    });
                    
                    mediaFrame.open();
                } else {
                    console.warn('WordPress media library not available');
                }
            });
            
            $('.remove-image-btn').off('click').on('click', function(e) {
                e.preventDefault();
                $('#profile_image').val('');
                $('.image-preview').empty();
                $(this).hide();
            });
        }
    };
    
    ReserveMateAdmin.init();
});
