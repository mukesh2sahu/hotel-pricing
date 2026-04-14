<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include __DIR__ . '/../hotels.php';

$xoteloDown = false;

// ==========================================
// CONFIGURATION
// ==========================================

define('SERP_API_KEY', 'a67d0d35664e07f1bc469e5d69bdf20c95287fc99eda0c02b5da1e92589b0185'); // Get key at serpapi.com for Google Hotels API


/**
 * Fetch Live Rates using Google Hotels API (via SerpApi)
 */
function fetchGoogleHotelsRates($hotelName) {
    if (!defined('SERP_API_KEY') || empty(SERP_API_KEY)) {
        return null; // Fallback
    }
    
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
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if (!$result) {
        error_log("SerpApi Error for '$hotelName': cURL failed - $curlError (HTTP $httpCode)");
        return null;
    }
    
    $data = json_decode($result, true);
    
    // Check for API errors
    if (isset($data['error'])) {
        error_log("SerpApi Error for '$hotelName': " . $data['error']);
        return null;
    }
    
    $rates = [];
    
    // First, try to get prices from the ads section (most reliable with provider names)
    if (isset($data['ads']) && is_array($data['ads'])) {
        foreach ($data['ads'] as $ad) {
            if (isset($ad['price']) && isset($ad['source'])) {
                // Extract numeric price from string like "$89"
                $priceStr = str_replace('$', '', (string)$ad['price']);
                $price = (float)$priceStr;
                
                $rates[] = [
                    'provider' => $ad['source'],
                    'price' => round($price, 0),
                    'url' => $ad['link'] ?? null
                ];
            }
        }
    }
    
    // If no ads found, try properties section
    if (empty($rates) && isset($data['properties']) && is_array($data['properties'])) {
        foreach (array_slice($data['properties'], 0, 3) as $property) {
            if (isset($property['rate_per_night']['extracted_lowest'])) {
                $price = (float)$property['rate_per_night']['extracted_lowest'];
                
                $rates[] = [
                    'provider' => 'Google Hotels',
                    'price' => round($price, 0),
                    'url' => $property['link'] ?? null
                ];
            }
        }
    }
    
    return count($rates) > 0 ? $rates : null;
}


$hotelId = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : null;

$targetSites = [
    "Hotel Website", "Agoda", "Expedia", "Booking.com", "MMT", "Goibibo", 
    "Trip.com", "Ticket.com", "Traveloka", "Hotels.com", "Airbnb", 
    "Hotelbeds.com", "Tripadvisor", "12go.asia"
];

$response = [];
foreach ($hotels as $hotel) {
    if ($hotelId === null || $hotel['id'] === $hotelId) {
        
        $prices = [];
        $googleRates = fetchGoogleHotelsRates($hotel['name']);
        
        if ($googleRates) {
            // Map Google Hotels API rates (via Serpapi)
            foreach ($googleRates as $rate) {
                $siteName = ucfirst($rate['provider']);
                if (stripos($siteName, 'Booking') !== false) $siteName = 'Booking.com';
                if (stripos($siteName, 'Expedia') !== false) $siteName = 'Expedia';
                if (stripos($siteName, 'Agoda') !== false) $siteName = 'Agoda';
                if (stripos($siteName, 'Hotels.com') !== false) $siteName = 'Hotels.com';
                if (stripos($siteName, 'Trip') !== false) $siteName = 'Trip.com';
                
                $prices[$siteName] = [
                    'rate' => round((float)$rate['price'], 0),
                    'url' => $rate['url'] ?? null
                ];
            }
        }

        // Only proceed if we have real API data
        if (empty($prices)) {
            // Skip hotels without real API data
            continue;
        }

        // Use only real API prices - no fallback/mock data
        $finalPrices = [];
        foreach ($targetSites as $site) {
            if (isset($prices[$site])) {
                $finalPrices[$site] = $prices[$site];
            }
        }
        
        // Only add URLs for the prices we actually have from API
        foreach ($finalPrices as $site => &$priceData) {
            if (empty($priceData['url'])) {
                $searchName = urlencode($hotel['name']);
                switch($site) {
                    case 'Agoda': 
                        $priceData['url'] = "https://www.agoda.com/search?asq=" . $searchName;
                        break;
                    case 'Booking.com': 
                        $priceData['url'] = "https://www.booking.com/searchresults.html?ss=" . $searchName;
                        break;
                    case 'Expedia': 
                        $priceData['url'] = "https://www.expedia.com/Hotel-Search?filtering=none&regionId=0&searchText=" . $searchName;
                        break;
                    case 'Tripadvisor': 
                        $priceData['url'] = "https://www.tripadvisor.com/Search?q=" . $searchName;
                        break;
                    case 'Hotel Website':
                        $priceData['url'] = "#";
                        break;
                    default:
                        $priceData['url'] = "https://www.google.com/search?q=" . $searchName . "+" . urlencode($site);
                }
            }
        }
        unset($priceData);
        
        // Build response with real API data only
        $hotelData = [
            "id" => $hotel['id'],
            "name" => $hotel['name'],
            "location" => $hotel['location'],
            "live_prices" => $finalPrices,
            "competitors" => []
        ];

        $response[] = $hotelData;
    }
}

$finalOutput = json_encode([
    "status" => "success",
    "timestamp" => date('Y-m-d H:i:s'),
    "data" => $response,
    "source" => "SkyCompare Engine (Real API Data)"
]);

echo $finalOutput;
?>

