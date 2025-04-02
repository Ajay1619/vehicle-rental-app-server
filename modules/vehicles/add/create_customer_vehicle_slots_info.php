<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}

// Get POST data (assuming JSON input from Android app)
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE || !$input) {
    sendResponse(400, 'error', 'Missing or invalid vehicleSlotInfo data');
}


try {
    $params_procedures = [
        ['name' => 'input', 'type' => 's', 'value' => json_encode($input)],
    ];

    $response = callProcedure('update_customer_vehicle_slots_info', $params_procedures);
    if ($response) {
        if ($response['particulars'][0]['status_code'] == 200) {
            sendResponse(
                $response['particulars'][0]['status_code'],
                $response['particulars'][0]['status'],
                $response['particulars'][0]['message']
            );
        } else {
            sendResponse(
                "300",
                "error",
                "Error: Vehicle Slots Upload Failed"
            );
        }
    } else {
        sendResponse(500, 'error', 'Vehicle Slots Upload Failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'create_customer_vehicle_slots_info.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
