<?php
defined('ABSPATH') or die('No direct access!');

function handle_daterange_booking_form() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['frontend_booking_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['frontend_booking_nonce'], 'final_form_submit')) {
        wp_die('Invalid nonce.');
    }
    
    error_log(print_r($_POST));
    if (!empty($_POST['multiple-properties']) && $_POST['multiple-properties'] == 'true') {
        // Handle multiple property IDs
        $property_ids = !empty($_POST['multiple-ids']) ? explode(',', $_POST['multiple-ids']) : array();
        error_log('Multiple property IDs: ' . print_r($property_ids, true));
    } else {
        // Handle single property ID
        $property_ids = !empty($_POST['single-id']) ? [$_POST['single-id']] : [];
        error_log('Single property ID: ' . print_r($property_ids, true));
    }
    
    if (empty($property_ids)) {
        echo json_encode(['success' => false, 'error' => 'No property selected']);
        exit;
    }

    // Sanitize and validate inputs
    $name = sanitize_text_field($_POST['name-field']);
    $email = sanitize_email($_POST['email-field']);
    $phone = sanitize_text_field($_POST['phone-field']);
    $adults = intval($_POST['adults-field']);
    $children = isset($_POST['children-field']) ? intval($_POST['children-field']) : 0;
    $pets = isset($_POST['pets-field']) ? intval($_POST['pets-field']) : 0;
    $start_date = sanitize_text_field($_POST['start-date-field']);
    $end_date = sanitize_text_field($_POST['end-date-field']);
    $client_request = isset($_POST['client_request-field']) ? sanitize_text_field($_POST['client_request-field']) : '';
    $total_cost = isset($_POST['total-cost-field']) ? floatval($_POST['total-cost-field']) : 0;
    $paid_amount = isset($_POST['actual-payment-field']) ? floatval($_POST['actual-payment-field']) : 0;

    // Handle payment
    $payment_success = handle_payment();
    if ($payment_success) {
        save_booking_to_db($property_ids, $name, $email, $phone, $adults, $children, $pets, $start_date, $end_date, $total_cost, $paid_amount, $payment_success['method'], $client_request);
        header('Location: ' . home_url() . '?booking_status=success');
        exit;
    } else {
        echo json_encode(['message' => 'Something went wrong.']);
        exit;
    }
}