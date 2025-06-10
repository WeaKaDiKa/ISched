<?php
require_once('session.php');
require_once('db.php');
require_once('includes/profile_functions.php');

$user_id = $_SESSION['user_id'] ?? null;

// Redirect if not logged in
if (!$user_id) {
    header('Location: login.php');
    exit;
}

// Get user profile information
$profile_sql = "SELECT id, first_name, last_name, profile_picture FROM patients WHERE id = ?";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$user = $profile_stmt->get_result()->fetch_assoc();

// Handle profile picture path
function check_file_exists($path) {
    return file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/'));
}

if (empty($user['profile_picture'])) {
    $user['profile_picture'] = 'assets/photos/default_avatar.png';
} else {
    // Original path from database
    $original_path = $user['profile_picture'];
    
    // Check if it's already a full path that exists
    if (strpos($original_path, '/') === 0 || strpos($original_path, 'assets/') === 0 || strpos($original_path, 'uploads/') === 0) {
        if (check_file_exists($original_path)) {
            // Path is valid, keep as is
            $user['profile_picture'] = $original_path;
        } else {
            // Path format is valid but file doesn't exist, use default
            $user['profile_picture'] = 'assets/photos/default_avatar.png';
        }
    } else {
        // Try different possible locations
        $possible_paths = [
            'assets/images/profiles/' . $original_path,
            'assets/photos/' . $original_path,
            'uploads/profiles/' . $original_path,
            'assets/photos/default_avatar.png' // Default fallback
        ];
        
        // Use the first path that exists
        foreach ($possible_paths as $path) {
            if (check_file_exists($path)) {
                $user['profile_picture'] = $path;
                break;
            }
        }
    }
}

// Process review edit if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle AJAX request
    $is_ajax = isset($_POST['ajax']) && $_POST['ajax'] === 'true';
    
    if (isset($_POST['edit_review']) || (isset($_POST['action']) && $_POST['action'] === 'update_review')) {
        $review_id = $_POST['review_id'] ?? 0;
        $rating = $_POST['rating'] ?? 5;
        $review_text = $_POST['review_text'] ?? '';
        // Keep the existing services from the database
        $get_services_sql = "SELECT services FROM reviews WHERE id = ? AND patient_id = ?";
        $get_services_stmt = $conn->prepare($get_services_sql);
        $get_services_stmt->bind_param("ii", $review_id, $user_id);
        $get_services_stmt->execute();
        $services_result = $get_services_stmt->get_result()->fetch_assoc();
        $services = $services_result ? $services_result['services'] : '[]';
    
        // Validate the data
        if (!empty($review_id) && !empty($review_text)) {
            // Update the review
            $update_sql = "UPDATE reviews SET rating = ?, text = ?, services = ? WHERE id = ? AND patient_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("issii", $rating, $review_text, $services, $review_id, $user_id);
        
            if ($update_stmt->execute()) {
                // Get the updated review data for AJAX response
                $get_updated_review = "SELECT r.*, DATE_FORMAT(r.date, '%M %d, %Y') as formatted_date FROM reviews r WHERE r.id = ? AND r.patient_id = ?";
                $get_review_stmt = $conn->prepare($get_updated_review);
                $get_review_stmt->bind_param("ii", $review_id, $user_id);
                $get_review_stmt->execute();
                $updated_review = $get_review_stmt->get_result()->fetch_assoc();
            
                if ($is_ajax) {
                    // Return JSON response for AJAX
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Your review has been updated successfully!',
                        'review' => $updated_review
                    ]);
                    exit;
                } else {
                    // Success message for regular form submission
                    $success_message = "Your review has been updated successfully!";
                }
            } else {
                if ($is_ajax) {
                    // Return JSON error response
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => "Failed to update your review. Please try again."
                    ]);
                    exit;
                } else {
                    // Error message for regular form submission
                    $error_message = "Failed to update your review. Please try again.";
                }
            }
        } else {
            if ($is_ajax) {
                // Return JSON error response
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Review text cannot be empty."
                ]);
                exit;
            } else {
                $error_message = "Review text cannot be empty.";
            }
        }
    }
}

// Fetch available services for dropdown
$services_sql = "SELECT id, name FROM services ORDER BY name";
$services_result = $conn->query($services_sql);
$available_services = [];
if ($services_result && $services_result->num_rows > 0) {
    while ($service = $services_result->fetch_assoc()) {
        $available_services[] = $service;
    }
}

// Fetch reviews for the logged-in user
$sql = "
    SELECT r.*, p.first_name, p.last_name, p.profile_picture
      FROM reviews r
 LEFT JOIN patients p ON p.id = r.patient_id
    WHERE r.patient_id = ?
   ORDER BY r.rating DESC, r.date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - iSched</title>
    <link rel="stylesheet" href="assets/css/profiles.css">
    <link rel="stylesheet" href="assets/css/myreviews.css">
    <link rel="stylesheet" href="assets/css/profile-icon.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        .edit-form {
            margin-top: 20px;
        }
        
        .edit-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .edit-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            min-height: 100px;
        }
        
        .star-rating {
            margin-bottom: 15px;
        }
        
        .star-rating i {
            font-size: 24px;
            color: #ccc;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .star-rating i.active {
            color: #FFD700;
        }
        
        /* Services selection styles removed */
        
        .submit-btn {
            background-color: #6c5ce7;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .submit-btn:hover {
            background-color: #5b4bc4;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        /* Image upload styles removed */
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="header">
            <a href="index.php" class="back-btn">Back</a>
            <h1 class="profile-title">My Reviews</h1>
        </div>

        <div class="profile-content">
            <?php if (isset($success_message)): ?>
                <div class="success-message">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            <!-- User profile summary removed -->
            <!-- Search box removed -->

            <?php if (empty($user_reviews)): ?>
                <div class="no-reviews-message" style="text-align: center; padding: 20px;">
                    <i class="fas fa-comment-slash" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                    <p>You haven't submitted any reviews yet.</p>
                </div>
            <?php else: ?>
                <div id="userReviewsContainer">
                    <?php foreach ($user_reviews as $review): ?>
                        <div class="review-item" data-id="<?= $review['id'] ?>" data-rating="<?= $review['rating'] ?>" data-text="<?= htmlspecialchars($review['text']) ?>">
                            <div class="review-header">
                                <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" class="reviewer-avatar" onerror="this.src='assets/photos/default_avatar.png';">
                                <div class="reviewer-info">
                                    <div class="name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                    <div class="date">
                                        <?= date('M d, Y', strtotime($review['date'])) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="review-rating">
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-text"><?= htmlspecialchars($review['rating']) ?> out of 5</span>
                            </div>
                            <div class="review-text">
                                <p><?= nl2br(htmlspecialchars($review['text'])) ?></p>
                            </div>
                            <div class="review-service">
                                <strong>Service:</strong> <?= implode(', ', json_decode($review['services'], true)) ?>
                            </div>
                            <button class="edit-button" data-review-id="<?= $review['id'] ?>">Edit</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Review Modal -->
    <div id="editReviewModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Your Review</h2>
            <form class="edit-form" method="post" action="">
                <input type="hidden" id="edit_review_id" name="review_id" value="">
                <input type="hidden" name="edit_review" value="1">
                
                <div class="star-rating">
                    <label>Your Rating:</label>
                    <div>
                        <i class="far fa-star" data-rating="1"></i>
                        <i class="far fa-star" data-rating="2"></i>
                        <i class="far fa-star" data-rating="3"></i>
                        <i class="far fa-star" data-rating="4"></i>
                        <i class="far fa-star" data-rating="5"></i>
                        <input type="hidden" name="rating" id="rating_input" value="5">
                    </div>
                </div>
                
                <div>
                    <label for="review_text">Your Review:</label>
                    <textarea id="review_text" name="review_text" placeholder="Share details of your own experience at this place"></textarea>
                </div>
                
                <!-- Services selection removed -->
                
                <!-- Image upload container removed -->
                
                <button type="button" id="updateReviewBtn" class="submit-btn">Update Review</button>
            </form>
        </div>
    </div>

    <!-- Add script tags for Font Awesome and functionality -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality removed
        
        // Edit review functionality
        const modal = document.getElementById('editReviewModal');
        const closeBtn = document.querySelector('.close');
        const editButtons = document.querySelectorAll('.edit-button');
        const starRating = document.querySelectorAll('.star-rating i');
        const ratingInput = document.getElementById('rating_input');
        const reviewTextArea = document.getElementById('review_text');
        const reviewIdInput = document.getElementById('edit_review_id');
        // Service checkboxes removed
        
        // Open modal when edit button is clicked
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const reviewId = this.getAttribute('data-review-id');
                const reviewItem = this.closest('.review-item');
                const reviewText = reviewItem.getAttribute('data-text');
                const reviewRating = reviewItem.getAttribute('data-rating');
                const reviewServices = reviewItem.querySelector('.review-service').textContent.replace('Service:', '').trim().split(', ');
                
                // Set values in the form
                reviewIdInput.value = reviewId;
                reviewTextArea.value = reviewText;
                setRating(reviewRating);
                
                // Service checkboxes handling removed
                
                // Show the modal
                modal.style.display = 'block';
            });
        });
        
        // Close modal when X is clicked
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
        
        // Star rating functionality
        function setRating(rating) {
            ratingInput.value = rating;
            starRating.forEach(star => {
                const starRating = parseInt(star.getAttribute('data-rating'));
                if (starRating <= parseInt(rating)) {
                    star.className = 'fas fa-star active';
                } else {
                    star.className = 'far fa-star';
                }
            });
        }
        
        starRating.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                setRating(rating);
            });
            
            star.addEventListener('mouseover', function() {
                const rating = this.getAttribute('data-rating');
                starRating.forEach(s => {
                    const r = parseInt(s.getAttribute('data-rating'));
                    if (r <= parseInt(rating)) {
                        s.className = 'fas fa-star active';
                    } else {
                        s.className = 'far fa-star';
                    }
                });
            });
            
            star.addEventListener('mouseout', function() {
                const currentRating = ratingInput.value;
                setRating(currentRating);
            });
        });
        
        // Image upload functionality removed
        
        // AJAX form submission for real-time updates
        const updateReviewBtn = document.getElementById('updateReviewBtn');
        const editForm = document.getElementById('editReviewForm');
        
        updateReviewBtn.addEventListener('click', function() {
            // Get form data
            const reviewId = reviewIdInput.value;
            const rating = ratingInput.value;
            const reviewText = reviewTextArea.value;
            
            // Validate form
            if (reviewText.trim() === '') {
                alert('Review text cannot be empty.');
                return;
            }
            
            // Create form data object
            const formData = new FormData();
            formData.append('action', 'update_review');
            formData.append('review_id', reviewId);
            formData.append('rating', rating);
            formData.append('review_text', reviewText);
            formData.append('ajax', 'true');
            
            // Send AJAX request
            fetch('myreviews.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the review in the DOM
                    updateReviewInDOM(reviewId, rating, reviewText, data.review.services);
                    
                    // Show success message
                    const successMsg = document.createElement('div');
                    successMsg.className = 'success-message';
                    successMsg.textContent = data.message;
                    document.querySelector('.profile-content').insertBefore(successMsg, document.querySelector('#userReviewsContainer'));
                    
                    // Remove success message after 3 seconds
                    setTimeout(() => {
                        successMsg.remove();
                    }, 3000);
                    
                    // Close the modal
                    modal.style.display = 'none';
                } else {
                    // Show error message
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the review. Please try again.');
            });
        });
        
        // Function to update the review in the DOM
        function updateReviewInDOM(reviewId, rating, reviewText, services) {
            const reviewItem = document.querySelector(`.review-item[data-id="${reviewId}"]`);
            if (reviewItem) {
                // Update data attributes
                reviewItem.setAttribute('data-rating', rating);
                reviewItem.setAttribute('data-text', reviewText);
                
                // Update star rating display
                const starsContainer = reviewItem.querySelector('.review-rating');
                starsContainer.innerHTML = '';
                for (let i = 1; i <= 5; i++) {
                    const star = document.createElement('i');
                    star.className = i <= rating ? 'fas fa-star' : 'far fa-star';
                    starsContainer.appendChild(star);
                }
                
                // Update review text
                reviewItem.querySelector('.review-text').textContent = reviewText;
            }
        }
    });
    </script>
</body>
</html> 