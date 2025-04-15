(function() {
    
    const bookingForm = document.getElementById('rm-booking-form');
    const today = new Date();
    const tomorrow = new Date().setDate(today.getDate() + 1);
    let startDateField = document.getElementById('start-date');
    let endDateField = document.getElementById('end-date');
    let minStay = '';
    let maxStay = '';
    let isPartialDays = 0;
    let seasonalRules = {};
    
    // Initialize the system
    init();
    
    function init() {
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
            disableMobile: true,
            disable: [
                ...bookedDates.map(date => date),
                ...disabledDates,
            ],
            // static: true,
            // inline: true,
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
    
})();