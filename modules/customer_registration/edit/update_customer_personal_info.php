<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}

// Get POST data (assuming JSON input from Android app)
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !$input || !isset($input['contactInfo'])) {
    sendResponse(400, 'error', 'Missing or invalid contactInfo data');
}

$contactInfo = $input['contactInfo'];
$contactName = sanitizeInput($contactInfo['contactName'], 'string') ?? null;
$mobileNumber = sanitizeInput($contactInfo['mobileNumber'], 'string') ?? null;
$emailId = sanitizeInput($contactInfo['emailId'], 'string') ?? null;

$customer_account_id = isset($_GET['id']) ? sanitizeInput(($_GET['id'])) : null;

$validation_error = checkEmptyField($contactName, "Contact Name") ?? checkEmptyField($mobileNumber, "Mobile Number") ?? checkEmptyField($emailId, "Email ID");
$validation_error = isPhoneNumber($mobileNumber);
$validation_error = isEmail($emailId);

if (!$validation_error) {
    sendResponse(400, 'error', $validation_error);
}
$contactName = capitalizeFirstLetter($contactName);

try {
    $params_procedures = [
        ['name' => 'customer_account_id', 'type' => 'i', 'value' => $customer_account_id],
        ['name' => 'contactName', 'type' => 's', 'value' => $contactName],
        ['name' => 'mobileNumber', 'type' => 's', 'value' => $mobileNumber],
        ['name' => 'emailId', 'type' => 's', 'value' => $emailId],

    ];

    $response = callProcedure('update_customer_personal_info', $params_procedures);
    if ($response) {
        sendResponse($response['particulars'][0]['status_code'], $response['particulars'][0]['status'], $response['particulars'][0]['message']);
    } else {
        sendResponse(500, 'error', 'Personal Data Updation Failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'create_customer_account.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
