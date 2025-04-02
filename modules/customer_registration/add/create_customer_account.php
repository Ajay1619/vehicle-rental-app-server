<?php
// vehicle_rental_app/modules/customer_registeration/create_customer_account.php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}

// Get POST data (assuming JSON input from Android app)
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !$input || !isset($input['accountInfo'])) {
    sendResponse(400, 'error', 'Missing or invalid accountInfo data');
}

$accountInfo = $input['accountInfo'];
$username = sanitizeInput($accountInfo['username'], 'string') ?? null;

$validation_error = checkEmptyField($username, "Username");

if ($validation_error) {
    sendResponse(400, 'error', $validation_error);
}
try {
    $check_username_existence_params_procedures = [
        ['name' => 'account_username', 'type' => 's', 'value' => $username],
        ['name' => 'account_portal_type', 'type' => 'i', 'value' => 1]
    ];

    $check_username_existence_response = callProcedure('check_username_existence', $check_username_existence_params_procedures);

    if ($check_username_existence_response) {
        if ($check_username_existence_response['particulars'][0]['status_code'] == 200) {
            $password = $accountInfo['password'] ?? null;

            $validation_error = checkEmptyField($password, "Password");
            if ($validation_error) {
                sendResponse(400, 'error', $validation_error);
            }

            $password_validation_error = validatePassword($password);
            if (!$password_validation_error) {
                sendResponse(400, 'error', $password_validation_error);
            }

            try {

                $password_key = generateMasterKey($password);
                $create_account_params_procedures = [
                    ['name' => 'account_username', 'type' => 's', 'value' => $username],
                    ['name' => 'account_password', 'type' => 's', 'value' => json_encode($password_key)],
                    ['name' => 'account_portal_type', 'type' => 'i', 'value' => 1],
                ];

                $create_account_response = callProcedure('create_account', $create_account_params_procedures);
                if ($create_account_response) {
                    if ($create_account_response['particulars'][0]['status_code'] === 200) {
                        $_SESSION['account_master_key'] = $password_key['master_key'];
                        $accountId = $create_account_response['data'][0][0]['account_id'];
                        sendResponse(200, $create_account_response['particulars'][0]['status'], $create_account_response['particulars'][0]['message'], ['account_id' => $accountId]);
                    } else {
                        sendResponse(400, $create_account_response['particulars'][0]['status'], $create_account_response['particulars'][0]['message']);
                    }
                } else {
                    sendResponse(500, 'error', 'Account creation failed');
                }
            } catch (\Throwable $th) {
                insert_error($th->getMessage(), 'create_customer_account.php', 2);
                sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
            }
        } else {
            sendResponse($check_username_existence_response['particulars'][0]['status_code'], $check_username_existence_response['particulars'][0]['status'], $check_username_existence_response['particulars'][0]['message']);
        }
    } else {
        sendResponse(500, 'error', 'Username check failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'create_customer_account.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
