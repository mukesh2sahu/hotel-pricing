<?php
require_once __DIR__ . '/api/makcorps_client.php';

echo "<h2>API Key Check</h2>";
echo "<p><strong>Provider:</strong> MakCorps RapidAPI</p>";
echo "<p><strong>Host:</strong> " . htmlspecialchars(MAKCORPS_RAPIDAPI_HOST) . "</p>";
echo "<p><strong>Key Length:</strong> " . strlen(MAKCORPS_RAPIDAPI_KEY) . " characters</p>";
echo "<p><strong>Testing Endpoint:</strong><br/>" . htmlspecialchars(MAKCORPS_RAPIDAPI_BASE_URL . '/auth') . "</p>";

$result = makcorpsAuthCheck();

echo "<p><strong>HTTP Code:</strong> " . (int)($result['status'] ?? 0) . "</p>";

if (empty($result['ok'])) {
    echo "<p style='color:red;'><strong>API Error:</strong> " . htmlspecialchars($result['error'] ?? 'Unknown error') . "</p>";
    if (isset($result['data'])) {
        echo "<pre>" . htmlspecialchars(json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . "</pre>";
    }
} else {
    echo "<h3>API Response:</h3>";
    echo "<pre>" . htmlspecialchars(json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . "</pre>";
    echo "<p style='color:green;'><strong>RapidAPI key appears valid.</strong></p>";
}
?>
