
document.addEventListener("DOMContentLoaded", function () {
    document.addEventListener('stripeFormRendered', function () {
        initializeStripe();
    });

    let stripeInstance = null;
    let elementsInstance = null;
    let cardInstance = null;
    let form = null;
    let submitButton = null;

    // Define the submit handler as a named function
    async function handleStripeSubmit(event) {
        event.preventDefault();

        // Disable the submit button to prevent multiple submissions
        submitButton.disabled = true;
        submitButton.value = 'Processing...'; // Update button text

        try {
            const clientSecret = await fetchClientSecret();
            if (!clientSecret) {
                throw new Error('Failed to fetch client secret.');
            }

            const { paymentIntent, error } = await stripeInstance.confirmCardPayment(clientSecret, {
                payment_method: { card: cardInstance },
            });

            if (error) {
                document.getElementById('card-errors').textContent = error.message;
            } else if (paymentIntent.status === "succeeded") {
                // Optionally, submit the form or redirect the user
                form.submit();
                // window.location.href = '/successful-booking';
            }
        } catch (err) {
            // console.error("Error during payment processing:", err);
            document.getElementById('card-errors').textContent = 'An unexpected error occurred. Please try again.';
            
            // Re-enable the submit button
            submitButton.disabled = false;
            submitButton.value = 'Pay Now';
        }
    }

    async function fetchClientSecret(updatedPaymentCost = '') {
        const totalPaymentCost = document.getElementById('actual-payment-field').value;
        const response = await fetch(ajaxScript.ajaxurl, { // WordPress global AJAX URL
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'create_payment_intent', // Match the action in functions.php
                totalPaymentCost: totalPaymentCost,
            }),
        });

        const data = await response.json();

        if (data.success && data.data.clientSecret) {
            document.querySelector('input[name="clientSecret"]').value = data.data.clientSecret;
            return data.data.clientSecret;
        } else {
            // console.error('Error fetching clientSecret:', data.data.error);
        }
    }

    async function initializeStripe(updatedPaymentCost = '') {
        // Unmount existing card element if it exists
        if (cardInstance) {
            cardInstance.unmount();
            cardInstance = null;
        }

        if (elementsInstance) {
            elementsInstance = null;
        }

        if (stripeInstance) {
            stripeInstance = null;
        }

        const cardElement = document.querySelector('#card-element');
        if (!cardElement) return;

        // Initialize Stripe
        stripeInstance = Stripe(stripe_vars.stripePublicKey);
        elementsInstance = stripeInstance.elements();
        cardInstance = elementsInstance.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    '::placeholder': { color: '#aab7c4' },
                },
                invalid: { color: '#fa755a' },
            },
            hidePostalCode: true,
        });
        cardInstance.mount('#card-element');

        // Fetch and set clientSecret before processing payment
        const clientSecret = await fetchClientSecret(updatedPaymentCost);
        if (!clientSecret) {
            document.getElementById('card-errors').textContent = 'Payment initialization failed.';
            return;
        }

        // Get the form and submit button
        form = document.querySelector('#payment-form');
        submitButton = form.querySelector('input[type="submit"]');

        // Remove existing submit event listener to avoid duplicates
        form.removeEventListener('submit', handleStripeSubmit);

        // Add the new submit event listener
        form.addEventListener('submit', handleStripeSubmit);

        cardElement.parentElement.style.padding = '1rem 0';
    }

    window.reInitStripe = async function reinitializeStripe() {
        const updatedPaymentCost = document.getElementById('actual-payment-field').value;

        // Reinitialize Stripe with the new clientSecret
        initializeStripe(updatedPaymentCost);
    };

    // Cleanup on page unload
    window.addEventListener('beforeunload', function () {
        if (cardInstance) {
            cardInstance.unmount();
            cardInstance = null;
        }

        if (form) {
            form.removeEventListener('submit', handleStripeSubmit);
        }
    });
});