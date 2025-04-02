<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}


$guest_account_id = isset($_GET['id']) ? sanitizeInput(($_GET['id'])) : null;

try {
    $params_procedures = [
        ['name' => 'guest_account_id', 'type' => 'i', 'value' => $guest_account_id]
    ];

    $response = callProcedure('fetch_individual_guest_info', $params_procedures);
    if ($response) {
        if ($response['particulars'][0]['status_code'] === 200) {
            if (isset($response['data'][0][0])) {
                $guest_info = $response['data'][0][0];
                sendResponse(200, $response['particulars'][0]['status'], $response['particulars'][0]['message'], ['account_info' => $guest_info]);
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
    insert_error($th->getMessage(), 'view_guest_info.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
