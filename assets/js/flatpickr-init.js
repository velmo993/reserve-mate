document.addEventListener('DOMContentLoaded', function () {
    const bookingForm = document.getElementById('rm-booking-form');
    const today = new Date();
    const tomorrow = new Date().setDate(today.getDate() + 1);
    const timeSlotContainer = document.getElementById('time-slot-container');
    const timeSlotsElement = document.getElementById('time-slots');
    let startDateField = document.getElementById('start-date');
    let endDateField = document.getElementById('end-date');
    let minStay = '';
    let maxStay = '';
    let isPartialDays = 0;
    let seasonalRules = {};
    let selectedTimeSlotButton = null;
    let isHourlyBooking = flatpickrVars.bookingSettings.hourly_booking_enabled === '1';
    let timezone = flatpickrVars.timezone;
    let locale = flatpickrVars.locale;
    if (isHourlyBooking) {
        fetchDateTimeBookings();
    }
    
    function fetchDateTimeBookings() {
        const url = `${flatpickrVars.ajaxurl}?action=get_date_time_bookings_data`;
        fetch(url)
            .then(response => response.json())
            .then((bookingsData) => {
                let bookedDates = new Set();
                if (bookingsData.success) {
                    bookedDates = bookingsData.data.map(booking => ({
                        start: new Date(booking.start_datetime),
                        end: new Date(booking.end_datetime)
                    }));
                    let settings = bookingsData.settings;
                    initializeDaySelector(bookedDates, settings);
                }
            })
            .catch((error) => {
                // console.error('Error fetching booked datetimes:', error);
                initializeDaySelector([]);
            });
    }
    
    function initializeDaySelector(bookedDates, settings) {    
        // Get the list of fully booked days
        const fullyBookedDays = [];
        const today = new Date();
        const endDate = new Date();
        endDate.setFullYear(today.getFullYear() + 1); // Check for the next year
    
        // Check if today should be disabled
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
                        selectedTimeSlotButton.classList.remove('selected-slot'); // Clear previous selection
                        selectedTimeSlotButton = null;
                    }
                
                    loadTimeSlots(selectedDay, bookedDates, settings);
                    
                    timeSlotContainer.classList.remove('open');
                    void timeSlotContainer.offsetWidth; // Trigger reflow
                    timeSlotContainer.classList.add('open');
                }
            }
        });
    
        initTimeSlotsEventListener();
        document.dispatchEvent(new Event("flatpickrInstance"));
        
        // Trigger next day by default to show the time slots
        // const tomorrow = new Date();
        // tomorrow.setDate(tomorrow.getDate() + 1);
        // const tomorrowFormatted = tomorrow.toISOString().split('T')[0];
        // window.flatpickrInstance.setDate(tomorrowFormatted, true);
        // loadTimeSlots(tomorrow, bookedDates, settings);
    }
    
    function loadTimeSlots(selectedDay, bookedDates, settings) {
        timeSlotsElement.innerHTML = "";
    
        const [minHour, minMinute] = settings.min_time.split(':').map(Number);
        const [maxHour, maxMinute] = settings.max_time.split(':').map(Number);
        const interval = settings.interval;
        const breakDuration = settings.break_duration || 0;
    
        // Parse selectedDay properly
        const selectedDate = new Date(selectedDay);
        
        // Get current time in the SELECTED TIMEZONE (not browser time)
        const now = new Date();
        const nowInSelectedTZ = new Date(now.toLocaleString('locale', { timeZone: timezone }));
        
        // Create time slots
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
    
            // Compare with the time in the selected timezone
            const isToday = nowInSelectedTZ.getFullYear() === selectedDate.getFullYear() && 
                           nowInSelectedTZ.getMonth() === selectedDate.getMonth() && 
                           nowInSelectedTZ.getDate() === selectedDate.getDate();
                           
            // Only disable if it's today AND the current time (in selected TZ) is after the slot start
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
            
            if (isPast) {
                timeSlotButton.classList.add('past-time-slot');
            }
            
            // Create time values with correct timezone offset for data-attributes
            // These represent the local time in the selected timezone
            const localTimeStart = createLocalTimeString(timeSlotStart);
            const localTimeEnd = createLocalTimeString(timeSlotEnd);
            
            timeSlotButton.dataset.start = localTimeStart;
            timeSlotButton.dataset.end = localTimeEnd;
            
            timeSlotsElement.appendChild(timeSlotButton);
    
            currentTime.setMinutes(currentTime.getMinutes() + interval + breakDuration);
        }
    
        if (allBooked) {
            window.flatpickrInstance.set('disable', [...window.flatpickrInstance.config.disable, selectedDay]);
        }
    }
    
    function createLocalTimeString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        // Get hours and minutes in the correct timezone
        const timeParts = formatSimpleTime(date).split(':');
        const hours = timeParts[0];
        const minutes = timeParts[1];
        
        // Create a timezone-aware ISO string that represents the local time
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
    
        // Get current time in SELECTED TIMEZONE
        const now = new Date();
        const nowInSelectedTZ = new Date(now.toLocaleString(locale, { timeZone: timezone }));
    
        while (currentTime < endTime) {
            const timeSlotStart = new Date(currentTime);
            const timeSlotEnd = new Date(timeSlotStart);
            timeSlotEnd.setMinutes(timeSlotStart.getMinutes() + interval);
    
            // Compare with the time in the selected timezone
            const isToday = nowInSelectedTZ.getFullYear() === dayDate.getFullYear() && 
                           nowInSelectedTZ.getMonth() === dayDate.getMonth() && 
                           nowInSelectedTZ.getDate() === dayDate.getDate();
                           
            // Only consider a slot "past" if it's today AND current time is after slot start
            const isPast = isToday && nowInSelectedTZ > timeSlotStart;
    
            const isBooked = bookedDates.some(booking => {
                const bookingStart = new Date(booking.start);
                const bookingEnd = new Date(booking.end);
                return timeSlotStart >= bookingStart && timeSlotEnd <= bookingEnd;
            });
    
            if (!isBooked && !isPast) {
                return false; // At least one time slot is available
            }
    
            currentTime.setMinutes(currentTime.getMinutes() + interval);
        }
    
        return true; // All time slots are booked or in the past
    }
    
    function isTodayFullyPast(settings) {
        // Get current time in SELECTED TIMEZONE
        const now = new Date();
        const nowInSelectedTZ = new Date(now.toLocaleString(locale, { timeZone: timezone }));
        
        const [maxHour, maxMinute] = settings.max_time.split(':').map(Number);
        
        // Create a date object for today with the max time
        const todayMax = new Date(nowInSelectedTZ);
        todayMax.setHours(maxHour, maxMinute, 0, 0);
        
        return nowInSelectedTZ > todayMax;
    }
    
    function isPreviousDay(date) {
        // Get current date in SELECTED TIMEZONE
        const now = new Date();
        const todayInSelectedTZ = new Date(now.toLocaleString(locale, { timeZone: timezone }));
        
        // Create a date object for the date we're checking
        const checkDate = new Date(date);
        
        // Compare dates (ignoring time)
        return checkDate.setHours(0,0,0,0) < todayInSelectedTZ.setHours(0,0,0,0);
    }
    
    // Add this new function that doesn't use timezone conversion
    function formatSimpleTime(date) {
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${hours}:${minutes}`;
    }
    
    function initTimeSlotsEventListener() {
        timeSlotsElement.addEventListener('click', function (e) {
            e.preventDefault();
            if (e.target.tagName === 'BUTTON' && !e.target.disabled) {
                const startTime = e.target.dataset.start;
                const endTime = e.target.dataset.end;
                selectTimeSlot(startTime, endTime);
                
                if (selectedTimeSlotButton) {
                    selectedTimeSlotButton.classList.remove('selected-slot');
                }
                selectedTimeSlotButton = e.target;
                selectedTimeSlotButton.classList.add('selected-slot');
            }
        });
    }
    
    function selectTimeSlot(startTime, endTime) {
        // These values should now already be in the correct timezone format
        startDateField.value = convertISOToFormattedDate(startTime);
        endDateField.value = convertISOToFormattedDate(endTime);
    }
    
    function convertISOToFormattedDate(isoString) {
        // Parse the ISO string
        const dateParts = isoString.split('T')[0].split('-');
        const timeParts = isoString.split('T')[1].split(':');
        
        // Create yyyy-MM-dd HH:mm format
        return `${dateParts[0]}-${dateParts[1]}-${dateParts[2]} ${timeParts[0]}:${timeParts[1]}`;
    }
    
    function formatTime(date) {
        return new Intl.DateTimeFormat('default', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            timeZone: timezone
        }).format(date);
    }
    
    function formatDateTime(date) {
        // Format date and time with respect to the user's timezone
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            timeZone: timezone
        };
        
        const parts = new Intl.DateTimeFormat(locale, options).formatToParts(date);
        const values = {};
        
        for (const part of parts) {
            values[part.type] = part.value;
        }
        
        // yyyy-MM-dd HH:mm format
        return `${values.year}-${values.month}-${values.day} ${values.hour}:${values.minute}`;
    }
    
    
    
    // ------------------------------------------------------------------------------------------------ ---------------------------------------------------------
    // ------------------------------------------------------- IF DATETIME (HOURLY) BOOKING ENABLED END ---------------------------------------------------------
    
    
    
    
    
    
    
    
    
    
    if(!isHourlyBooking) {
        let propertyIds = [...document.querySelectorAll('.property_id')].map(el => el.value).join(',');
        if (!propertyIds && bookingForm) {
            bookingForm.innerText = "Add property first with the same ID as the shortcode to display the calendar!";
        } else {
            fetchBookings(propertyIds);
        }
    }
    
    function fetchBookings(propertyIds) {
    const url = `${flatpickrVars.ajaxurl}?action=get_booked_dates_data&property_ids=${propertyIds}`;
    fetch(url)
        .then(response => response.json())
        .then((bookingsData) => {
            const bookedDates = new Set();
            const onlyDeparture = new Set();
            const onlyArrival = new Set();
            const mergedBookings = [];
            const disabledDates = [];
            window.dataForPayment = {
                data: bookingsData.data
            };
            if (bookingsData.success) {
                const maxAdults = bookingsData.combined_max_adults;
                const maxChildren = bookingsData.combined_max_children;
                const maxPets = bookingsData.combined_max_pets;
                // const minAdults = ;
                updateGuestFields(maxAdults, maxChildren, maxPets);
                seasonalRules = {};
                bookingsData.data.forEach(data => {
                    minStay = parseInt(data.property.min_stay) || '';
                    maxStay = parseInt(data.property.max_stay) || '';
                    
                    const propertySeasonalRules = data.property.seasonal_rules ? JSON.parse(data.property.seasonal_rules) : {};
                
                    // Merge the new property's seasonal rules with existing ones
                    for (const month in propertySeasonalRules) {
                        if (!seasonalRules[month]) {
                            seasonalRules[month] = { min: '', max: '' };
                        }
                
                        if (propertySeasonalRules[month].min) {
                            const newMin = parseInt(propertySeasonalRules[month].min);
                            const existingMin = seasonalRules[month].min ? parseInt(seasonalRules[month].min) : null;
                            seasonalRules[month].min = existingMin === null || newMin > existingMin ? newMin : existingMin;
                        }
                
                        if (propertySeasonalRules[month].max) {
                            const newMax = parseInt(propertySeasonalRules[month].max);
                            const existingMax = seasonalRules[month].max ? parseInt(seasonalRules[month].max) : null;
                            seasonalRules[month].max = existingMax === null || newMax > existingMax ? newMax : existingMax;
                        }
                    }
                    
                    if (data.property.disabled_dates) {
                        const disabledDatesRules = data.property.disabled_dates 
                            ? JSON.parse(data.property.disabled_dates)
                            : {};
                        
                        // Convert disabled dates to Flatpickr format
                        Object.values(disabledDatesRules).forEach(rule => {
                            switch(rule.type) {
                                case 'specific':
                                    disabledDates.push(rule.date);
                                    if (rule.repeat_yearly === "1") {
                                        const date = new Date(rule.date);
                                        disabledDates.push({
                                            repeat: {
                                                year: '*',
                                                month: date.getMonth() + 1,
                                                day: date.getDate()
                                            }
                                        });
                                    }
                                    break;
                                    
                                case 'range':
                                    disabledDates.push({
                                        from: rule.start_date,
                                        to: rule.end_date
                                    });
                                    if (rule.repeat_yearly === "1") {
                                        const start = new Date(rule.start_date);
                                        const end = new Date(rule.end_date);
                                        disabledDates.push({
                                            repeat: {
                                                year: '*',
                                                month: start.getMonth() + 1,
                                                day: start.getDate()
                                            }
                                        }, {
                                            repeat: {
                                                year: '*',
                                                month: end.getMonth() + 1,
                                                day: end.getDate()
                                            }
                                        });
                                    }
                                    break;
                                    
                                case 'weekly':
                                    // Convert day names to numbers (0=Sunday, 6=Saturday)
                                    const days = rule.days.map(day => 
                                        ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
                                        .indexOf(day.toLowerCase())
                                    );
                                    
                                    disabledDates.push((date) => {
                                        return days.includes(date.getDay());
                                    });
                                    break;
                            }
                        });
                    }
                    
                    isPartialDays = data.property.partial_days;

                    // Collect all bookings from all properties
                    data.bookings.forEach((booking) => {
                        mergedBookings.push({
                            start_date: new Date(booking.start_date),
                            end_date: new Date(booking.end_date)
                        });
                    });
                });

                // **Step 1: Sort all bookings by start_date**
                mergedBookings.sort((a, b) => a.start_date - b.start_date);

                // **Step 2: Recalculate gaps based on highest min_stay**
                let mergedGaps = [];
                for (let i = 1; i < mergedBookings.length; i++) {
                    let prevEnd = mergedBookings[i - 1].end_date;
                    let currStart = mergedBookings[i].start_date;

                    let gapStartDate = new Date(prevEnd);
                    gapStartDate.setDate(gapStartDate.getDate() + 1);

                    let gapEndDate = new Date(currStart);
                    gapEndDate.setDate(gapEndDate.getDate() - 1);

                    let gapDays = Math.floor((currStart - prevEnd) / (1000 * 60 * 60 * 24));

                    // Determine the highest min_stay within the gap
                    let highestMinStay = minStay; // Default to global property's min_stay
                    let currentDate = new Date(gapStartDate);

                    while (currentDate <= gapEndDate) {
                        const gapMonth = new Date(gapStartDate).getMonth() + 1; // Convert to 1-12

                        if (seasonalRules && seasonalRules[gapMonth] && seasonalRules[gapMonth]['min']) {
                            let seasonalMinStay = parseInt(seasonalRules[gapMonth]['min']);
                            if (seasonalMinStay > 0 && seasonalMinStay > highestMinStay) {
                                highestMinStay = seasonalMinStay;
                            }
                        }
                        currentDate.setDate(currentDate.getDate() + 1);
                    }

                    // Only mark gaps as unavailable if they are less than the required min_stay
                    if (gapDays > 0 && gapDays < highestMinStay) {
                        mergedGaps.push({
                            start_date: gapStartDate.toISOString().slice(0, 10),
                            end_date: gapEndDate.toISOString().slice(0, 10)
                        });
                    }
                }

                // **Process merged bookings into disabled dates**
                mergedBookings.forEach((booking) => {
                    const currentDate = new Date(booking.start_date);

                    while (currentDate <= booking.end_date) {
                        const formattedCurrentDate = currentDate.toISOString().slice(0, 10);

                        if (isPartialDays == 1 && formattedCurrentDate === booking.end_date.toISOString().slice(0, 10)) {
                            onlyArrival.add(formattedCurrentDate);
                        } else if (isPartialDays == 1 && formattedCurrentDate === booking.start_date.toISOString().slice(0, 10)) {
                            onlyDeparture.add(formattedCurrentDate);
                        } else {
                            bookedDates.add(formattedCurrentDate);
                        }
                        currentDate.setDate(currentDate.getDate() + 1);
                    }
                });

                // **Add recalculated gaps**
                mergedGaps.forEach((gap) => {
                    bookedDates.add(gap.start_date);
                    bookedDates.add(gap.end_date);
                });
                
                window.flatpickrInstance = flatpickr("#date-range");
                window.flatpickrInstance.clear();
                // **Initialize the calendar with new merged data**
                initializeFlatpickr(
                    [...bookedDates],
                    [...onlyDeparture],
                    [...onlyArrival],
                    disabledDates
                );
            }
        })
        .catch((error) => {
            // console.error('Error fetching booked dates:', error);
        });
    }

    function initializeFlatpickr(bookedDates, onlyDeparture, onlyArrival, disabledDates = []) {
        window.flatpickrInstance = flatpickr("#date-range", {
            mode: "range",
            dateFormat: "Y-m-d",
            minDate: tomorrow,
            disableMobile: false,
            disable: [
                ...bookedDates.map(date => date),
                ...disabledDates,
                (date) => {
                    const dateStr = flatpickr.formatDate(date, 'Y-m-d');
                    return onlyDeparture.includes(dateStr) || onlyArrival.includes(dateStr);
                }
            ],
            static: true,
            inline: true,
            locale: hu,
            firsDayOfWeek: 1,
            onDayCreate: function (dObj, dStr, fp, dayElem) {
                if(isPartialDays) {
                    const dateLabel = dayElem.getAttribute("aria-label");
                    
                    const parsedDate = fp.parseDate(dateLabel, "F j, Y");
                    if (!parsedDate) {
                        console.error("Invalid date:", dateLabel);
                        return;
                    }
                    const formattedDate = fp.formatDate(fp.parseDate(dateLabel, "F j, Y"), "Y-m-d");
                    const prevDay = new Date(parsedDate.getTime() - 60 * 60 * 1000).toISOString().slice(0, 10);
                    const nextDay = new Date(parsedDate.getTime() + 48 * 60 * 60 * 1000).toISOString().slice(0, 10);
    
                    if(onlyDeparture.includes(formattedDate)) {
                        if (bookedDates.includes(prevDay) ) {

                            dayElem.classList.add("flatpickr-disabled");
                            dayElem.setAttribute("aria-disabled", "true");
                        } else {
                            dayElem.classList.add("departure-only");
                        }
                    } else if (onlyArrival.includes(formattedDate)) {
                        if (bookedDates.includes(nextDay) ) {
                            dayElem.classList.add("flatpickr-disabled");
                            dayElem.setAttribute("aria-disabled", "true");
                        } else {
                            dayElem.classList.add("arrival-only");
                        }
                    }
                }
            },
            onChange: function (selectedDates, dateStr, instance) {
                // Ensure selected day on mobile gets the startRange class for css purposes
                if (selectedDates.length === 1) {
                    const selectedDay = instance.calendarContainer.querySelector(".flatpickr-day.selected");
                    if (selectedDay) {
                        selectedDay.classList.add("startRange");
                    }
                } else if (selectedDates.length !== 2) {
                    startDateField.value = "";
                    endDateField.value = "";
                } else {
                    const startDate = new Date(selectedDates[0].getTime() - selectedDates[0].getTimezoneOffset() * 60000);
                    const endDate = new Date(selectedDates[1].getTime() - selectedDates[1].getTimezoneOffset() * 60000);
                    startDateField.value = startDate.toISOString().slice(0, 10);
                    endDateField.value = endDate.toISOString().slice(0, 10);

                    const startMonth = startDate.getMonth() + 1;

                    const minimumStay = getMinStayForMonth(startMonth, minStay);
                    const maximumStay = getMaxStayForMonth(startMonth, maxStay);

                    const differenceInDays = Math.ceil((selectedDates[1] - selectedDates[0]) / (1000 * 3600 * 24));
                    const nightsCount = document.getElementById('nights-count');
                    nightsCount.innerText = differenceInDays > 0 ? `${differenceInDays} nights` : '';
                    
                    if (differenceInDays < minimumStay || !startDateField.value) {
                        alert(`A minimum of ${minimumStay} nights required for booking!`);
                        resetCalendarInstance(startDateField, instance, nightsCount);
                        return;
                    } else if (maximumStay !== '' && differenceInDays > maximumStay) {
                        alert(`Maximum ${maximumStay} nights can be booked!`);
                        resetCalendarInstance(endDateField, instance, nightsCount);
                        return;
                    }
                }
            }
            
        });

        document.dispatchEvent(new Event("flatpickrInstance"));
        initMultipleBookingEventListener();
    }
    
    function resetCalendarInstance(dateField, instance, nightsCount) {
        dateField.value = "";
        instance.clear();
        nightsCount.innerText = "";
    }
    
    function initMultipleBookingEventListener() {
        const propertySelect = document.getElementById('choose-apartman');
        if(propertySelect) {
            propertySelect.removeEventListener('change', handlePropertyChange);
            propertySelect.addEventListener('change', handlePropertyChange);
        }
    }
    
    function updateGuestFields(maxAdults, maxChildren, maxPets) {
        // Update adults dropdown
        let adultsSelect = document.getElementById('adults');
        adultsSelect.innerHTML = '';
        for (let i = 1; i <= maxAdults; i++) {
            adultsSelect.innerHTML += `<option value="${i}">${i}</option>`;
        }
    
        // Update children dropdown
        let childrenSelect = document.getElementById('children');
        if (childrenSelect) {
            childrenSelect.innerHTML = '';
            for (let i = 0; i <= maxChildren; i++) {
                childrenSelect.innerHTML += `<option value="${i}">${i}</option>`;
            }
        }
    
        // Update pets dropdown
        let petsSelect = document.getElementById('pets');
        if (petsSelect) {
            petsSelect.innerHTML = '';
            for (let i = 0; i <= maxPets; i++) {
                petsSelect.innerHTML += `<option value="${i}">${i}</option>`;
            }
        }
    }
    
    function handlePropertyChange(e) {
        const singleId =  document.querySelector('.property_id').value;
        const multipleIds = document.getElementById('property_ids').value;
        const multipleBookings = document.getElementById('multiple-bookings');
        const selectedValue = e.target.value;
        if (selectedValue === "multiple") {
            multipleBookings.value = true;
            fetchBookings(multipleIds);
        } else {
            multipleBookings.value = false;
            fetchBookings(singleId);
        }
    }
    
    function getMinStayForMonth(month, minStay) {
        if (seasonalRules && seasonalRules.hasOwnProperty(month)) {
            let seasonalMin = seasonalRules[month].min ? parseInt(seasonalRules[month].min) : null;
            return seasonalMin > 0 ? seasonalMin : minStay; // Use seasonal min if valid, otherwise fallback to global minStay
        }
        return minStay; // Default to global minStay if no seasonal rule exists
    }
    
    function getMaxStayForMonth(month, maxStay) {
        if (seasonalRules && seasonalRules.hasOwnProperty(month)) {
            let seasonalMax = seasonalRules[month].max ? parseInt(seasonalRules[month].max) : null;
            return seasonalMax > 0 ? seasonalMax : maxStay; // Use seasonal max if valid, otherwise fallback to global maxStay
        }
        return maxStay; // Default to global maxStay if no seasonal rule exists
    }
    
    
});
