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

// Process review edit if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle AJAX request

    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if (isset($_POST['edit_review']) || (isset($_POST['action']) && $_POST['action'] === 'update_review')) {
        $review_id = (int) ($_POST['review_id'] ?? 0);
        $rating = min(max((int) ($_POST['rating'] ?? 5), 1), 5); // Ensure rating is between 1-5
        $review_text = trim($_POST['review_text'] ?? '');

        // Validate input
        if ($review_id < 1) {
            sendResponse($is_ajax, false, "Invalid review ID");
        }

        if (empty($review_text)) {
            sendResponse($is_ajax, false, "Review text cannot be empty");
        }

        try {
            // Get existing services (using transaction for safety)
            $conn->begin_transaction();

            $services = '[]';
            $get_services_stmt = $conn->prepare("SELECT services FROM reviews WHERE id = ? AND patient_id = ?");
            $get_services_stmt->bind_param("ii", $review_id, $user_id);
            $get_services_stmt->execute();
            $services_result = $get_services_stmt->get_result()->fetch_assoc();
            $services = $services_result ? $services_result['services'] : '[]';

            // Update review
            $update_stmt = $conn->prepare("UPDATE reviews SET rating = ?, text = ?, services = ? WHERE id = ? AND patient_id = ?");
            $update_stmt->bind_param("issii", $rating, $review_text, $services, $review_id, $user_id);

            if (!$update_stmt->execute()) {
                throw new Exception("Database update failed");
            }

            // Get updated review
            $updated_review = null;
            $get_review_stmt = $conn->prepare("SELECT r.*, DATE_FORMAT(r.date, '%M %d, %Y') as formatted_date FROM reviews r WHERE r.id = ? AND r.patient_id = ?");
            $get_review_stmt->bind_param("ii", $review_id, $user_id);
            $get_review_stmt->execute();
            $updated_review = $get_review_stmt->get_result()->fetch_assoc();

            $conn->commit();

            sendResponse($is_ajax, true, "Review updated successfully", $updated_review);

        } catch (Exception $e) {
            $conn->rollback();
            sendResponse($is_ajax, false, "Error updating review: " . $e->getMessage());
        }
    }
}

// Helper function for consistent responses
function sendResponse($is_ajax, $success, $message, $data = null)
{
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'review' => $data
        ]);
        exit;
    } else {
        if ($success) {
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_message'] = $message;
        }
        header("Location: myreviews.php");
        exit;
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
$sql = "SELECT r.*, p.first_name, p.last_name, p.profile_picture
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
    <?php require_once 'includes/head.php' ?>
    <style>
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
    <header>
        <?php include_once('includes/navbar.php'); ?>
    </header>
    <main class="container mt-4">
        <h1 class="text-center">My Reviews</h1>
        <div class="card p-4">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($user_reviews)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-comment-slash display-4 text-secondary mb-3"></i>
                    <p class="text-muted">You haven't submitted any reviews yet.</p>
                </div>
            <?php else: ?>
                <div id="userReviewsContainer" class="list-group">
                    <?php foreach ($user_reviews as $review): ?>
                        <div class="list-group-item review-item" data-id="<?= $review['id'] ?>"
                            data-rating="<?= $review['rating'] ?>" data-text="<?= htmlspecialchars($review['text']) ?>">
                            <div class="d-flex align-items-center">
                                <img src="assets/images/profiles/<?= htmlspecialchars($review['profile_picture']) ?>"
                                    alt="Profile Picture" class="rounded-circle me-3" width="50" height="50"
                                    onerror="this.src='assets/photos/default_avatar.png';">
                                <div>
                                    <div class="fw-bold">
                                        <?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?>
                                    </div>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($review['date'])) ?></small>
                                </div>


                            </div>
                            <div class="mt-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star text-warning"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($review['rating']) ?> out of 5</span>
                                </div>
                            </div>
                            <p class="mt-2"><?= nl2br(htmlspecialchars($review['text'])) ?></p>
                            <div class="text-muted review-service">
                                <strong>Service:</strong> <?= implode(', ', json_decode($review['services'], true)) ?>
                            </div>
                            <button class="edit-button btn btn-sm btn-primary mt-2"
                                data-review-id="<?= $review['id'] ?>">Edit</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <!-- Bootstrap 5 Modal -->
    <div class="modal fade" id="editReviewModal" tabindex="-1" aria-labelledby="editReviewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editReviewModalLabel">Edit Your Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="edit-form" method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_review_id" name="review_id" value="">
                        <input type="hidden" name="edit_review" value="1">

                        <div class="mb-3 star-rating">
                            <label class="form-label">Your Rating:</label>
                            <div class="d-flex gap-1">
                                <i class="far fa-star fs-3" data-rating="1" style="cursor: pointer;"></i>
                                <i class="far fa-star fs-3" data-rating="2" style="cursor: pointer;"></i>
                                <i class="far fa-star fs-3" data-rating="3" style="cursor: pointer;"></i>
                                <i class="far fa-star fs-3" data-rating="4" style="cursor: pointer;"></i>
                                <i class="far fa-star fs-3" data-rating="5" style="cursor: pointer;"></i>
                                <input type="hidden" name="rating" id="rating_input" value="5">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="review_text" class="form-label">Your Review:</label>
                            <textarea class="form-control" id="review_text" name="review_text" rows="4"
                                placeholder="Share details of your own experience at this place"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="updateReviewBtn" class="btn btn-primary">Update Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Bootstrap modal
            const editReviewModal = new bootstrap.Modal(document.getElementById('editReviewModal'));

            // Edit review functionality
            const editButtons = document.querySelectorAll('.edit-button');
            const starRating = document.querySelectorAll('.star-rating i');
            const ratingInput = document.getElementById('rating_input');
            const reviewTextArea = document.getElementById('review_text');
            const reviewIdInput = document.getElementById('edit_review_id');

            // Star rating functionality
            function setRating(rating) {
                starRating.forEach(star => {
                    if (star.getAttribute('data-rating') <= rating) {
                        star.classList.add('fas', 'text-warning');
                        star.classList.remove('far');
                    } else {
                        star.classList.add('far');
                        star.classList.remove('fas', 'text-warning');
                    }
                });
                ratingInput.value = rating;
            }

            starRating.forEach(star => {
                star.addEventListener('click', function () {
                    const rating = this.getAttribute('data-rating');
                    setRating(rating);
                });
            });

            // Open modal when edit button is clicked
            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const reviewId = this.getAttribute('data-review-id');
                    const reviewItem = this.closest('.review-item');
                    const reviewText = reviewItem.getAttribute('data-text');
                    const reviewRating = reviewItem.getAttribute('data-rating');

                    // Set values in the form
                    reviewIdInput.value = reviewId;
                    reviewTextArea.value = reviewText;
                    setRating(reviewRating);

                    // Show the modal
                    editReviewModal.show();
                });
            });

            // Update review button functionality
            document.getElementById('updateReviewBtn').addEventListener('click', function () {
                document.querySelector('.edit-form').submit();
            });

            // Star rating functionality
            function setRating(rating) {
                ratingInput.value = rating;
                starRating.forEach(star => {
                    star.classList.toggle('fas', star.dataset.rating <= rating);
                    star.classList.toggle('far', star.dataset.rating > rating);
                    star.classList.toggle('text-warning', star.dataset.rating <= rating);
                });
            }

            // Event delegation for star ratings
            document.querySelector('.star-rating').addEventListener('click', e => {
                if (e.target.matches('[data-rating]')) {
                    setRating(parseInt(e.target.dataset.rating));
                }
            });

            document.querySelector('.star-rating').addEventListener('mouseover', e => {
                if (e.target.matches('[data-rating]')) {
                    const hoverRating = parseInt(e.target.dataset.rating);
                    starRating.forEach(star => {
                        star.classList.toggle('fas', star.dataset.rating <= hoverRating);
                        star.classList.toggle('far', star.dataset.rating > hoverRating);
                    });
                }
            });

            document.querySelector('.star-rating').addEventListener('mouseout', () => {
                setRating(ratingInput.value);
            });

            // AJAX form submission
            document.getElementById('updateReviewBtn').addEventListener('click', async function () {
                const reviewText = reviewTextArea.value.trim();

                if (!reviewText) {
                    alert('Review text cannot be empty.');
                    return;
                }

                try {
                    const response = await fetch('myreviews.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'update_review',
                            review_id: reviewIdInput.value,
                            rating: ratingInput.value,
                            review_text: reviewText,
                            csrf_token: document.querySelector('[name="csrf_token"]').value
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Server error');
                    }

                    if (data.success) {
                        updateReviewInDOM(data.review);
                        showToast(data.message, 'success');
                        editReviewModal.hide();
                    } else {
                        showToast(data.message, 'danger');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast(error.message, 'danger');
                }
            });

            // DOM update function
            function updateReviewInDOM(review) {
                const reviewItem = document.querySelector(`.review-item[data-id="${review.id}"]`);
                if (!reviewItem) return;

                // Update data attributes
                reviewItem.dataset.rating = review.rating;
                reviewItem.dataset.text = review.text;

                // Update stars
                const starsContainer = reviewItem.querySelector('.review-rating');
                starsContainer.innerHTML = Array.from({ length: 5 }, (_, i) =>
                    `<i class="${i < review.rating ? 'fas text-warning' : 'far'} fa-star"></i>`
                ).join('');

                // Update text and date
                reviewItem.querySelector('.review-text').textContent = review.text;
                if (review.formatted_date) {
                    reviewItem.querySelector('.review-date').textContent = review.formatted_date;
                }
            }

            // Toast notification
            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `position-fixed top-0 end-0 p-3 text-bg-${type} rounded`;
                toast.innerHTML = `
        <div class="toast show" role="alert">
            <div class="toast-body">${message}</div>
        </div>
    `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        });
    </script>
</body>

</html>