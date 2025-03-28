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
    function initManageBookingsPage() {
        const activeTab = localStorage.getItem('activeTab');
        const $propertySelect = $("#property_id");

        if (activeTab) {
            $('.tab-button, .tab-content').removeClass('active');
            $(activeTab).addClass('active');
            $('.tab-button[data-target="' + activeTab + '"]').addClass('active');
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

    function initManagePropertiesPage() {
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
    }

    function initManageServicesPage() {
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
        initManageBookingsPage();
    } else if (window.location.search.includes('page=manage-properties')) {
        initManagePropertiesPage();
    } else if (window.location.search.includes('page=manage-services')) {
        initManageServicesPage();
    }
});