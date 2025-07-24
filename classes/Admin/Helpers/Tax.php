<?php
namespace ReserveMate\Admin\Helpers;

defined('ABSPATH') or die('No direct access!');

use WP_Error;

class Tax {
    public static function add_tax($name, $rate, $type = 'percentage') {
        if (!current_user_can('manage_options')) {
            return new WP_Error('insufficient_permissions', 'You do not have permission to add taxes.');
        }

        if (empty($name) || !is_string($name)) {
            return new WP_Error('invalid_name', 'Tax name is required and must be a string.');
        }

        if (!is_numeric($rate) || $rate < 0) {
            return new WP_Error('invalid_rate', 'Tax rate must be a positive number.');
        }

        $allowed_types = ['percentage', 'fixed', 'per_person'];
        if (!in_array($type, $allowed_types, true)) {
            return new WP_Error('invalid_type', 'Invalid tax type provided.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . "reservemate_taxes";

        $existing_tax = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE name = %s",
            sanitize_text_field($name)
        ));

        if ($existing_tax) {
            return new WP_Error('duplicate_tax', 'A tax with this name already exists.');
        }

        $result = $wpdb->insert(
            $table_name,
            [
                'name' => sanitize_text_field($name),
                'rate' => floatval($rate),
                'type' => $type,
            ],
            ['%s', '%f', '%s']
        );

        if ($result === false) {
            return new WP_Error('insert_failed', 'Failed to add tax to database.');
        }

        return $wpdb->insert_id;
    }

    public static function delete_tax($tax_id) {
        $tax_id = intval($tax_id);
        if ($tax_id <= 0) {
            return new WP_Error('invalid_id', 'Invalid tax ID provided.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . "reservemate_taxes";
        $existing_tax = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $tax_id
        ));

        if (!$existing_tax) {
            return new WP_Error('tax_not_found', 'Tax not found.');
        }

        $result = $wpdb->delete(
            $table_name,
            ['id' => $tax_id],
            ['%d']
        );

        if ($result === false) {
            error_log("ReserveMate: Failed to delete tax ID $tax_id - " . $wpdb->last_error);
            return new WP_Error('delete_failed', 'Failed to delete tax from database.');
        }

        if ($result === 0) {
            return new WP_Error('tax_not_found', 'Tax not found or already deleted.');
        }

        return true;
    }

    public static function get_taxes($type = null, $limit = null, $offset = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . "reservemate_taxes";

        $sql = "SELECT * FROM $table_name";
        $where_conditions = [];
        $prepare_values = [];

        if ($type && in_array($type, ['percentage', 'fixed', 'per_person'], true)) {
            $where_conditions[] = "type = %s";
            $prepare_values[] = $type;
        }

        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(" AND ", $where_conditions);
        }

        $sql .= " ORDER BY name ASC";

        if ($limit && is_numeric($limit)) {
            $sql .= " LIMIT %d";
            $prepare_values[] = intval($limit);

            if ($offset && is_numeric($offset)) {
                $sql .= " OFFSET %d";
                $prepare_values[] = intval($offset);
            }
        }

        if (!empty($prepare_values)) {
            $prepared_sql = $wpdb->prepare($sql, $prepare_values);
            $taxes = $wpdb->get_results($prepared_sql, OBJECT);
        } else {
            $taxes = $wpdb->get_results($sql, OBJECT);
        }

        return $taxes ? $taxes : [];
    }
}