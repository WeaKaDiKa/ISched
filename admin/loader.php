<!-- Dotted Path Loader Overlay -->
<div id="loaderOverlay" class="loader-overlay" style="display:none;">
    <div class="loader-dots">
        <div class="loader-dot dot1"></div>
        <div class="loader-dot dot2"></div>
        <div class="loader-dot dot3"></div>
        <div class="loader-dot dot4"></div>
        <div class="loader-dot dot5"></div>
        <div class="loader-dot dot6"></div>
        <div class="loader-dot dot7"></div>
        <div class="loader-dot dot8"></div>
        <div class="loader-dot dot9"></div>
        <div class="loader-dot dot10"></div>
        <div class="loader-dot dot11"></div>
        <div class="loader-dot dot12"></div>
    </div>

</div>
<script>
    // Show loader on navigation
    document.querySelectorAll('a').forEach(function (link) {
        if (link.getAttribute('href') && !link.getAttribute('href').startsWith('#') && !link.hasAttribute('target')) {
            link.addEventListener('click', function (e) {
                // Only show loader for internal navigation
                document.getElementById('loaderOverlay').style.display = 'flex';
            });
        }
    });
    // Hide loader on page load
    window.addEventListener('DOMContentLoaded', function () {
        document.getElementById('loaderOverlay').style.display = 'none';
    });
</script> <!-- Dotted Path Loader Overlay -->
<div id="loaderOverlay" class="loader-overlay" style="display:none;">
    <div class="loader-dots">
        <div class="loader-dot dot1"></div>
        <div class="loader-dot dot2"></div>
        <div class="loader-dot dot3"></div>
        <div class="loader-dot dot4"></div>
        <div class="loader-dot dot5"></div>
        <div class="loader-dot dot6"></div>
        <div class="loader-dot dot7"></div>
        <div class="loader-dot dot8"></div>
        <div class="loader-dot dot9"></div>
        <div class="loader-dot dot10"></div>
        <div class="loader-dot dot11"></div>
        <div class="loader-dot dot12"></div>
    </div>

</div>
<script>
    // Show loader on navigation
    document.querySelectorAll('a').forEach(function (link) {
        if (link.getAttribute('href') && !link.getAttribute('href').startsWith('#') && !link.hasAttribute('target')) {
            link.addEventListener('click', function (e) {
                // Only show loader for internal navigation
                document.getElementById('loaderOverlay').style.display = 'flex';
            });
        }
    });
    // Hide loader on page load
    window.addEventListener('DOMContentLoaded', function () {
        document.getElementById('loaderOverlay').style.display = 'none';
    });
</script>