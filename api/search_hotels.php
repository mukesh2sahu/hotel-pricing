<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('SERP_API_KEY', 'a67d0d35664e07f1bc469e5d69bdf20c95287fc99eda0c02b5da1e92589b0185');

/**
 * Search Hotels from SerpApi
 */
function searchHotels($hotelName, $checkInDaysFromNow = 14) {
    if (!defined('SERP_API_KEY') || empty(SERP_API_KEY)) {
        return null;
    }
    
    $checkIn = date('Y-m-d', strtotime('+' . $checkInDaysFromNow . ' days'));
    $checkOut = date('Y-m-d', strtotime('+' . ($checkInDaysFromNow + 1) . ' days'));
    
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
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    curl_close($ch);
    
    if (!$result) {
        return null;
    }
    
    $data = json_decode($result, true);
    
    if (isset($data['error'])) {
        return null;
    }
    
    $hotels = [];
    
    // Extract hotels from ads section (these have OTA prices and provider names)
    if (isset($data['ads']) && is_array($data['ads'])) {
        foreach ($data['ads'] as $ad) {
            $hotel = [
                'name' => $ad['name'] ?? 'N/A',
                'price' => str_replace('$', '', $ad['price'] ?? 'N/A'),
                'source' => $ad['source'] ?? 'Unknown',
                'link' => $ad['link'] ?? null,
                'thumbnail' => $ad['thumbnail'] ?? null,
                'rating' => $ad['overall_rating'] ?? null,
                'reviews' => $ad['reviews'] ?? null,
                'hotel_class' => $ad['hotel_class'] ?? null,
                'amenities' => $ad['amenities'] ?? []
            ];
            $hotels[] = $hotel;
        }
    }
    
    // If no ads, try properties section
    if (empty($hotels) && isset($data['properties']) && is_array($data['properties'])) {
        foreach (array_slice($data['properties'], 0, 10) as $property) {
            $hotel = [
                'name' => $property['name'] ?? 'N/A',
                'price' => $property['rate_per_night']['extracted_lowest'] ?? 'N/A',
                'source' => 'Google Hotels',
                'link' => $property['link'] ?? null,
                'thumbnail' => isset($property['images'][0]) ? $property['images'][0]['thumbnail'] : null,
                'rating' => $property['overall_rating'] ?? null,
                'reviews' => $property['reviews'] ?? null,
                'hotel_class' => $property['hotel_class'] ?? null,
                'amenities' => $property['amenities'] ?? []
            ];
            $hotels[] = $hotel;
        }
    }
    
    return $hotels;
}

$hotelName = isset($_GET['q']) ? trim($_GET['q']) : null;

if (!$hotelName || strlen($hotelName) < 2) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Hotel name must be at least 2 characters"
    ]);
    exit;
}

$results = searchHotels($hotelName);

if ($results === null || empty($results)) {
    echo json_encode([
        "status" => "success",
        "data" => [],
        "message" => "No hotels found for: " . htmlspecialchars($hotelName)
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "data" => $results,
        "count" => count($results)
    ]);
}
?>
