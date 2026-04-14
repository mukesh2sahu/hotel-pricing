<?php
require_once __DIR__ . '/api/makcorps_client.php';

$hotelName = "Holiday Inn Bangkok Sukhumvit";
$checkIn = date('Y-m-d', strtotime('+14 days'));
$checkOut = date('Y-m-d', strtotime('+15 days'));

echo "<h2>Testing MakCorps RapidAPI</h2>";
echo "<p><strong>Hotel:</strong> " . htmlspecialchars($hotelName) . "</p>";
echo "<p><strong>Check-in:</strong> " . htmlspecialchars($checkIn) . "</p>";
echo "<p><strong>Check-out:</strong> " . htmlspecialchars($checkOut) . "</p>";

$mapping = makcorpsMappingSearch($hotelName);
echo "<p><strong>Mapping Status:</strong> " . (int)($mapping['status'] ?? 0) . "</p>";

if (empty($mapping['ok'])) {
    echo "<p style='color:red;'><strong>Mapping Error:</strong> " . htmlspecialchars($mapping['error'] ?? 'Unknown error') . "</p>";
    if (isset($mapping['data'])) {
        echo "<pre>" . htmlspecialchars(json_encode($mapping['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . "</pre>";
    }
    exit;
}

echo "<h3>Mapping Response:</h3>";
echo "<pre>" . htmlspecialchars(json_encode($mapping['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . "</pre>";

$firstHotelId = $mapping['data'][0]['document_id'] ?? null;
if (!$firstHotelId) {
    echo "<p style='color:red;'><strong>No hotel ID returned by mapping API.</strong></p>";
    exit;
}

$comparison = makcorpsHotelComparison($firstHotelId, $checkIn, $checkOut, 'USD', 2, 1);
echo "<p><strong>Comparison Status:</strong> " . (int)($comparison['status'] ?? 0) . "</p>";

if (empty($comparison['ok'])) {
    echo "<p style='color:red;'><strong>Comparison Error:</strong> " . htmlspecialchars($comparison['error'] ?? 'Unknown error') . "</p>";
    if (isset($comparison['data'])) {
        echo "<pre>" . htmlspecialchars(json_encode($comparison['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . "</pre>";
    }
    exit;
}

echo "<h3>Comparison Response:</h3>";
echo "<pre>" . htmlspecialchars(json_encode($comparison['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . "</pre>";
?>
