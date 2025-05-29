<?php
// Database connection
require_once('db.php');

// Read the SQL file
$sql_file = file_get_contents('fix_services_table.sql');

// Split the SQL file into individual statements
$statements = explode(';', $sql_file);

// Execute each statement
$success = true;
$error_messages = [];

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        if (!$conn->query($statement)) {
            $success = false;
            $error_messages[] = "Error executing statement: " . $conn->error . " in statement: " . $statement;
        }
    }
}

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Services Table</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Fix Services Table</h1>
    
    <?php if ($success): ?>
        <div class="success">
            <h2>Success!</h2>
            <p>The services table has been successfully created and populated with data.</p>
        </div>
    <?php else: ?>
        <div class="error">
            <h2>Error</h2>
            <p>There were errors while executing the SQL statements:</p>
            <ul>
                <?php foreach ($error_messages as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <h2>SQL Executed:</h2>
    <pre><?php echo htmlspecialchars($sql_file); ?></pre>
    
    <a href="appointments.php" class="btn">Return to Appointments</a>
</body>
</html>
