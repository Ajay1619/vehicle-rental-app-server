<?php

// Include necessary config files
require_once '../../config/sparrow.php';

// Get POST data (assuming JSON input from Android app)
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($input)) {
    sendResponse(400, 'error', 'Missing or invalid Login data');
}

$username = isset($input['username']) ? sanitizeInput($input['username'], 'string') : null;
$userpassword = isset($input['username']) ? $input['password'] : null;

try {
    $check_login_username_params_procedures = [
        ['name' => 'username', 'type' => 's', 'value' => $username]
    ];

    $check_login_username_response = callProcedure('check_login_username', $check_login_username_params_procedures);
    if ($check_login_username_response) {
        if ($check_login_username_response['particulars'][0]['status_code'] == 200) {
            if (isset($check_login_username_response['data'][0][0]) && $check_login_username_response['data'][0][0]['exist'] > 0) {
                $check_username_existence_data = $check_login_username_response['data'][0][0];
                $account_id = $check_username_existence_data['account_id'];
                $account_portal_type = $check_username_existence_data['account_portal_type'];
                $account_code = $check_username_existence_data['account_code'];
                $salt_value = $check_username_existence_data['salt_value'];
                $master_key = $check_username_existence_data['master_key'];
                $iterations_value = $check_username_existence_data['iterations_value'];
                $iv_value = $check_username_existence_data['iv_value'];
                $checked_password = verifyAndDecryptMasterKey($userpassword, $master_key, $salt_value, $iterations_value, $iv_value);
                $user_ip_address = getUserIP();
                if ($checked_password) {
                    if ($user_ip_address) {
                        $login_log_params_procedures = [
                            ['name' => 'username', 'type' => 's', 'value' => $username],
                            ['name' => 'account_id', 'type' => 'i', 'value' => $account_id],
                            ['name' => 'user_ip_address', 'type' => 's', 'value' => $user_ip_address],
                            ['name' => 'successful_login', 'type' => 'i', 'value' => 1],
                            ['name' => 'login_status', 'type' => 'i', 'value' => 1],
                            ['name' => 'account_portal_type', 'type' => 'i', 'value' => $account_portal_type],
                            ['name' => 'p_type', 'type' => 'i', 'value' => 1],
                            ['name' => 'login_id', 'type' => 'i', 'value' => 0],
                        ];
                        $login_log_response = callProcedure('login_log', $login_log_params_procedures);
                        if ($login_log_response) {
                            if ($login_log_response['particulars'][0]['status_code'] === 200) {
                                $data = $login_log_response['data'][0][0];
                                $return_data = [
                                    'user_id' => $data['user_id'],
                                    'account_id' => $account_id,
                                    'account_code' => $account_code,
                                    'name' => $data['name'],
                                    'image_url' => $data['image_url']
                                ];
                                sendResponse(200, $login_log_response['particulars'][0]['status'], $login_log_response['particulars'][0]['message'], ['data' => $return_data]);
                            } else {
                                sendResponse(400, $login_log_response['particulars'][0]['status'], $login_log_response['particulars'][0]['message']);
                            }
                        } else {
                            sendResponse(500, 'error', 'Login failed! Please Try Again Later');
                        }
                    } else {
                        sendResponse(400, 'error', 'Invalid IP Address');
                    }
                } else {
                    $login_log_params_procedures = [
                        ['name' => 'username', 'type' => 's', 'value' => $username],
                        ['name' => 'account_id', 'type' => 'i', 'value' => $account_id],
                        ['name' => 'user_ip_address', 'type' => 's', 'value' => $user_ip_address],
                        ['name' => 'successful_login', 'type' => 'i', 'value' => 0],
                        ['name' => 'login_status', 'type' => 'i', 'value' => 2],
                        ['name' => 'account_portal_type', 'type' => 'i', 'value' => $account_portal_type],
                        ['name' => 'p_type', 'type' => 'i', 'value' => 2],
                        ['name' => 'login_id', 'type' => 'i', 'value' => 0],

                    ];
                    $login_log_response = callProcedure('login_log', $login_log_params_procedures);
                    sendResponse(400, 'error', 'Invalid Password');
                }
            } else {
                sendResponse(400, 'error', 'User Name does not exist');
            }
        } else {
            sendResponse($check_login_username_response['particulars'][0]['status_code'], $check_login_username_response['particulars'][0]['status'], $check_login_username_response['particulars'][0]['message']);
        }
    } else {
        sendResponse(500, 'error', 'User Name Vaidation Failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'create_guest_account.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
