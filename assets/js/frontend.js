document.addEventListener('DOMContentLoaded', function () {
    var servicesElement = document.getElementById("services");
    if (servicesElement) {
        jQuery(servicesElement).select2({
            placeholder: "Select services",
            multiple: true,
            allowClear: true,
        });
    }
    const msgModal = document.getElementById('booking-success-modal');
    if (msgModal && window.location.href.includes('booking_status=success')) {
        msgModal.classList.add('show');
        document.querySelector('.close-success-modal').addEventListener('click', (e) => {
            e.preventDefault();
            msgModal.classList.remove('show');
            window.location.href = window.location.origin;
        });
        setTimeout(() => {
            msgModal.classList.remove('show');
            window.location.href = window.location.origin;
        }, 30000);
    }

});

document.addEventListener("flatpickrInstance", function () {
    const bookingForm = document.getElementById('rm-booking-form');
    const datePicker = window.flatpickrInstance;
    const propertyBooking = document.getElementById('property_ids') || document.getElementById('property_id');
    if(bookingForm && propertyBooking) {
        document.getElementById('proceed-to-checkout').addEventListener('click', function (e) {
            e.preventDefault();
            const selectedDates = datePicker.selectedDates;
            if (!selectedDates || selectedDates.length < 2) {
                alert("Please select both arrival and departure dates!");
                return;
            }
            handleCheckout(bookingForm, selectedDates);
        });
    } else {
        let startDateTime = document.getElementById('start-date');
        let endDateTime = document.getElementById('end-date');
        document.getElementById('proceed-to-checkout').addEventListener('click', function (e) {
            e.preventDefault();
            if (!startDateTime.value || !endDateTime.value) {
                alert("Please select time!");
                return;
            }
            handleDateTimeBooking(bookingForm, startDateTime, endDateTime);
        });
    }
    
    function handleDateTimeBooking(bookingForm, startDateTime, endDateTime) {
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const guests = document.getElementById('adults').value;
        startDateTime = startDateTime.value;
        endDateTime = endDateTime.value;
    
        // Get selected services from Select2
        const selectedServices = jQuery('#services').select2('data'); // Get selected services as an array of objects
        const serviceIds = selectedServices.map(service => service.id); // Extract service IDs
        const serviceNames = selectedServices.map(service => service.text); // Extract service names
        
        // Calculate total cost based on selected services
        let totalCost = calculateTotalCost(selectedServices);
    
        document.getElementById('total-payment-cost').value = totalCost;
    
        const bookingDetails = {
            name,
            email,
            phone,
            guests,
            startDateTime,
            endDateTime,
            totalCost,
            serviceIds, // Add selected service IDs
            serviceNames, // Add selected service names
            currency: ajaxScript.currency
        };
    
        if (!validateBookingForm(bookingDetails)) {
            return;
        } else {
            bookingForm.classList.add("hidden");
            dateTimeBookingPaymentForm(bookingDetails);
        }
    }
    
    function calculateTotalCost(selectedServices) {
        let totalCost = 0;
        selectedServices.forEach(service => {
            const servicePrice = parseFloat(jQuery(`#services option[value="${service.id}"]`).data('price')); // Get price from data attribute
            totalCost += servicePrice;
        });
        return totalCost;
    }
    
    function validateBookingForm(bookingDetails) {
        const errors = [];
        bookingDetails.name = bookingDetails.name.trim();
        bookingDetails.email = bookingDetails.email.trim();
        bookingDetails.phone = bookingDetails.phone.trim();
        bookingDetails.guests = bookingDetails.guests.trim();
        bookingDetails.startDateTime = bookingDetails.startDateTime.trim();
        bookingDetails.endDateTime = bookingDetails.endDateTime.trim();
    
        // Validate fields
        if (!bookingDetails.name) errors.push('name');
        if (!validateEmail(bookingDetails.email)) errors.push('email');
        if (!validatePhone(bookingDetails.phone)) errors.push('phone');
        if (!bookingDetails.phone) errors.push('phone');
        if (!bookingDetails.guests) errors.push('guests');
        if (!bookingDetails.startDateTime && !bookingDetails.endDateTime) errors.push('dates');
    
        // Display error messages
        if (errors.length > 0) {
            if (errors.includes('dates')) {
                alert('Please select time slot for booking.');
            } else if (errors.length === 1) {
                const field = errors[0];
                alert(`Please fill in the ${field} field.`);
            } else {
                alert('Please fill in all fields.');
            }
            return false;
        }
        return true;
    }
    
    function validateEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    function validatePhone(phone) {
        const regex = /^\d{7,17}$/; // Allows 7 to 17 digits (with or without country code)
        return regex.test(phone);
    }
    
    function dateTimeBookingPaymentForm(bookingDetails) {
        const paymentWrap = document.getElementById("payment-form-wrap");
        const paymentForm = document.getElementById("payment-form");
        document.getElementById("name-field").value = bookingDetails.name;
        document.getElementById("email-field").value = bookingDetails.email;
        document.getElementById("phone-field").value = bookingDetails.phone;
        document.getElementById("adults-field").value = bookingDetails.guests;
        document.getElementById("start-date-field").value = bookingDetails.startDateTime;
        document.getElementById("end-date-field").value = bookingDetails.endDateTime;
        document.getElementById("total-cost-field").value = document.getElementById('actual-payment-field').value = bookingDetails.totalCost;
        const servicesField = document.getElementById("services-field");
        servicesField.value = JSON.stringify(bookingDetails.serviceIds);
        
        // Show modal and overlay
        paymentWrap.classList.remove("hidden");
        
        setTimeout(() => {
            paymentWrap.scrollIntoView({ behavior: "smooth", block: "start" });
            setTimeout(() => {
                window.scrollBy(0, -100);
            }, 300);
        }, 100);

        const fixedAdvancePayment = ajaxScript.paymentSettings.advance_payment_type == "fixed";
        const percentageAdvancePayment = ajaxScript.paymentSettings.advance_payment_type == "percentage";
        const imagePath = ajaxScript.imagePath;
        let endTime = new Date(bookingDetails.endDateTime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
        
        // Populate the form with booking details
        paymentForm.lastElementChild.innerHTML = `
            <div id="summary">
                <div id="booking-summary">
                    <p><strong>👤</strong><span>${bookingDetails.name}</span></p>
                    <p><strong>📧</strong><span>${bookingDetails.email}</span></p>
                    <p><strong>📞</strong><span>${bookingDetails.phone}</span></p>
                    <p><strong>👥</strong><span>${bookingDetails.guests}</span></p>
                    <p><strong>📅</strong><span>${bookingDetails.startDateTime} ---> ${endTime}</span></p>
                </div>
                <div id="booking-cost-summary">
                    <p><strong>🛠️</strong></p>
                    ${bookingDetails.serviceNames.map(service => `<p><span>${service} *1</span><span>$30</span></p>`).join('')}
                    <p><span>Total Price:</span><span>${bookingDetails.totalCost} ${bookingDetails.currency}</span></p>
                </div>
            </div>
            
            <div class="choose-payment">
                <div id="payment-methods">
                    ${ajaxScript.paymentSettings.stripe_enabled === "1" ?
                    `<button class="payment-btn" data-method="stripe" data-role="payment-method">
                        <img src="${imagePath}/mastercard-logo.png" alt="Mastercard" class="payment-logo">
                        <img src="${imagePath}/visa-logo.png" alt="Visa" class="payment-logo">
                        <span>Credit/Debit Card</span>
                    </button>` : ''}
                    ${ajaxScript.paymentSettings.paypal_enabled === "1" ? 
                    `<button class="payment-btn" data-method="paypal" data-role="payment-method">
                        <img src="${imagePath}/paypal-color-icon.png" alt="PayPal" class="payment-logo">
                        <span>PayPal</span>
                    </button>` : ''}
                    ${ajaxScript.paymentSettings.bank_transfer_enabled === "1" ? 
                    `<button class="payment-btn" id="bank_transfer" data-method="bank-transfer" data-role="payment-method">
                        <img src="${imagePath}/bank-transfer-icon.png" alt="Bank Transfer" class="payment-logo">
                        <span>Bank Transfer</span>
                    </button>` : ''}
                    ${ajaxScript.paymentSettings.pay_on_arrival_enabled === "1" ? 
                    `<button class="payment-btn" id="pay_on_arrival" data-method="pay-on-arrival" data-role="payment-method">
                        <img src="${imagePath}/buyer-pay-icon.png" alt="Cash" class="payment-logo">
                        <span>Pay On Arrival</span>
                    </button>` : ''}
                    ${ajaxScript.paymentSettings.stripe_enabled !== "1" && ajaxScript.paymentSettings.paypal_enabled !== "1" && ajaxScript.paymentSettings.bank_transfer_enabled !== "1" && ajaxScript.paymentSettings.pay_on_arrival_enabled !== "1" ? `<p>Payment methods are disabled.</p>` : ''}
                </div>
                <div id="payment-options">
                    ${percentageAdvancePayment ?
                    `<div class="advance-payment-form hidden">
                        <div class="payment-toggle">
                            <label class="toggle-switch">
                                <input type="checkbox" id="payment-type-toggle" name="payment-type" value="advance">
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-labels">
                                <span class="toggle-label">Full Payment</span>
                                <span class="toggle-label">Advance (${ajaxScript.paymentSettings.advance_payment_percentage}%)</span>
                            </div>
                        </div>
                        <div class="price-display">
                            <span id="full-price">Total: ${bookingDetails.totalCost}${bookingDetails.currency}</span>
                            <span id="advance-price" class="hidden">Advance: ${getAdvancePaymentCost(bookingDetails.totalCost)}${bookingDetails.currency}</span>
                        </div>
                        <!-- Hidden radio inputs for form submission -->
                        <input type="radio" name="payment-type" value="full" checked class="hidden-radio">
                        <input type="radio" name="payment-type" value="advance" class="hidden-radio">
                    </div>`
                    : ''}
                    
                    ${fixedAdvancePayment ?
                    `<div class="advance-payment-form hidden">
                        <div class="payment-toggle">
                            <label class="toggle-switch">
                                <input type="checkbox" id="payment-type-toggle" name="payment-type" value="advance">
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-labels">
                                <span class="toggle-label">Full Payment</span>
                                <span class="toggle-label">Advance Payment (${ajaxScript.paymentSettings.advance_payment_fixed_amount})</span>
                            </div>
                        </div>
                        <div class="price-display">
                            <span id="full-price">Total: ${bookingDetails.totalCost}${bookingDetails.currency}</span>
                            <span id="advance-price" class="hidden">Advance: ${getAdvancePaymentCost(bookingDetails.totalCost)}${bookingDetails.currency}</span>
                        </div>
                        <!-- Hidden radio inputs for form submission -->
                        <input type="radio" name="payment-type" value="full" checked class="hidden-radio">
                        <input type="radio" name="payment-type" value="advance" class="hidden-radio">
                    </div>`
                    : ''}
                    
                    <div id="stripe-payment-form" class="payment-method hidden">
                        <div class="form-field">
                            <div id="card-element" class="form-control"></div>
                            <div id="card-errors" role="alert"></div>
                        </div>
                        <div class="form-field">
                            <input type="submit" id="submit-stripe-payment" value="Pay Now">
                        </div>
                    </div>
            
                    <div id="paypal-payment-form" class="payment-method hidden">
                        <div>
                            <div id="paypal-button-container"></div>
                        </div>
                        <input type="hidden" id="paypalPaymentID" name="paypalPaymentID">
                    </div>
                    
                    <div id="pay-on-arrival-payment-form" class="payment-method hidden">
                        <div class="form-field">
                            <input type="submit" id="submit-pay-on-arrival" name="submit-pay-on-arrival" value="Confirm Booking">
                        </div>
                    </div>
                    
                    <div id="bank-transfer-payment-form" class="payment-method hidden">
                        <div class="form-field">
                            <input type="submit" id="submit-bank-transfer" name="submit-bank-transfer" value="Confirm Booking">
                        </div>
                    </div>
                </div>
            </div>
        `;
    
        setTimeout(() => {
            selectPaymentListener(bookingDetails.totalCost);
        }, 500);
        
        // Go back functionality
        // document.querySelector(".cancel-payment").addEventListener("click", hidePaymentForm(bookingForm, paymentWrap));
        initPaymentButtons();
    }
    
    function handleCheckout(bookingForm, selectedDates) {
        if (!window.dataForPayment || !window.dataForPayment.data) {
            // console.error("Property data not available for payment calculation!");
            return;
        }
                
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const adults = parseInt(document.getElementById('adults').value);
        const children = document.getElementById('children') ? parseInt(document.getElementById('children').value) : 0;
        const pets = document.getElementById('pets') ? parseInt(document.getElementById('pets').value) : 0;
        const request = document.getElementById('client_request').value;
        const dateOfArrival = new Date(selectedDates[0].getTime() - selectedDates[0].getTimezoneOffset() * 60000).toISOString().slice(0, 10);
        const dateOfDeparture = new Date(selectedDates[1].getTime() - selectedDates[1].getTimezoneOffset() * 60000).toISOString().slice(0, 10);
        if (!dateOfArrival || !dateOfDeparture) {
            alert("Please select valid dates!");
            return;
        }
        let totalCost = 0;
        let basePrice = 0;
        const daysBooked = Math.max(1, (selectedDates[1] - selectedDates[0]) / (1000 * 60 * 60 * 24));
        let ratePerAdult = 0;
        let ratePerChild = 0;
        let ratePerPet = 0;
        
        const properties = window.dataForPayment.data.map(pd => pd.property).filter(p => p);
        properties.sort((a, b) => a.adult_price - b.adult_price);
        
        let remainingAdults = adults;
        let remainingChildren = children;
        let remainingPets = pets;
        
        properties.forEach(property => {
            if (!property) {
                // console.warn("Skipping a property because data is missing.");
                return;
            }
        
            ratePerAdult = property.adult_price || 0;
            ratePerChild = property.child_price || 0;
            ratePerPet = property.pet_price || 0;
            
            // Assign as many adults as possible to this property
            let assignedAdults = Math.min(remainingAdults, property.max_adult_number);
            let assignedChildren = Math.min(remainingChildren, property.max_child_number);
            let assignedPets = Math.min(remainingPets, property.max_pet_number);
            let propertyCostPerNight = (ratePerAdult * assignedAdults) +
                               (ratePerChild * assignedChildren) +
                               (ratePerPet * assignedPets);
                                       
            // Deduct assigned guests from remaining count
            remainingAdults -= assignedAdults;
            remainingChildren -= assignedChildren;
            remainingPets -= assignedPets;
        
            // Add property cost to total
            totalCost += propertyCostPerNight * daysBooked;
            basePrice += propertyCostPerNight;
        });
        
        // Taxes Calculation
        let totalTaxAmount = 0;
        const taxes = window.dataForPayment.data[0].taxes || [];
        let taxAmount = 0;
        let taxDetails = [];
        
        taxes.forEach(tax => {
            if (tax.type === 'percentage') {
                taxAmount = (parseInt(totalCost) * parseInt(tax.rate)) / 100;
            } else if (tax.type === 'fixed') {
                taxAmount = parseInt(tax.rate);
            } else if (tax.type === 'per_person_per_night') {
                taxAmount = (parseInt(tax.rate) / 100) * ((adults * parseInt(ratePerAdult)) + (children * parseInt(ratePerChild))) * daysBooked;
            }
            totalTaxAmount += taxAmount;
            // Store tax details for later use
            taxDetails.push({ name: tax.name || "Tax", amount: taxAmount });
        });
        
        // Final cost including taxes
        totalCost += totalTaxAmount;
        
        document.getElementById('total-payment-cost').value = totalCost;
    
        const bookingDetails = {
            name,
            email,
            phone,
            adults,
            children,
            pets,
            request,
            arrivalDate: dateOfArrival,
            departureDate: dateOfDeparture,
            totalCost,
            basePrice,
            currency: window.dataForPayment.data[0].currency,
            taxDetails,
            totalTaxAmount,
            daysBooked
        };
    
        if (name && email) {
            bookingForm.classList.add("hidden");
            renderPaymentForm(bookingDetails);
        }
    }
    
    // Paypal and Stripe payment form
    function renderPaymentForm(bookingDetails) {
        const paymentWrap = document.getElementById("payment-form-wrap");
        const paymentForm = document.getElementById("payment-form");
        document.getElementById("multiple-properties").value = document.getElementById("multiple-bookings").value || "";
        document.getElementById("multiple-ids").value = document.getElementById("property_ids")?.value || "";
        document.getElementById("single-id").value = document.getElementById("property_id").value || "";
        document.getElementById("name-field").value = bookingDetails.name;
        document.getElementById("email-field").value = bookingDetails.email;
        document.getElementById("phone-field").value = bookingDetails.phone;
        document.getElementById("client-request-field").value = bookingDetails.request;
        document.getElementById("adults-field").value = bookingDetails.adults;
        document.getElementById("children-field").value = bookingDetails.children;
        document.getElementById("pets-field").value = bookingDetails.pets;
        document.getElementById("start-date-field").value = bookingDetails.arrivalDate;
        document.getElementById("end-date-field").value = bookingDetails.departureDate;
        document.getElementById("total-cost-field").value = document.getElementById('actual-payment-field').value = bookingDetails.totalCost;
        
        // Show modal and overlay
        paymentWrap.classList.remove("hidden");
        
        setTimeout(() => {
            paymentWrap.scrollIntoView({ behavior: "smooth", block: "start" });
        
            setTimeout(() => {
                window.scrollBy(0, -100);
            }, 300);
        }, 100);
            
        const fixedAdvancePayment = ajaxScript.paymentSettings.advance_payment_type == "fixed";
        const percentageAdvancePayment = ajaxScript.paymentSettings.advance_payment_type == "percentage";
        const basePrice = bookingDetails.basePrice || 0;
        const totalBaseCost = basePrice * bookingDetails.daysBooked;
        const imagePath = ajaxScript.imagePath;
        // Populate the form with booking details
        paymentForm.lastElementChild.innerHTML = `
            <div id="summary">
                <div id="booking-summary">
                    <p><strong>👤</strong><span>${bookingDetails.name}</span></p>
                    <p><strong>📧</strong><span>${bookingDetails.email}</span></p>
                    <p><strong>📞</strong><span>${bookingDetails.phone}</span></p>
                    ${bookingDetails.children !== 0 ? `
                    <p><strong>👥</strong><span>${bookingDetails.adults}</span></p>
                    <p><strong>👶</strong><span>${bookingDetails.children}</span></p>
                    ` : `<p><strong>👥</strong><span>${bookingDetails.adults}</span></p>`}
                    ${bookingDetails.pets !== 0 ? `
                    <p><strong>🐕</strong><span>${bookingDetails.pets}</span></p>
                    ` : ``}
                    <p><strong>📅</strong><span>${bookingDetails.arrivalDate} ---> ${bookingDetails.departureDate}</span></p>
                </div>
                <div id="booking-cost-summary">
                    <p><strong>🏠</strong> <span>${basePrice} ${bookingDetails.currency} x ${bookingDetails.daysBooked} night${bookingDetails.daysBooked > 1 ? 's' : ''} = ${totalBaseCost} ${bookingDetails.currency}</span></p>
                    ${bookingDetails.taxDetails.map(tax => `<p><strong>${tax.name}:</strong> <span>${tax.amount} ${bookingDetails.currency}</span></p>`).join('')}
                    <p><strong>Tax Amount:</strong> <span>${bookingDetails.totalTaxAmount} ${bookingDetails.currency}</span></p>
                    <p><span>Total Price:</span><span>${bookingDetails.totalCost} ${bookingDetails.currency}</span></p>
                </div>
            </div>
            
            <div class="choose-payment">
                <div id="payment-methods">
                    ${ajaxScript.paymentSettings.stripe_enabled === "1" ?
                    `<button class="payment-btn" data-method="stripe" data-role="payment-method">
                        <img src="${imagePath}/mastercard-logo.png" alt="Mastercard" class="payment-logo">
                        <img src="${imagePath}/visa-logo.png" alt="Visa" class="payment-logo">
                        <span>Credit/Debit Card</span>
                    </button>` : ''}
                    ${ajaxScript.paymentSettings.paypal_enabled === "1" ? 
                    `<button class="payment-btn" data-method="paypal" data-role="payment-method">
                        <img src="${imagePath}/paypal-color-icon.png" alt="PayPal" class="payment-logo">
                        <span>PayPal</span>
                    </button>` : ''}
                    ${ajaxScript.paymentSettings.bank_transfer_enabled === "1" ? 
                    `<button class="payment-btn" id="bank_transfer" data-method="bank-transfer" data-role="payment-method">
                        <img src="${imagePath}/bank-transfer-icon.png" alt="Bank Transfer" class="payment-logo">
                        <span>Bank Transfer</span>
                    </button>` : ''}
                    ${ajaxScript.paymentSettings.pay_on_arrival_enabled === "1" ? 
                    `<button class="payment-btn" id="pay_on_arrival" data-method="pay-on-arrival" data-role="payment-method">
                        <img src="${imagePath}/buyer-pay-icon.png" alt="Cash" class="payment-logo">
                        <span>Pay On Arrival</span>
                    </button>` : ''}
                    ${ajaxScript.paymentSettings.stripe_enabled !== "1" && ajaxScript.paymentSettings.paypal_enabled !== "1" && ajaxScript.paymentSettings.bank_transfer_enabled !== "1" && ajaxScript.paymentSettings.pay_on_arrival_enabled !== "1" ? `<p>Payment methods are disabled.</p>` : ''}
                </div>
                <div id="payment-options">
                    ${percentageAdvancePayment ?
                    `<div class="advance-payment-form hidden">
                        <div class="payment-toggle">
                            <label class="toggle-switch">
                                <input type="checkbox" id="payment-type-toggle" name="payment-type" value="advance">
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-labels">
                                <span class="toggle-label">Full Payment</span>
                                <span class="toggle-label">Advance (${ajaxScript.paymentSettings.advance_payment_percentage}%)</span>
                            </div>
                        </div>
                        <div class="price-display">
                            <span id="full-price">Total: ${bookingDetails.totalCost}${bookingDetails.currency}</span>
                            <span id="advance-price" class="hidden">Advance: ${getAdvancePaymentCost(bookingDetails.totalCost)}${bookingDetails.currency}</span>
                        </div>
                        <!-- Hidden radio inputs for form submission -->
                        <input type="radio" name="payment-type" value="full" checked class="hidden-radio">
                        <input type="radio" name="payment-type" value="advance" class="hidden-radio">
                    </div>`
                    : ''}
                    
                    ${fixedAdvancePayment ?
                    `<div class="advance-payment-form hidden">
                        <div class="payment-toggle">
                            <label class="toggle-switch">
                                <input type="checkbox" id="payment-type-toggle" name="payment-type" value="advance">
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-labels">
                                <span class="toggle-label">Full Payment</span>
                                <span class="toggle-label">Advance Payment (${ajaxScript.paymentSettings.advance_payment_fixed_amount})</span>
                            </div>
                        </div>
                        <div class="price-display">
                            <span id="full-price">Total: ${bookingDetails.totalCost}${bookingDetails.currency}</span>
                            <span id="advance-price" class="hidden">Advance: ${getAdvancePaymentCost(bookingDetails.totalCost)}${bookingDetails.currency}</span>
                        </div>
                        <!-- Hidden radio inputs for form submission -->
                        <input type="radio" name="payment-type" value="full" checked class="hidden-radio">
                        <input type="radio" name="payment-type" value="advance" class="hidden-radio">
                    </div>`
                    : ''}
                    
                    <div id="stripe-payment-form" class="payment-method hidden">
                        <div class="form-field">
                            <div id="card-element" class="form-control"></div>
                            <div id="card-errors" role="alert"></div>
                        </div>
                        <div class="form-field">
                            <input type="submit" id="submit-stripe-payment" value="Pay Now">
                        </div>
                    </div>
            
                    <div id="paypal-payment-form" class="payment-method hidden">
                        <div>
                            <div id="paypal-button-container"></div>
                        </div>
                        <input type="hidden" id="paypalPaymentID" name="paypalPaymentID">
                    </div>
                    
                    <div id="pay-on-arrival-payment-form" class="payment-method hidden">
                        <div class="form-field">
                            <input type="submit" id="submit-pay-on-arrival" name="submit-pay-on-arrival" value="Confirm Booking">
                        </div>
                    </div>
                    
                    <div id="bank-transfer-payment-form" class="payment-method hidden">
                        <div class="form-field">
                            <input type="submit" id="submit-bank-transfer" name="submit-bank-transfer" value="Confirm Booking">
                        </div>
                    </div>
                </div>
            </div>
        `;
    
        setTimeout(() => {
            selectPaymentListener(bookingDetails.totalCost);
        }, 500);
        
        // Go back functionality
        // document.querySelector(".cancel-payment").addEventListener("click", hidePaymentForm(bookingForm, paymentWrap));
        initPaymentButtons();
    }
    
    function selectPaymentListener(totalPaymentCost) {
        const paymentButtons = document.querySelectorAll(".payment-btn");
        const paymentMethods = document.querySelectorAll(".payment-method");
        const hiddenRadios = document.querySelectorAll('.hidden-radio');
        const paymentToggle = document.getElementById('payment-type-toggle');
        const fullPriceDisplay = document.getElementById('full-price');
        const advancePriceDisplay = document.getElementById('advance-price');
        const fullLabel = document.querySelector('.toggle-label:first-child');
        const advanceLabel = document.querySelector('.toggle-label:last-child');
        const advancePaymentForm = document.querySelector(".advance-payment-form");
    
        // Handle toggle switch change
        if (paymentToggle) {
            paymentToggle.addEventListener('change', function () {
                const paymentType = this.checked ? 'advance' : 'full';
                updatePaymentAmount(totalPaymentCost, paymentType);
    
                // Update hidden radio inputs
                hiddenRadios.forEach(radio => {
                    if (radio.value === paymentType) {
                        radio.checked = true;
                    }
                });
                
                // Update labels and price display
                if (fullLabel && advanceLabel && fullPriceDisplay && advancePriceDisplay) {
                    fullLabel.classList.toggle('active', paymentType === 'full');
                    advanceLabel.classList.toggle('active', paymentType === 'advance');
                    fullPriceDisplay.classList.toggle('active', paymentType === 'full');
                    advancePriceDisplay.classList.toggle('active', paymentType === 'advance');
                }
            });
            
            // Initialize toggle state on page load
            const initialPaymentType = paymentToggle.checked ? 'advance' : 'full';
            if (fullLabel && advanceLabel && fullPriceDisplay && advancePriceDisplay) {
                fullLabel.classList.toggle('active', initialPaymentType === 'full');
                advanceLabel.classList.toggle('active', initialPaymentType === 'advance');
                fullPriceDisplay.classList.toggle('active', initialPaymentType === 'full');
                advancePriceDisplay.classList.toggle('hidden', initialPaymentType === 'advance');
            }
        }
    
        paymentButtons.forEach(button => {
            button.addEventListener("click", function (e) {
                e.preventDefault();
    
                // Get the button's role
                const role = this.getAttribute("data-role");
    
                if (role === "payment-method") {
                    // Handle payment method buttons (credit card, PayPal, bank transfer)
                    paymentButtons.forEach(btn => btn.classList.remove("active"));
                    this.classList.add("active");
    
                    // Get the selected payment method
                    const selectedMethod = this.getAttribute("data-method");
                    
                    // Check if advance payment is enabled for this payment method
                    let advanceEnabledForMethod = false;
                    if (selectedMethod === "stripe") {
                        advanceEnabledForMethod = ajaxScript.paymentSettings.advance_payment_methods && 
                                                  ajaxScript.paymentSettings.advance_payment_methods.stripe === "1";
                    } else if (selectedMethod === "paypal") {
                        advanceEnabledForMethod = ajaxScript.paymentSettings.advance_payment_methods && 
                                                  ajaxScript.paymentSettings.advance_payment_methods.paypal === "1";
                    } else if (selectedMethod === "bank-transfer") {
                        advanceEnabledForMethod = ajaxScript.paymentSettings.advance_payment_methods && 
                                                  ajaxScript.paymentSettings.advance_payment_methods.bank_transfer === "1";
                    } else if (selectedMethod === "pay-on-arrival") {
                        advanceEnabledForMethod = ajaxScript.paymentSettings.advance_payment_methods && 
                                                  ajaxScript.paymentSettings.advance_payment_methods.pay_on_arrival === "1";
                    }
                    
                    // Show/hide advance payment form based on whether it's enabled for this method
                    if (advancePaymentForm) {
                        if (advanceEnabledForMethod) {
                            advancePaymentForm.classList.remove('hidden');
                            // Reset to full payment when switching payment methods
                            if (paymentToggle && paymentToggle.checked) {
                                paymentToggle.checked = false;
                                updatePaymentAmount(totalPaymentCost, 'full');
                                
                                // Update hidden radio inputs
                                hiddenRadios.forEach(radio => {
                                    if (radio.value === 'full') {
                                        radio.checked = true;
                                    }
                                });
                                
                                // Update labels and price display
                                if (fullLabel && advanceLabel && fullPriceDisplay && advancePriceDisplay) {
                                    fullLabel.classList.toggle('active', paymentType === 'full');
                                    advanceLabel.classList.toggle('active', paymentType === 'advance');
                                    fullPriceDisplay.classList.toggle('active', paymentType === 'full');
                                    advancePriceDisplay.classList.toggle('active', paymentType === 'advance');
                                }
                            }
                        } else {
                            advancePaymentForm.classList.add('hidden');
                            // If advance payment is not enabled for this method, ensure we use full payment
                            updatePaymentAmount(totalPaymentCost, 'full');
                            
                            // Update hidden radio inputs
                            hiddenRadios.forEach(radio => {
                                if (radio.value === 'full') {
                                    radio.checked = true;
                                }
                            });
                        }
                    }
    
                    // Hide all payment methods
                    paymentMethods.forEach(method => method.classList.remove("active"));
                    paymentMethods.forEach(method => method.classList.add("hidden"));
    
                    // Show selected payment method
                    const selectedForm = document.getElementById(`${selectedMethod}-payment-form`);
                    if (selectedForm) {
                        selectedForm.classList.add("active");
                        selectedForm.classList.remove("hidden");
                    }
                }
            });
        });
    
        // Automatically select the first enabled payment method button
        const firstEnabledBtn = Array.from(paymentButtons).find(button => 
            button.getAttribute("data-role") === "payment-method" && !button.classList.contains('hidden')
        );
        if (firstEnabledBtn) {
            firstEnabledBtn.click();
            firstEnabledBtn.classList.add('active');
        }
    }
    
    function updatePaymentAmount(totalPaymentCost, paymentType) {
        const actualPaymentField = document.getElementById('actual-payment-field');
        const totalCost = totalPaymentCost;
        
        if (paymentType === 'advance') {
            let advancePaymentCost = getAdvancePaymentCost(totalCost);
            actualPaymentField.value = advancePaymentCost;
        } else if (paymentType === 'full') {
            actualPaymentField.value = totalCost;
        }
    
        window.reInitStripe();
        window.reInitPayPal(); // Update PayPal with the new amount
    }
    
    function getAdvancePaymentCost(totalCost) {
        let advancePaymentCost = ajaxScript.paymentSettings.advance_payment_type === "percentage" 
                ? (totalCost * ajaxScript.paymentSettings.advance_payment_percentage) / 100 
                : ajaxScript.paymentSettings.advance_payment_fixed_amount;
        return advancePaymentCost.toFixed(2);
    }
    
    function initPaymentButtons() {
        if (ajaxScript.paymentSettings.stripe_enabled === "1") {
            const event = new Event('stripeFormRendered');
            document.dispatchEvent(event);
        }
    
        if (ajaxScript.paymentSettings.paypal_enabled === "1") {
            const event = new Event('paypalFormRendered');
            document.dispatchEvent(event);
        }
        
        if(ajaxScript.paymentSettings.pay_on_arrival_enabled === "1") {
            document.getElementById("pay_on_arrival").addEventListener("click", function(e) {
                e.preventDefault();
            })
        }
        
        if(ajaxScript.paymentSettings.bank_transfer_enabled === "1") {
            document.getElementById("bank_transfer").addEventListener("click", function(e) {
                e.preventDefault();
            })
        }
        document.querySelector(".card-for-test").classList.remove("hidden");
    }
    
});