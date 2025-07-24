document.addEventListener('DOMContentLoaded', function () {
    const currency = paypal_vars.currency;
    let currentTotalCost = 0;
    let paypalButtonsInstance = null;
    
    function initializePayPal() {
        const paypalButtonContainer = document.querySelector('#paypal-button-container');
        currentTotalCost = document.getElementById('actual-payment-field').value;
        
        if (!paypalButtonContainer) {
            console.warn('PayPal button container not found');
            return;
        }
        
        if (!window.paypal) {
            console.error('PayPal SDK not loaded');
            return;
        }
        
        // Clear existing buttons
        paypalButtonContainer.innerHTML = '';
        
        try {
            paypalButtonsInstance = paypal.Buttons({
                style: {
                    height: 50,
                },
                createOrder: function (data, actions) {
                    // Get the latest total cost when creating order
                    const latestTotalCost = document.getElementById('actual-payment-field').value;
                    
                    if (!latestTotalCost || parseFloat(latestTotalCost) <= 0) {
                        throw new Error('Invalid payment amount');
                    }
                    
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: parseFloat(latestTotalCost).toFixed(2),
                                currency_code: currency
                            }
                        }]
                    });
                },
                onApprove: function (data, actions) {
                    return actions.order.capture().then(function (details) {
                        document.getElementById('paypalPaymentID').value = data.orderID;
                        document.getElementById('payment-form').submit();
                    });
                },
                onError: function (err) {
                    console.error('PayPal payment error:', err);
                    
                    // More specific error messages
                    let errorMessage = 'Payment failed. Please try again.';
                    
                    if (err.message && err.message.includes('currency')) {
                        errorMessage = `Currency ${currency} is not supported. Please contact support.`;
                    } else if (err.message && err.message.includes('amount')) {
                        errorMessage = 'Invalid payment amount. Please check and try again.';
                    }
                    
                    alert(errorMessage);
                },
                onCancel: function (data) {
                    // Optional: Handle cancellation
                }
            });
            
            paypalButtonsInstance.render('#paypal-button-container').then(() => {
                paypalButtonContainer.parentElement.style.padding = "1rem 0";
            }).catch((err) => {
                console.error('Failed to render PayPal buttons:', err);
                paypalButtonContainer.innerHTML = '<p>Payment system temporarily unavailable. Please try again later.</p>';
            });
            
        } catch (error) {
            console.error('Error initializing PayPal:', error);
            paypalButtonContainer.innerHTML = '<p>Payment system error. Please contact support.</p>';
        }
    }
    
    function destroyPayPalButtons() {
        if (paypalButtonsInstance) {
            try {
                paypalButtonsInstance.close();
            } catch (e) {
                console.warn('Error closing PayPal buttons:', e);
            }
            paypalButtonsInstance = null;
        }
    }
    
    // Initialize PayPal when the form is rendered
    document.addEventListener('paypalFormRendered', initializePayPal);
    
    // Global function to reinitialize PayPal (properly)
    window.reInitPayPal = function reinitializePayPal() {

        const updatedTotalCost = document.getElementById('actual-payment-field')?.value;
        
        if (!updatedTotalCost || parseFloat(updatedTotalCost) <= 0) {
            console.warn('Invalid total cost for PayPal reinitialization');
            return;
        }
        
        currentTotalCost = updatedTotalCost;
        
        // Destroy existing buttons and reinitialize
        destroyPayPalButtons();
        
        // Small delay to ensure cleanup is complete
        setTimeout(() => {
            initializePayPal();
        }, 100);
    };
    
    // Optional: Handle page visibility changes (useful for mobile)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page is hidden, could pause any ongoing processes
        } else {
            // Page is visible again, ensure PayPal is still working
            const paypalContainer = document.querySelector('#paypal-button-container');
            if (paypalContainer && paypalContainer.children.length === 0) {
                initializePayPal();
            }
        }
    });
});