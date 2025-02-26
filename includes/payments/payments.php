<?php
defined('ABSPATH') or die('No direct access!');

use Stripe\Stripe;
use Stripe\Charge;

\Stripe\Stripe::setApiKey(get_option('payment_settings')['stripe_secret_key']);

add_action('wp_ajax_create_payment_intent', 'create_payment_intent');
add_action('wp_ajax_nopriv_create_payment_intent', 'create_payment_intent');

function create_payment_intent() {
    if (!isset($_POST['totalPaymentCost'])) {
        wp_send_json_error(['error' => 'Missing total payment cost.']);
    }

    $amount = floatval($_POST['totalPaymentCost']); // Ensure it's a number
    $response = create_stripe_payment_intent($amount);

    if ($response['success']) {
        wp_send_json_success(['clientSecret' => $response['clientSecret']]);
    } else {
        wp_send_json_error(['error' => $response['error']]);
    }
}

function create_stripe_payment_intent($amount) {
    $amount = floatval($amount) * 100; // Convert to cents

    try {
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'usd',
            'payment_method_types' => ['card'],
        ]);
        return ['success' => true, 'clientSecret' => $paymentIntent->client_secret];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function retrieve_payment_intent($paymentIntentId) {
    $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
    return $paymentIntent;
}

// Stripe Payments END


// PayPal Payments
// function verify_paypal_payment($paypalPaymentID) {
//     $paypalClientID = '';
//     $paypalSecret = '';
//     $isSandbox = get_option('paypal_mode') === 'sandbox';
    
//     $paypalUrl = $isSandbox ? "https://api-m.sandbox.paypal.com/v2/checkout/orders/" : "https://api-m.paypal.com/v2/checkout/orders/";

//     $auth = base64_encode("$paypalClientID:$paypalSecret");

//     // Get PayPal Access Token
//     $tokenResponse = wp_remote_post($isSandbox ? "https://api-m.sandbox.paypal.com/v1/oauth2/token" : "https://api-m.paypal.com/v1/oauth2/token", [
//         'body' => 'grant_type=client_credentials',
//         'headers' => [
//             'Authorization' => "Basic $auth",
//             'Content-Type'  => 'application/x-www-form-urlencoded',
//         ],
//     ]);

//     $tokenBody = json_decode(wp_remote_retrieve_body($tokenResponse), true);
//     error_log(print_r($tokenBody));
//     if (!isset($tokenBody['access_token'])) {
//         return false; // Failed to get access token
//     }

//     $accessToken = $tokenBody['access_token'];

//     // Verify PayPal Order
//     $orderResponse = wp_remote_get($paypalUrl . $paypalPaymentID, [
//         'headers' => [
//             'Authorization' => "Bearer $accessToken",
//             'Content-Type'  => 'application/json',
//         ],
//     ]);

//     $orderBody = json_decode(wp_remote_retrieve_body($orderResponse), true);
//     error_log("PayPal Verification Failed: " . json_encode($orderBody));
//     // Check if payment is completed
//     return isset($orderBody['status']) && $orderBody['status'] === 'COMPLETED';
// }







