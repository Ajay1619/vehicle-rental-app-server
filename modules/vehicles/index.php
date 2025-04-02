<?php
// vehicle_rental_app/modules/customer_registeration/index.php

// Include necessary config files
require_once '../../config/sparrow.php';


// Define valid routes and types
$validRoutes = ['add', 'edit', 'view', 'delete'];
$validTypes = ['basic', 'images', 'slots', 'vehicles', 'vehicle_status', 'vehicle', 'vehicle_brands'];


// Get request parameters (route and type) from URL query string
$route = isset($_GET['route']) ? strtolower(sanitizeInput(($_GET['route']))) : null;
$type = isset($_GET['type']) ? strtolower(sanitizeInput(($_GET['type']))) : null;
// Validate route and type
if (!$route || !in_array($route, $validRoutes)) {
    sendResponse(400, 'error', 'Invalid or missing route parameter. Allowed: ' . implode(', ', $validRoutes));
}
if (!$type || !in_array($type, $validTypes)) {
    sendResponse(400, 'error', 'Invalid or missing type parameter. Allowed: ' . implode(', ', $validTypes));
}

// Map routes and types to specific files
$actionFileMap = [
    'add' => [
        'basic' => 'add/create_customer_vehicle_basic_info.php',
        'images' => 'add/create_customer_vehicle_images_info.php',
        'slots' => 'add/create_customer_vehicle_slots_info.php'
    ],
    'edit' => [
        'basic' => 'edit/update_customer_vehicle_basic_info.php',
        'images' => 'edit/update_customer_vehicle_images_info.php',
        'slots' => 'edit/update_customer_vehicle_slots_info.php',
        'vehicle_status' => 'edit/update_customer_vehicle_vehicle_status.php'
    ],
    'view' => [
        'vehicle' => 'view/view_customer_individual_vehicle_info.php',
        'vehicles' => 'view/view_customer_vehicles_info.php',
        'vehicle_brands' => 'view/view_customer_vehicle_brands.php'
    ],
    'delete' => [
        'vehicle' => 'delete/delete_customer_individual_vehicle.php'
    ],
];

// Determine the target file
$targetFile = null;
if (isset($actionFileMap[$route][$type])) {
    $targetFile = $actionFileMap[$route][$type];
} else {
    sendResponse(404, 'error', "No action defined for route '$route' and type '$type'");
}

// Check if the file exists and include it
$targetFilePath = __DIR__ . '/' . $targetFile;
if (file_exists($targetFilePath)) {
    require_once $targetFilePath;
} else {
    sendResponse(500, 'error', "Action file '$targetFile' not found");
}

// Note: The included file (e.g., create_customer_account.php) is expected to handle the logic
// and call sendResponse() with the appropriate data.