<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}

// Get POST data (assuming JSON input from Android app)
$input = json_decode(file_get_contents('php://input'), true);


$customer_account_id = isset($_POST['accountId']) ? sanitizeInput($_POST['accountId'], 'int') : null;
$businessName = isset($_POST['businessName']) ? sanitizeInput($_POST['businessName'], 'string') : null;
$registerNumber = isset($_POST['registerNumber']) ? sanitizeInput($_POST['registerNumber'], 'string') : null;
$gstin = isset($_POST['gstin']) ? sanitizeInput($_POST['gstin'], 'string') : null;


$logo_link = "";
$license_link = "";
// Check and process logo file upload
if (isset($_FILES['logoFile']) && $_FILES['logoFile']['error'] == UPLOAD_ERR_OK) {
    $logoPath = uploadFile($_FILES['logoFile'], ROOT . '/uploads/customer_logos', $businessName . " - " . $registerNumber . " - BUSINESS_LOGO - ");
    if ($logoPath['status_code'] == 200) {
        $logo_link = $logoPath['files'][0];
    }
}

// Check and process license file upload
if (isset($_FILES['licenseFile']) && $_FILES['licenseFile']['error'] == UPLOAD_ERR_OK) {
    $licensePath = uploadFile($_FILES['licenseFile'], ROOT . '/uploads/customer_licenses', $businessName . " - " . $registerNumber . " - BUSINESS_LICENSE - ");
    if ($licensePath['status_code'] == 200) {
        $license_link = $licensePath['files'][0];
    }
}


try {
    $params_procedures = [
        ['name' => 'customer_account_id', 'type' => 'i', 'value' => $customer_account_id],
        ['name' => 'businessName', 'type' => 's', 'value' => $businessName],
        ['name' => 'registerNumber', 'type' => 's', 'value' => $registerNumber],
        ['name' => 'gstin', 'type' => 's', 'value' => $gstin],
        ['name' => 'logo_link', 'type' => 's', 'value' => $logo_link],
        ['name' => 'license_link', 'type' => 's', 'value' => $license_link],

    ];

    $response = callProcedure('update_customer_business_info', $params_procedures);
    if ($response) {
        sendResponse($response['particulars'][0]['status_code'], $response['particulars'][0]['status'], $response['particulars'][0]['message']);
    } else {
        sendResponse(500, 'error', 'Business Data Updation Failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'create_customer_account.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
