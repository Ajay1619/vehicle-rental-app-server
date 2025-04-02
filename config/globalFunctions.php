<?php
//checklogin function
function checkLogin($login_id)
{
    $check_login_procedure_params = [
        ['value' => $login_id, 'type' => 'i']
    ];

    try {
        // Call the stored procedure
        $result = callProcedure('check_user_login_status', $check_login_procedure_params);

        // Handle the result
        if ($result['particulars'][0]['status_code'] !== 200) {
            // User login status is valid
            session_destroy();
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function insert_error($error_message = "Error!!", $activity_page = '', $error_side = 0)
{
    $error_log_procedure_params = [
        ['value' => 0, 'type' => 'i'],
        ['value' => $error_side, 'type' => 'i'],
        ['value' => $activity_page, 'type' => 's'],
        ['value' => $error_message, 'type' => 's']
    ];

    try {
        // Call the stored procedure
        $result = callProcedure('insert_error_log', $error_log_procedure_params);
        return $result;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}



// Function to send JSON response
function sendResponse($code, $status, $message, $data = [])
{
    $response = [
        'code' => $code,
        'status' => $status,
        'message' => $message,
        'data' => $data
    ];
    echo json_encode($response);
    exit;
}

function getUserIP()
{
    // Check HTTP_X_FORWARDED_FOR first (common in proxy setups)
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    // Fallback to REMOTE_ADDR (most reliable direct connection)
    if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    // Rarely used, but check HTTP_CLIENT_IP as a last resort
    if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    // Return empty string if no valid IP is found
    return '';
}


// Function to generate masterkey and related parameters
function generateMasterKey(string $password): array
{
    // Step 1: Generate a random salt
    $salt = random_bytes(16); // 16 bytes = 128 bits, strong salt

    // Step 2: Define PBKDF2 parameters
    $iterations = random_int(10000, 50000); // Random value between 10,000 and 50,000
    $keyLength = 32;      // 256-bit key length (32 bytes)
    $hashAlgo = 'sha256'; // Hash algorithm for PBKDF2

    // Step 3: Derive a key from the password using PBKDF2
    $derivedKey = hash_pbkdf2(
        $hashAlgo,        // Hash algorithm
        $password,        // User-entered password
        $salt,            // Random salt
        $iterations,      // Number of iterations
        $keyLength,       // Desired key length in bytes
        true              // Return raw binary output
    );

    // Step 4: Generate a random value to encrypt (e.g., a secret or seed)
    $randomValue = random_bytes(32); // 32 bytes = 256-bit random secret

    // Step 5: Encrypt the random value with the derived key to create the masterkey
    $iv = random_bytes(16); // 16-byte IV for AES-256-CBC (128-bit block size)
    $cipherAlgo = 'aes-256-cbc'; // AES-256 in CBC mode
    $encryptedValue = openssl_encrypt(
        $randomValue,       // Data to encrypt (random value)
        $cipherAlgo,        // Encryption algorithm
        $derivedKey,        // Key derived from PBKDF2
        OPENSSL_RAW_DATA,   // Return raw binary output
        $iv                 // Initialization vector
    );

    if ($encryptedValue === false) {
        throw new Exception('Encryption failed');
    }

    // Step 6: The encrypted value serves as the masterkey
    $masterKey = $encryptedValue;

    // Step 7: Return the masterkey and parameters needed for decryption/validation
    return [
        'master_key' => base64_encode($masterKey), // Base64 for storage/transmission
        'salt' => base64_encode($salt),            // Base64 for storage
        'iterations' => $iterations,               // Store iteration count
        'iv' => base64_encode($iv)
    ];
}

// Function to verify password and decrypt masterkey
function verifyAndDecryptMasterKey(string $password, string $storedMasterKey, string $salt, int $iterations, string $iv): bool
{
    // Step 1: Decode stored values from base64
    $masterKey = base64_decode($storedMasterKey);
    $salt = base64_decode($salt);
    $iv = base64_decode($iv);

    // Step 2: Re-derive the key using the stored random iterations
    $keyLength = 32;      // 256-bit key length
    $hashAlgo = 'sha256'; // Match the original hash algorithm
    $derivedKey = hash_pbkdf2(
        $hashAlgo,
        $password,
        $salt,
        $iterations, // Use the stored random iteration count
        $keyLength,
        true
    );

    // Step 3: Attempt to decrypt the masterkey with the derived key
    $cipherAlgo = 'aes-256-cbc';
    $decryptedValue = openssl_decrypt(
        $masterKey,
        $cipherAlgo,
        $derivedKey,
        OPENSSL_RAW_DATA,
        $iv
    );

    // Step 4: Check if decryption succeeded
    if ($decryptedValue == false) {
        return false; // Wrong password
    }

    // Step 5: Password is correct if decryption succeeds
    return true;
}

function generateUniqueFilename($originalFilename = '', $prefix = '', $includeOriginalName = true)
{
    // Get the file extension
    $fileExt = pathinfo($originalFilename, PATHINFO_EXTENSION);

    // Get the original base name (without extension) if including it
    $baseName = $includeOriginalName ? pathinfo($originalFilename, PATHINFO_FILENAME) : '';

    // Generate a random unique string using uniqid and replace the dot with a hyphen
    $randomString = str_replace('.', '', uniqid('', true)); // Replace . with ''
    $randomString = str_replace('', '', $randomString);
    // Get the current timestamp (customize format as needed)
    $timestamp = date('YmdHis'); // e.g., 20250313121329

    // Construct the unique filename
    $uniqueFilename = $prefix;
    if ($includeOriginalName && !empty($baseName)) {
        $uniqueFilename .= $baseName . '_';
    }
    $uniqueFilename .= $randomString . '_' . $timestamp . '.' . $fileExt;

    return $uniqueFilename;
}

function uploadFile($fileInput, $destinationDir, $prefix = '')
{
    // Array to hold the status and errors
    $result = ['status_code' => 200, 'status' => 'success', 'files' => [], 'message' => ''];

    // Ensure the destination directory exists
    if (!file_exists($destinationDir)) {
        if (!mkdir($destinationDir, 0777, true)) {
            return ['status_code' => 400, 'status' => 'error', 'message' => 'Failed to create destination directory.'];
        }
    }

    // Ensure the temporary directory exists
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('upload_', true);
    if (!mkdir($tempDir)) {
        return ['status_code' => 400, 'status' => 'error', 'message' => 'Failed to create temporary directory.'];
    }

    // Check if the input is an array of files or a single file
    $isMultiple = is_array($fileInput['name']);
    $filesCount = $isMultiple ? count($fileInput['name']) : 1;

    // Process files
    for ($i = 0; $i < $filesCount; $i++) {
        $fileName = $isMultiple ? $fileInput['name'][$i] : $fileInput['name'];
        $fileTmpName = $isMultiple ? $fileInput['tmp_name'][$i] : $fileInput['tmp_name'];
        $fileError = $isMultiple ? $fileInput['error'][$i] : $fileInput['error'];

        // Check for upload errors
        if ($fileError !== UPLOAD_ERR_OK) {
            $result['status'] = 'error';
            $result['message'] = "Error uploading file: $fileName. Error code: $fileError.";
            continue;
        }

        // Move the uploaded file to the temporary directory
        $tempFilePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file($fileTmpName, $tempFilePath)) {
            $result['status'] = 'error';
            $result['message'] = "Failed to move file to temp directory: $fileName.";
            continue;
        }

        // Generate a unique filename using the separate function
        $newFileName = generateUniqueFilename($fileName, $prefix, true); // Include original name
        $destinationPath = $destinationDir . DIRECTORY_SEPARATOR . $newFileName;

        // Check if the file already exists in the destination directory
        if (file_exists($destinationPath)) {
            if (!unlink($destinationPath)) {
                $result['status'] = 'error';
                $result['message'] = "Failed to delete existing file: $newFileName.";
                continue;
            }
        }

        // Move the file to the destination directory
        if (rename($tempFilePath, $destinationPath)) {
            $result['files'][] = $newFileName;
        } else {
            $result['status'] = 'error';
            $result['message'] = "Failed to move file to destination directory: $fileName.";
        }
    }

    // Clean up temporary directory
    foreach (glob($tempDir . '/*') as $file) {
        unlink($file);
    }
    rmdir($tempDir);

    // Set status code based on final status
    if ($result['status'] === 'error') {
        $result['status_code'] = 400;
    }

    return $result;
}



//check empty fields
function checkEmptyField($field, $fieldName)
{
    if ($field == null || $field == "" || $field == " ") {
        return "Error: $fieldName is required.";
    }
    return "";
}

//1. Validate Length with value and length
function validateLength($value, $length)
{
    if (strlen($value) <= $length) {
        return false;
    } else {
        return true;
    }
}

//2.validate Email
function isEmail($email)
{
    return preg_match('/^\S+@[\w\d.-]{2,}\.[\w]{2,6}$/iU', $email) ? true : "Please enter a valid email address.";
}

//isYear
function isYear($year)
{
    return preg_match('/^\d{4}$/', $year) ? true : false;
}
//isPercentage 
function isPercentage($percentage)
{
    return preg_match('/^100(\.0{1,2})?$|^\d{1,2}(\.\d{1,2})?$/', $percentage) ? true : false;
}

function base62_encode($data)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $base = strlen($characters);
    $encoded = '';

    $data = unpack('H*', $data)[1];
    $data = gmp_init($data, 16);

    while (gmp_cmp($data, 0) > 0) {
        $remainder = gmp_mod($data, $base);
        $encoded = $characters[gmp_intval($remainder)] . $encoded;
        $data = gmp_div_q($data, $base);
    }

    return $encoded;
}

function base62_decode($data)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $base = strlen($characters);
    $decoded = gmp_init(0);

    for ($i = 0, $len = strlen($data); $i < $len; $i++) {
        $decoded = gmp_add(gmp_mul($decoded, $base), strpos($characters, $data[$i]));
    }

    $decoded = gmp_strval($decoded, 16);
    return pack('H*', str_pad($decoded, ceil(strlen($decoded) / 2) * 2, '0', STR_PAD_LEFT));
}


function validatePassword($password)
{
    $errors = [];

    // Check if the password has at least 8 characters
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    // Check if the password contains at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }

    // Check if the password contains at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }

    // Check if the password contains at least one symbol
    if (!preg_match('/[\W_]/', $password)) {
        $errors[] = "Password must contain at least one symbol (e.g., !@#$%^&*).";
    }

    // Return errors if any, otherwise return true
    return empty($errors) ? true : $errors;
}



//5. Validate Phone Number 
function isPhoneNumber($phone)
{
    // Check if the phone number starts with 6-9 and is exactly 10 digits long
    return preg_match('/^[6-9]\d{9}$/', $phone) ? true : "Please enter a valid Mobile number.";
}


// 6. Is Not Null
function isNotNull($value)
{
    if ($value == null) {
        return false;
    } else {
        return true;
    }
}

// 7. sanitize input
function sanitizeInput(mixed $value, string $type = 'string'): mixed
{
    if (is_array($value)) {
        return array_map(fn($item) => sanitizeInput($item, $type), $value);
    }

    // Attempt to convert the value to the specified type
    switch ($type) {
        case 'int':
            $value = (int)$value;
            break;
        case 'float':
            $value = (float)$value;
            break;
        case 'bool':
        case 'boolean':
            // Handle boolean casting with explicit checks for known true/false values
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value === null) {
                $value = false; // Default to false if not a valid boolean representation
            }
            break;
        case 'email':
        case 'url':
        case 'string':
            $value = (string)$value;
            break;
        default:
            throw new InvalidArgumentException("Invalid type specified: $type");
    }

    // Trim leading/trailing whitespace if it's a string type
    if (is_string($value)) {
        $value = trim($value);
    }

    // Sanitize based on input type
    switch ($type) {
        case 'string':
            $value = htmlspecialchars($value, ENT_QUOTES);
            break;
        case 'int':
            $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            break;
        case 'float':
            $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            break;
        case 'email':
            $value = filter_var($value, FILTER_SANITIZE_EMAIL);
            break;
        case 'url':
            $value = filter_var($value, FILTER_SANITIZE_URL);
            break;
        case 'bool':
        case 'boolean':
            // No additional sanitization needed for boolean after validation above
            break;
    }

    return $value;
}



// 8.capitalize first letter after all whitespaces
function capitalizeFirstLetter($value)
{
    return ucwords(strtolower($value));
}

//9.remove whitespaces at start and end
function removeWhitespaces($value)
{
    return trim($value);
}

//10. get current date 
function getCurrentDate()
{
    return date(DATE_FORMAT);
}

//11. get current time in 12 hrs format
function getCurrentTime()
{
    return date(TIME_FORMAT);
}

//12. get current date and time
function getCurrentDateTime()
{
    return date(DATETIME_FORMAT);
}

//13.Show Time stamp.  Eg.: few seconds ago
function showTimeStamp(int|string $timeStamp): string
{
    $timeStamp = strtotime($timeStamp);
    $timeElapsed = time() - $timeStamp;  // Time elapsed since the timestamp

    $timeUnits = [
        // Units in descending order of magnitude
        'year'   => 31536000,
        'month'  => 2592000,
        'week'   => 604800,
        'day'    => 86400,
        'hour'   => 3600,
        'minute' => 60,
        'second' => 1,
    ];

    foreach ($timeUnits as $unit => $secondsInUnit) {
        if ($timeElapsed >= $secondsInUnit) {
            $numberOfUnits = floor($timeElapsed / $secondsInUnit);
            return "$numberOfUnits {$unit}" . ($numberOfUnits > 1 ? 's' : '') . ' ago';
        }
    }

    // If no matching unit found, fall back to seconds
    return "a few seconds ago";
}

function convertNumberToWords($number)
{
    $dictionary = [
        0 => 'zero',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
        20 => 'twenty',
        30 => 'thirty',
        40 => 'forty',
        50 => 'fifty',
        60 => 'sixty',
        70 => 'seventy',
        80 => 'eighty',
        90 => 'ninety',
        100 => 'hundred',
        100000 => 'lakh',
        10000000 => 'crore'
    ];

    if ($number < 0) {
        return 'minus ' . convertNumberToWords(-$number);
    }

    if ($number === 0) {
        return $dictionary[0];
    }

    // Split the number into integer and decimal parts
    $numberParts = explode('.', number_format($number, 2, '.', ''));
    $integerPart = (int) $numberParts[0];
    $fractionPart = isset($numberParts[1]) ? (int) $numberParts[1] : 0;

    $words = [];

    // Convert integer part to words
    $words[] = convertIntegerToWords($integerPart, $dictionary);

    // Add "and" for decimal part
    if ($fractionPart > 0) {
        $words[] = 'and';
        $words[] = convertIntegerToWords($fractionPart, $dictionary) . ' paise';
    }

    return implode(' ', $words);
}

function convertIntegerToWords($number, $dictionary)
{
    if ($number === 0) {
        return $dictionary[0];
    }

    $words = [];

    // Handle crores
    if ($number >= 10000000) {
        $crore = intdiv($number, 10000000);
        $words[] = convertIntegerToWords($crore, $dictionary) . ' crore';
        $number %= 10000000;
        if ($number > 0) {
            $words[] = 'and'; // Add 'and' if there is remaining amount
        }
    }

    // Handle lakhs
    if ($number >= 100000) {
        $lakh = intdiv($number, 100000);
        $words[] = convertIntegerToWords($lakh, $dictionary) . ' lakh';
        $number %= 100000;
        if ($number > 0) {
            $words[] = 'and'; // Add 'and' if there is remaining amount
        }
    }

    // Handle thousands
    if ($number >= 1000) {
        $thousand = intdiv($number, 1000);
        $words[] = convertIntegerToWords($thousand, $dictionary) . ' thousand';
        $number %= 1000;
        if ($number > 0) {
            $words[] = 'and'; // Add 'and' if there is remaining amount
        }
    }

    // Handle hundreds
    if ($number >= 100) {
        $hundred = intdiv($number, 100);
        $words[] = convertIntegerToWords($hundred, $dictionary) . ' hundred';
        $number %= 100;
        if ($number > 0) {
            $words[] = 'and'; // Add 'and' if there is remaining amount
        }
    }

    // Handle tens and units
    if ($number > 0) {
        if ($number < 20) {
            $words[] = $dictionary[$number];
        } else {
            $tens = intdiv($number, 10) * 10;
            $units = $number % 10;
            $words[] = $dictionary[$tens];
            if ($units > 0) {
                $words[] = $dictionary[$units];
            }
        }
    }

    return implode(' ', $words);
}




// Function to format numbers in Indian style
function formatNumberIndian($number)
{
    // Convert number to string with two decimal places
    $number = number_format($number, 2, '.', '');
    $parts = explode('.', $number);
    $integerPart = $parts[0];
    $decimalPart = isset($parts[1]) ? $parts[1] : '00';

    // Apply Indian formatting
    $integerPartLength = strlen($integerPart);

    // Handle cases where the integer part is less than 1000
    if ($integerPartLength <= 3) {
        $formattedIntegerPart = $integerPart;
    } else {
        // Separate out the last three digits
        $lastThreeDigits = substr($integerPart, -3);
        $remainingDigits = substr($integerPart, 0, -3);

        // Format the remaining digits
        $remainingDigits = strrev($remainingDigits);
        $formattedRemainingDigits = preg_replace('/(\d{2})(?=\d)/', '$1,', $remainingDigits);
        $formattedRemainingDigits = strrev($formattedRemainingDigits);

        // Combine parts
        $formattedIntegerPart = $formattedRemainingDigits . ',' . $lastThreeDigits;
    }

    return $formattedIntegerPart . '.' . $decimalPart;
}

function convertUnitAmount($amount, $fromUnit, $toUnit)
{
    $conversionRates = [
        'piece' => 1,
        'tonne' => 1000000, // grams
        'packets' => 1,
        'kg' => 1000, // grams
        'g' => 1,
        'lb' => 453.592, // grams
        'oz' => 28.3495, // grams
        'l' => 1000, // milliliters
        'ml' => 1,
        'm' => 100, // centimeters
        'cm' => 1,
        'mm' => 0.1, // centimeters
        'ft' => 30.48, // centimeters
        'in' => 2.54 // centimeters
    ];

    if (!isset($conversionRates[$fromUnit]) || !isset($conversionRates[$toUnit])) {
        throw new Exception("Invalid unit provided");
    }

    $amountInBaseUnit = $amount / $conversionRates[$fromUnit];
    $convertedAmount = $amountInBaseUnit * $conversionRates[$toUnit];

    return $convertedAmount;
}

function convertUnitQuantity($quantity, $fromUnit, $toUnit)
{
    $conversionRates = [
        'piece' => 1,
        'tonne' => 1000000, // grams
        'packets' => 1,
        'kg' => 1000, // grams
        'g' => 1, // grams
        'lb' => 453.592, // grams
        'oz' => 28.3495, // grams
        'l' => 1000, // milliliters
        'ml' => 1, // milliliters
        'm' => 100, // centimeters
        'cm' => 1, // centimeters
        'mm' => 0.1, // centimeters
        'ft' => 30.48, // centimeters
        'in' => 2.54 // centimeters
    ];

    // Validate units
    if (!isset($conversionRates[$fromUnit]) || !isset($conversionRates[$toUnit])) {
        throw new Exception("Invalid unit provided");
    }

    // Convert the quantity to the base unit
    $quantityInBaseUnit = $quantity * $conversionRates[$fromUnit];

    // Convert from the base unit to the target unit
    $convertedQuantity = $quantityInBaseUnit / $conversionRates[$toUnit];

    return $convertedQuantity;
}

function calculateAge($dob)
{
    // Convert the date of birth to a DateTime object
    $birthDate = new DateTime($dob);
    // Get today's date
    $today = new DateTime();
    // Calculate the difference between today and the date of birth
    $age = $today->diff($birthDate)->y;
    return $age;
}


//15. get rounded value
function getRoundedValue($value)
{
    return round($value, 2);
}

function getNumberSuffix($number)
{
    $last_digit = $number % 10;
    $last_two_digits = $number % 100;

    if ($last_two_digits >= 11 && $last_two_digits <= 13) {
        return 'th';
    }

    switch ($last_digit) {
        case 1:
            return 'st';
        case 2:
            return 'nd';
        case 3:
            return 'rd';
        default:
            return 'th';
    }
}
