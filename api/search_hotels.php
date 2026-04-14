<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/makcorps_client.php';

/**
 * Search hotels from MakCorps RapidAPI using mapping + hotel comparison.
 */
function searchHotels($hotelName, $checkInDaysFromNow = 14) {
    $checkIn = date('Y-m-d', strtotime('+' . $checkInDaysFromNow . ' days'));
    $checkOut = date('Y-m-d', strtotime('+' . ($checkInDaysFromNow + 1) . ' days'));

    $mappingResponse = makcorpsMappingSearch($hotelName);
    if (!$mappingResponse['ok']) {
        error_log("MakCorps mapping error for '$hotelName': " . $mappingResponse['error']);
        return null;
    }

    $mappingResults = $mappingResponse['data'];
    if (!is_array($mappingResults) || empty($mappingResults)) {
        return [];
    }

    $hotels = [];

    foreach (array_slice($mappingResults, 0, 11) as $mappedHotel) {
        if (!is_array($mappedHotel)) {
            continue;
        }

        $hotelId = $mappedHotel['document_id'] ?? null;
        if (!$hotelId) {
            continue;
        }

        $hotelResponse = makcorpsHotelComparison($hotelId, $checkIn, $checkOut, 'USD', 2, 1);
        if (!$hotelResponse['ok']) {
            error_log("MakCorps hotel error for '$hotelName' [$hotelId]: " . $hotelResponse['error']);
            continue;
        }

        $prices = makcorpsNormalizeComparison($hotelResponse['data']['comparison'] ?? []);
        if (empty($prices)) {
            continue;
        }

        $details = $mappedHotel['details'] ?? [];
        $hotels[] = [
            'id' => (string)$hotelId,
            'name' => $mappedHotel['name'] ?? $hotelName,
            'prices' => $prices,
            'source' => 'MakCorps RapidAPI',
            'link' => null,
            'thumbnail' => null,
            'rating' => $details['rating'] ?? null,
            'reviews' => $details['reviews'] ?? null,
            'hotel_class' => $details['hotel_class'] ?? null,
            'amenities' => [],
            'location' => trim(implode(', ', array_filter([
                $details['address'] ?? null,
                $details['parent_name'] ?? null,
                $details['grandparent_name'] ?? null
            ])))
        ];
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
        "message" => "No hotels found for: " . htmlspecialchars($hotelName) . ". " . $suggestion,
        "source" => "MakCorps RapidAPI"
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "data" => $results,
        "count" => count($results),
        "source" => "MakCorps RapidAPI"
    ]);
}
?>
