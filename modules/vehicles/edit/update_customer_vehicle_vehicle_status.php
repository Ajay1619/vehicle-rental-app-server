<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}

$vehicleId = isset($_GET['vehicleId']) ? sanitizeInput(($_GET['vehicleId']), 'int') : null;
$isActive = isset($_GET['isActive']) ? sanitizeInput(($_GET['isActive']), 'boolean') : null;
if (!$vehicleId || !isset($vehicleId)) {
    sendResponse(400, 'error', 'Missing or invalid Vehicle Id data');
}

if ($isActive == true) {
    $isActive = 1;
} else {
    $isActive = 2;
}

try {
    $params_procedures = [
        ['name' => 'vehicleId', 'type' => 'i', 'value' => $vehicleId],
        ['name' => 'isActive', 'type' => 'i', 'value' => $isActive],
    ];

    $response = callProcedure('update_customer_vehicle_status', $params_procedures);

    if ($response) {
        sendResponse(
            $response['particulars'][0]['status_code'],
            $response['particulars'][0]['status'],
            $response['particulars'][0]['message']
        );
    } else {
        sendResponse(500, 'error', 'Vehicle Status Updation failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'update_customer_vehicle_vehicle_status.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
