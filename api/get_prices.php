<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include __DIR__ . '/../hotels.php';
require_once __DIR__ . '/makcorps_client.php';

$xoteloDown = false;

// ==========================================
// CONFIGURATION
// ==========================================

/**
 * Fetch live rates using MakCorps mapping + hotel comparison.
 */
function fetchMakCorpsRates($hotelName) {
    $checkIn = date('Y-m-d', strtotime('+14 days'));
    $checkOut = date('Y-m-d', strtotime('+15 days'));

    $mappingResponse = makcorpsMappingSearch($hotelName);
    if (!$mappingResponse['ok']) {
        error_log("MakCorps mapping error for '$hotelName': " . $mappingResponse['error']);
        return null;
    }

    $mappingResults = $mappingResponse['data'];
    if (!is_array($mappingResults) || empty($mappingResults)) {
        return null;
    }

    $hotelId = $mappingResults[0]['document_id'] ?? null;
    if (!$hotelId) {
        return null;
    }

    $hotelResponse = makcorpsHotelComparison($hotelId, $checkIn, $checkOut, 'USD', 2, 1);
    if (!$hotelResponse['ok']) {
        error_log("MakCorps hotel error for '$hotelName' [$hotelId]: " . $hotelResponse['error']);
        return null;
    }

    $prices = makcorpsNormalizeComparison($hotelResponse['data']['comparison'] ?? []);
    if (empty($prices)) {
        return null;
    }

    $rates = [];
    foreach ($prices as $provider => $priceData) {
        $rates[] = [
            'provider' => $provider,
            'price' => round((float)$priceData['rate'], 0),
            'url' => $priceData['url'] ?? null
        ];
    }

    return $rates;
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
        $googleRates = fetchMakCorpsRates($hotel['name']);
        
        if ($googleRates) {
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
    "source" => "SkyCompare Engine (MakCorps RapidAPI)"
]);

echo $finalOutput;
?>

