<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}

$vehicle_id = isset($_GET['vehicle_id']) ? sanitizeInput(($_GET['vehicle_id']), 'int') : null;

if (!$vehicle_id || !isset($vehicle_id)) {
    sendResponse(400, 'error', 'Missing or invalid Vehicle Id data');
}

try {
    $params_procedures = [
        ['name' => 'vehicle_id', 'type' => 'i', 'value' => $vehicle_id]
    ];
    $response = callProcedure('delete_customer_individual_vehicle', $params_procedures);

    if ($response) {
        sendResponse(
            200,
            $response['particulars'][0]['status'],
            $response['particulars'][0]['message']
        );
    } else {
        sendResponse(500, 'error', 'Delete failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'delete_customer_individual_vehicle.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
