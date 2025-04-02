<?php
// vehicle_rental_app/modules/customer_registeration/index.php

// Include necessary config files
require_once '../../config/sparrow.php';


// Define valid routes and types
$validRoutes = ['add', 'edit', 'view'];
$validTypes = ['account', 'personal', 'address', 'bank', 'business', 'customer'];


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
        'account' => 'add/create_customer_account.php'
    ],
    'edit' => [
        'personal' => 'edit/update_customer_personal_info.php',
        'address' => 'edit/update_customer_address_info.php',
        'bank' => 'edit/update_customer_bank_info.php',
        'business' => 'edit/update_customer_business_info.php'
    ],
    'view' => [
        'customer' => 'view/view_customer_info.php'
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