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
    $hotelNames = []; // Track unique hotel names to avoid duplicates
    
    // Extract hotels from ads section (these have OTA prices and provider names)
    if (isset($data['ads']) && is_array($data['ads'])) {
        foreach ($data['ads'] as $ad) {
            $name = $ad['name'] ?? 'N/A';
            if (!isset($hotelNames[$name])) {
                $hotel = [
                    'name' => $name,
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
                $hotelNames[$name] = true;
            }
        }
    }
    
    // Also get hotels from properties section (for comprehensive results)
    if (isset($data['properties']) && is_array($data['properties'])) {
        foreach ($data['properties'] as $property) {
            $name = $property['name'] ?? 'N/A';
            // Only add if not already in list (avoid duplicates)
            if (!isset($hotelNames[$name])) {
                $hotel = [
                    'name' => $name,
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
                $hotelNames[$name] = true;
            }
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

// If no results, try multiple fallback strategies
if (($results === null || empty($results)) && strlen($hotelName) > 2) {
    
    // Strategy 1: Remove common brand identifiers
    if (strlen($hotelName) > 10) {
        $simplified = $hotelName;
        $simplified = preg_replace('/,\s*an\s+[A-Z]+\s+[A-Za-z]+/i', '', $simplified);
        $simplified = preg_replace('/\s+by\s+[A-Za-z]+/i', '', $simplified);
        $simplified = preg_replace('/\s+\([^)]*\)/i', '', $simplified);
        $simplified = preg_replace('/\s+&\s+[A-Za-z]+/i', '', $simplified);
        $simplified = preg_replace('/\s+(hotels|resorts|inns|motels|lodges)$/i', '', $simplified);
        $simplified = trim($simplified);
        
        if ($simplified !== $hotelName && strlen($simplified) > 2) {
            $results = searchHotels($simplified);
        }
    }
    
    // Strategy 2: If it looks like a brand/chain name, try with "hotels in" popular locations
    if (($results === null || empty($results))) {
        // Extract potential chain name (remove words like "hotels", "resorts", "&")
        $chainName = preg_replace('/\s+(and|&)\s+.*/i', '', $hotelName);
        $chainName = preg_replace('/\s+(hotels|resorts|inns)$/i', '', $chainName);
        $chainName = trim($chainName);
        
        // List of major hotel destinations to try
        $majorLocations = ['New Delhi', 'Mumbai', 'Bangkok', 'Singapore', 'Dubai', 'London', 'Paris', 'New York', 'Tokyo'];
        
        foreach ($majorLocations as $location) {
            $searchQuery = $chainName . " " . $location;
            $results = searchHotels($searchQuery);
            if ($results !== null && !empty($results)) {
                break;
            }
        }
    }
    
    // Strategy 3: If still no results, search for category/type + popular location
    if (($results === null || empty($results))) {
        // Try "hotels in [popular locations]"
        $fallbackSearches = [
            'hotels in India',
            'hotels in Asia',
            'hotels worldwide',
            'resorts worldwide',
            'luxury accommodations'
        ];
        
        foreach ($fallbackSearches as $search) {
            $results = searchHotels($search);
            if ($results !== null && !empty($results)) {
                break;
            }
        }
    }
}

if ($results === null || empty($results)) {
    // If still no results, suggest what user can try
    $suggestion = "Try searching with a specific location (e.g., 'Oberoi Delhi') or search for 'hotels in [city]'.";
    
    echo json_encode([
        "status" => "success",
        "data" => [],
        "message" => "No hotels found for: " . htmlspecialchars($hotelName) . ". " . $suggestion
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "data" => $results,
        "count" => count($results)
    ]);
}
?>
