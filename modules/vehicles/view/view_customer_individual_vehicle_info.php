<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}


$vehicleId = isset($_GET['vehicleId']) ? sanitizeInput(($_GET['vehicleId'])) : null;

try {
    $params_procedures = [
        ['name' => 'vehicleId', 'type' => 'i', 'value' => $vehicleId]
    ];

    $response = callProcedure('fetch_customer_individual_vehicle_info', $params_procedures);
    if ($response) {
        if ($response['particulars'][0]['status_code'] === 200) {
            if (isset($response['data'][0])) {
                $vehicleBasicInfo = $response['data'][0][0];
                $vehicleImages = $response['data'][1];
                $vehicleSlots = $response['data'][2];

                sendResponse(
                    200,
                    $response['particulars'][0]['status'],
                    $response['particulars'][0]['message'],
                    [
                        'vehicleBasicInfo' => $vehicleBasicInfo,
                        'vehicleImages' => $vehicleImages,
                        'vehicleSlots' => $vehicleSlots
                    ]
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
