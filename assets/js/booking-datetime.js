(function() {    
    const bookingForm = document.getElementById('rm-booking-form');
    const today = new Date();
    const tomorrow = new Date().setDate(today.getDate() + 1);
    const timeSlotContainer = document.getElementById('time-slot-container');
    const timeSlotsElement = document.getElementById('time-slots');
    const staffSelectionContainer = document.getElementById('staff-selection-container');
    const staffOptionsElement = document.getElementById('staff-options');
    const selectedStaffField = document.getElementById('staff-id');
    let startDateField = document.getElementById('start-date');
    let endDateField = document.getElementById('end-date');
    let selectedTimeSlotButton = null;
    let timezone = flatpickrVars.timezone;
    let locale = flatpickrVars.locale;
    let selectedServiceId = null;
    let staffAvailabilityCache = {};
    
    init();
    
    function init() {
        fetchDateTimeBookings();
        setupServiceChangeListener();
    }
    
    function setupServiceChangeListener() {
        const servicesSelect = document.getElementById('services');
        if (servicesSelect) {
            servicesSelect.addEventListener('change', function() {
                selectedServiceId = this.options[this.selectedIndex]?.value;
                resetStaffSelection();
                
                const selectedDate = window.flatpickrInstance?.selectedDates?.[0];
                if (selectedDate) {
                    fetchStaffAvailabilityForDay(selectedDate);
                }
            });
        }
    }
    
    function resetStaffSelection() {
        staffSelectionContainer.style.display = 'none';
        selectedStaffField.value = '';
        if (selectedTimeSlotButton) {
            selectedTimeSlotButton.classList.remove('selected-slot');
            selectedTimeSlotButton = null;
        }
        startDateField.value = '';
        endDateField.value = '';
    }
    
    function fetchDateTimeBookings() {
        const url = `${flatpickrVars.ajaxurl}?action=get_date_time_bookings_data`;
        fetch(url)
            .then(response => response.json())
            .then((bookingsData) => {
                if (bookingsData.success) {
                    // Only get time slots where ALL staff are booked
                    const fullyBookedSlots = bookingsData.data.map(booking => ({
                        start: new Date(booking.start_datetime),
                        end: new Date(booking.end_datetime)
                    }));
                    
                    let settings = bookingsData.settings;
                    initializeDaySelector(fullyBookedSlots, settings);
                }
            })
            .catch((error) => {
                console.error('Error fetching booked datetimes:', error);
                initializeDaySelector([], {});
            });
    }
    
    function initializeDaySelector(bookedDates, settings) {    
        const fullyBookedDays = [];
        const today = new Date();
        const endDate = new Date();
        endDate.setFullYear(today.getFullYear() + 1); // Check for the next year
    
        if (isTodayFullyPast(settings)) {
            fullyBookedDays.push(new Date(today));
        }
    
        for (let day = new Date(today); day <= endDate; day.setDate(day.getDate() + 1)) {
            if (isDayFullyBooked(new Date(day), bookedDates, settings)) {
                fullyBookedDays.push(new Date(day));
            }
        }
    
        window.flatpickrInstance = flatpickr('#day-selector', {
            enableTime: false,
            dateFormat: "Y-m-d",
            minDate: "today",
            inline: flatpickrVars.inline_calendar === "inline",
            disable: [
                ...bookedDates.map(booking => ({
                    from: booking.start,
                    to: booking.end
                })),
                ...fullyBookedDays,
                isPreviousDay
            ],
            onChange: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 1) {
                    const selectedDay = selectedDates[0];
                    timeSlotContainer.style.display = "block";
                    if (selectedTimeSlotButton) {
                        selectedTimeSlotButton.classList.remove('selected-slot');
                        selectedTimeSlotButton = null;
                    }
                
                    loadTimeSlots(selectedDay, bookedDates, settings);
                    
                    fetchStaffAvailabilityForDay(selectedDay);
                    
                    timeSlotContainer.classList.remove('open');
                    void timeSlotContainer.offsetWidth;
                    timeSlotContainer.classList.add('open');
                }
            }
        });
    
        initTimeSlotsEventListener();
        document.dispatchEvent(new Event("flatpickrInstance"));
    }
    
    function loadTimeSlots(selectedDay, bookedDates, settings) {
        timeSlotsElement.innerHTML = "";
        resetStaffSelection();
    
        const [minHour, minMinute] = settings.min_time.split(':').map(Number);
        const [maxHour, maxMinute] = settings.max_time.split(':').map(Number);
        const interval = settings.interval;
        const breakDuration = settings.break_duration || 0;
    
        const selectedDate = new Date(selectedDay);
        
        const now = new Date();
        const nowInSelectedTZ = new Date(now.toLocaleString('locale', { timeZone: timezone }));
        
        let currentTime = new Date(selectedDate);
        currentTime.setHours(minHour, minMinute, 0, 0);
        
        const endTime = new Date(selectedDate);
        endTime.setHours(maxHour, maxMinute, 0, 0);
    
        let allBooked = true;
    
        while (currentTime < endTime) {
            const timeSlotStart = new Date(currentTime);
            const timeSlotEnd = new Date(timeSlotStart);
            timeSlotEnd.setMinutes(timeSlotStart.getMinutes() + interval);
    
            if (timeSlotEnd > endTime) {
                break;
            }
    
            const isToday = nowInSelectedTZ.getFullYear() === selectedDate.getFullYear() && 
                           nowInSelectedTZ.getMonth() === selectedDate.getMonth() && 
                           nowInSelectedTZ.getDate() === selectedDate.getDate();
                           
            const isPast = isToday && nowInSelectedTZ > timeSlotStart;
    
            const isBooked = bookedDates.some(booking => {
                const bookingStart = new Date(booking.start);
                const bookingEnd = new Date(booking.end);
                return timeSlotStart >= bookingStart && timeSlotEnd <= bookingEnd;
            });
    
            if (!isBooked && !isPast) {
                allBooked = false;
            }
    
            const timeSlotButton = document.createElement('button');
            timeSlotButton.textContent = `${formatSimpleTime(timeSlotStart)} - ${formatSimpleTime(timeSlotEnd)}`;
            timeSlotButton.disabled = isBooked || isPast;
            timeSlotButton.className = 'time-slot';
            
            if (isPast) {
                timeSlotButton.classList.add('past-time-slot');
            }
            
            const localTimeStart = createLocalTimeString(timeSlotStart);
            const localTimeEnd = createLocalTimeString(timeSlotEnd);
            
            timeSlotButton.dataset.start = localTimeStart;
            timeSlotButton.dataset.end = localTimeEnd;
            
            timeSlotButton.dataset.timeKey = `${formatSimpleTime(timeSlotStart)}-${formatSimpleTime(timeSlotEnd)}`;
            
            timeSlotsElement.appendChild(timeSlotButton);
    
            currentTime.setMinutes(currentTime.getMinutes() + interval);
            if (breakDuration > 0) {
                currentTime.setMinutes(currentTime.getMinutes() + breakDuration);
            }
        }
    
        if (allBooked) {
            window.flatpickrInstance.set('disable', [...window.flatpickrInstance.config.disable, selectedDay]);
        }
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
    
    function initTimeSlotsEventListener() {
        timeSlotsElement.addEventListener('click', function (e) {
            e.preventDefault();
            if (e.target.classList.contains('time-slot') && !e.target.disabled) {
                const startTime = e.target.dataset.start;
                const endTime = e.target.dataset.end;
                const timeKey = e.target.dataset.timeKey;
                
                const servicesSelect = document.getElementById('services');
                const selectedServices = Array.from(servicesSelect.selectedOptions)
                    .map(option => option.value)
                    .filter(Boolean);

                if (selectedServices.length === 0) {
                    alert('Please select at least one service first');
                    return;
                }

                selectTimeSlot(startTime, endTime);
                
                if (selectedTimeSlotButton) {
                    selectedTimeSlotButton.classList.remove('selected-slot');
                }
                selectedTimeSlotButton = e.target;
                selectedTimeSlotButton.classList.add('selected-slot');
                
                showAvailableStaffFromCache(timeKey);
            }
        });
    }
    
    function fetchStaffAvailabilityForDay(selectedDay) {
        const year = selectedDay.getFullYear();
        const month = String(selectedDay.getMonth() + 1).padStart(2, '0');
        const day = String(selectedDay.getDate()).padStart(2, '0');
        const dateString = `${year}-${month}-${day}`;
        const serviceSelect = document.getElementById('services');
        const selectedServices = Array.from(serviceSelect.selectedOptions)
            .map(option => option.value)
            .filter(Boolean);
        
        if (selectedServices.length === 0) {
            return;
        }
        
        const cacheKey = `${dateString}_${selectedServices.join('_')}`;
        
        if (staffAvailabilityCache[cacheKey]) {
            return;
        }
        
        staffAvailabilityCache[cacheKey] = {};
        
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'loading-indicator';
        loadingIndicator.textContent = 'Loading staff availability...';
        timeSlotContainer.appendChild(loadingIndicator);
        
        const params = new URLSearchParams({
            action: 'get_staff_availability_for_day',
            date: dateString,
            service_ids: JSON.stringify(selectedServices),
            nonce: flatpickrVars.nonce
        });
        
        fetch(flatpickrVars.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const loadingIndicator = document.querySelector('.loading-indicator');
            if (loadingIndicator) {
                loadingIndicator.remove();
            }
            
            if (data.success && data.data) {
                staffAvailabilityCache[cacheKey] = data.data;

                if (selectedTimeSlotButton) {
                    const timeKey = selectedTimeSlotButton.dataset.timeKey;
                    showAvailableStaffFromCache(timeKey);
                }
            } else {
                console.error('Error fetching staff availability:', data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error fetching staff availability:', error);
            
            const loadingIndicator = document.querySelector('.loading-indicator');
            if (loadingIndicator) {
                loadingIndicator.remove();
            }
        });
    }

    function showAvailableStaffFromCache(timeKey) {
        staffOptionsElement.innerHTML = '<div class="loading-staff">Loading available staff...</div>';
        staffSelectionContainer.style.display = 'block';
        
        const serviceSelect = document.getElementById('services');
        const selectedServices = Array.from(serviceSelect.selectedOptions)
            .map(option => option.value)
            .filter(Boolean);
        
        if (selectedServices.length === 0) {
            staffOptionsElement.innerHTML = '<div class="no-staff">Please select at least one service</div>';
            return;
        }
        
        const selectedDate = window.flatpickrInstance.selectedDates[0];
        const year = selectedDate.getFullYear();
        const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
        const day = String(selectedDate.getDate()).padStart(2, '0');
        const dateString = `${year}-${month}-${day}`;
        const cacheKey = `${dateString}_${selectedServices.join('_')}`;
        
        if (staffAvailabilityCache[cacheKey] && staffAvailabilityCache[cacheKey][timeKey]) {
            const staffMembers = staffAvailabilityCache[cacheKey][timeKey];
            
            if (staffMembers.length > 0) {
                displayStaffOptions(staffMembers);
            } else {
                staffOptionsElement.innerHTML = '<div class="no-staff">No staff available for this time slot. Please choose another time or adjust your service selection.</div>';
            }
        } else {
            if (staffAvailabilityCache[cacheKey]) {
                for (const key in staffAvailabilityCache[cacheKey]) {
                    const keyTimeParts = key.split('-');
                    const slotTimeParts = timeKey.split('-');
                    
                    if (keyTimeParts[0] === slotTimeParts[0] && keyTimeParts[1] === slotTimeParts[1]) {
                        const staffMembers = staffAvailabilityCache[cacheKey][key];
                        
                        if (staffMembers.length > 0) {
                            displayStaffOptions(staffMembers);
                        } else {
                            staffOptionsElement.innerHTML = '<div class="no-staff">No staff available for this time slot. Please choose another time or adjust your service selection.</div>';
                        }
                        return;
                    }
                }
                staffOptionsElement.innerHTML = '<div class="no-staff">No staff available for this time slot. Please choose another time or adjust your service selection.</div>';
            } else {
                staffOptionsElement.innerHTML = '<div class="staff-error">Error loading staff availability. Please try again or select a different date.</div>';
                fetchStaffAvailabilityForDay(selectedDate);
            }
        }
    }
    
    function displayStaffOptions(staffMembers) {
        staffOptionsElement.innerHTML = '';
        
        staffMembers.forEach(staff => {
            const staffOption = document.createElement('div');
            staffOption.className = 'staff-option';
            staffOption.dataset.staffId = staff.id;
            staffOption.innerHTML = `
                <div class="staff-image">
                    ${staff.profile_image ? 
                        `<img src="${staff.profile_image}" alt="${staff.name}">` : 
                        `<div class="staff-initials">${getInitials(staff.name)}</div>`
                    }
                </div>
                <div class="staff-info">
                    <h5>${staff.name}</h5>
                    ${staff.bio ? `<p class="staff-bio">${staff.bio}</p>` : ''}
                </div>
                <button class="select-staff">Select</button>
            `;
            
            staffOptionsElement.appendChild(staffOption);
        });
        
        document.querySelectorAll('.select-staff').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const staffOption = this.closest('.staff-option');
                selectStaffMember(staffOption);
            });
        });
    }
    
    function selectStaffMember(staffOption) {
        document.querySelectorAll('.staff-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        staffOption.classList.add('selected');
        const staffId = staffOption.dataset.staffId;
        selectedStaffField.value = staffId;
        document.getElementById('proceed-to-checkout').disabled = false;
    }
    
    function getInitials(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase();
    }
    
    function selectTimeSlot(startTime, endTime) {
        // These values should now already be in the correct timezone format
        startDateField.value = convertISOToFormattedDate(startTime);
        endDateField.value = convertISOToFormattedDate(endTime);
    }
    
    function convertISOToFormattedDate(isoString) {
        const dateParts = isoString.split('T')[0].split('-');
        const timeParts = isoString.split('T')[1].split(':');
        
        return `${dateParts[0]}-${dateParts[1]}-${dateParts[2]} ${timeParts[0]}:${timeParts[1]}`;
    }
    
})();