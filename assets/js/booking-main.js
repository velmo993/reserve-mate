jQuery(document).ready(function ($) {
    const bookingForm = $('#rm-booking-form');
    const timeSlotContainer = $('#time-slot-container');
    const timeSlotsElement = $('#time-slots');
    const staffSelectionContainer = $('#staff-selection-container');
    const staffOptionsElement = $('#staff-options');
    const selectedStaffField = $('#staff-id');
    const timezone = rmVars.timezone;
    const locale = rmVars.locale; // for time calculations
    const localeConfig = rmVars.hasLocale ? rmVars.locale : 'default'; // for flatpickr locale
    const timeSlotFormat = rmVars.timeslot_format;
    const minAvailableDate = rmVars.min_date ? new Date(rmVars.min_date) : 'today';
    const maxAvailableDate = rmVars.max_date ? new Date(rmVars.max_date) : null;
    const maxServices = rmVars.max_services ? rmVars.max_services : '10';
    let startDateField = $('#start-date');
    let endDateField = $('#end-date');
    let selectedTimeSlotButton = null;
    let staffAvailabilityCache = {};
    let hasStaff = rmVars.staff_enabled || false;
    let loadingTimeout;
    init();
    
    function init() {
        loadingTimeout = setTimeout(() => {
            if ($('#booking-form-loading').is(':visible')) {
                resetFormLoading();
            }
        }, 20000);
    
        updateProgress(10, 'Loading services...');
        fetchBookings();
        serviceChangeListener();
        bookingFormListener();
        
        initializeFormComponents();
        
    }
    
    function resetFormLoading() {
        $('#booking-form-loading').hide();
        $('#booking-form-wrapper').show();
        alert('There was an issue loading the form. Please refresh the page and try again.');
        location.reload(); // Optional: automatically refresh the page
    }
    
    function initializeFormComponents() {
        updateProgress(30, 'Setting up form fields...');
        if ($('#services').length && typeof $.fn.select2 !== 'undefined') {
            $('#services').select2({
                placeholder: 'Select services',
                allowClear: true
            });
            updateProgress(50, 'Configuring service selection...');
        }
        
        updateProgress(70, 'Preparing calendar...');
        
        $(document).on("flatpickrInstance", function() {
            updateProgress(90, 'Finalizing setup...');
            
            setTimeout(function() {
                updateProgress(100, 'Ready!');
                hideLoadingShowForm();
            }, 200);
        });
    }
    
    function updateProgress(percentage, status) {
        $('#progress-fill').css('width', percentage + '%');
        $('#progress-percentage').text(percentage + '%');
        $('#progress-status').text(status);
    }
    
    function hideLoadingShowForm() {
        clearTimeout(loadingTimeout);
        clearTimeout(showLoaderTimeout);
        $('#booking-form-loading').fadeOut(200, function () {
            $('#booking-form-wrapper').fadeIn(200);
        });
    }
    
    let showLoaderTimeout = setTimeout(() => {
        $('#booking-form-loading').fadeIn(200);
    }, 150);
    
    function serviceChangeListener() {
        const servicesSelect = $('#services');
        if (servicesSelect.length) {
            servicesSelect.on('select2:select select2:unselect', function() {
                const selectedServices = $(this).val() || [];
                if (selectedServices.length > maxServices) {
                    const newSelected = selectedServices.slice(0, maxServices);
                    $(this).val(newSelected).trigger('change');
                    
                    alert(`You can select maximum ${maxServices} service(s) at a time.`);
                    return;
                }
                
                resetStaffSelection();
                
                const selectedDate = window.flatpickrInstance?.selectedDates?.[0];
                if (selectedDate) {
                    window.flatpickrInstance.setDate(selectedDate, true);
                }
            });
        }
    }
    
    function bookingFormListener() {
        bookingForm.on('change', '#email', function() {
            const email = $('#email').val();
            const date = $('#start-date').val();
            if(email.length !== 0 && date.length !== 0) {
                fetchBookingLimit(email, date);
            }
        });
    }
    
    function fetchBookingLimit(email, date) {
        const url = `${rmVars.ajaxurl}?action=check_booking_limit`;
        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            data: {
                email: email,
                date: date
            },
            success: function(response) {
                if (response.success) {
                    if (response.data && response.data.validation_message) {
                        alert(response.data.validation_message);
                        $('#time-slot-container').toggle();
                        $('#staff-selection-container').toggle();
                        window.flatpickrInstance.clear();
                    }
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while checking booking limits. Please try again.');
            }
        });
    }
    
    function resetStaffSelection() {
        if (hasStaff) {
            staffSelectionContainer.css('display', 'none');
            selectedStaffField.val('');
        } else {
            selectedStaffField.val('0');
        }
        
        if (selectedTimeSlotButton) {
            selectedTimeSlotButton.removeClass('selected-slot');
            selectedTimeSlotButton = null;
        }
        startDateField.val('');
        endDateField.val('');
    }
    
    function fetchBookings() {
        updateProgress(20, 'Loading availability...');
        const url = `${rmVars.ajaxurl}?action=get_date_time_bookings_data`;
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function(bookingsData) {
                updateProgress(40, 'Processing calendar data...');
                if (bookingsData.success) {
                    const fullyBookedSlots = bookingsData.data.map(booking => ({
                        start: new Date(booking.start_datetime),
                        end: new Date(booking.end_datetime)
                    }));
                    
                    window.taxSettings = bookingsData.taxes || '';
                    
                    let settings = bookingsData.settings;
                    initializeDaySelector(fullyBookedSlots, settings);
                }
            },
            error: function(error) {
                updateProgress(40, 'Loading calendar...');
                // console.error('Error fetching booked datetimes:', error);
                initializeDaySelector([], {});
            }
        });
    }
    
    function initializeDaySelector(bookedDates, settings) {    
        const fullyBookedDays = calculateFullyBookedDays(settings, bookedDates);
        const noStaffDays = settings.no_staff_days || [];
        
        const flatpickrConfig = createFlatpickrConfig(fullyBookedDays, bookedDates, settings, noStaffDays);
        
        window.flatpickrInstance = flatpickr('#day-selector', flatpickrConfig);
    
        initTimeSlotsEventListener();
        $(document).trigger("flatpickrInstance");
    }
    
    function createFlatpickrConfig(fullyBookedDays, bookedDates, settings, noStaffDays) {
        const disabledDates = rmVars.disabled_dates || [];
        const disabledDays = rmVars.disabled_days || [];
        let noStaffDaysFormatted = [];
        if(hasStaff) {
            noStaffDaysFormatted = noStaffDays.map(day => {
                // Convert Monday (1) to 1, Sunday (7) to 0
                return day === 7 ? 0 : day;
            });
        }
        
        return {
            enableTime: false,
            locale: localeConfig,
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: rmVars.date_format.split(' ')[0],
            minDate: minAvailableDate,
            maxDate: maxAvailableDate,
            inline: rmVars.inline_calendar === "inline",
            disable: [
                ...bookedDates.map(booking => ({
                    from: booking.start,
                    to: booking.end
                })),
                ...fullyBookedDays,
                function(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const dateString = `${year}-${month}-${day}`;
                    
                    if (disabledDates.includes(dateString)) {
                        return true;
                    }
                    
                    const dayOfWeek = date.getDay();
                    if (disabledDays.includes(dayOfWeek) || noStaffDaysFormatted.includes(dayOfWeek)) {
                        return true;
                    }
                    
                    return isPreviousDay(date);
                }
            ],
            onChange: handleDateChange(bookedDates, settings)
        };
    }
    
    function handleDateChange(bookedDates, settings) {
        return function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 1) {
                const selectedDay = selectedDates[0];
                timeSlotContainer.css('display', 'block');
                if (selectedTimeSlotButton) {
                    selectedTimeSlotButton.removeClass('selected-slot');
                    selectedTimeSlotButton = null;
                }
            
                loadTimeSlots(selectedDay, bookedDates, settings);
                
                if (!hasStaff) {
                    $("#staff-selection-container").hide();
                    
                    if ($("#staff-id-field").length) {
                        $("#staff-id-field").val(0);
                    }
                }
                
                timeSlotContainer.removeClass('open');
                void timeSlotContainer[0].offsetWidth;
                timeSlotContainer.addClass('open');
            }
        };
    }
    
    function calculateFullyBookedDays(settings, bookedDates) {
        const fullyBookedDays = [];
        const today = new Date();
        const endDate = new Date();
        endDate.setFullYear(today.getFullYear() + 1);
    
        if (isTodayFullyPast(settings)) {
            fullyBookedDays.push(new Date(today));
        }
    
        for (let day = new Date(today); day <= endDate; day.setDate(day.getDate() + 1)) {
            if (isDayFullyBooked(new Date(day), bookedDates, settings)) {
                fullyBookedDays.push(new Date(day));
            }
        }
        
        return fullyBookedDays;
    }
    
    function isTimeDisabledForDate(date, time) {
        if (!rmVars.disabled_time_periods || !rmVars.disabled_time_periods.length) {
            return false;
        }
        
        function timeToMinutes(timeStr) {
            const [hours, minutes] = timeStr.split(':').map(Number);
            return hours * 60 + minutes;
        }
        
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const dateString = `${year}-${month}-${day}`;
        
        const dayOfWeek = date.getDay(); // 0 = Sunday, 6 = Saturday
        const timeMinutes = timeToMinutes(time);
        
        return rmVars.disabled_time_periods.some(period => {
            if (period.date && period.date === dateString) {
                const startMinutes = timeToMinutes(period.start_time);
                const endMinutes = timeToMinutes(period.end_time);
                return timeMinutes >= startMinutes && timeMinutes < endMinutes;
            }
            
            if (period.day === dayOfWeek && !period.date) {
                const startMinutes = timeToMinutes(period.start_time);
                const endMinutes = timeToMinutes(period.end_time);
                return timeMinutes >= startMinutes && timeMinutes < endMinutes;
            }
            
            return false;
        });
    }
    
    function loadTimeSlots(selectedDay, bookedDates, settings) {
        timeSlotsElement.empty();
        resetStaffSelection();
    
        const serviceSelect = $('#services');
        const selectedServices = getSelectedServices(serviceSelect);
        const totalDuration = calculateTotalDuration(selectedServices, settings);
        const selectedDate = new Date(selectedDay);
        
        const timeSettings = getTimeSettings(selectedDate, settings);
        
        const allBooked = generateTimeSlots(selectedDate, totalDuration, timeSettings, bookedDates);
    
        if (allBooked) {
            window.flatpickrInstance.set('disable', [...window.flatpickrInstance.config.disable, selectedDay]);
        }
        
        if (hasStaff && selectedServices.length > 0) {
            fetchStaffAvailabilityForDay(selectedDate);
        }
    }
    
    function getSelectedServices(serviceSelect) {
        return $.map(serviceSelect.find('option:selected'), function(option) {
            return $(option).val();
        }).filter(Boolean);
    }
    
    function calculateTotalDuration(selectedServices, settings) {
        let totalDuration = 0;
        if (selectedServices.length > 0) {
            $.each(selectedServices, function(index, serviceId) {
                const option = $('#services').find(`option[value="${serviceId}"]`);
                const duration = option.length ? parseInt(option.data('duration')) || 30 : 30;
                totalDuration += duration;
            });
        } else {
            totalDuration = settings.interval || 30;
        }
        return totalDuration;
    }
    
    function getTimeSettings(selectedDate, settings) {
        const now = new Date();
        const nowInSelectedTZ = new Date(now.toLocaleString('locale', { timeZone: timezone }));
        
        const minTime = new Date(selectedDate);
        minTime.setHours(
            parseInt(settings.min_time.split(':')[0]), 
            parseInt(settings.min_time.split(':')[1]), 
            0, 0
        );
        
        const maxTime = new Date(selectedDate);
        maxTime.setHours(
            parseInt(settings.max_time.split(':')[0]), 
            parseInt(settings.max_time.split(':')[1]), 
            0, 0
        );
        
        return {
            nowInSelectedTZ,
            minTime,
            maxTime,
            interval: settings.interval || 30,
            bufferTime: settings.buffer_time || 0,
            minLeadTime: (settings.minimum_lead_time || 60) * 60 * 1000
        };
    }
    
    function generateTimeSlots(selectedDate, totalDuration, timeSettings, bookedDates) {
        let allBooked = true;
        let currentTime = new Date(timeSettings.minTime);
    
        while (currentTime.getTime() + (totalDuration * 60 * 1000) <= timeSettings.maxTime.getTime()) {
            const timeSlotStart = new Date(currentTime);
            const timeSlotEnd = new Date(timeSlotStart.getTime() + (totalDuration * 60 * 1000));
            
            const slotAvailabilityInfo = checkSlotAvailability(selectedDate, timeSlotStart, timeSlotEnd, timeSettings.nowInSelectedTZ, bookedDates, timeSettings.minLeadTime);
            
            if (!slotAvailabilityInfo.isBooked && !slotAvailabilityInfo.isPast && !slotAvailabilityInfo.isTimeDisabled) {
                allBooked = false;
                createTimeSlotButton(timeSlotStart, timeSlotEnd, totalDuration);
            }
    
            currentTime = new Date(timeSlotEnd.getTime() + (timeSettings.bufferTime * 60 * 1000));
        }
        
        return allBooked;
    }
    
    function checkSlotAvailability(selectedDate, timeSlotStart, timeSlotEnd, nowInSelectedTZ, bookedDates, minLeadTime) {
        const isToday = nowInSelectedTZ.getFullYear() === selectedDate.getFullYear() && 
                      nowInSelectedTZ.getMonth() === selectedDate.getMonth() && 
                      nowInSelectedTZ.getDate() === selectedDate.getDate();
                       
        const isPast = isToday && (nowInSelectedTZ.getTime() + minLeadTime) > timeSlotStart.getTime();
    
        const isBooked = bookedDates.some(booking => {
            const bookingStart = new Date(booking.start);
            const bookingEnd = new Date(booking.end);
            return (
                (timeSlotStart >= bookingStart && timeSlotStart < bookingEnd) ||
                (timeSlotEnd > bookingStart && timeSlotEnd <= bookingEnd) ||
                (timeSlotStart <= bookingStart && timeSlotEnd >= bookingEnd)
            );
        });
    
        const timeStr = formatSimpleTime(timeSlotStart);
        const isTimeDisabled = isTimeDisabledForDate(selectedDate, timeStr);
        
        return { isBooked, isPast, isTimeDisabled };
    }
    
    function createTimeSlotButton(timeSlotStart, timeSlotEnd, totalDuration) {
        const timeText = timeSlotFormat === 'range' 
            ? `${formatSimpleTime(timeSlotStart)} - ${formatSimpleTime(timeSlotEnd)}`
            : formatSimpleTime(timeSlotStart);
    
        const timeSlotButton = $('<button></button>')
            .text(timeText)
            .addClass('time-slot');
    
        const localTimeStart = createLocalTimeString(timeSlotStart);
        const localTimeEnd = createLocalTimeString(timeSlotEnd);
        
        timeSlotButton.data('start', localTimeStart);
        timeSlotButton.data('end', localTimeEnd);
        timeSlotButton.data('duration', totalDuration);
        timeSlotButton.data('timeKey', `${formatSimpleTime(timeSlotStart)}-${formatSimpleTime(timeSlotEnd)}`);
        
        timeSlotsElement.append(timeSlotButton);
    }
        
    function updateTimeSlotsBasedOnStaffAvailability(cacheKey) {
        if (!hasStaff) return;
        if (!staffAvailabilityCache[cacheKey]) return;
        
        const timeSlots = $('.time-slot:not([disabled])');
        let allDisabled = true;
        
        timeSlots.each(function() {
            const timeKey = $(this).data('timeKey');
            const staffForTime = staffAvailabilityCache[cacheKey][timeKey];
            
            if (!staffForTime || staffForTime.length === 0) {
                $(this).prop('disabled', true);
                $(this).addClass('no-staff-slot');
            } else {
                allDisabled = false;
            }
        });
    }
    
    function initTimeSlotsEventListener() {
        timeSlotsElement.on('click', '.time-slot', function (e) {
            e.preventDefault();
            if (!$(this).prop('disabled')) {
                const startTime = $(this).data('start');
                const endTime = $(this).data('end');
                const timeKey = $(this).data('timeKey');
                
                const servicesSelect = $('#services');
                const selectedServices = $.map(servicesSelect.find('option:selected'), function(option) {
                    return $(option).val();
                }).filter(Boolean);

                if (selectedServices.length > maxServices) {
                    alert(`You can select maximum ${maxServices} service(s) at a time.`);
                    return;
                }

                if (selectedServices.length === 0) {
                    alert('Please select at least one service first');
                    return;
                }
                
                const email = $('#email').val();
                if(email.length !== 0 && startTime.length !== 0) {
                    fetchBookingLimit(email, startTime);
                }
                
                selectTimeSlot(startTime, endTime);
                
                if (selectedTimeSlotButton) {
                    selectedTimeSlotButton.removeClass('selected-slot');
                }
                selectedTimeSlotButton = $(this);
                selectedTimeSlotButton.addClass('selected-slot');
                
                if (hasStaff) {
                    showAvailableStaffFromCache(timeKey);
                } else {
                    selectedStaffField.val('0');
                    $('#proceed-to-checkout').prop('disabled', false);
                }
            }
        });
    }
    
    function createLocalTimeString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const timeParts = formatSimpleTime(date).split(':');
        const hours = timeParts[0];
        const minutes = timeParts[1];
        
        // Format: 2025-04-09T10:30:00.000Z for 10:30 AM
        return `${year}-${month}-${day}T${hours}:${minutes}:00.000Z`;
    }
    
    function isDayFullyBooked(day, bookedDates, settings) {
        const [minHour, minMinute] = settings.min_time.split(':').map(Number);
        const [maxHour, maxMinute] = settings.max_time.split(':').map(Number);
        const interval = settings.interval;
    
        const dayDate = new Date(day);
        
        let currentTime = new Date(dayDate);
        currentTime.setHours(minHour, minMinute, 0, 0);
    
        const endTime = new Date(dayDate);
        endTime.setHours(maxHour, maxMinute, 0, 0);
    
        const now = new Date();
        const nowInSelectedTZ = new Date(now.toLocaleString(locale, { timeZone: timezone }));
    
        while (currentTime < endTime) {
            const timeSlotStart = new Date(currentTime);
            const timeSlotEnd = new Date(timeSlotStart);
            timeSlotEnd.setMinutes(timeSlotStart.getMinutes() + interval);
    
            const isToday = nowInSelectedTZ.getFullYear() === dayDate.getFullYear() && 
                          nowInSelectedTZ.getMonth() === dayDate.getMonth() && 
                          nowInSelectedTZ.getDate() === dayDate.getDate();
                           
            const isPast = isToday && nowInSelectedTZ > timeSlotStart;
    
            const isBooked = bookedDates.some(booking => {
                const bookingStart = new Date(booking.start);
                const bookingEnd = new Date(booking.end);
                return timeSlotStart >= bookingStart && timeSlotEnd <= bookingEnd;
            });
    
            if (!isBooked && !isPast) {
                return false;
            }
    
            currentTime.setMinutes(currentTime.getMinutes() + interval);
        }
    
        return true;
    }
    
    function isTodayFullyPast(settings) {
        const now = new Date();
        const nowInSelectedTZ = new Date(now.toLocaleString(locale, { timeZone: timezone }));
        const [maxHour, maxMinute] = settings.max_time.split(':').map(Number);
        const todayMax = new Date(nowInSelectedTZ);
        todayMax.setHours(maxHour, maxMinute, 0, 0);
        
        return nowInSelectedTZ > todayMax;
    }
    
    function isPreviousDay(date) {
        const now = new Date();
        const todayInSelectedTZ = new Date(now.toLocaleString(locale, { timeZone: timezone }));
        const checkDate = new Date(date);
        return checkDate.setHours(0,0,0,0) < todayInSelectedTZ.setHours(0,0,0,0);
    }
    
    function formatSimpleTime(date) {
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${hours}:${minutes}`;
    }
    
    function fetchStaffAvailabilityForDay(selectedDay) {
        if (!hasStaff) {
            return;
        }
        
        const dateString = formatDateToString(selectedDay);
        const selectedServices = getSelectedServices($('#services'));
        
        if (selectedServices.length === 0) {
            return;
        }
        
        const cacheKey = `${dateString}_${selectedServices.join('_')}`;
        
        if (staffAvailabilityCache[cacheKey]) {
            updateTimeSlotsBasedOnStaffAvailability(cacheKey);
            return;
        }
        
        showLoadingIndicator();
        fetchStaffData(dateString, selectedServices, cacheKey);
    }
    
    function formatDateToString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    function showLoadingIndicator() {
        const loadingIndicator = $('<div></div>')
            .addClass('loading-indicator')
            .text('Loading available staff...');
        timeSlotContainer.append(loadingIndicator);
    }
    
    function fetchStaffData(dateString, selectedServices, cacheKey) {
        $.ajax({
            url: rmVars.ajaxurl,
            method: 'POST',
            data: {
                action: 'get_staff_availability_for_day',
                date: dateString,
                service_ids: JSON.stringify(selectedServices),
                nonce: rmVars.nonce
            },
            success: function(data) {
                $('.loading-indicator').remove();
                handleStaffAvailabilityResponse(data, cacheKey);
            },
            error: function(error) {
                $('.loading-indicator').remove();
                // console.error('Error fetching staff availability:', error);
            }
        });
    }
    
    function handleStaffAvailabilityResponse(data, cacheKey) {
        if (data.success && data.data) {
            staffAvailabilityCache[cacheKey] = data.data;
            updateTimeSlotsBasedOnStaffAvailability(cacheKey);
            
            if (selectedTimeSlotButton) {
                const timeKey = selectedTimeSlotButton.data('timeKey');
                showAvailableStaffFromCache(timeKey);
            }
        }
    }

    function showAvailableStaffFromCache(timeKey) {
        staffSelectionContainer.css('display', 'block');
        
        const selectedServices = getSelectedServices($('#services'));
        
        if (selectedServices.length === 0) {
            staffOptionsElement.html('<div class="no-staff">Please select at least one service</div>');
            return;
        }
        
        const selectedDate = window.flatpickrInstance.selectedDates[0];
        const dateString = formatDateToString(selectedDate);
        const cacheKey = `${dateString}_${selectedServices.join('_')}`;
        
        findAndDisplayStaffForTimeSlot(cacheKey, timeKey, selectedDate);
    }
    
    function findAndDisplayStaffForTimeSlot(cacheKey, timeKey, selectedDate) {
        if (staffAvailabilityCache[cacheKey] && staffAvailabilityCache[cacheKey][timeKey]) {
            const staffMembers = staffAvailabilityCache[cacheKey][timeKey];
            
            if (staffMembers.length > 0) {
                displayStaffOptions(staffMembers);
            } else {
                displayNoStaffMessage();
            }
        } else {
            if (staffAvailabilityCache[cacheKey]) {
                searchForAlternativeStaff(cacheKey, timeKey);
            } else {
                displayStaffErrorMessage();
                fetchStaffAvailabilityForDay(selectedDate);
            }
        }
    }
    
    function searchForAlternativeStaff(cacheKey, timeKey) {
        let foundStaff = false;
        const slotTimeParts = timeKey.split('-');
        
        $.each(staffAvailabilityCache[cacheKey], function(key, value) {
            const keyTimeParts = key.split('-');
            
            if (keyTimeParts[0] === slotTimeParts[0] && keyTimeParts[1] === slotTimeParts[1]) {
                const staffMembers = value;
                
                if (staffMembers.length > 0) {
                    displayStaffOptions(staffMembers);
                    foundStaff = true;
                    return false;
                }
            }
        });
        
        if (!foundStaff) {
            displayNoStaffMessage();
        }
    }
    
    function displayNoStaffMessage() {
        staffOptionsElement.html('<div class="no-staff">No staff available for this time slot. Please choose another time or adjust your service selection.</div>');
    }
    
    function displayStaffErrorMessage() {
        staffOptionsElement.html('<div class="staff-error">Error loading staff availability. Please try again or select a different date.</div>');
    }
    
    function displayStaffOptions(staffMembers) {
        staffOptionsElement.empty();
        
        $.each(staffMembers, function(index, staff) {
            const staffOption = $('<div></div>')
                .addClass('staff-option')
                .data('staffId', staff.id);
                
            const staffHTML = `
                <div class="staff-image">
                    ${staff.profile_image ? 
                        `<img src="${staff.profile_image}" alt="${staff.name}">` : 
                        `<div class="staff-initials">${getInitials(staff.name)}</div>`
                    }
                </div>
                <div class="staff-info">
                    <h5>${staff.name} is available.</h5>
                    ${staff.bio ? `<p class="staff-bio">${staff.bio}</p>` : ''}
                </div>
            `;
            
            staffOption.html(staffHTML);
            staffOptionsElement.append(staffOption);
        });
        
        if (staffMembers.length > 0) {
            const firstStaffOption = staffOptionsElement.find('.staff-option').first();
            selectStaffMember(firstStaffOption);
        }
        
        $('.staff-option').on('click', function(e) {
            e.preventDefault();
            selectStaffMember($(this));
        });
    }
    
    function selectStaffMember(staffOption) {
        $('.staff-option').removeClass('selected');
        
        staffOption.addClass('selected');
        const staffId = staffOption.data('staffId');
        selectedStaffField.val(staffId);
        $('#proceed-to-checkout').prop('disabled', false);
    }
    
    function getInitials(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase();
    }
    
    function selectTimeSlot(startTime, endTime) {
        startDateField.val(convertISOToFormattedDate(startTime));
        endDateField.val(convertISOToFormattedDate(endTime));
    }
    
    function convertISOToFormattedDate(isoString) {
        const dateParts = isoString.split('T')[0].split('-');
        const timeParts = isoString.split('T')[1].split(':');
        
        return `${dateParts[0]}-${dateParts[1]}-${dateParts[2]} ${timeParts[0]}:${timeParts[1]}`;
    }
    
});