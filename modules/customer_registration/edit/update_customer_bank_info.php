<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}

// Get POST data (assuming JSON input from Android app)
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !$input || !isset($input['bankInfo'])) {
    sendResponse(400, 'error', 'Missing or invalid bankInfo data');
}

$bankInfo = $input['bankInfo'];
$accountHolderName = sanitizeInput($bankInfo['accountHolderName'], 'string') ?? null;
$bankName = sanitizeInput($bankInfo['bankName'], 'string') ?? null;
$branchName = sanitizeInput($bankInfo['branchName'], 'string') ?? null;
$accountNumber = sanitizeInput($bankInfo['accountNumber'], 'string') ?? null;
$ifscCode = sanitizeInput($bankInfo['ifscCode'], 'string') ?? null;
$upiNumber = sanitizeInput($bankInfo['upiNumber'], 'string') ?? null;

$customer_account_id = isset($_GET['id']) ? sanitizeInput(($_GET['id'])) : null;


try {
    $params_procedures = [
        ['name' => 'customer_account_id', 'type' => 'i', 'value' => $customer_account_id],
        ['name' => 'accountHolderName', 'type' => 's', 'value' => $accountHolderName],
        ['name' => 'bankName', 'type' => 's', 'value' => $bankName],
        ['name' => 'branchName', 'type' => 's', 'value' => $branchName],
        ['name' => 'accountNumber', 'type' => 's', 'value' => $accountNumber],
        ['name' => 'ifscCode', 'type' => 's', 'value' => $ifscCode],
        ['name' => 'upiNumber', 'type' => 's', 'value' => $upiNumber]

    ];

    $response = callProcedure('update_customer_bank_info', $params_procedures);
    if ($response) {
        sendResponse($response['particulars'][0]['status_code'], $response['particulars'][0]['status'], $response['particulars'][0]['message']);
    } else {
        sendResponse(500, 'error', 'Bank Data Updation Failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'create_customer_account.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
