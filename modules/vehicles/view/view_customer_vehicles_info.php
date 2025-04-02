<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}

$customerId = isset($_GET['customerId']) ? sanitizeInput(($_GET['customerId']), 'int') : null;
$vehicleType = isset($_GET['vehicleType']) ? sanitizeInput(($_GET['vehicleType']), 'int') : null;

if (!$customerId || !isset($customerId) || !$vehicleType || !isset($vehicleType)) {
    sendResponse(400, 'error', 'Missing or invalid User Id data');
}

try {
    $params_procedures = [
        ['name' => 'customer_account_id', 'type' => 'i', 'value' => $customerId],
        ['name' => 'vehicle_type', 'type' => 'i', 'value' => $vehicleType],
    ];
    $response = callProcedure('fetch_customer_vehicles_list', $params_procedures);

    if ($response) {
        if ($response['particulars'][0]['status_code'] === 200) {
            if (isset($response['data'][0])) {
                sendResponse(
                    200,
                    $response['particulars'][0]['status'],
                    $response['particulars'][0]['message'],
                    $response['data'][0]
                );
            } else {
                sendResponse(200, 'warning', 'No Data Found');
            }
        } else {
            sendResponse(400, $response['particulars'][0]['status'], $response['particulars'][0]['message']);
        }
    } else {
        sendResponse(500, 'error', 'Fetch failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'view_customer_vehicles_info.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
