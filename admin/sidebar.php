<!-- Profile Section -->
<div class="flex flex-col items-center mb-8">
    <img alt="Profile photo" class="rounded-full w-24 h-24 object-cover mb-2" 
         src="assets/photo/me.jpg"/>
    <h3 class="text-center text-sm font-semibold text-gray-900 leading-tight user-name">
        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Dr. Ardeen Dofiles Oida'); ?>
    </h3>
    <p class="text-center text-xs text-gray-500 mt-1">
        Professional Dentist
    </p>
</div> 