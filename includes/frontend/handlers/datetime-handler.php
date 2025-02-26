<?php
defined('ABSPATH') or die('No direct access!');

function handle_datetime_booking_form() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['frontend_booking_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['frontend_booking_nonce'], 'final_form_submit')) {
        wp_die('Invalid nonce.');
    }

    // Sanitize and validate inputs
    $service_name = isset($_POST['service_name']) ? sanitize_text_field($_POST['service_name']) : 'Service One';
    $name = sanitize_text_field($_POST['name-field']);
    $email = sanitize_email($_POST['email-field']);
    $phone = sanitize_text_field($_POST['phone-field']);
    $adults = intval($_POST['adults-field']);
    $start_date = sanitize_text_field($_POST['start-date-field']);
    $end_date = sanitize_text_field($_POST['end-date-field']);
    $total_cost = isset($_POST['total-cost-field']) ? floatval($_POST['total-cost-field']) : 0;
    $paid_amount = isset($_POST['actual-payment-field']) ? floatval($_POST['actual-payment-field']) : 0;

    // Handle payment
    $payment_success = handle_payment();
    if ($payment_success) {
        save_datetime_booking_to_db($service_name, $name, $email, $phone, $adults, $start_date, $end_date, $total_cost, $payment_success['method'], $paid_amount);
        header('Location: ' . home_url() . '?booking_status=success');
        exit;
    } else {
        echo json_encode(['message' => 'Something went wrong.']);
        exit;
    }
}