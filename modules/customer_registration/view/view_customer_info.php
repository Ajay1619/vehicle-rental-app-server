<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}


$customer_account_id = isset($_GET['id']) ? sanitizeInput(($_GET['id'])) : null;

try {
    $params_procedures = [
        ['name' => 'customer_account_id', 'type' => 'i', 'value' => $customer_account_id]
    ];

    $response = callProcedure('fetch_individual_customer_info', $params_procedures);
    if ($response) {
        if ($response['particulars'][0]['status_code'] === 200) {
            if (isset($response['data'][0])) {
                $contactInfo = $response['data'][0][0];
                $addressInfo = $response['data'][1][0];
                $bankInfo = $response['data'][2][0];
                $businessInfo = $response['data'][3][0];

                sendResponse(
                    200,
                    $response['particulars'][0]['status'],
                    $response['particulars'][0]['message'],
                    [
                        'contactInfo' => $contactInfo,
                        'addressInfo' => $addressInfo,
                        'bankInfo' => $bankInfo,
                        'businessInfo' => $businessInfo
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
