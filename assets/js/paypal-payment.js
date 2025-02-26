document.addEventListener('DOMContentLoaded', function () {
    let currentTotalCost = 0; // Store the current total cost

    function initializePayPal() {
        const paypalButtonContainer = document.querySelector('#paypal-button-container');
        currentTotalCost = document.getElementById('actual-payment-field').value;
        if (!paypalButtonContainer) {
            return;
        }
        
        // Clear the existing PayPal button container
        paypalButtonContainer.innerHTML = '';

        // Initialize PayPal buttons
        paypal.Buttons({
            style: {
                height: 44,
            },
            createOrder: function (data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: currentTotalCost // Use the dynamic amount
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
                // console.error('PayPal payment error: ', err);
                alert('Payment failed. Please try again.');
            }
        }).render('#paypal-button-container');

        paypalButtonContainer.parentElement.style.padding = "1rem 0";
    }

    // Initialize PayPal when the form is rendered
    document.addEventListener('paypalFormRendered', initializePayPal);

    // Function to update the total cost dynamically
    window.reInitPayPal = function reinitializePayPal() {
        const updatedTotalCost = document.getElementById('actual-payment-field').value;
        currentTotalCost = updatedTotalCost; // Update the dynamic amount
    };
});