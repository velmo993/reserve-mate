jQuery(document).ready(function($) {
    // Utility Functions (Shared across pages)
    function setupDetailsToggle(toggleClass, dataIdPrefix) {
        let $currentOpenDetails = null;
        let $currentOpenSummaryRow = null;

        $(toggleClass).each(function() {
            const $button = $(this);
            const toggleDetails = function(event) {
                if (event.type === 'touchstart') {
                    event.preventDefault();
                }
    
                const entityId = $button.attr(`data-${dataIdPrefix}-id`);
                const $detailsRow = $('#details-' + entityId);
                const $summaryRow = $button.closest('tr');

                // If no details are open or a different details is clicked
                if (!$currentOpenDetails || $currentOpenDetails[0] !== $detailsRow[0]) {
                    // Close any currently open details first
                    if ($currentOpenDetails) {
                        const $previousButton = $(toggleClass + `[data-${dataIdPrefix}-id="${$currentOpenDetails.attr('id').replace('details-', '')}"]`);
                        $previousButton.html('<i>▼</i>');
                        $currentOpenDetails.hide();
                        
                        // Remove highlight from previous row
                        if ($currentOpenSummaryRow) {
                            $currentOpenSummaryRow.removeClass('details-row-active');
                            $currentOpenDetails.removeClass('details-section-active');
                        }
                    }

                    // Open new details
                    $detailsRow.show();
                    $button.html('<i>▲</i>');
                    $currentOpenDetails = $detailsRow;
                    
                    // Add highlight to current row and details section
                    $summaryRow.addClass('details-row-active');
                    $detailsRow.addClass('details-section-active');
                    $currentOpenSummaryRow = $summaryRow;
                } else {
                    // Clicking the same details closes it
                    $detailsRow.hide();
                    $button.html('<i>▼</i>');
                    $summaryRow.removeClass('details-row-active');
                    $detailsRow.removeClass('details-section-active');
                    $currentOpenDetails = null;
                    $currentOpenSummaryRow = null;
                }
            };
    
            $button.on('click', toggleDetails);
            $button.on('touchstart', toggleDetails);
        });
    }

    function setupTabNavigation() {
        $('.tab-button').on('click', function() {
            $('.tab-button, .tab-content').removeClass('active');

            $(this).addClass('active');
            const target = $(this).attr('data-target');
            $(target).addClass('active');

            // Persist active tab in local storage if on manage-bookings page
            if (window.location.search.includes('page=manage-bookings')) {
                localStorage.setItem('activeTab', target);
            }
        });
    }

    // Page-specific Initializations
    function initBookingsPage() {
        const activeTab = localStorage.getItem('activeTab');
        const $propertySelect = $("#property_id");

        if (activeTab) {
            $('.tab-button, .tab-content').removeClass('active');
            $(activeTab).addClass('active');
            $('.tab-button[data-target="' + activeTab + '"]').addClass('active');
        }

        setupDetailsToggle('.toggle-details-booking', 'booking');
        
        $propertySelect.on("change", function() {
            updatePropertyFields($(this));
        });
        
        $('#toggle-form-btn').on('click', function() {
            const $form = $('#booking-form');
            const isHidden = $form.is(':hidden');
            
            $form.slideToggle(500, function() {
                $(this).closest('.form-section').toggleClass('form-expanded', !isHidden);
            });
            $(this).text(isHidden ? 'Hide Form' : 'Add New Booking');
        });

        // Initial property fields setup
        updatePropertyFields($propertySelect);
    }
    
    function updatePropertyFields($propertySelect) {
        const $selectedOption = $propertySelect.find('option:selected');
        const $adultsSelect = $("#adults");
        const $childrenSelect = $("#children");
        const $petsSelect = $("#pets");
        const $childrenRow = $(".children-row");
        const $petsRow = $(".pets-row");

        if ($selectedOption.length) {
            const maxAdults = parseInt($selectedOption.attr("data-max-adults")) || 1;
            const maxChildren = parseInt($selectedOption.attr("data-max-children")) || 0;
            const maxPets = parseInt($selectedOption.attr("data-max-pets")) || 0;
            const allowChildren = $selectedOption.attr("data-allow-children") === "1";
            const allowPets = $selectedOption.attr("data-allow-pets") === "1";
        
            // Update Adults Dropdown
            $adultsSelect.empty();
            for (let i = 1; i <= maxAdults; i++) {
                $adultsSelect.append(`<option value="${i}">${i}</option>`);
            }
    
            // Update Children Dropdown
            $childrenSelect.empty();
            if (allowChildren) {
                $childrenRow.show();
                for (let i = 0; i <= maxChildren; i++) {
                    $childrenSelect.append(`<option value="${i}">${i}</option>`);
                }
            } else {
                $childrenRow.hide();
            }
    
            // Update Pets Dropdown
            $petsSelect.empty();
            if (allowPets) {
                $petsRow.show();
                for (let i = 0; i <= maxPets; i++) {
                    $petsSelect.append(`<option value="${i}">${i}</option>`);
                }
            } else {
                $petsRow.hide();
            }
        }
    }

    function initPropertiesPage() {
        const $allowChildren = $("#allow_children");
        const $allowPets = $("#allow_pets");
        const $seasonalRulesBtn = $("#seasonal-rules-btn");
        const $seasonalRulesContainer = $(".seasonal-rules-container");
        
        function toggleFields($allowChildren, $allowPets) {
            $(".children-field").each(function() {
                $allowChildren.prop('checked') ? 
                    $(this).removeClass('hidden') : 
                    $(this).addClass('hidden');
            });

            $(".pets-field").each(function() {
                $allowPets.prop('checked') ? 
                    $(this).removeClass('hidden') : 
                    $(this).addClass('hidden');
            });
        }

        // Add this function to handle initial state when editing a property
        function initializeFieldVisibility() {
            toggleFields($allowChildren, $allowPets);
        }

        function toggleSeasonalRules($seasonalRulesContainer, $seasonalRulesBtn) {
            $seasonalRulesContainer.each(function() {
                const $container = $(this);
                const isHidden = $container.hasClass('hidden');
                
                $container.slideToggle(500, function() {
                    $container.toggleClass('hidden');
                    $seasonalRulesBtn.html(isHidden ? '<i>▲</i>' : '<i>▼</i>');
                });
            });
        }
        
        // Call initialization on page load
        initializeFieldVisibility();

        $allowChildren.on("change", function() {
            toggleFields($allowChildren, $allowPets);
        });
        $allowPets.on("change", function() {
            toggleFields($allowChildren, $allowPets);
        });
        $seasonalRulesBtn.on("click", function(e) {
            e.preventDefault();
            toggleSeasonalRules($seasonalRulesContainer, $seasonalRulesBtn);
        });
        
        // Flatpickr time inputs
        ['check_in_time_start', 'check_in_time_end', 
         'check_out_time_start', 'check_out_time_end'].forEach(function(inputName) {
            flatpickr(`input[name='${inputName}']`, {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true
            });
        });
        
        setupDetailsToggle('.toggle-details-property', 'property');
        
        $('#toggle-form-btn').on('click', function() {
            const $form = $('#property-form');
            const isHidden = $form.is(':hidden');
            
            $form.slideToggle(500, function() {
                $(this).closest('.form-section').toggleClass('form-expanded', !isHidden);
            });
            $(this).text(isHidden ? 'Hide Form' : 'Add New Property');
        });
        
        
        
        
        // Toggle disabled dates section
        $('#disabled-dates-btn').on('click', function() {
            $('.disabled-dates-container').toggleClass('hidden');
            $(this).find('i').text($('.disabled-dates-container').hasClass('hidden') ? '▼' : '▲');
        });
        
        // Add new disabled date rule
        $('#add-disabled-date-rule').on('click', function() {

            var index = Date.now();
            
            $.ajax({
                url: reserve_mate_admin.ajax_url,
                type: 'POST',
                dataType: 'json', // Ensure we're expecting JSON
                data: {
                    action: 'get_disabled_date_rule',
                    index: index,
                    security: reserve_mate_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        $('#disabled-dates-rules').append(response.data);
                        initDatePickers();
                    } else {
                        console.error("Invalid response format", response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                }
            });
        });
        
        // Remove disabled date rule
        $(document).on('click', '.remove-disabled-date-rule', function() {
            $(this).closest('.disabled-date-rule').remove();
        });
        
        // Change disabled date type
        $(document).on('change', '.disabled-date-type', function() {
            var $container = $(this).closest('.disabled-date-rule').find('.disabled-date-options');
            $container.find('> div').hide();
            $container.find('.disabled-date-' + $(this).val()).show();
        });
        
        // Initialize date pickers
        function initDatePickers() {
        $('.datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0
            });
        }
        
        initDatePickers();
        
    
    }

    function initServicesPage() {
        setupDetailsToggle('.toggle-details-service', 'service');

        $('#toggle-form-btn').on('click', function() {
            const $form = $('#service-form');
            const isHidden = $form.is(':hidden');
            
            $form.slideToggle(500, function() {
                $(this).closest('.form-section').toggleClass('form-expanded', !isHidden);
            });
            $(this).text(isHidden ? 'Hide Form' : 'Add New Service');
        });
    }
    
    function initTabManagement(storageKey, defaultTab) {
        const activeTab = localStorage.getItem(storageKey) || defaultTab;
        
        // Activate the saved tab
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        
        $(`a[data-tab="${activeTab}"]`).addClass('nav-tab-active');
        $(`#${activeTab}`).addClass('active');
        
        // Handle tab clicks
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const tabId = $(this).data('tab');
            
            // Update tabs
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Update content
            $('.tab-content').removeClass('active');
            $(`#${tabId}`).addClass('active');
            
            // Save to localStorage
            localStorage.setItem(storageKey, tabId);
        });
    }
    
    function initSettingsPage() {
        initTabManagement('booking_settings_active_tab', 'general-tab');
        
        $('.color-field').each(function() {
            // Create color picker
            $(this).wpColorPicker({
                defaultColor: $(this).data('default-color'),
                change: function(event, ui) {
                    // Handle color changes
                },
                clear: function() {
                    // Handle clearing
                }
            });
        });
    }
    
    function initPaymentsPage() {
        initTabManagement('payment_settings_active_tab', 'online-tab');
    }
    
    function initServiceBookingsPage() {
        const activeTab = localStorage.getItem('activeTab');
        const $serviceSelect = $("#services");
    
        if (activeTab) {
            $('.tab-button, .tab-content').removeClass('active');
            $(activeTab).addClass('active');
            $('.tab-button[data-target="' + activeTab + '"]').addClass('active');
        }
        
        setupDetailsToggle('.toggle-details-booking', 'booking');
        
        // Initialize select2 for multiple service selection
        $serviceSelect.select2({
            placeholder: "Select service(s)",
            multiple: true,
            allowClear: true,
        });
        
        $('#toggle-form-btn').on('click', function() {
            const $form = $('#booking-form');
            const isHidden = $form.is(':hidden');
            
            $form.slideToggle(500, function() {
                $(this).closest('.form-section').toggleClass('form-expanded', !isHidden);
            });
            $(this).text(isHidden ? 'Hide Form' : 'Add New Booking');
        });
        
        // Set up datetime pickers for start and end datetime
        $('#start_datetime, #end_datetime').datetimepicker({
            format: 'Y-m-d H:i',
            step: 15,
            onShow: function(ct) {
                this.setOptions({
                    minDate: new Date()
                });
            }
        });
        
        // End datetime should be after start datetime
        $('#end_datetime').datetimepicker({
            onShow: function(ct) {
                const startDate = $('#start_datetime').val() ? 
                    $('#start_datetime').datetimepicker('getValue') : new Date();
                this.setOptions({
                    minDate: startDate
                });
            }
        });
        
        // Calculate total cost when service, guests, or dates change
        $('#services, #guests').on('change', function() {
            const selectedServices = $("#services").select2('data'); // Get selected services as an array of objects
            calculateTotalCost(selectedServices);
        });
        
    }
    
    function initStaffSettingsPage() {
        $('#toggle-form-btn').on('click', function() {
            const $form = $('#staff-form');
            const isHidden = $form.is(':hidden');
            
            $form.slideToggle(500, function() {
                $(this).closest('.form-section').toggleClass('form-expanded', !isHidden);
            });
            $(this).text(isHidden ? 'Hide Form' : 'Add New Staff Member');
        });
        
        const days = [
            {formIndex: 0, name: 'Sunday'},
            {formIndex: 1, name: 'Monday'},
            {formIndex: 2, name: 'Tuesday'},
            {formIndex: 3, name: 'Wednesday'},
            {formIndex: 4, name: 'Thursday'},
            {formIndex: 5, name: 'Friday'},
            {formIndex: 6, name: 'Saturday'}
        ];
        
        // Initialize working hours for each day
        days.forEach(day => {
            // Toggle working hours visibility
            $(`.day-enabled[data-day="${day.formIndex}"]`).on('change', function() {
                $(`.time-periods[data-day="${day.formIndex}"]`).toggle(this.checked);
            });
            
            // Add new time period
            $(`.add-period[data-day="${day.formIndex}"]`).on('click', function() {
                const periodContainer = $(this).closest('.time-periods');
                const dayIndex = periodContainer.data('day');
                const periodCount = periodContainer.find('.time-period').length;
                
                const newPeriod = $(`
                    <div class="time-period">
                        <select name="working_hours[${dayIndex}][${periodCount}][start]" class="time-select">
                            ${generateTimeOptions()}
                        </select>
                        to
                        <select name="working_hours[${dayIndex}][${periodCount}][end]" class="time-select">
                            ${generateTimeOptions()}
                        </select>
                        <button type="button" class="remove-period button-secondary">Remove</button>
                    </div>
                `);
                
                periodContainer.append(newPeriod);
            });
        });
        
        // Handle removing time periods
        $(document).on('click', '.remove-period', function() {
            $(this).closest('.time-period').remove();
        });
        
        // Helper function to generate time options
        function generateTimeOptions() {
            let options = '';
            for (let hour = 0; hour < 24; hour++) {
                for (let min = 0; min < 60; min += 30) {
                    const timeStr = `${String(hour).padStart(2, '0')}:${String(min).padStart(2, '0')}`;
                    options += `<option value="${timeStr}">${timeStr}</option>`;
                }
            }
            return options;
        }
        
        
        $('.select-image-btn').click(function(e) {
            e.preventDefault();
            
            var imageField = $('#profile_image');
            var imagePreview = $(this).siblings('.image-preview');
            
            var mediaFrame = wp.media({
                title: 'Select Profile Image',
                library: {
                    type: 'image'
                },
                multiple: false
            });
            
            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                imageField.val(attachment.id);
                imagePreview.html('<img src="' + attachment.url + '" style="max-width: 150px;">');
                $('.remove-image-btn').show();
            });
            
            mediaFrame.open();
        });
        
        // Remove image
        $('.remove-image-btn').click(function(e) {
            e.preventDefault();
            $('#profile_image').val('');
            $('.image-preview').empty();
            $(this).hide();
        });
        
    }
    
    function calculateTotalCost(selectedServices) {
        let totalCost = 0;
        selectedServices.forEach(service => {
            const servicePrice = parseFloat(jQuery(`#services option[value="${service.id}"]`).data('price')); // Get price from data attribute
            totalCost += servicePrice;
        });
        $("#total_cost").val(totalCost);
        return totalCost;
    }
    
    function initGlobalFeatures() {
        setupTabNavigation();

        // Calendar settings toggle
        function toggleCalendarSettings() {
            const $checkbox = $('#save_to_google_calendar');
            const fields = [
                'calendar_api_key',
                'calendar_id',
                'calendar_timezones'
            ];
        
            fields.forEach(function(fieldId) {
                $(`[name="booking_settings[${fieldId}]"]`).prop('disabled', !$checkbox.prop('checked'));
            });
        }
        
        $('#save_to_google_calendar').on('click', function(e) {
            e.preventDefault();
            toggleCalendarSettings();
        });
    }

    // Page-specific Initialization
    initGlobalFeatures();

    if (window.location.search.includes('page=manage-bookings')) {
        initBookingsPage();
    } else if (window.location.search.includes('page=manage-properties')) {
        initPropertiesPage();
    } else if (window.location.search.includes('page=manage-services')) {
        initServicesPage();
    } else if (window.location.search.includes('page=reserve-mate-settings')) {
        initSettingsPage();
    } else if (window.location.search.includes('page=payment-settings')) {
        initPaymentsPage();
    } else if (window.location.search.includes('page=manage-datetime-bookings')) {
        initServiceBookingsPage();
    } else if (window.location.search.includes('page=manage-staff')) {
        initStaffSettingsPage();
    }
});