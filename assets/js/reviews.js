// assets/js/reviews.js
document.addEventListener('DOMContentLoaded', () => {
  // Globals from PHP
  const isLoggedIn       = window.isLoggedIn;
  const currentUserName  = window.currentUserName || '';
  const calculateAverage = window.calculateAverage;
  const countByRating    = window.countByRating;

  // DOM elements
  const reviewsContainer = document.getElementById('reviewsContainer');
  const addBtn           = document.getElementById('addReviewBtn');
  const modal            = document.getElementById('reviewModal');
  const closeBtn         = modal.querySelector('.close');
  const submitBtn        = document.getElementById('submitReview');
  const serviceSel       = document.getElementById('serviceType');
  const stars            = Array.from(document.querySelectorAll('.star-rating .star'));
  const reviewText       = document.getElementById('reviewText');
  const wordCount        = document.getElementById('wordCount');
  const anonToggle       = document.getElementById('anonToggle');
  const modalUser        = document.getElementById('modalUsername');
  
  // Rating summary elements
  const averageRatingEl  = document.getElementById('averageRating');
  const starDisplayEl    = document.getElementById('starDisplay');
  const ratingFilters    = document.getElementById('ratingFilters');
  
  let allReviews = [];
  let selectedRating = 0;
  let currentFilter = 'all';

  // --- Modal open/close ---
  addBtn.addEventListener('click', () => {
    if (!isLoggedIn) {
      alert('Please log in to add a review.');
      return window.location.href = 'login.php';
    }
    modal.classList.add('show');
    modalUser.textContent = currentUserName;
    reviewText.value = '';
    wordCount.textContent = '0 / 500 words';
    anonToggle.checked = false;
    selectedRating = 0;
    resetStars();
    serviceSel.selectedIndex = 0;
  });
  closeBtn.addEventListener('click', () => modal.classList.remove('show'));
  modal.addEventListener('click', e => {
    if (e.target === modal) modal.classList.remove('show');
  });

  // --- Star rating ---
  stars.forEach(s => {
    const val = +s.dataset.value;
    s.addEventListener('click', () => { selectedRating = val; updateStars(); });
    s.addEventListener('mouseover', () => highlightStars(val));
    s.addEventListener('mouseout', resetStars);
  });
  function updateStars() {
    stars.forEach(s => s.classList.toggle('active', +s.dataset.value <= selectedRating));
  }
  function highlightStars(n) {
    stars.forEach(s => s.classList.toggle('hover', +s.dataset.value <= n));
  }
  function resetStars() {
    stars.forEach(s => s.classList.remove('hover'));
    updateStars();
  }

  // --- Word count ---
  reviewText.addEventListener('input', () => {
    const cnt = reviewText.value.trim().split(/\s+/).filter(w => w).length;
    wordCount.textContent = `${Math.min(cnt, 500)} / 500 words`;
  });

  // --- Anonymous toggle ---
  const modalAvatar = document.getElementById('modalAvatar');
  const userProfilePic = modalAvatar.src;
  const defaultAvatar = 'assets/photos/default_avatar.png';
  
  anonToggle.addEventListener('change', () => {
    modalUser.textContent = anonToggle.checked ? 'Anonymous' : currentUserName;
    modalAvatar.src = anonToggle.checked ? defaultAvatar : userProfilePic;
  });

  // --- Submit review ---
  submitBtn.addEventListener('click', () => {
    const name    = anonToggle.checked ? 'Anonymous' : currentUserName;
    const service = serviceSel.value;
    const text    = reviewText.value.trim();

    if (!service || !selectedRating || !text) {
      return alert('Please select a service, rating, and enter feedback.');
    }

    fetch('reviews.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        name,
        rating: selectedRating,
        text,
        services: [ service ],
        anon: anonToggle.checked
      })
    })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        modal.style.display = 'none';
        loadReviews();
      } else {
        alert('Submit failed: ' + (json.error || 'Unknown error'));
      }
    })
    .catch(() => alert('Network error submitting review.'));
  });

  // --- Fetch & render reviews ---
  function loadReviews() {
    fetch('reviews.php?api=1')
      .then(r => r.json())
      .then(data => {
        allReviews = data;
        filterReviews();
      })
      .catch(() => {
        reviewsContainer.innerHTML =
          '<p class="text-center text-red-500 py-8">Failed to load reviews.</p>';
      });
  }
  
  function filterReviews() {
    let filtered = allReviews;
    
    if (currentFilter !== 'all') {
      const rating = parseInt(currentFilter);
      filtered = allReviews.filter(r => r.rating === rating);
    }
    
    renderReviews(filtered);
  }
  
  // Set up rating filter buttons
  if (ratingFilters) {
    const filterButtons = ratingFilters.querySelectorAll('button');
    filterButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        // Update active button
        filterButtons.forEach(b => {
          b.classList.remove('text-[#124085]', 'border-[#124085]');
          b.classList.add('border-gray-200');
        });
        
        btn.classList.remove('border-gray-200');
        btn.classList.add('text-[#124085]', 'border-[#124085]');
        
        // Apply filter
        currentFilter = btn.dataset.filter;
        filterReviews();
      });
    });
  }

  function renderReviews(list) {
    if (!list.length) {
      reviewsContainer.innerHTML = '<p class="text-center text-gray-500 py-8">No reviews found.</p>';
      return;
    }
    
    reviewsContainer.innerHTML = list.map(r => {
      const service = (r.services || [])[0] || '';
      
      // Generate star HTML using Font Awesome
      const starsHtml = Array(5).fill().map((_, i) => 
        i < r.rating 
          ? '<i class="fas fa-star text-[#124085] text-sm"></i>' 
          : '<i class="far fa-star text-[#124085] text-sm"></i>'
      ).join('');
      
      return `
        <article class="mb-6 border-b border-gray-200 pb-6">
          <div class="flex items-center space-x-4 mb-1">
            <img src="${r.profile_picture}" class="w-10 h-10 rounded-full object-cover" alt="">
            <div class="text-sm font-normal">${r.name}</div>
          </div>
          <div class="flex items-center space-x-1 mb-1 text-[#124085] text-sm">
            ${starsHtml}
          </div>
          <div class="text-xs text-gray-500 mb-2">
            ${r.date_display} | Service: ${service}
          </div>
          <p class="mb-3 text-sm">${r.text}</p>
        </article>`;
    }).join('');
    
    // Update rating summary
    updateRatingSummary();
  }
  
  function updateRatingSummary() {
    // Update average rating
    const avgRating = calculateAverage(allReviews);
    if (averageRatingEl) {
      averageRatingEl.textContent = avgRating;
    }
    
    // Update star counts
    for (let i = 5; i >= 1; i--) {
      const count = countByRating(allReviews, i);
      const countElement = document.getElementById(`count-${i}`);
      if (countElement) {
        countElement.textContent = count;
      }
    }
    
    // Update star display
    if (starDisplayEl) {
      const fullStars = Math.floor(avgRating);
      const hasHalfStar = avgRating % 1 >= 0.5;
      
      starDisplayEl.innerHTML = '';
      
      // Add full stars
      for (let i = 0; i < fullStars; i++) {
        starDisplayEl.innerHTML += '<i class="fas fa-star text-[#124085] text-xl"></i>';
      }
      
      // Add half star if needed
      if (hasHalfStar) {
        starDisplayEl.innerHTML += '<i class="fas fa-star-half-alt text-[#124085] text-xl"></i>';
      }
      
      // Add empty stars
      const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
      for (let i = 0; i < emptyStars; i++) {
        starDisplayEl.innerHTML += '<i class="far fa-star text-[#124085] text-xl"></i>';
      }
      
      // Update out of 5 text
      const outOfFiveEl = document.querySelector('#averageRating + span');
      if (outOfFiveEl) {
        outOfFiveEl.textContent = ' out of 5';
      }
    }
  }

  // initial load
  loadReviews();
});
