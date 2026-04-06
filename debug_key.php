<?php
define('SERP_API_KEY', 'a67d0d35664e07f1bc469e5d69bdf20c95287fc99eda0c02b5da1e92589b0185');

echo "<h2>API Key Check</h2>";
echo "<p><strong>API Key:</strong> " . SERP_API_KEY . "</p>";
echo "<p><strong>Key Length:</strong> " . strlen(SERP_API_KEY) . " characters</p>";

// Simple test
$url = "https://serpapi.com/search.json";
$params = [
    "engine" => "google_hotels",
    "q" => "hotel",
    "api_key" => SERP_API_KEY
];

$fullUrl = $url . "?" . http_build_query($params);
echo "<p><strong>Testing URL:</strong><br/>" . htmlspecialchars($fullUrl) . "</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";

$data = json_decode($result, true);
if (isset($data['error'])) {
    echo "<p style='color:red;'><strong>Error:</strong> " . $data['error'] . "</p>";
} else if (isset($data['properties'])) {
    echo "<p style='color:green;'><strong>Success! Found properties</strong></p>";
} else {
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
}
?>
