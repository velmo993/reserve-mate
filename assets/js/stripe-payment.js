document.addEventListener("DOMContentLoaded", function () {
    document.addEventListener('stripeFormRendered', function () {
        initializeStripe();
    });
    
    let stripeInstance = null;
    let elementsInstance = null;
    let cardInstance = null;
    let form = null;
    let submitButton = null;

    async function handleStripeSubmit(event) {
        event.preventDefault();

        submitButton.disabled = true;
        submitButton.value = 'Processing...';
        
        const agreement = document.querySelector('#stripe-payment-form .agreement');
        if (!agreement || !agreement.checked) {
            alert('You must accept the privacy policy to continue.');
            submitButton.value = 'Pay Now';
            return false;
        }

        try {
            // Create payment intent only when user clicks Pay Now
            const clientSecret = await fetchClientSecret();
            if (!clientSecret) {
                throw new Error('Failed to fetch client secret.');
            }

            const { paymentIntent, error } = await stripeInstance.confirmCardPayment(clientSecret, {
                payment_method: { card: cardInstance },
            });

            if (error) {
                document.getElementById('card-errors').textContent = error.message;
                submitButton.disabled = false;
                submitButton.value = 'Pay Now';
            } else if (paymentIntent.status === "succeeded") {
                form.submit();
                // window.location.href = '/successful-booking';
            }
        } catch (err) {
            // console.error("Error during payment processing:", err);
            document.getElementById('card-errors').textContent = 'An unexpected error occurred. Please try again.';
            
            submitButton.disabled = false;
            submitButton.value = 'Pay Now';
        }
    }

    async function fetchClientSecret(updatedPaymentCost = '') {
        const totalPaymentCost = document.getElementById('actual-payment-field').value;
        const response = await fetch(stripe_vars.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'create_payment_intent',
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
    
        stripeInstance = Stripe(stripe_vars.stripePublicKey, {
            betas: ['elements_enable_deferred_intent_beta_1'],
        });
    
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
        
        form = document.querySelector('#payment-form');
        submitButton = form.querySelector('#submit-stripe-payment');

        form.removeEventListener('submit', handleStripeSubmit);
        form.addEventListener('submit', handleStripeSubmit);

        cardElement.parentElement.style.padding = '1rem 0';
    }
    

    window.reInitStripe = async function reinitializeStripe() {
        const updatedPaymentCost = document.getElementById('actual-payment-field').value;

        initializeStripe(updatedPaymentCost);
    };

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