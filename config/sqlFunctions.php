<?php
require_once 'db.php';
function callProcedure($procedureName, $inputParams = [])
{
    global $pdo; // Assuming $pdo is your PDO connection object

    // Build the parameter placeholders for the prepared statement
    $placeholders = array_fill(0, count($inputParams), '?');
    $sql = "CALL $procedureName(" . implode(', ', $placeholders) . ")";

    try {
        // Prepare the statement
        $stmt = $pdo->prepare($sql);

        // Bind input parameters
        if (!empty($inputParams)) {
            $values = [];
            foreach ($inputParams as $param) {
                $values[] = $param['value'];
            }
            // Execute the statement with bound parameters
            $stmt->execute($values);
        } else {
            // Execute the statement without parameters
            $stmt->execute();
        }

        // Fetch all results
        $procedureResult = [];
        $particulars = [];
        do {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                // Check for status_code and status in the second result set
                if (isset($result[0]['status_code']) && isset($result[0]['message'])) {
                    $particulars = $result;
                    if (isset($result[0]['total_count']) && isset($result[0]['filtered_count'])) {
                        $procedureResult['total_count'] = $result[0]['total_count'];
                        $procedureResult['filtered_count'] = $result[0]['filtered_count'];
                    }
                } else {
                    // Collect user data
                    $procedureResult[] = $result;
                }
            }
        } while ($stmt->nextRowset());

        return [
            'particulars' => $particulars,
            'data' => $procedureResult
        ];
    } catch (PDOException $e) {
        // Handle any errors gracefully
        return [
            'status_code' => 500,
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
}




// Create a record (Create)
function createRecord(string $tableName, array $columns, array $values, array $check_db = []): array
{
    global $conn;

    $result = [];

    if (count($columns) !== count($values)) {
        $result['errors'] = 'Number of columns and values must be equal.';
        return $result;
    }

    // Validate data types (optional, adjust based on your needs)
    foreach ($columns as $index => $column) {
        $value = $values[$index];
        if (is_numeric($column) && !is_numeric($value)) {
            $result['errors'] = "Value for column '$column' must be numeric.";
            return $result;
        }
    }

    if (!empty($check_db)) {
        // Check for existing values
        $placeholders = array_fill(0, count($check_db), '?');
        $checkQuery = "SELECT 1 FROM $tableName WHERE " . implode(' = ? AND ', $check_db) . ' = ?';
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param(str_repeat('s', count($placeholders)), ...array_merge($values, $values));
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $result = []; // Clear previous result for consistency
            $result['errors'] = "Record already exists with the given values.";
            return $result;
        }
    }

    // Generate type specifiers based on column data types
    $typeSpecifiers = "";
    foreach ($columns as $column) {
        if (is_numeric($column)) {
            $typeSpecifiers .= 'i'; // Integer
        } else if (preg_match('/^date$/i', $column)) {
            // Assuming date format is YYYY-MM-DD (adjust if needed)
            $values[$index] = date('Y-m-d', strtotime($values[$index])); // Convert to desired format
            $typeSpecifiers .= 's'; // String for date
        } else {
            $typeSpecifiers .= 's'; // String for other data types
        }
    }

    // Prepared statement for insertion
    $placeholders = array_fill(0, count($columns), '?');
    $sql = "INSERT INTO $tableName (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($typeSpecifiers, ...$values);
        $stmt->execute();

        $result['success'] = true;
        $result['response'] = "Record created successfully.";
        return $result;
    } catch (mysqli_sql_exception $e) {
        $result['success'] = false;
        $result['response'] = "Error creating record: " . $e->getMessage();
        return $result;
    } finally {
        // Close prepared statement (if not automatically closed)
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}


// Read records (Read) with optional WHERE and ORDER BY clauses
function getRecords(string $tableName, array $columns = ["*"], array $whereConditions = [], string $orderBy = ""): array
{
    global $conn;

    $result = []; // Initialize with an empty array

    try {
        // Build column string efficiently (avoiding unnecessary checks)
        $columnString = implode(', ', $columns);

        // Construct WHERE clause with prepared statements (if conditions exist)
        $whereClause = "";
        $placeholders = [];
        $values = [];
        if (!empty($whereConditions)) {
            $whereClause = " WHERE " . implode(' AND ', array_fill(0, count($whereConditions), '?'));
            foreach ($whereConditions as $condition) {
                $placeholders[] = '&' . $condition['operator']; // Use placeholders for operators
                $values[] = $condition['value'];
            }
        }

        // Construct complete SQL statement
        $sql = "SELECT $columnString FROM $tableName" . $whereClause . (empty($orderBy) ? " ORDER BY id ASC" : " ORDER BY $orderBy");

        // Prepare and execute the statement
        $stmt = $conn->prepare($sql);
        if (!empty($values)) {
            $stmt->bind_param(str_repeat('', count($placeholders)), ...$values); // Efficient binding
        }
        $stmt->execute();

        // Fetch results efficiently using mysqli_stmt_get_result
        $result = $stmt->get_result();

        // Handle success and failure cases consistently:
        if ($result->num_rows > 0) {
            $records = [];
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
            $result = ['success' => true, 'response' => $records];
        } else {
            $result = ['success' => false, 'response' => "0 results"];
        }

        return $result;
    } catch (mysqli_sql_exception $e) {
        $result = ['success' => false, 'response' => $e->getMessage()];
        return $result;
    } finally {
        // Close prepared statement (if not automatically closed)
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}


// Update a record (Update)
function updateRecord(string $tableName, array $setColumns, array $setValues, array $whereConditions): array
{
    global $conn;

    $result = [];

    try {
        // Construct SET clause with placeholders and data type handling
        $setPairs = [];
        $typeSpecifiers = ""; // Initialize type specifiers for binding

        foreach ($setColumns as $index => $column) {
            $value = $setValues[$index];

            // Determine data type and convert value if necessary
            if (is_numeric($column)) {
                $type = 'i'; // Integer
            } else if (preg_match('/^date$/i', $column)) {
                $type = 's'; // String for date (assuming YYYY-MM-DD format)
                $value = date('Y-m-d', strtotime($value));
            } else if (is_bool($value)) {
                $type = 'i'; // Boolean (store as 0/1)
                $value = $value ? 1 : 0;
            } else {
                $type = 's'; // String (default for other data types)
            }

            $setPairs[] = "$column = ?"; // Use placeholders for values
            $typeSpecifiers .= $type;
            $values[] = $value; // Add value to bind parameters
        }
        $setString = implode(", ", $setPairs);

        // Construct WHERE clause with placeholders
        $placeholders = [];
        foreach ($whereConditions as $condition) {
            $placeholders[] = '&' . $condition['operator']; // Use placeholders for operators
            $values[] = $condition['value'];
        }
        $whereString = implode(" AND ", array_fill(0, count($whereConditions), '?'));

        // Construct complete SQL statement
        $sql = "UPDATE $tableName SET $setString WHERE $whereString";

        // Prepare statement with all placeholders
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($typeSpecifiers, ...$values); // Efficient binding with type specifiers

        // Execute the statement
        $stmt->execute();

        // Check for successful update
        if ($stmt->affected_rows > 0) {
            $result['success'] = true;
            $result['response'] = "Record updated successfully.";
        } else {
            $result['success'] = false;
            // Consider more specific message based on affected_rows
            $result['response'] = "No records updated.";
        }

        return $result;
    } catch (mysqli_sql_exception $e) {
        $result['success'] = false;
        $result['response'] = "Error updating record: " . $e->getMessage();
        return $result;
    } finally {
        // Close prepared statement (if not automatically closed)
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

// Delete records (Delete) with optional WHERE clause
function deleteRecords(string $tableName, array $whereConditions = []): array
{
    global $conn;

    $result = [];

    try {
        // Construct WHERE clause with placeholders (if conditions exist)
        $placeholders = [];
        $values = [];
        $whereString = "";
        if (!empty($whereConditions)) {
            $whereString = " WHERE " . implode(' AND ', array_fill(0, count($whereConditions), '?'));
            foreach ($whereConditions as $condition) {
                $placeholders[] = '&' . $condition['operator']; // Use placeholders for operators
                $values[] = $condition['value'];
            }
        }

        // Construct complete SQL statement
        $sql = "DELETE FROM $tableName" . $whereString;

        // Prepare and execute the statement with placeholders
        $stmt = $conn->prepare($sql);
        if (!empty($values)) {
            $stmt->bind_param(str_repeat(strval(gettype($values[0])), count($whereConditions)), ...$values);
        }
        $stmt->execute();

        $result['success'] = $stmt->affected_rows > 0; // Set success based on affected rows
        if ($result['success']) {
            $result['response'] = "Records deleted successfully.";
        } else {
            $result['response'] = "No records deleted."; // Consider more specific message
        }

        return $result;
    } catch (mysqli_sql_exception $e) {
        $result['success'] = false;
        $result['response'] = "Error deleting records: " . $e->getMessage();
        return $result;
    } finally {
        // Close prepared statement (if not automatically closed)
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}


//for all sql queries for join
function sqlAction($query): array
{
    global $conn;

    $result = [];

    try {
        // Prepare the statement for security and parameter binding
        $stmt = $conn->prepare($query);

        // Assuming you'll bind values for any placeholders in the query
        // ... (bind values here using $stmt->bind_param())

        // Execute the prepared statement
        $stmt->execute();

        $result['success'] = $stmt->affected_rows >= 0; // Success if affected rows >= 0 or result set exists

        if ($result['success']) {
            if (stripos($query, 'SELECT') === 0) {
                // For SELECT queries, fetch results
                $result['response'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else {
                // For other queries, provide a success message
                $result['response'] = "Operation successful.";
            }
        } else {
            $result['response'] = "No records affected."; // Consider more specific message if needed
        }
    } catch (mysqli_sql_exception $e) {
        $result['success'] = false;
        $result['response'] = "Error: " . $e->getMessage();
    } finally {
        // Close prepared statement (if not automatically closed)
        if (isset($stmt)) {
            $stmt->close();
        }
    }

    return $result;
}

// Example usage:
// Uncomment and modify the following lines as needed

// $result = createRecord("your_table_name", ["name", "email"], ["John Doe", "john@example.com"]);
// echo $result === true ? "Record created successfully" : $result;

// $result = getRecords("your_table_name", ["id", "name", "email"], ["name = 'John Doe'", "id > 5"], "id DESC");
// print_r($result);

// $result = updateRecord("your_table_name", ["name", "email"], ["Updated Name", "updated@example.com"], ["id = 1"]);
// echo $result === true ? "Record updated successfully" : $result;

// $result = deleteRecords("your_table_name", ["id = 2"]);
// echo $result === true ? "Records deleted successfully" : $result;

/*      // Example usage with a JOIN query
            $query = "SELECT users.username, orders.order_id, orders.order_date
                    FROM users
                    INNER JOIN orders ON users.user_id = orders.user_id";

            $result = sqlAction($query);

            // Display the result
            if (isset($result['error'])) {
                echo "Error: " . $result['error'];
            } else {
                print_r($result);
            }

    $inputParams = [
        ['value' => 1, 'type' => PDO::PARAM_INT], // Integer
        ['value' => 'John Doe', 'type' => PDO::PARAM_STR], // String
        ['value' => 123.45, 'type' => PDO::PARAM_STR], // Float treated as string in most databases
        ['value' => true, 'type' => PDO::PARAM_BOOL], // Boolean
        ['value' => null, 'type' => PDO::PARAM_NULL] // Null value
    ];

*/
// Close the connection
//$conn->close();
