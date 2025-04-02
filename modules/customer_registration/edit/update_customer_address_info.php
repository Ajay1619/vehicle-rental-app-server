<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}

// Get POST data (assuming JSON input from Android app)
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !$input || !isset($input['addressInfo'])) {
    sendResponse(400, 'error', 'Missing or invalid addressInfo data');
}

$addressInfo = $input['addressInfo'];
$doorNo = sanitizeInput($addressInfo['doorNo'], 'string') ?? null;
$streetName = sanitizeInput($addressInfo['streetName'], 'string') ?? null;
$locality = sanitizeInput($addressInfo['locality'], 'string') ?? null;
$city = sanitizeInput($addressInfo['city'], 'string') ?? null;
$district = sanitizeInput($addressInfo['district'], 'string') ?? null;
$state = sanitizeInput($addressInfo['state'], 'string') ?? null;
$country = sanitizeInput($addressInfo['country'], 'string') ?? null;
$postalCode = sanitizeInput($addressInfo['postalCode'], 'string') ?? null;
$latitude = sanitizeInput($addressInfo['latitude'], 'string') ?? null;
$longitude = sanitizeInput($addressInfo['longitude'], 'string') ?? null;

$customer_account_id = isset($_GET['id']) ? sanitizeInput(($_GET['id'])) : null;


try {
    $params_procedures = [
        ['name' => 'customer_account_id', 'type' => 'i', 'value' => $customer_account_id],
        ['name' => 'doorNo', 'type' => 's', 'value' => $doorNo],
        ['name' => 'streetName', 'type' => 's', 'value' => $streetName],
        ['name' => 'locality', 'type' => 's', 'value' => $locality],
        ['name' => 'city', 'type' => 's', 'value' => $city],
        ['name' => 'district', 'type' => 's', 'value' => $district],
        ['name' => 'state', 'type' => 's', 'value' => $state],
        ['name' => 'country', 'type' => 's', 'value' => $country],
        ['name' => 'postalCode', 'type' => 's', 'value' => $postalCode],
        ['name' => 'latitude', 'type' => 's', 'value' => $latitude],
        ['name' => 'longitude', 'type' => 's', 'value' => $longitude],

    ];

    $response = callProcedure('update_customer_address_info', $params_procedures);
    if ($response) {
        sendResponse($response['particulars'][0]['status_code'], $response['particulars'][0]['status'], $response['particulars'][0]['message']);
    } else {
        sendResponse(500, 'error', 'Address Data Updation Failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'create_customer_account.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
