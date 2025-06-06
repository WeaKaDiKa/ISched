<?php
require_once('session.php');
require 'db.php';
require_once('includes/profile_functions.php');

// Helper function to capitalize names
function capitalizeNames($name) {
    $parts = explode(' ', trim($name));
    $parts = array_map(function($part) {
        return ucfirst(strtolower($part));
    }, $parts);
    return implode(' ', $parts);
}

$user_id = $_SESSION['user_id'];

// Fetch existing user data
$sql = "
    SELECT 
        first_name, middle_name, last_name, email,
        phone_number, date_of_birth, gender, 
        region, province, city, barangay, zip_code,
        profile_picture
    FROM patients  
    WHERE id = ?
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
$selected_region_id = $user['region'] ?? array_key_first($regions); // Use user's region or first region if none
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
$selected_province_id = $user['province'] ?? null; // Use user's province or null if none
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
$selected_city_id = $user['city'] ?? null; // Use user's city or null if none
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
    // Sanitize and capitalize names
    $first_name = capitalizeNames(filter_var(trim($_POST["first_name"])), FILTER_SANITIZE_STRING);
    $middle_name = !empty($_POST["middle_name"]) ? capitalizeNames(filter_var(trim($_POST["middle_name"])), FILTER_SANITIZE_STRING) : '';
    $last_name = capitalizeNames(filter_var(trim($_POST["last_name"])), FILTER_SANITIZE_STRING);

    // Handle profile picture upload using our new function
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $result = upload_profile_image($user_id, $_FILES['profile_picture']);
        if (!$result['success']) {
            $error = $result['message'];
        }
    }

    // Date handling
    try {
        $date_of_birth = DateTime::createFromFormat('d-m-Y', $_POST['date_of_birth']);
        $mysql_date = $date_of_birth->format('Y-m-d');
    } catch (Exception $e) {
        $error = "Invalid date format. Use DD-MM-YYYY";
    }

    if (!$error) {
        $update_sql = "UPDATE patients SET 
            first_name = ?, middle_name = ?, last_name = ?,
            email = ?, phone_number = ?, date_of_birth = ?,
            gender = ?, region = ?, province = ?, city = ?,
            barangay = ?, zip_code = ?
            WHERE id = ?";

        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssssssssssi",
            $first_name,
            $middle_name,
            $last_name,
            $_POST['email'],
            $_POST['phone_number'],
            $mysql_date,
            $_POST['gender'],
            $_POST['region'], // Save region ID
            $_POST['province'], // Save province ID
            $_POST['city'], // Save city ID
            $_POST['barangay'], // Save barangay ID
            $_POST['zip_code'],
            $user_id
        );

        if ($stmt->execute()) {
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
    <link rel="stylesheet" href="assets/css/profile-icon.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="profile-container">
        <form method="POST" enctype="multipart/form-data">
            <div class="header">
                <a href="profile.php" class="back-btn">Back</a>
                <h1 class="profile-title">Edit Profile</h1>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="profile-content">
                <div class="profile-pic-section">
                    <div class="profile-pic-container">
                        <img id="profilePreview" src="<?php echo get_profile_image_url($user_id); ?>" 
                            alt="Profile Picture" class="profile-pic">
                    </div>
                    <div class="form-group">
                        <label>Update Profile Picture:</label>
                        <input id="profileInput" type="file" name="profile_picture" accept="image/*">
                    </div>
                </div>

                <div class="profile-info">
                    <div class="info-column">
                        <div class="form-group">
                            <label for="firstName">First Name:</label>
                            <input type="text" id="firstName" name="first_name" 
                                value="<?php echo htmlspecialchars($user['first_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="middleName">Middle Name:</label>
                            <input type="text" id="middleName" name="middle_name" 
                                value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name:</label>
                            <input type="text" id="lastName" name="last_name" 
                                value="<?php echo htmlspecialchars($user['last_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address:</label>
                            <input type="email" id="email" name="email" 
                                value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number:</label>
                            <input type="text" id="phone" name="phone_number" 
                                value="<?php echo htmlspecialchars($user['phone_number']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth:</label>
                            <div class="date-input-container">
                                <input type="text" id="dob" name="date_of_birth" 
                                    value="<?php echo htmlspecialchars($formatted_dob); ?>">
                                <span class="date-icon">📅</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender:</label>
                            <select id="gender" name="gender" class="styled-select">
                                <option value="Male" <?= $user['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $user['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $user['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="info-column">
                        <div class="form-group">
                            <label for="region">Region:</label>
                            <select id="region" name="region" class="styled-select">
                                <?php foreach ($regions as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($user['region'] ?? '') === $value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="province">Province:</label>
                            <select id="province" name="province" class="styled-select">
                                <?php foreach ($provinces as $province): ?>
                                    <option value="<?= htmlspecialchars($province['province_id']) ?>" <?= ($user['province'] ?? '') === $province['province_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($province['province_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="city">City/Municipality:</label>
                            <select id="city" name="city" class="styled-select">
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= htmlspecialchars($city['municipality_id']) ?>" <?= ($user['city'] ?? '') === $city['municipality_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($city['municipality_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="barangay">Barangay:</label>
                            <select id="barangay" name="barangay" class="styled-select">
                                <?php foreach ($barangays as $barangay): ?>
                                    <option value="<?= htmlspecialchars($barangay['brgy_id']) ?>" <?= ($user['barangay'] ?? '') === $barangay['brgy_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($barangay['barangay_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="zipCode">ZipCode:</label>
                            <input type="text" id="zipCode" name="zip_code" 
                                value="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <p class="validation-note">*Always double check your new information before saving*</p>

                <div class="action-buttons">
                    <a href="profile.php" class="cancel-btn">Cancel</a>
                    <button type="submit" class="save-btn">Save Changes</button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Date picker initialization
        flatpickr("#dob", {
            dateFormat: "d-m-Y",
            allowInput: true
        });

        // Region-Province dynamic update
        document.getElementById('region').addEventListener('change', function() {
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');

            provinceSelect.innerHTML = '';
            citySelect.innerHTML = ''; // Clear city and barangay when region changes
            barangaySelect.innerHTML = '';

            const selectedRegion = this.value;
            if (!selectedRegion) return; // Don't fetch if no region is selected

            fetch('fetch_provinces.php?region_id=' + selectedRegion)
                .then(response => response.json())
                .then(data => {
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
        document.getElementById('province').addEventListener('change', function() {
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');

            citySelect.innerHTML = '';
            barangaySelect.innerHTML = ''; // Clear barangay when province changes

            const selectedProvince = this.value;
             if (!selectedProvince) return; // Don't fetch if no province is selected

            fetch('fetch_cities.php?province_id=' + selectedProvince)
                .then(response => response.json())
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
        document.getElementById('city').addEventListener('change', function() {
            const barangaySelect = document.getElementById('barangay');
            barangaySelect.innerHTML = '';

            const selectedCity = this.value;
             if (!selectedCity) return; // Don't fetch if no city is selected

            fetch('fetch_barangays.php?city_id=' + selectedCity)
                .then(response => response.json())
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

        // Trigger the region change event on page load to populate provinces, cities, and barangays
        document.addEventListener('DOMContentLoaded', function() {
            const regionSelect = document.getElementById('region');
            // Only trigger if a region is already selected (i.e., user has saved location data)
            if (regionSelect.value) {
                // Store the current values before triggering change events
                const currentProvince = '<?= $user['province'] ?? '' ?>';
                const currentCity = '<?= $user['city'] ?? '' ?>';
                const currentBarangay = '<?= $user['barangay'] ?? '' ?>';

                regionSelect.dispatchEvent(new Event('change'));

                // After provinces load, select the user's province and trigger city load
                document.getElementById('province').addEventListener('change', function() {
                     // Select the user's saved city after cities load
                    document.getElementById('city').addEventListener('change', function() {
                        if (currentBarangay) {
                           document.getElementById('barangay').value = currentBarangay;
                        }
                    }, { once: true }); // Run only once

                    if (currentCity) {
                        document.getElementById('city').value = currentCity;
                         document.getElementById('city').dispatchEvent(new Event('change'));
                    }
                }, { once: true }); // Run only once

                 if (currentProvince) {
                    document.getElementById('province').value = currentProvince;
                    document.getElementById('province').dispatchEvent(new Event('change'));
                }

            }
        });

        // Image preview functionality
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('profilePreview');
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        // Add the onchange event to the file input
        document.getElementById('profileInput').addEventListener('change', previewImage);
    </script>
</body>
</html>