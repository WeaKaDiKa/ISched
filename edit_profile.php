<?php
require_once('session.php');
require 'db.php';
require_once('includes/profile_functions.php');

// Helper function to capitalize names
function capitalizeNames($name)
{
    $parts = explode(' ', trim($name));
    $parts = array_map(function ($part) {
        return ucfirst(strtolower($part));
    }, $parts);
    return implode(' ', $parts);
}

$user_id = $_SESSION['user_id'];

// Fetch existing user data
$sql = "SELECT 
        p.first_name, p.middle_name, p.last_name, p.email,
        p.phone_number, p.date_of_birth, p.gender, 
        p.region as region_id, p.province as province_id, p.city as city_id, p.barangay as barangay_id, p.zip_code,
        p.profile_picture,
        COALESCE(reg.region_description, 'Unknown Region') as region_name,
        COALESCE(prov.province_name, 'Unknown Province') as province_name,
        COALESCE(city.municipality_name, 'Unknown City') as city_name,
        COALESCE(brgy.barangay_name, 'Unknown Barangay') as barangay_name
    FROM patients p
    LEFT JOIN refregion reg ON p.region = reg.region_id
    LEFT JOIN refprovince prov ON p.province = prov.province_id
    LEFT JOIN refcity city ON p.city = city.municipality_id
    LEFT JOIN refbrgy brgy ON p.barangay = brgy.brgy_id
    WHERE p.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Region Data: Fetch from database
$regions = [];
$sql_regions = "SELECT region_id, region_description FROM refregion ORDER BY region_description";
$result_regions = $conn->query($sql_regions);
if ($result_regions) {
    while ($row_regions = $result_regions->fetch_assoc()) {
        $regions[$row_regions['region_id']] = $row_regions['region_description'];
    }
}

// Province Data: Fetch based on user's current region or a default if none is set
$provinces = [];
$selected_region_id = $user['region_id'] ?? array_key_first($regions); // Use user's region or first region if none
if ($selected_region_id) {
    $sql_provinces = "SELECT province_id, province_name FROM refprovince WHERE region_id = ? ORDER BY province_name";
    $stmt_provinces = $conn->prepare($sql_provinces);
    if ($stmt_provinces) {
        $stmt_provinces->bind_param("s", $selected_region_id);
        $stmt_provinces->execute();
        $result_provinces = $stmt_provinces->get_result();
        while ($row_provinces = $result_provinces->fetch_assoc()) {
            // Store both ID and name
            $provinces[] = ['province_id' => $row_provinces['province_id'], 'province_name' => $row_provinces['province_name']];
        }
        $stmt_provinces->close();
    }
}

// City Data: Fetch based on user's current province or a default if none is set
$cities = [];
$selected_province_id = $user['province_id'] ?? null; // Use user's province or null if none
if ($selected_province_id) {
    // Assuming refcity table has province_id column and municipality_id/municipality_name
    $sql_cities = "SELECT municipality_id, municipality_name FROM refcity WHERE province_id = ? ORDER BY municipality_name";
    $stmt_cities = $conn->prepare($sql_cities);
    if ($stmt_cities) {
        $stmt_cities->bind_param("s", $selected_province_id);
        $stmt_cities->execute();
        $result_cities = $stmt_cities->get_result();
        while ($row_cities = $result_cities->fetch_assoc()) {
            $cities[] = ['municipality_id' => $row_cities['municipality_id'], 'municipality_name' => $row_cities['municipality_name']];
        }
        $stmt_cities->close();
    }
}

// Barangay Data: Fetch based on user's current city or a default if none is set
$barangays = [];
$selected_city_id = $user['city_id'] ?? null; // Use user's city or null if none
if ($selected_city_id) {
    // Assuming refbrgy table has municipality_id column and brgy_id/barangay_name
    $sql_barangays = "SELECT brgy_id, barangay_name FROM refbrgy WHERE municipality_id = ? ORDER BY barangay_name";
    $stmt_barangays = $conn->prepare($sql_barangays);
    if ($stmt_barangays) {
        $stmt_barangays->bind_param("s", $selected_city_id);
        $stmt_barangays->execute();
        $result_barangays = $stmt_barangays->get_result();
        while ($row_barangays = $result_barangays->fetch_assoc()) {
            $barangays[] = ['brgy_id' => $row_barangays['brgy_id'], 'barangay_name' => $row_barangays['barangay_name']];
        }
        $stmt_barangays->close();
    }
}

// Handle form submission
$error = '';
$profile_picture = $user['profile_picture'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone_number = htmlspecialchars($_POST['phone_number'] ?? '');
    $region = filter_input(INPUT_POST, 'region', FILTER_VALIDATE_INT);
    $province = filter_input(INPUT_POST, 'province', FILTER_VALIDATE_INT);
    $city = filter_input(INPUT_POST, 'city', FILTER_VALIDATE_INT);
    $barangay = filter_input(INPUT_POST, 'barangay', FILTER_VALIDATE_INT);
    $zip_code = htmlspecialchars($_POST['zip_code'] ?? '');

    $date_of_birth_input = htmlspecialchars($_POST['date_of_birth'] ?? '');
    $date_of_birth = '';

    if (!empty($date_of_birth_input)) {
        $date_obj = DateTime::createFromFormat('d-m-Y', $date_of_birth_input);
        if ($date_obj) {
            $date_of_birth = $date_obj->format('Y-m-d');
        } else {
            $error = "Invalid date format. Please use DD-MM-YYYY format";
        }
    }
    $gender = htmlspecialchars($_POST['gender'] ?? '');

    // Basic validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (empty($phone_number)) {
        $error = "Phone number is required";
    }
    // Handle profile picture upload
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $file_type = $_FILES['profile_picture']['type'];

        if (array_key_exists($file_type, $allowed_types)) {
            $upload_dir = 'assets/images/profiles/';

            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Get file extension from our allowed types mapping
            $file_ext = $allowed_types[$file_type];
            $filename = 'user_' . $user_id . '.' . $file_ext;
            $destination = $upload_dir . $filename;

            // Delete old profile picture if exists (both jpg and png versions)
            $old_jpg = $upload_dir . 'user_' . $user_id . '.jpg';
            $old_png = $upload_dir . 'user_' . $user_id . '.png';

            if (file_exists($old_jpg))
                unlink($old_jpg);
            if (file_exists($old_png))
                unlink($old_png);

            // Move the uploaded file
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                $profile_picture = $filename;
            } else {
                $error = "Failed to upload profile picture";
            }
        } else {
            $error = "Only JPG and PNG images are allowed";
        }
    }
    if (!$error) {
        $update_sql = "UPDATE patients SET 
        email = ?, 
        phone_number = ?, 
        region = ?, 
        province = ?, 
        city = ?,
        barangay = ?, 
        zip_code = ?,
        date_of_birth = ?,
        gender = ?";

        if ($profile_picture) {
            $update_sql .= ", profile_picture = ?";
        }

        $update_sql .= " WHERE id = ?";

        $stmt = $conn->prepare($update_sql);

        if ($profile_picture) {
            $stmt->bind_param(
                "ssiiiissssi",
                $email,
                $phone_number,
                $region,
                $province,
                $city,
                $barangay,
                $zip_code,
                $date_of_birth,
                $gender,
                $profile_picture,
                $user_id
            );
        } else {
            $stmt->bind_param(
                "ssiiiisssi",
                $email,
                $phone_number,
                $region,
                $province,
                $city,
                $barangay,
                $zip_code,
                $date_of_birth,
                $gender,
                $user_id
            );
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully";
            header("Location: profile.php");
            exit();
        } else {
            $error = "Update failed: " . $conn->error;
        }
    }
}


$formatted_dob = date('d-m-Y', strtotime($user['date_of_birth']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="assets/css/profiles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <?php require_once 'includes/head.php' ?>
</head>

<body>
    <header>
        <?php include_once('includes/navbar.php'); ?>
    </header>
    <div class="container my-4">
        <div class="card">
            <div class="card-body p-5">
                <h1 class="profile-title mb-4">Edit Profile</h1>

                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?php if ($error): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="profile-content">
                        <div class="profile-pic-section d-flex flex-column flex-md-row align-items-center ms-0 mb-4">
                            <div class="profile-pic-container me-md-4 mb-3 mb-md-0 ms-auto ms-md-0">
                                <img id="profilePreview" src="<?php echo get_profile_image_url($user_id); ?>"
                                    alt="Profile Picture" class="rounded-circle"
                                    style="width: 150px; height: 150px; object-fit: cover;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Update Profile Picture:</label>
                                <input id="profileInput" type="file" name="profile_picture" accept="image/*"
                                    class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label">First Name:</label>
                                    <input type="text" id="firstName" name="first_name" class="form-control" disabled
                                        value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="middleName" class="form-label">Middle Name:</label>
                                    <input type="text" id="middleName" name="middle_name" class="form-control" disabled
                                        value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="lastName" class="form-label">Last Name:</label>
                                    <input type="text" id="lastName" name="last_name" class="form-control" disabled
                                        value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address:</label>
                                    <input type="email" id="email" name="email" class="form-control"
                                        value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number:</label>
                                    <input type="text" id="phone" name="phone_number" class="form-control"
                                        value="<?php echo htmlspecialchars($user['phone_number']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="dob" class="form-label">Date of Birth:</label>
                                    <div class="input-group">
                                        <input type="text" id="dob" name="date_of_birth" class="form-control"
                                            value="<?php echo htmlspecialchars($formatted_dob); ?>">
                                        <span class="input-group-text">📅</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="gender" class="form-label">Gender:</label>
                                    <select id="gender" name="gender" class="form-select">
                                        <option value="Male" <?= $user['gender'] === 'Male' ? 'selected' : '' ?>>Male
                                        </option>
                                        <option value="Female" <?= $user['gender'] === 'Female' ? 'selected' : '' ?>>Female
                                        </option>
                                        <option value="Other" <?= $user['gender'] === 'Other' ? 'selected' : '' ?>>Other
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="region" class="form-label">Region:</label>
                                    <select id="region" name="region" class="form-select">
                                        <?php foreach ($regions as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= ($user['region_id'] ?? '') === $value ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="province" class="form-label">Province:</label>
                                    <select id="province" name="province" class="form-select">
                                        <?php foreach ($provinces as $province): ?>
                                            <option value="<?= htmlspecialchars($province['province_id']) ?>"
                                                <?= ($user['province_id'] ?? '') === $province['province_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($province['province_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="city" class="form-label">City/Municipality:</label>
                                    <select id="city" name="city" class="form-select">

                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= htmlspecialchars($city['municipality_id']) ?>"
                                                <?= ($user['city_id'] ?? '') === $city['municipality_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($city['municipality_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="barangay" class="form-label">Barangay:</label>
                                    <select id="barangay" name="barangay" class="form-select">
                                        <?php foreach ($barangays as $barangay): ?>
                                            <option value="<?= htmlspecialchars($barangay['brgy_id']) ?>"
                                                <?= ($user['barangay_id'] ?? '') === $barangay['brgy_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($barangay['barangay_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="zipCode" class="form-label">ZipCode:</label>
                                    <input type="text" id="zipCode" name="zip_code" class="form-control"
                                        value="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <p class="text-muted mb-4"><small>*Always double check your new information before
                                saving*</small></p>

                        <div class="d-flex justify-content-end gap-3">
                            <a href="profile.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                </form>


            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Date picker initialization
        flatpickr("#dob", {
            dateFormat: "d-m-Y",
            allowInput: true
        });

        // Region-Province dynamic update
        document.getElementById('region').addEventListener('change', function () {
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');

            provinceSelect.innerHTML = '';
            citySelect.innerHTML = ''; // Clear city and barangay when region changes
            barangaySelect.innerHTML = '';

            const selectedRegion = this.value;
            if (!selectedRegion) return; // Don't fetch if no region is selected

            fetch('fetch_provinces.php?region_id=' + selectedRegion)
                .then(response => response.text()) // Change temporarily to .text()
                .then(text => {
                    console.log('Raw prov:', text); // Check what's coming back
                    return JSON.parse(text); // Then try to parse it manually
                })
                .then(data => {
                    console.log(data)
                    data.forEach(province => {
                        const option = document.createElement('option');
                        option.value = province.province_id;
                        option.textContent = province.province_name;
                        provinceSelect.appendChild(option);
                    });
                    // Trigger province change to load cities for the first province
                    if (provinceSelect.options.length > 0) {
                        provinceSelect.dispatchEvent(new Event('change'));
                    } else {
                        // If no provinces, trigger city change to clear city/barangay dropdowns
                        citySelect.dispatchEvent(new Event('change'));
                    }
                })
                .catch(error => console.error('Error fetching provinces:', error));
        });

        // Province-City dynamic update
        document.getElementById('province').addEventListener('change', function () {
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');

            citySelect.innerHTML = '';
            barangaySelect.innerHTML = ''; // Clear barangay when province changes

            const selectedProvince = this.value;
            if (!selectedProvince) return; // Don't fetch if no province is selected

            fetch('fetch_cities.php?province_id=' + selectedProvince)
                .then(response => response.text()) // Change temporarily to .text()
                .then(text => {
                    console.log('Raw city:', text); // Check what's coming back
                    return JSON.parse(text); // Then try to parse it manually
                })
                .then(data => {
                    data.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.municipality_id;
                        option.textContent = city.municipality_name;
                        citySelect.appendChild(option);
                    });
                    // Trigger city change to load barangays for the first city
                    if (citySelect.options.length > 0) {
                        citySelect.dispatchEvent(new Event('change'));
                    } else {
                        // If no cities, trigger barangay change to clear barangay dropdown
                        barangaySelect.dispatchEvent(new Event('change'));
                    }
                })
                .catch(error => console.error('Error fetching cities:', error));
        });

        // City-Barangay dynamic update
        document.getElementById('city').addEventListener('change', function () {
            const barangaySelect = document.getElementById('barangay');
            barangaySelect.innerHTML = '';

            const selectedCity = this.value;
            if (!selectedCity) return; // Don't fetch if no city is selected

            fetch('fetch_barangays.php?city_id=' + selectedCity)
                .then(response => response.text()) // Change temporarily to .text()
                .then(text => {
                    console.log('Raw brgy:', text); // Check what's coming back
                    return JSON.parse(text); // Then try to parse it manually
                })
                .then(data => {
                    data.forEach(barangay => {
                        const option = document.createElement('option');
                        option.value = barangay.brgy_id;
                        option.textContent = barangay.barangay_name;
                        barangaySelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching barangays:', error));
        });

        document.addEventListener('DOMContentLoaded', function () {
            const regionSelect = document.getElementById('region');
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');

            const currentProvince = '<?= $user['province_id'] ?? '' ?>';
            const currentCity = '<?= $user['city_id'] ?? '' ?>';
            const currentBarangay = '<?= $user['barangay_id'] ?? '' ?>';

            if (regionSelect.value) {
                fetch('fetch_provinces.php?region_id=' + regionSelect.value)
                    .then(response => response.json())

                    .then(provinces => {
                        provinceSelect.innerHTML = '';
                        provinces.forEach(province => {
                            const option = document.createElement('option');
                            option.value = province.province_id;
                            option.textContent = province.province_name;
                            provinceSelect.appendChild(option);
                        });

                        if (currentProvince) {
                            provinceSelect.value = currentProvince;
                        }

                        return fetch('fetch_cities.php?province_id=' + provinceSelect.value);
                    })
                    .then(response => response.json())
                    .then(cities => {
                        citySelect.innerHTML = '';
                        cities.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city.municipality_id;
                            option.textContent = city.municipality_name;
                            citySelect.appendChild(option);
                        });

                        if (currentCity) {
                            citySelect.value = currentCity;
                        }

                        return fetch('fetch_barangays.php?city_id=' + citySelect.value);
                    })
                    .then(response => response.json())
                    .then(barangays => {
                        barangaySelect.innerHTML = '';
                        barangays.forEach(barangay => {
                            const option = document.createElement('option');
                            option.value = barangay.brgy_id;
                            option.textContent = barangay.barangay_name;
                            barangaySelect.appendChild(option);
                        });

                        if (currentBarangay) {
                            barangaySelect.value = currentBarangay;
                        }
                    })
                    .catch(error => console.error('Error during cascading preselection:', error));
            }
        });


        // Image preview functionality
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const preview = document.getElementById('profilePreview');
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        // Add the onchange event to the file input
        document.getElementById('profileInput').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    document.getElementById('profilePreview').src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Remove the console.log for production
        console.log('<?= $user['region_id'] ?><?= $user['province_id'] ?><?= $user['city_id'] ?><?= $user['barangay_id'] ?>');


    </script>
</body>

</html>