<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}


$vehicle_id = isset($_POST['vehicleId']) ? sanitizeInput($_POST['vehicleId'], 'int') : 0;
$vehicle_customer_id = isset($_POST['customerId']) ? sanitizeInput($_POST['customerId'], 'int') : 0;
$vehicle_type = isset($_POST['vehicleType']) ? sanitizeInput($_POST['vehicleType'], 'int') : 0;
$vehicle_title = isset($_POST['vehicleTitle']) ? sanitizeInput($_POST['vehicleTitle'], 'string') : "";
$vehicle_brand = isset($_POST['vehicleBrandId']) ? sanitizeInput($_POST['vehicleBrandId'], 'int') : 0;
$vehicle_model = isset($_POST['vehicleModel']) ? sanitizeInput($_POST['vehicleModel'], 'string') : "";
$vehicle_number = isset($_POST['vehicleNumber']) ? sanitizeInput($_POST['vehicleNumber'], 'string') : "";
$vehicle_category = isset($_POST['vehicleCategory']) ? sanitizeInput($_POST['vehicleCategory'], 'int') : 0;
$vehicle_color = isset($_POST['vehicleColor']) ? sanitizeInput($_POST['vehicleColor'], 'string') : "";
$vehicle_seating_capacity = isset($_POST['seatingCapacity']) ? sanitizeInput($_POST['seatingCapacity'], 'int') : 0;
$vehicle_luggage_capacity = isset($_POST['luggageCapacity']) ? sanitizeInput($_POST['luggageCapacity'], 'int') : 0;
$rc_link = "";

$prVehicleregistrationCertificate = [];
if ($vehicle_id != -1) {

    $rc_params_procedures = [
        ['name' => 'vehicle_id', 'type' => 'i', 'value' => $vehicle_id]
    ];
    $rc_response = callProcedure('fetch_customer_individual_vehicle_info', $rc_params_procedures);
    if ($rc_response) {
        if ($rc_response['particulars'][0]['status_code'] == 200) {
            $prVehicleregistrationCertificate[] = $rc_response['data'][0][0]['rc_path'];
        } else {
            sendResponse(
                "300",
                "error",
                "Error: Vehicle Basic Info Upload Failed"
            );
        }
    } else {
        sendResponse(500, 'error', 'Vehicle Image Upload failed');
    }
}
if (isset($_FILES['registrationCertificate']['name']) && $_FILES['registrationCertificate']['error'] == UPLOAD_ERR_OK) {
    if (!in_array($_FILES['registrationCertificate']['name'], $prVehicleregistrationCertificate)) {

        $rcPath = uploadFile($_FILES['registrationCertificate'], ROOT . '/uploads/vehicles_rc', "VEHICLE_RC - ");
        if ($rcPath['status_code'] == 200) {
            $rc_link = $rcPath['files'][0];
        }
    } else {
        $rc_link = $_FILES['registrationCertificate']['name'];
    }
}

try {
    $params_procedures = [
        ['name' => 'vehicle_id', 'type' => 'i', 'value' => $vehicle_id],
        ['name' => 'vehicle_customer_id', 'type' => 'i', 'value' => $vehicle_customer_id],
        ['name' => 'vehicle_type', 'type' => 'i', 'value' => $vehicle_type],
        ['name' => 'vehicle_title', 'type' => 's', 'value' => $vehicle_title],
        ['name' => 'vehicle_brand', 'type' => 'i', 'value' => $vehicle_brand],
        ['name' => 'vehicle_model', 'type' => 's', 'value' => $vehicle_model],
        ['name' => 'vehicle_category', 'type' => 'i', 'value' => $vehicle_category],
        ['name' => 'vehicle_color', 'type' => 's', 'value' => $vehicle_color],
        ['name' => 'vehicle_seating_capacity', 'type' => 'i', 'value' => $vehicle_seating_capacity],
        ['name' => 'vehicle_luggage_capacity', 'type' => 'i', 'value' => $vehicle_luggage_capacity],
        ['name' => 'vehicle_number', 'type' => 'i', 'value' => $vehicle_number],
        ['name' => 'rc_link', 'type' => 's', 'value' => $rc_link],
    ];

    $response = callProcedure('update_customer_vehicle_basic_info', $params_procedures);

    if ($response) {
        if ($response['particulars'][0]['status_code'] == 200) {
            $new_vehicle_id = $response['data'][0][0]['new_vehicle_id'];
            sendResponse(
                $response['particulars'][0]['status_code'],
                $response['particulars'][0]['status'],
                $response['particulars'][0]['message'],
                [
                    "vehicle_id" => $new_vehicle_id
                ]
            );
        } else {
            sendResponse(
                $response['particulars'][0]['status_code'],
                $response['particulars'][0]['status'],
                $response['particulars'][0]['message']
            );
        }
    } else {
        sendResponse(500, 'error', 'Username check failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'create_customer_vehicle_basic_info.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
