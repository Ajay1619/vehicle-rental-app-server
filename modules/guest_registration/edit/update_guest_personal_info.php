<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}

// Get POST data (assuming JSON input from Android app)
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !$input || !isset($input['personalInfo'])) {
    sendResponse(400, 'error', 'Missing or invalid personalInfo data');
}

$personalInfo = $input['personalInfo'];
$guestName = sanitizeInput($personalInfo['guest_name'], 'string') ?? null;
$guestMobileNumber = sanitizeInput($personalInfo['guest_mobile_number'], 'string') ?? null;
$guestEmailId = sanitizeInput($personalInfo['guest_email_id'], 'string') ?? null;

$guest_account_id = isset($_GET['id']) ? sanitizeInput(($_GET['id'])) : null;

$validation_error = checkEmptyField($guestName, "Contact Name") ?? checkEmptyField($guestMobileNumber, "Mobile Number") ?? checkEmptyField($guestEmailId, "Email ID");
$validation_error = isPhoneNumber($guestMobileNumber);
$validation_error = isEmail($guestEmailId);

if (!$validation_error) {
    sendResponse(400, 'error', $validation_error);
}
$guestName = capitalizeFirstLetter($guestName);

try {
    $params_procedures = [
        ['name' => 'guest_account_id', 'type' => 'i', 'value' => $guest_account_id],
        ['name' => 'guestName', 'type' => 's', 'value' => $guestName],
        ['name' => 'guestMobileNumber', 'type' => 's', 'value' => $guestMobileNumber],
        ['name' => 'guestEmailId', 'type' => 's', 'value' => $guestEmailId],

    ];

    $response = callProcedure('update_guest_personal_info', $params_procedures);
    if ($response) {
        sendResponse($response['particulars'][0]['status_code'], $response['particulars'][0]['status'], $response['particulars'][0]['message']);
    } else {
        sendResponse(500, 'error', 'Personal Data Updation Failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'create_guest_account.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
