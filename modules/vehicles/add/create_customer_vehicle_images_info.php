<?php

// Ensure this file is only included via index.php
if (!defined('INCLUDED_VIA_INDEX')) {
    define('INCLUDED_VIA_INDEX', true);
}

// Get POST data (assuming JSON input from Android app)
$input = json_decode(file_get_contents('php://input'), true);
$vehicle_id = isset($_POST['vehicle_id']) ? sanitizeInput($_POST['vehicle_id'], 'int') : 0;
$pr_vehicle_id = isset($_POST['pr_vehicle_id']) ? sanitizeInput($_POST['pr_vehicle_id'], 'int') : 0;
$prVehicleImages = [];
$image_link = [];
try {
    if ($pr_vehicle_id) {
        $im_params_procedures = [
            ['name' => 'vehicle_id', 'type' => 'i', 'value' => $pr_vehicle_id]
        ];
        $im_response = callProcedure('fetch_customer_individual_vehicle_info', $im_params_procedures);

        if ($im_response) {
            if ($im_response['particulars'][0]['status_code'] == 200) {
                if (isset($im_response['data'][1])) {
                    foreach ($im_response['data'][1] as $key => $value) {
                        $prVehicleImages[] = $value['vehicle_image_path'];
                    }
                }
            } else {
                sendResponse(
                    "300",
                    "error",
                    "Error: Vehicle Image Upload Failed"
                );
            }
        } else {
            sendResponse(500, 'error', 'Vehicle Image Upload failed');
        }
    }

    foreach ($_FILES['images']['name'] as $key => $value) {

        if (isset($_FILES['images']['name'][$key]) && $_FILES['images']['error'][$key] == UPLOAD_ERR_OK) {
            if (!in_array($_FILES['images']['name'][$key], $prVehicleImages)) {
                $file = [
                    'name' => $_FILES['images']['name'][$key],
                    'type' => $_FILES['images']['type'][$key],
                    'tmp_name' => $_FILES['images']['tmp_name'][$key],
                    'error' => $_FILES['images']['error'][$key],
                    'size' => $_FILES['images']['size'][$key]
                ];
                $imagePath = uploadFile($file, ROOT . '/uploads/vehicles_images', "VEHICLE_IMAGE - ");
                if ($imagePath['status_code'] == 200) {
                    $image_link[] = $imagePath['files'][0];
                }
            } else {
                $image_link[] = $_FILES['images']['name'][$key];
            }
        }
    }

    $params_procedures = [
        ['name' => 'vehicle_id', 'type' => 'i', 'value' => $vehicle_id],
        ['name' => 'image_link', 'type' => 's', 'value' => json_encode($image_link)]
    ];


    $response = callProcedure('update_customer_vehicle_images_info', $params_procedures);
    if ($response) {
        if ($response['particulars'][0]['status_code'] == 200) {
            sendResponse(
                $response['particulars'][0]['status_code'],
                $response['particulars'][0]['status'],
                $response['particulars'][0]['message']
            );
        } else {
            sendResponse(
                "300",
                "error",
                "Error: Vehicle Image Upload Failed"
            );
        }
    } else {
        sendResponse(500, 'error', 'Vehicle Image Upload failed');
    }
} catch (\Throwable $th) {
    insert_error($th->getMessage(), 'create_customer_vehicle_images_info.php', 2);
    sendResponse(500, 'error', 'Database error: ' . $th->getMessage());
}
