<?php
require_once('db.php');
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title>Account Roles - M&A Oida Dental Clinic</title>

    <?php require_once 'head.php' ?>

</head>

<body class="bg-white text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require_once 'nav.php' ?>
        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <?php require_once 'header.php' ?>
            <!-- Breadcrumb -->
            <nav class="flex items-center space-x-2 px-6 py-3 bg-gray-50 border-b border-gray-200">
                <ol class="flex items-center space-x-2">
                    <li>
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <li>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </li>
                    <li>
                        <span class="text-gray-600">Account Roles</span>
                    </li>
                </ol>
            </nav>

            <!-- Content area -->
            <div class="flex-1 overflow-y-auto p-4">
                <div class="w-full max-w-6xl mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-[#0B2E61] text-xl font-semibold">Account Roles</h1>
                        <a href="newrole.php"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Add Account</a>

                    </div>
                    <table id="example" class="display">
                        <thead>
                            <tr>
                                <th>Profile</th>
                                <th>Email</th>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Age</th>
                                <th>Mobile</th>
                                <th>Gender</th>
                                <th>Created At</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            $sql = "SELECT profile_photo, email, admin_id, first_name, last_name, age, mobile, gender, created_at, type FROM admin_logins";
                            $result = $conn->query($sql);

                            while ($row = $result->fetch_assoc()):

                                $photo = !empty($row['profile_photo']) ? $row['profile_photo'] : 'assets/photo/default_avatar.png';

                                ?>
                                <tr>
                                    <td><img src="<?= htmlspecialchars($photo) ?>" style="width:100%">
                                    </td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['admin_id']) ?></td>
                                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                                    <td><?= htmlspecialchars($row['age']) ?></td>
                                    <td><?= htmlspecialchars($row['mobile']) ?></td>
                                    <td><?= htmlspecialchars($row['gender']) ?></td>
                                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($row['type'])) ?></td>
                                </tr>
                            <?php endwhile;
                            $conn->close(); ?>
                        </tbody>
                    </table>


                </div>
            </div>
        </main>
    </div>

    <script>
        $(document).ready(function () {
            $('#example').DataTable();
        });
    </script>

</body>

</html>