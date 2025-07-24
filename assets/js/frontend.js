jQuery(document).ready(function ($) {
    var servicesElement = document.getElementById("services");
    if (servicesElement) {
        jQuery(servicesElement).select2({
            placeholder: "Select services",
            multiple: true,
            allowClear: true,
        }).on('change', function() {
            var selected = jQuery(this).val();
            var container = jQuery(this).siblings('.select2-container');
            if (selected && selected.length > 0) {
                container.addClass('has-selection');
            } else {
                container.removeClass('has-selection');
            }
        });
        
        jQuery(servicesElement).trigger('change');
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

jQuery(document).on("flatpickrInstance", function () {
    const paymentFormWrap = document.getElementById('payment-form-wrap');
    const bookingForm = document.getElementById('rm-booking-form');
    const taxes = window.taxSettings;
    let startDateTime = document.getElementById('start-date');
    let endDateTime = document.getElementById('end-date');
    let paymentTypeUpdateTimeout = null;
    let toggleClickCount = 0;
    let toggleClickTimer = null;
    let paymentLoadingTimeout;
    const MAX_TOGGLE_CLICKS = 5;
    const TOGGLE_COOLDOWN = 30000;

    jQuery('#proceed-to-checkout').on('click', function (e) {
        e.preventDefault();
        if (!startDateTime.value || !endDateTime.value) {
            alert("Please select time!");
            return;
        }
        handleBooking(bookingForm, startDateTime, endDateTime);
    });
    
    function handleBooking(bookingForm, startDateTime, endDateTime) {
        const formData = collectFormData(bookingForm, startDateTime, endDateTime);
        if (!validateBookingForm(formData)) {
            return;
        } else {
            bookingForm.classList.add("hidden");
            bookingPaymentForm(formData);
        }
    }
    
    function collectFormData(bookingForm, startDateTime, endDateTime) {
        const formFields = bookingForm.querySelectorAll('input, select, textarea');
        const formData = {};
        
        formFields.forEach(field => {
            if (field.type === 'hidden' && (field.id === 'start-date' || field.id === 'end-date' || field.id === 'total-payment-cost')) {
                return;
            }
            
            if (field.name && field.name.startsWith('custom_')) {
                const fieldKey = field.name;
                
                if (field.type === 'checkbox') {
                    if (!formData[fieldKey]) {
                        formData[fieldKey] = [];
                    }
                    if (field.checked) {
                        formData[fieldKey].push(field.value);
                    }
                } else if (field.tagName === 'SELECT' && field.multiple) {
                    formData[fieldKey] = Array.from(field.selectedOptions).map(option => option.value);
                } else {
                    formData[fieldKey] = field.value;
                }
                return;
            }
            
            if (field.type === 'checkbox') {
                if (!formData[field.name]) {
                    formData[field.name] = [];
                }
                if (field.checked) {
                    formData[field.name].push(field.value);
                }
            } else if (field.type === 'radio') {
                if (field.checked) {
                    formData[field.name] = field.value;
                }
            } else if (field.tagName === 'SELECT' && field.multiple) {
                formData[field.name] = Array.from(field.selectedOptions).map(option => option.value);
            } else {
                formData[field.name] = field.value;
            }
        });
        
        formData.startDateTime = startDateTime.value;
        formData.endDateTime = endDateTime.value;
        formData.staff = document.getElementById('staff-id').value;
        
        const selectedServices = jQuery('#services').select2('data');
        formData.serviceIds = selectedServices.map(service => service.id);
        formData.services = selectedServices;
        
        formData.totalCost = calculateTotalCost(selectedServices).totalCost;
        document.getElementById('total-payment-cost').value = formData.totalCost;
        formData.currency = paymentVars.currency;
        
        return formData;
    }
    
    function roundToCurrency(amount, decimals = 2) {
        return Math.round(amount * Math.pow(10, decimals)) / Math.pow(10, decimals);
    }
    
    function calculateTotalCost(selectedServices) {
        let subtotal = 0;
        selectedServices.forEach(service => {
            const servicePrice = parseFloat(jQuery(`#services option[value="${service.id}"]`).data('price'));
            subtotal += servicePrice;
        });
        
        const taxCalculation = calculateTaxes(subtotal, selectedServices.length);
        
        return {
            subtotal: roundToCurrency(subtotal),
            totalTax: roundToCurrency(taxCalculation.totalTax),
            totalCost: roundToCurrency(subtotal + taxCalculation.totalTax),
            taxBreakdown: taxCalculation.breakdown
        };
    }
    
    function calculateTaxes(subtotal, numberOfPersons) {
        let totalTax = 0;
        const taxBreakdown = [];
        
        if (!taxes || !Array.isArray(taxes)) {
            return {
                totalTax: 0,
                breakdown: []
            };
        }
        
        taxes.forEach(tax => {
            let taxAmount = 0;
            const rate = parseFloat(tax.rate);
            
            switch (tax.type) {
                case 'percentage':
                    taxAmount = (subtotal * rate) / 100;
                    break;
                    
                case 'fixed':
                    taxAmount = rate;
                    break;
                    
                case 'per_person':
                    taxAmount = rate * numberOfPersons;
                    break;
                    
                default:
                    taxAmount = 0;
            }
            
            totalTax += taxAmount;
            
            taxBreakdown.push({
                id: tax.id,
                name: tax.name,
                type: tax.type,
                rate: rate,
                amount: taxAmount
            });
        });
        
        return {
            totalTax: parseFloat(totalTax.toFixed(2)),
            breakdown: taxBreakdown
        };
    }
    
    function generateTaxBreakdownHTML(bookingDetails) {
        const taxCalculation = calculateTotalCost(bookingDetails.services);
        
        if (!taxCalculation.taxBreakdown || taxCalculation.taxBreakdown.length === 0) {
            return '';
        }
        
        let taxBreakdownHTML = '';
        taxCalculation.taxBreakdown.forEach(tax => {
            let description = '';
            switch (tax.type) {
                case 'percentage':
                    description = `${tax.rate}%`;
                    break;
                case 'fixed':
                    description = `Fixed amount`;
                    break;
                case 'per_person':
                case 'per person':
                    description = `${tax.rate} per person`;
                    break;
            }
            
            taxBreakdownHTML += `
                <p class="tax-item">
                    <span>${tax.name} (${description})</span>
                    <span>${bookingDetails.currency} ${tax.amount.toFixed(2)}</span>
                </p>
            `;
        });
        
        return taxBreakdownHTML;
    }
    
    function validateBookingForm(bookingDetails) {
        const errors = [];
        bookingDetails.name = bookingDetails.name.trim();
        bookingDetails.email = bookingDetails.email.trim();
        bookingDetails.phone = bookingDetails.phone.trim();
        bookingDetails.startDateTime = bookingDetails.startDateTime.trim();
        bookingDetails.endDateTime = bookingDetails.endDateTime.trim();
        
        if (!bookingDetails.name) errors.push('name');
        if (!validateEmail(bookingDetails.email)) errors.push('email');
        if (!validatePhone(bookingDetails.phone)) errors.push('phone');
        if (!bookingDetails.phone) errors.push('phone');
        if (!bookingDetails.startDateTime && !bookingDetails.endDateTime) errors.push('dates');
        
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
        const digitsOnly = phone.replace(/\D/g, '');
        
        if (digitsOnly.length < 7 || digitsOnly.length > 17) {
            return false;
        }
        
        return true;
    }
    
    function bookingPaymentForm(bookingDetails) {
        populatePaymentForm(bookingDetails);
        showPaymentForm();
        selectPaymentListener(bookingDetails.totalCost);
        initPaymentButtons();
    }
    
    function getServicePrice(serviceId) {
        return parseFloat(jQuery(`#services option[value="${serviceId}"]`).data('price'));
    }
    
    function populatePaymentForm(bookingDetails) {
        const paymentForm = document.getElementById("payment-form");
        document.getElementById("name-field").value = bookingDetails.name;
        document.getElementById("email-field").value = bookingDetails.email;
        document.getElementById("phone-field").value = bookingDetails.phone;
        document.getElementById("start-date-field").value = bookingDetails.startDateTime;
        document.getElementById("end-date-field").value = bookingDetails.endDateTime;
        document.getElementById("staff-id-field").value = bookingDetails.staff;
        document.getElementById("total-cost-field").value = document.getElementById('actual-payment-field').value = bookingDetails.totalCost;
        const servicesField = document.getElementById("services-field");
        servicesField.value = JSON.stringify(bookingDetails.serviceIds);
        
        let customFieldsHTML = '';
        for (const [key, value] of Object.entries(bookingDetails)) {
            if (key.startsWith('custom_') && value) {
                customFieldsHTML += `<input type="hidden" name="${key}" value="${Array.isArray(value) ? value.join(',') : value}">`;
            }
        }
        
        const bookingSummaryHTML = generateBookingSummaryHTML(bookingDetails);
        const paymentOptionsHTML = generatePaymentOptionsHTML(bookingDetails);
        
        paymentForm.lastElementChild.innerHTML = `
            ${customFieldsHTML}
            <div id="summary">
                <div id="booking-summary">
                    ${bookingSummaryHTML}
                </div>
                <div id="booking-cost-summary">
                    ${bookingDetails.services.map(service => `<p><span>${service.text} *1</span><span>${bookingDetails.currency} ${getServicePrice(service.id)}</span></p>`).join('')}
                    <p><span>Subtotal:</span><span>${bookingDetails.currency} ${calculateTotalCost(bookingDetails.services).subtotal.toFixed(2)}</span></p>
                    ${generateTaxBreakdownHTML(bookingDetails)}
                    <p style="font-weight: bold;">
                        <span>Total Price:</span>
                        <span id="total-payable">${bookingDetails.totalCost}</span>
                        <span>${bookingDetails.currency}</span>
                    </p>
                </div>
            </div>
            
            <div class="choose-payment">
                ${generatePaymentMethodsHTML()}
                ${paymentOptionsHTML}
            </div>
        `;
    }
    
    function formatDate(dateString, format) {
        const date = new Date(dateString);
        
        // First handle all the non-letter characters and escape them
        const formatParts = [];
        let currentPart = '';
        let inLiteral = false;
        
        // Parse the format string into tokens and literals
        for (const char of format) {
            if (char === '\\') {
                inLiteral = true;
            } else if (inLiteral) {
                currentPart += char;
                inLiteral = false;
            } else if (['Y','y','m','n','d','j','H','G','h','g','i','s','A','a','F','M'].includes(char)) {
                if (currentPart) {
                    formatParts.push({ type: 'literal', value: currentPart });
                    currentPart = '';
                }
                formatParts.push({ type: 'token', value: char });
            } else {
                currentPart += char;
            }
        }
        
        if (currentPart) {
            formatParts.push({ type: 'literal', value: currentPart });
        }
        
        // Create a map of format tokens to their values
        const monthNames = ['January','February','March','April','May','June','July',
                           'August','September','October','November','December'];
        const monthShortNames = ['Jan','Feb','Mar','Apr','May','Jun',
                                'Jul','Aug','Sep','Oct','Nov','Dec'];
        
        const hours = date.getHours();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const ampmLower = hours >= 12 ? 'pm' : 'am';
        
        const tokenValues = {
            'Y': date.getFullYear(),                          // 2025
            'y': String(date.getFullYear()).slice(-2),        // 25
            'm': String(date.getMonth() + 1).padStart(2, '0'), // 07
            'n': date.getMonth() + 1,                         // 7
            'd': String(date.getDate()).padStart(2, '0'),     // 20
            'j': date.getDate(),                              // 20
            'H': String(date.getHours()).padStart(2, '0'),    // 15
            'G': date.getHours(),                             // 15
            'h': String(hours % 12 || 12).padStart(2, '0'),  // 03
            'g': hours % 12 || 12,                            // 3
            'i': String(date.getMinutes()).padStart(2, '0'),  // 05
            'A': ampm,                                        // PM
            'a': ampmLower,                                   // pm
            'F': monthNames[date.getMonth()],                 // July
            'M': monthShortNames[date.getMonth()],            // Jul
        };
        
        // Build the formatted string
        let formatted = '';
        for (const part of formatParts) {
            if (part.type === 'literal') {
                formatted += part.value;
            } else {
                formatted += tokenValues[part.value] || part.value;
            }
        }
        
        return formatted;
    }
    
    function generateBookingSummaryHTML(bookingDetails) {
        const formattedStart = formatDate(bookingDetails.startDateTime, rmVars.date_format);
        
        let bookingSummaryHTML = `
            <p><strong>👤</strong><span>${bookingDetails.name || 'Not provided'}</span></p>
            <p><strong>📧</strong><span>${bookingDetails.email || 'Not provided'}</span></p>
        `;
        
        if (bookingDetails.phone) {
            bookingSummaryHTML += `<p><strong>📞</strong><span>${bookingDetails.phone}</span></p>`;
        }
        
        for (const [key, value] of Object.entries(bookingDetails)) {
            if (key.startsWith('custom_') && value) {
                const fieldName = key.replace('custom_', '').replace(/_/g, ' ');
                bookingSummaryHTML += `<p><span style="padding:0;">${fieldName}:</span><span>${value}</span></p>`;
            }
        }
        
        bookingSummaryHTML += `<p><strong>📅</strong><span>${formattedStart}</span></p>`;
        
        return bookingSummaryHTML;
    }
    
    function generatePaymentMethodsHTML() {
        const imagePath = paymentVars.imagePath;
        let paymentMethodsHTML = `<div id="payment-methods">`;
        
        if (paymentVars.paymentSettings.stripe_enabled === "1") {
            paymentMethodsHTML += `
                <button class="payment-btn" data-method="stripe" data-role="payment-method">
                    <img src="${imagePath}/mastercard-logo.png" alt="Mastercard" class="payment-logo">
                    <img src="${imagePath}/visa-logo.png" alt="Visa" class="payment-logo">
                    <span>Credit/Debit Card</span>
                </button>`;
        }
        
        if (paymentVars.paymentSettings.paypal_enabled === "1") {
            paymentMethodsHTML += `
                <button class="payment-btn" data-method="paypal" data-role="payment-method">
                    <img src="${imagePath}/paypal-color-icon.png" alt="PayPal" class="payment-logo">
                    <span>PayPal</span>
                </button>`;
        }
        
        if (paymentVars.paymentSettings.bank_transfer_enabled === "1") {
            paymentMethodsHTML += `
                <button class="payment-btn" id="bank_transfer" data-method="bank-transfer" data-role="payment-method">
                    <img src="${imagePath}/bank-transfer-icon.png" alt="Bank Transfer" class="payment-logo">
                    <span>Bank Transfer</span>
                </button>`;
        }
        
        if (paymentVars.paymentSettings.pay_on_arrival_enabled === "1") {
            paymentMethodsHTML += `
                <button class="payment-btn" id="pay_on_arrival" data-method="pay-on-arrival" data-role="payment-method">
                    <img src="${imagePath}/buyer-pay-icon.png" alt="Cash" class="payment-logo">
                    <span>Pay On Arrival</span>
                </button>`;
        }
        
        if (paymentVars.paymentSettings.stripe_enabled !== "1" && 
            paymentVars.paymentSettings.paypal_enabled !== "1" && 
            paymentVars.paymentSettings.bank_transfer_enabled !== "1" && 
            paymentVars.paymentSettings.pay_on_arrival_enabled !== "1") {
            paymentMethodsHTML += `<p>Payment methods are disabled.</p>`;
        }
        
        paymentMethodsHTML += `</div>`;
        return paymentMethodsHTML;
    }
    
    function generatePaymentOptionsHTML(bookingDetails) {
        const fixedDepositPayment = paymentVars.paymentSettings.deposit_payment_type == "fixed";
        const percentageDepositPayment = paymentVars.paymentSettings.deposit_payment_type == "percentage";
        let paymentOptionsHTML = `<div id="payment-options">`;
        
        if (percentageDepositPayment && paymentVars.paymentSettings.deposit_payment_percentage > 0) {
            paymentOptionsHTML += `
                <div class="deposit-payment-form hidden">
                    <div class="toggle-labels">
                        <div class="select-payment-amount active" data-payment-type="full">
                            <span class="toggle-label">Full Payment</span>
                            <span class="price-display" id="full-price">${bookingDetails.totalCost}${bookingDetails.currency}</span>
                        </div>
                        <div class="select-payment-amount" data-payment-type="deposit">
                            <span class="toggle-label">Deposit (${paymentVars.paymentSettings.deposit_payment_percentage}%)</span>
                            <span class="price-display" id="deposit-price">${getDepositPaymentCost(bookingDetails.totalCost)}${bookingDetails.currency}</span>
                        </div>
                    </div>
                    <!-- Hidden radio inputs for form submission -->
                    <input type="radio" name="payment-type" value="full" checked class="hidden-radio">
                    <input type="radio" name="payment-type" value="deposit" class="hidden-radio">
                </div>`;
        }
        
        if (fixedDepositPayment && paymentVars.paymentSettings.deposit_payment_fixed_amount > 0) {
            paymentOptionsHTML += `
                <div class="deposit-payment-form hidden">
                    <div class="toggle-labels">
                        <div class="select-payment-amount active" data-payment-type="full">
                            <span class="toggle-label">Full Payment</span>
                            <span class="price-display" id="full-price">${bookingDetails.totalCost}${bookingDetails.currency}</span>
                        </div>
                        <div class="select-payment-amount" data-payment-type="deposit">
                            <span class="toggle-label">Deposit</span>
                            <span class="price-display" id="deposit-price">${getDepositPaymentCost(bookingDetails.totalCost)}${bookingDetails.currency}</span>
                        </div>
                    </div>
                    <!-- Hidden radio inputs for form submission -->
                    <input type="radio" name="payment-type" value="full" checked class="hidden-radio">
                    <input type="radio" name="payment-type" value="deposit" class="hidden-radio">
                </div>`;
        }
        
        paymentOptionsHTML += `
            <div id="stripe-payment-form" class="payment-method hidden">
                <div class="form-field">
                    <div id="card-element" class="form-control"></div>
                    <div id="card-errors" role="alert"></div>
                </div>
                
                <div class="agreement-container">
                    <label for="agreement">I have read and accept the 
                    <a href="${paymentVars.homeurl}/terms-conditions">Terms & Conditions</a>
                        and <a href="${paymentVars.privacyurl}">Privacy Policy</a>
                    </label> 
                    <input class="agreement" name="agreement" type="checkbox" required>
                </div>
                <div class="form-field">
                    <input type="submit" id="submit-stripe-payment" value="Pay Now" disabled>
                </div>
            </div>
    
            <div id="paypal-payment-form" class="payment-method hidden">
                <div class="agreement-container">
                    <label for="agreement">I have read and accept the 
                    <a href="${paymentVars.homeurl}/terms-conditions">Terms & Conditions</a>
                        and <a href="${paymentVars.privacyurl}">Privacy Policy</a>
                    </label> 
                    <input class="agreement" name="agreement" type="checkbox" required>
                </div>
                <div>
                    <div id="paypal-button-container"></div>
                </div>
                <input type="hidden" id="paypalPaymentID" name="paypalPaymentID">
            </div>
            
            <div id="pay-on-arrival-payment-form" class="payment-method hidden">
                <div class="agreement-container">
                    <label for="agreement">I have read and accept the 
                    <a href="${paymentVars.homeurl}/terms-conditions">Terms & Conditions</a>
                        and <a href="${paymentVars.privacyurl}">Privacy Policy</a>
                    </label>
                    <input class="agreement" name="agreement" type="checkbox" required>
                </div>
                <div class="form-field">
                    <input type="submit" id="submit-pay-on-arrival" name="submit-pay-on-arrival" value="Confirm Booking" disabled>
                </div>
            </div>
            
            <div id="bank-transfer-payment-form" class="payment-method hidden">
                <div class="agreement-container">
                    <label for="agreement">I have read and accept the 
                    <a href="${paymentVars.homeurl}/terms-conditions">Terms & Conditions</a>
                        and <a href="${paymentVars.privacyurl}">Privacy Policy</a>
                    </label>
                    <input class="agreement" name="agreement" type="checkbox" required>
                </div>
                <div class="form-field">
                    <input type="submit" id="submit-bank-transfer" name="submit-bank-transfer" value="Confirm Booking" disabled>
                </div>
            </div>
        </div>`;
        
        return paymentOptionsHTML;
    }
    
    function showPaymentForm() {
        showPaymentLoading();
        
        paymentLoadingTimeout = setTimeout(() => {
            if (document.getElementById('payment-form-loading') && document.getElementById('payment-form-wrap').classList.contains('hidden')) {
                resetPaymentLoading();
            }
        }, 20000); 
        
        const loadingElement = document.getElementById('payment-form-loading');
        if (loadingElement) {
            loadingElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function showActualForm() {
            paymentFormWrap.classList.remove("hidden");
            
            setTimeout(() => {
                const paymentFormTop = paymentFormWrap.getBoundingClientRect().top + window.pageYOffset;
                paymentFormWrap.scrollIntoView({ behavior: "smooth", block: "start" });
                setTimeout(() => {
                    window.scrollTo({top: paymentFormTop -120, behavior: "smooth"});
                }, 300);
            }, 100);
        }
    
        initPaymentLoadingSequence(showActualForm);
    }
    
    function showPaymentLoading() {
        if (document.getElementById('payment-form-loading')) return;
        const paymentLoadingHTML = `
            <div id="payment-form-loading" class="payment-form-loading" aria-live="polite">
                <div class="payment-loading-content">
                    <div class="payment-loading-spinner"></div>
                    <div class="payment-loading-progress">
                        <div class="payment-progress-bar">
                            <div class="payment-progress-fill" id="payment-progress-fill"></div>
                        </div>
                        <div class="payment-progress-text">
                            <span id="payment-progress-percentage">0%</span>
                            <span id="payment-progress-status">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        paymentFormWrap.insertAdjacentHTML('afterend', paymentLoadingHTML);
    }
    
    function initPaymentLoadingSequence(callback) {
        updatePaymentProgress(10, 'Preparing payment options...');
        
        setTimeout(() => {
            updatePaymentProgress(30, 'Loading payment methods...');
            
            setTimeout(() => {
                updatePaymentProgress(60, 'Setting up payment processors...');
                
                setTimeout(() => {
                    updatePaymentProgress(90, 'Finalizing payment form...');
                    
                    setTimeout(() => {
                        updatePaymentProgress(100, 'Ready for payment!');
                        hidePaymentLoadingShowForm(callback);
                    }, 200);
                }, 200);
            }, 200);
        }, 200);
    }
    
    function hidePaymentLoadingShowForm(callback) {
        clearTimeout(paymentLoadingTimeout);
        const paymentLoading = document.getElementById('payment-form-loading');
        if (paymentLoading) {
            paymentLoading.style.opacity = '0';
            setTimeout(() => {
                paymentLoading.remove();
                if (typeof callback === 'function') {
                    callback();
                }
            }, 100);
        } else if (typeof callback === 'function') {
            callback();
        }
    }
    
    function resetPaymentLoading() {
        const paymentLoading = document.getElementById('payment-form-loading');
        if (paymentLoading) {
            paymentLoading.remove();
        }
        alert('There was an issue loading the payment form. Please refresh the page and try again.');
        location.reload();
    }
    
    function updatePaymentProgress(percentage, status) {
        const progressFill = document.getElementById('payment-progress-fill');
        const progressPercentage = document.getElementById('payment-progress-percentage');
        const progressStatus = document.getElementById('payment-progress-status');
        
        if (progressFill) progressFill.style.width = percentage + '%';
        if (progressPercentage) progressPercentage.textContent = percentage + '%';
        if (progressStatus) progressStatus.textContent = status;
    }
    
    function selectPaymentListener(totalPaymentCost) {
        const paymentButtons = document.querySelectorAll(".payment-btn");
        const paymentMethods = document.querySelectorAll(".payment-method");
        const hiddenRadios = document.querySelectorAll('.hidden-radio');
        const selectAmounts = document.querySelectorAll(".select-payment-amount");
        const depositPaymentForm = document.querySelector(".deposit-payment-form");
    
        setupPaymentAmountToggle(selectAmounts, hiddenRadios, totalPaymentCost);
        setupPaymentMethodButtons(paymentButtons, paymentMethods, depositPaymentForm, hiddenRadios, totalPaymentCost);
        selectFirstEnabledPaymentMethod(paymentButtons);
    }
    
    function setupPaymentAmountToggle(selectAmounts, hiddenRadios, totalPaymentCost) {
        selectAmounts.forEach(amount => {
            amount.addEventListener('click', function() {
                toggleClickCount++;
                
                if (!toggleClickTimer) {
                    toggleClickTimer = setTimeout(() => {
                        toggleClickCount = 0;
                        toggleClickTimer = null;
                    }, 10000);
                }
        
                if (toggleClickCount >= MAX_TOGGLE_CLICKS) {
                    disablePaymentToggles(true);
                    setTimeout(() => disablePaymentToggles(false), TOGGLE_COOLDOWN);
                    return;
                }
        
                const paymentType = this.getAttribute('data-payment-type');
                selectAmounts.forEach(el => el.classList.remove('active'));
                this.classList.add('active');
                
                hiddenRadios.forEach(radio => {
                    radio.checked = (radio.value === paymentType);
                });
                
                updatePaymentAmount(totalPaymentCost, paymentType);
            });
        });
    }
    
    function setupPaymentMethodButtons(paymentButtons, paymentMethods, depositPaymentForm, hiddenRadios, totalPaymentCost) {
        paymentButtons.forEach(button => {
            button.addEventListener("click", function (e) {
                e.preventDefault();
    
                const role = this.getAttribute("data-role");
    
                if (role === "payment-method") {
                    paymentButtons.forEach(btn => btn.classList.remove("active"));
                    this.classList.add("active");
    
                    const selectedMethod = this.getAttribute("data-method");
                    
                    handleDepositPaymentVisibility(selectedMethod, depositPaymentForm, hiddenRadios, totalPaymentCost);
    
                    paymentMethods.forEach(method => method.classList.remove("active"));
                    paymentMethods.forEach(method => method.classList.add("hidden"));
    
                    const selectedForm = document.getElementById(`${selectedMethod}-payment-form`);
                    if (selectedForm) {
                        selectedForm.classList.add("active");
                        selectedForm.classList.remove("hidden");
                    }
                }
            });
        });
    }
    
    function handleDepositPaymentVisibility(selectedMethod, depositPaymentForm, hiddenRadios, totalPaymentCost) {
        if (!depositPaymentForm) return;
        
        let depositEnabledForMethod = false;
        if (selectedMethod === "stripe") {
            depositEnabledForMethod = paymentVars.paymentSettings.deposit_payment_methods && 
                                      paymentVars.paymentSettings.deposit_payment_methods.stripe === "1";
        } else if (selectedMethod === "paypal") {
            depositEnabledForMethod = paymentVars.paymentSettings.deposit_payment_methods && 
                                      paymentVars.paymentSettings.deposit_payment_methods.paypal === "1";
        } else if (selectedMethod === "bank-transfer") {
            depositEnabledForMethod = paymentVars.paymentSettings.deposit_payment_methods && 
                                      paymentVars.paymentSettings.deposit_payment_methods.bank_transfer === "1";
        } else if (selectedMethod === "pay-on-arrival") {
            depositEnabledForMethod = paymentVars.paymentSettings.deposit_payment_methods && 
                                      paymentVars.paymentSettings.deposit_payment_methods.pay_on_arrival === "1";
        }
        
        if (depositEnabledForMethod) {
            depositPaymentForm.classList.remove('hidden');
            const fullPaymentOption = document.querySelector('.select-payment-amount[data-payment-type="full"]');
            if (fullPaymentOption) {
                fullPaymentOption.click();
            }
        } else {
            depositPaymentForm.classList.add('hidden');
            updatePaymentAmount(totalPaymentCost, 'full');
            
            hiddenRadios.forEach(radio => {
                if (radio.value === 'full') {
                    radio.checked = true;
                }
            });
        }
    }
    
    function selectFirstEnabledPaymentMethod(paymentButtons) {
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
        const totalCost = parseFloat(totalPaymentCost);
        const previousValue = parseFloat(actualPaymentField.value);
        
        if (paymentType === 'deposit') {
            let depositPaymentCost = getDepositPaymentCost(totalCost);
            actualPaymentField.value = depositPaymentCost;
        } else if (paymentType === 'full') {
            actualPaymentField.value = totalCost;
        }
        
        if (paymentTypeUpdateTimeout) {
            clearTimeout(paymentTypeUpdateTimeout);
        }
        
        if (previousValue !== parseFloat(actualPaymentField.value)) {
            paymentTypeUpdateTimeout = setTimeout(() => {
                window.reInitStripe();
                window.reInitPayPal();
                paymentTypeUpdateTimeout = null;
            }, 500);
        }
    }
    
    function getDepositPaymentCost(totalCost) {
        let depositPaymentCost = paymentVars.paymentSettings.deposit_payment_type === "percentage" 
                ? (parseFloat(totalCost) * parseFloat(paymentVars.paymentSettings.deposit_payment_percentage)) / 100 
                : parseFloat(paymentVars.paymentSettings.deposit_payment_fixed_amount);
        
        return roundToCurrency(depositPaymentCost);
    }
    
    
    function initPaymentButtons() {
        if (paymentVars.paymentSettings.stripe_enabled === "1") {
            const event = new Event('stripeFormRendered');
            document.dispatchEvent(event);
        }
    
        if (paymentVars.paymentSettings.paypal_enabled === "1") {
            const event = new Event('paypalFormRendered');
            document.dispatchEvent(event);
            
            setTimeout(() => {
                const paypalContainer = document.querySelector('#paypal-button-container');
                if (paypalContainer) {
                    paypalContainer.style.pointerEvents = 'none';
                    paypalContainer.style.opacity = '0.5';
                }
            }, 100);
        }
        
        const agreementCheckboxes = document.querySelectorAll('.agreement');
        agreementCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                agreementCheckboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
                
                const allSubmitButtons = document.querySelectorAll('#submit-pay-on-arrival, #submit-bank-transfer, #submit-stripe-payment');
                allSubmitButtons.forEach(button => {
                    button.disabled = !this.checked;
                });
                
                const paypalContainer = document.querySelector('#paypal-button-container');
                if (paypalContainer) {
                    paypalContainer.style.pointerEvents = this.checked ? 'auto' : 'none';
                    paypalContainer.style.opacity = this.checked ? '1' : '0.5';
                }
            });
        });
        
        if(paymentVars.paymentSettings.pay_on_arrival_enabled === "1") {
            document.getElementById("submit-pay-on-arrival").addEventListener("click", function(e) {
                e.preventDefault();
                const agreement = this.closest('.payment-method').querySelector('.agreement');
                if (!agreement.checked) {
                    alert('You must accept the privacy policy to continue.');
                    return false;
                }
                document.getElementById("submit-pay-on-arrival-field").value = "1";
                document.getElementById("payment-form").submit();
            });
        }
        
        if(paymentVars.paymentSettings.bank_transfer_enabled === "1") {
            document.getElementById("submit-bank-transfer").addEventListener("click", function(e) {
                e.preventDefault();
                const agreement = this.closest('.payment-method').querySelector('.agreement');
                if (!agreement.checked) {
                    alert('You must accept the privacy policy to continue.');
                    return false;
                }
                document.getElementById("submit-bank-transfer-field").value = "1";
                document.getElementById("payment-form").submit();
            });
        }
        
        document.querySelector(".card-for-test").classList.remove("hidden");
        
        preventEnterKeySubmission();
    }
    
    function preventEnterKeySubmission() {
        const cardElement = document.getElementById('card-element');
        const paymentForm = document.getElementById('payment-form');
        
        if (cardElement) {
            cardElement.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const agreement = document.querySelector('#stripe-payment-form .agreement');
                    if (!agreement || !agreement.checked) {
                        alert('You must accept the privacy policy to continue.');
                        return false;
                    }
                    
                    const submitButton = document.getElementById('submit-stripe-payment');
                    if (submitButton && !submitButton.disabled) {
                        submitButton.click();
                    }
                }
            });
        }
        
        if (paymentForm) {
            paymentForm.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    const activePaymentMethod = document.querySelector('.payment-method.active');
                    if (activePaymentMethod) {
                        const agreement = activePaymentMethod.querySelector('.agreement');
                        if (!agreement || !agreement.checked) {
                            e.preventDefault();
                            e.stopPropagation();
                            alert('You must accept the privacy policy to continue.');
                            return false;
                        }
                    }
                }
            });
        }
    }
    
    function disablePaymentToggles(disable) {
        const toggles = document.querySelectorAll('.select-payment-amount');
        toggles.forEach(toggle => {
            if (disable) {
                toggle.style.opacity = '0.5';
                toggle.style.pointerEvents = 'none';
                toggle.title = 'Please wait before changing payment type again';
            } else {
                toggle.style.opacity = '';
                toggle.style.pointerEvents = '';
                toggle.title = '';
                toggleClickCount = 0;
                if (toggleClickTimer) {
                    clearTimeout(toggleClickTimer);
                    toggleClickTimer = null;
                }
            }
        });
    }
    
});