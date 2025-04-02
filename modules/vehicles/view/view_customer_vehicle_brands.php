<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}


$vehicleType = isset($_GET['vehicleType']) ? sanitizeInput(($_GET['vehicleType'])) : null;
try {
    $params_procedures = [
        ['name' => 'vehicleType', 'type' => 'i', 'value' => $vehicleType]
    ];

    $response = callProcedure('fetch_customer_individual_vehicle_brands', $params_procedures);

    if ($response) {
        if ($response['particulars'][0]['status_code'] === 200) {
            if (isset($response['data'][0])) {
                $data = ['brands' => $response['data'][0]];
                sendResponse(
                    200,
                    $response['particulars'][0]['status'],
                    $response['particulars'][0]['message'],
                    $data
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
    insert_error($th->getMessage(), 'create_customer_account.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
