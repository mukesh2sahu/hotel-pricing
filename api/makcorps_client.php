<?php

define('MAKCORPS_RAPIDAPI_KEY', 'd670f89106mshcc7b90f1deaf9d6p158291jsnd7f380c32b4f');
define('MAKCORPS_RAPIDAPI_HOST', 'manthankool-makcorps-hotel-price-comparison-v1.p.rapidapi.com');
define('MAKCORPS_RAPIDAPI_BASE_URL', 'https://' . MAKCORPS_RAPIDAPI_HOST);

function makcorpsRapidApiRequest($path, array $query = [], $method = 'GET', $body = null) {
    $url = rtrim(MAKCORPS_RAPIDAPI_BASE_URL, '/') . '/' . ltrim($path, '/');
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-rapidapi-key: ' . MAKCORPS_RAPIDAPI_KEY,
        'x-rapidapi-host: ' . MAKCORPS_RAPIDAPI_HOST,
        'Content-Type: application/json'
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $response === null || $response === '') {
        return [
            'ok' => false,
            'status' => $httpCode,
            'error' => $curlError ?: 'Empty response from MakCorps RapidAPI'
        ];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'ok' => false,
            'status' => $httpCode,
            'error' => 'Invalid JSON response',
            'raw' => $response
        ];
    }

    if ($httpCode >= 400) {
        $message = null;
        if (is_array($decoded)) {
            $message = $decoded['message'] ?? $decoded['error'] ?? null;
        }

        return [
            'ok' => false,
            'status' => $httpCode,
            'error' => $message ?: ('HTTP ' . $httpCode . ' returned from MakCorps RapidAPI'),
            'data' => $decoded
        ];
    }

    return [
        'ok' => true,
        'status' => $httpCode,
        'data' => $decoded
    ];
}

function makcorpsAuthCheck() {
    return makcorpsRapidApiRequest('auth', [], 'POST', null);
}

function makcorpsMappingSearch($name) {
    return makcorpsRapidApiRequest('mapping', [
        'name' => $name
    ]);
}

function makcorpsHotelComparison($hotelId, $checkIn, $checkOut, $currency = 'USD', $adults = 2, $rooms = 1) {
    return makcorpsRapidApiRequest('hotel', [
        'hotelid' => $hotelId,
        'checkin' => $checkIn,
        'checkout' => $checkOut,
        'cur' => $currency,
        'adults' => $adults,
        'rooms' => $rooms
    ]);
}

function makcorpsNormalizeComparison(array $comparisonRows) {
    $prices = [];

    foreach ($comparisonRows as $group) {
        if (!is_array($group)) {
            continue;
        }

        foreach ($group as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            foreach ($entry as $key => $value) {
                if (!preg_match('/^vendor(\d+)$/', $key, $matches)) {
                    continue;
                }

                $index = $matches[1];
                $vendor = trim((string)$value);
                $rate = makcorpsParseMoneyValue($entry['price' . $index] ?? null);

                if ($vendor === '' || $rate === null) {
                    continue;
                }

                $prices[$vendor] = [
                    'rate' => $rate,
                    'tax' => makcorpsParseMoneyValue($entry['tax' . $index] ?? null),
                    'url' => null
                ];
            }
        }
    }

    return $prices;
}

function makcorpsParseMoneyValue($value) {
    if ($value === null || $value === '') {
        return null;
    }

    $numeric = preg_replace('/[^\d.]/', '', (string)$value);
    if ($numeric === '') {
        return null;
    }

    return (float)$numeric;
}
