<?php
// Quick branding update script
$siteName = 'SwapnoNibash';

echo "<! DOCTYPE html>";
echo "<html><head><title>Update Branding</title></head><body>";
echo "<h1>Updating to: $siteName</h1>";

// Update config.php
$configFile = __DIR__ . '/config/config. php';
if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    $content = preg_replace(
        "/define\('APP_NAME',\s*'[^']*'\)/",
        "define('APP_NAME', '$siteName')",
        $content
    );
    file_put_contents($configFile, $content);
    echo "<p style='color:green;'>✓ Updated config.php</p>";
} else {
    echo "<p style='color:red;'>✗ config.php not found</p>";
}

echo "<h2>Manual Updates Needed:</h2>";
echo "<ul>";
echo "<li>Check admin/dashboard.php sidebar for 'Admin Panel' and change to 'SwapnoNibash Admin'</li>";
echo "<li>Check admin/login.php header for site name</li>";
echo "<li>Clear browser cache and test! </li>";
echo "</ul>";

echo "<p><a href='admin/login.php'>Go to Admin Login</a></p>";
echo "</body></html>";
?>