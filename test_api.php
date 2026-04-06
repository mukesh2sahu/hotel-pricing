<?php
define('SERP_API_KEY', 'a67d0d35664e07f1bc469e5d69bdf20c95287fc99eda0c02b5da1e92589b0185');

$hotelName = "Holiday Inn Bangkok Sukhumvit";
$checkIn = date('Y-m-d', strtotime('+14 days'));
$checkOut = date('Y-m-d', strtotime('+15 days'));

$params = [
    "engine" => "google_hotels",
    "q" => $hotelName,
    "check_in_date" => $checkIn,
    "check_out_date" => $checkOut,
    "adults" => "2",
    "currency" => "USD",
    "gl" => "us",
    "hl" => "en",
    "api_key" => SERP_API_KEY
];

$url = "https://serpapi.com/search.json?" . http_build_query($params);

echo "<h2>Testing SerpApi</h2>";
echo "<p><strong>URL:</strong> " . htmlspecialchars($url) . "</p>";
echo "<p><strong>Hotel:</strong> $hotelName</p>";
echo "<p><strong>Check-in:</strong> $checkIn</p>";
echo "<p><strong>Check-out:</strong> $checkOut</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if (!$result) {
    echo "<p style='color:red;'><strong>cURL Error:</strong> $curlError</p>";
} else {
    $data = json_decode($result, true);
    
    echo "<h3>API Response:</h3>";
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
    
    if (isset($data['error'])) {
        echo "<p style='color:red;'><strong>API Error:</strong> " . $data['error'] . "</p>";
    } else if (isset($data['properties'])) {
        echo "<p style='color:green;'><strong>Found " . count($data['properties']) . " properties</strong></p>";
    }
}
?>
