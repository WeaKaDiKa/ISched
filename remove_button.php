<?php
// Script to remove the decline button from appointments.php
$file = 'admin/appointments.php';
$content = file_get_contents($file);

// Pattern to match the decline button
$pattern = '/<button\s+class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded px-2 py-1 mr-1"\s+type="button" title="Decline"\s+onclick="showConfirmModal\(\'decline\', \'\<\?= htmlspecialchars\(\$patientName\) \?\>\', \'\<\?= htmlspecialchars\(\$date\) \?\>\', \'\<\?= htmlspecialchars\(\$time\) \?\>\', \'\<\?= htmlspecialchars\(\$ref\) \?\>\', \'\<\?= htmlspecialchars\(\$service\) \?\>\'\)"><i\s+class="fas fa-times"><\/i><\/button>/';

// Remove the button
$modified_content = preg_replace($pattern, '', $content);

// Save the modified content back to the file
if ($modified_content !== $content) {
    file_put_contents($file, $modified_content);
    echo "Decline button removed successfully.";
} else {
    echo "No changes were made. Button pattern not found.";
}
?>
