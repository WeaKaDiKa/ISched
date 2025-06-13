<?php
require_once('session.php');
require_once('db.php');
require_once('includes/profile_functions.php');

// 1) INITIALIZE
$user = null;
$preselectService = '';
$preselectServices = [];

// 2) FETCH LOGGED-IN USER
if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("
      SELECT first_name, last_name, profile_picture 
        FROM patients 
       WHERE id = ?
    ");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc() ?: null;
}

// POST: insert review
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');
  $d = json_decode(file_get_contents('php://input'), true);
  if (isset($d['rating'], $d['text'], $d['services'])) {
    $pid = $_SESSION['user_id'] ?? null;
    $name = !empty($d['anon'])
      ? 'Anonymous'
      : htmlspecialchars(trim($d['name'] ?? "{$user['first_name']} {$user['last_name']}"));
    $rating = (int) $d['rating'];
    $text = htmlspecialchars(trim($d['text']));
    $services = json_encode($d['services']);
    $ins = $conn->prepare("
      INSERT INTO reviews
        (patient_id, name, rating, text, services, date)
      VALUES (?,?,?,?,?,NOW())
    ");
    $ins->bind_param('isiss', $pid, $name, $rating, $text, $services);
    if ($ins->execute())
      echo json_encode(['success' => true]);
    else {
      http_response_code(500);
      echo json_encode(['success' => false, 'error' => 'DB insert failed']);
    }
    exit;
  }
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Rating, text, services required']);
  exit;
}

// GET: fetch reviews API
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['api'])) {
  header('Content-Type: application/json');
  $res = $conn->query("
    SELECT r.*, p.profile_picture
      FROM reviews r
 LEFT JOIN patients p ON p.id = r.patient_id
   ORDER BY r.rating DESC, r.date DESC
  ");
  $out = [];
  while ($row = $res->fetch_assoc()) {
    $isAnonymous = strtolower(trim($row['name'])) === 'anonymous';
    $out[] = [
      'id' => (int) $row['id'],
      'name' => $row['name'],
      'rating' => (int) $row['rating'],
      'text' => $row['text'],
      'services' => json_decode($row['services'], true),
      'date_display' => date('M d, Y', strtotime($row['date'])),
      'date_raw' => $row['date'],
      'profile_picture' => $isAnonymous
        ? 'assets/photos/default_avatar.png'
        : get_profile_image_url($row['patient_id'] ?? null)
    ];
  }
  echo json_encode($out);
  exit;
}

// services list
$servicesList = [
  'Dental Check-ups & Consultation',
  'Teeth Cleaning (Oral Prophylaxis)',
  'Tooth Extraction',
  'Dental Fillings',
  'Gum Treatment and Gingivectomy (Periodontal Care)',
  'Teeth Whitening',
  'Dental Veneers',
  'Dental Bonding',
  'Metal Braces / Ceramic Braces',
  'Clear Aligners / Retainers',
  'Dental Crown',
  'Dental Bridges',
  'Dentures (Partial & Full)',
  'Dental Implants',
  'Fluoride Treatment',
  'Dental Sealants',
  "Kids' Braces & Orthodontic Care",
  'Wisdom Tooth Extraction (Odontectomy)',
  'Root Canal Treatment (Endodontics)',
  'TMJ Treatment',
  'Intraoral X-ray',
  'Panoramic X-ray / Full Mouth X-Ray',
  'Lateral Cephalometric X-Ray',
  'Periapical X-Ray / Single Tooth X-Ray',
  'TMJ Transcranial X-ray'
];

if (isset($_GET['appointment_id'])) {
    $aid = intval($_GET['appointment_id']);
    $stmt = $conn->prepare("SELECT services FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $aid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $preselectServices = array_map('trim', explode(',', $row['services']));
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reviews - ISched of M&A Oida Dental Clinic</title>
    <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/reviews.css">
  <?php require_once 'includes/head.php' ?>


  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#124085',
          }
        }
      }
    }
  </script>

  <style>
    /* Custom styles for star rating */
    .star-rating .star.active {
      color: #FFD700 !important;
    }

    .star-rating .star.hover {
      color: #FFD700 !important;
    }

    /* Form styles */
    select,
    textarea {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #D1D5DB;
      border-radius: 0.375rem;
      background-color: white;
    }

    select:focus,
    textarea:focus {
      outline: none;
      border-color: #124085;
      box-shadow: 0 0 0 3px rgba(18, 64, 133, 0.1);
    }

    /* Modal positioning */
    #reviewModal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 900;
      background-color: rgba(0, 0, 0, 0.5);
      align-items: center;
      justify-content: center;
    }

    #reviewModal.show {
      display: flex !important;
    }

    #reviewModal>div {
      max-width: 500px;
      width: 90%;
      margin: 0 auto;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    h5{
      font-weight: 500!important;
      font-size: 1.25rem!important;
    }
  </style>

  <script>
    window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    window.currentUserName = <?= json_encode($user ? "{$user['first_name']} {$user['last_name']}" : "") ?>;
    window.preselectServices = <?= json_encode($preselectServices) ?>;

    // Calculate average rating
    window.calculateAverage = function (reviews) {
      if (!reviews || !reviews.length) return 0;
      const sum = reviews.reduce((acc, r) => acc + r.rating, 0);
      return (sum / reviews.length).toFixed(1);
    };

    // Count reviews by rating
    window.countByRating = function (reviews, rating) {
      return reviews.filter(r => r.rating === rating).length;
    };
  </script>
  <script src="assets/js/reviews.js" defer></script>

</head>

<body>

  <header>
    <?php include_once('includes/navbar.php'); ?>
  </header>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-base font-normal">Clinic Reviews</h2>
    </div>

    <section id="ratingSummary"
      class="border border-transparent border-b-gray-200 border-t-transparent border-l-transparent border-r-transparent bg-[#fff8f3] rounded-md p-6 mb-6 flex flex-col sm:flex-row sm:items-center sm:space-x-6">
      <div class="flex flex-col items-start sm:items-center sm:flex-row sm:space-x-4 mb-4 sm:mb-0">
        <div id="averageRating" class="text-[#124085] font-semibold text-3xl leading-none">0.0</div>
        <div class="text-[#124085] text-sm font-normal mt-1 sm:mt-0 sm:ml-0.5">out of 5</div>
      </div>

      <div id="ratingFilters" class="flex space-x-2 flex-wrap">
        <button class="text-[#124085] border border-[#124085] rounded px-4 py-2 text-sm font-normal" type="button"
          data-filter="all">All</button>
        <button class="border border-gray-200 rounded px-4 py-2 text-sm font-normal" type="button" data-filter="5">5
          Star (<span id="count-5">0</span>)</button>
        <button class="border border-gray-200 rounded px-4 py-2 text-sm font-normal" type="button" data-filter="4">4
          Star (<span id="count-4">0</span>)</button>
        <button class="border border-gray-200 rounded px-4 py-2 text-sm font-normal" type="button" data-filter="3">3
          Star (<span id="count-3">0</span>)</button>
        <button class="border border-gray-200 rounded px-4 py-2 text-sm font-normal" type="button" data-filter="2">2
          Star (<span id="count-2">0</span>)</button>
        <button class="border border-gray-200 rounded px-4 py-2 text-sm font-normal" type="button" data-filter="1">1
          Star (<span id="count-1">0</span>)</button>
      </div>

      <div aria-label="Star rating" class="flex space-x-1 mt-4 sm:mt-0 sm:ml-auto" id="starDisplay">
        <i class="fas fa-star text-[#124085] text-xl"></i>
        <i class="fas fa-star text-[#124085] text-xl"></i>
        <i class="fas fa-star text-[#124085] text-xl"></i>
        <i class="fas fa-star text-[#124085] text-xl"></i>
        <i class="far fa-star text-[#124085] text-xl"></i>
      </div>
    </section>

    <div id="reviewsContainer"></div>

    <div class="text-center mt-6">
      <button id="addReviewBtn"
        class="bg-[#124085] text-white px-6 py-2 rounded-md hover:bg-[#0d3166] transition-colors duration-200">Add a
        Review</button>
    </div>
  </div>

  <!-- Review Modal -->
  <div id="reviewModal">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden">
      <!-- Modal Header with close button -->
      <div class="flex justify-between items-center border-b p-4">
        <h3 class="text-lg font-medium text-gray-900">Add a Review</h3>
        <button type="button" class="close text-gray-400 hover:text-gray-500 focus:outline-none">
          <span class="text-2xl">&times;</span>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6">
        <!-- User Profile -->
        <div class="flex items-center mb-6">
          <img
            src="<?= $user ? get_profile_image_url($_SESSION['user_id'] ?? null) : 'assets/photos/default_avatar.png' ?>"
            class="w-12 h-12 rounded-full object-cover mr-3" id="modalAvatar">
          <div id="modalUsername" class="text-sm font-medium text-gray-900"></div>
        </div>

        <!-- Service Type -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Type of Service</label>
          <div class="relative">
            <select id="serviceType" multiple
              class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
              required>
              <option value="" disabled>Select service(s)...</option>
              <?php
                $serviceOptions = !empty($preselectServices) ? $preselectServices : $servicesList;
                foreach ($serviceOptions as $svc):
              ?>
                <option value="<?= htmlspecialchars($svc) ?>"><?= htmlspecialchars($svc) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Rating -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Your Rating</label>
          <div class="flex space-x-1 star-rating">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="star text-2xl cursor-pointer text-gray-300 hover:text-yellow-400"
                data-value="<?= $i ?>">★</span>
            <?php endfor; ?>
          </div>
          <div class="rating-text text-sm text-gray-500 mt-1">Select your rating</div>
        </div>

        <!-- Feedback -->
        <div class="mb-4">
          <label for="reviewText" class="block text-sm font-medium text-gray-700 mb-1">Your Feedback</label>
          <textarea id="reviewText" rows="4" maxlength="500"
            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
            placeholder="Tell us about your experience..."></textarea>
          <div id="wordCount" class="text-xs text-gray-500 text-right mt-1">0 / 500 words</div>
        </div>

        <!-- Anonymous Toggle -->
        <div class="flex items-center mb-6">
          <input id="anonToggle" type="checkbox"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
          <label for="anonToggle" class="ml-2 block text-sm text-gray-700">Review anonymously</label>
        </div>

        <!-- Submit Button -->
        <button id="submitReview" type="button"
          class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
          Submit Review
        </button>
      </div>
    </div>
  </div>



  <footer>
    <p>&copy; 2025 ISched of M&A Oida Dental Clinic. All Rights Reserved.</p>
  </footer>

  <script>
    // pass PHP login state
    const isLoggedIn = <?= $user ? 'true' : 'false' ?>;
  </script>

  <!-- Notification script is now included in user_notification_bell.php -->

</body>

</html>