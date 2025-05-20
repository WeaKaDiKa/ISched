<?php

require_once 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title>Staff Management - M&A Oida Dental Clinic</title>
    <?php require_once 'head.php' ?>
</head>

<body class="bg-white text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php include 'sidebar.php'; ?>
        <main class="flex-1 flex flex-col overflow-hidden">
            <?php include 'topbar.php'; ?>
            <div class="flex-1 flex flex-col items-center justify-center bg-gray-100 w-full min-h-0">
                <section
                    class="w-full max-w-5xl mx-auto bg-white rounded-lg border border-gray-300 shadow-md p-8 mt-6 flex flex-col items-center justify-center">
                    <h1 class="text-2xl font-bold text-blue-900 mb-4">Staff Management</h1>
                    <p class="text-gray-700">This is the Staff Management page. Add your content here.</p>
                </section>
            </div>
        </main>
    </div>
</body>

</html>