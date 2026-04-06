let hotelLiveData = [];
let currentCurrency = 'THB';

const exchangeRates = {
    'THB': 36.5,
    'USD': 1.0,
    'EUR': 0.92,
    'GBP': 0.79,
    'JPY': 151.4,
    'SGD': 1.35,
    'AUD': 1.53,
    'INR': 83.2
};

const currencySymbols = {
    'THB': '฿',
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'JPY': '¥',
    'SGD': 'S$',
    'AUD': 'A$',
    'INR': '₹'
};

/**
 * Toggle the mobile navigation menu.
 */
function toggleMobileMenu() {
    const nav = document.querySelector('nav');
    nav.classList.toggle('show');
}

/**
 * Handle currency change.
 */
function changeCurrency(currency) {
    currentCurrency = currency;
    updatePricesInUI();

    // If rate shopper is open, refresh it
    const section = document.getElementById('rate-shopper-section');
    if (section && section.style.display !== 'none') {
        const hotelNameElement = document.getElementById('shopper-hotel-name');
        const hotelName = hotelNameElement.innerText.replace('Rate Shopper - ', '');
        const hotel = hotelLiveData.find(h => h.name === hotelName);
        if (hotel) openRateShopper(hotel.id);
    }
}

/**
 * Format price based on current currency.
 */
function formatPrice(usdPrice) {
    if (usdPrice === 'N/A' || isNaN(usdPrice)) return 'N/A';

    const converted = usdPrice * exchangeRates[currentCurrency];
    const symbol = currencySymbols[currentCurrency];

    if (currentCurrency === 'THB' || currentCurrency === 'JPY' || currentCurrency === 'INR') {
        return `${symbol}${Math.round(converted).toLocaleString()}`;
    }
    return `${symbol}${(Math.round(converted * 100) / 100).toFixed(2)}`;
}

/**
 * Fetch "Live" pricing data from our API.
 */
async function fetchLivePrices() {
    try {
        const response = await fetch('api/get_prices.php');
        const result = await response.json();
        if (result.status === 'success') {
            hotelLiveData = result.data;
            updatePricesInUI();
        }
    } catch (error) {
        console.error("Failed to fetch live prices:", error);
    }
}

/**
 * Update the DOM with newly fetched "live" prices.
 */
function updatePricesInUI() {
    hotelLiveData.forEach(hotel => {
        const card = document.getElementById(`hotel-${hotel.id}`);
        if (!card) return;

        // Update each site's price in the main grid
        const priceList = card.querySelector('.price-list');
        priceList.innerHTML = '';

        for (const [site, data] of Object.entries(hotel.live_prices)) {
            // Ensure data is the object {rate, url}
            const price = (data && typeof data === 'object') ? data.rate : data;
            const url = (data && typeof data === 'object') ? data.url : '#';

            const li = document.createElement('li');
            li.setAttribute('data-site', site);
            li.style.cursor = 'pointer';
            li.setAttribute('title', `Book on ${site}`);
            li.onclick = () => window.open(url, '_blank');

            li.innerHTML = `
                <span class="site-name">${site}</span>
                <span class="price-tag">${formatPrice(price)} <small class="live-pulse">LIVE</small></span>
                <a href="${url}" target="_blank" class="view-btn" onclick="event.stopPropagation()">View Deal</a>
            `;
            priceList.appendChild(li);
        }

        // Update competitor mini-list
        const compList = card.querySelector('.comp-mini-list');
        if (compList) {
            compList.innerHTML = '';
            hotel.competitors.forEach(comp => {
                const item = document.createElement('div');
                item.className = 'comp-mini-item';
                item.innerHTML = `
                    <span class="comp-mini-name">${comp.name}</span>
                    <span class="comp-mini-price">${formatPrice(comp.current_price)}</span>
                `;
                compList.appendChild(item);
            });
        }

        // Add a visual indicator for live data if not present
        if (!card.querySelector('.live-status')) {
            const status = document.createElement('div');
            status.className = 'live-status';
            status.innerHTML = '<span class="pulse-dot"></span> Real-time prices synced';
            card.querySelector('.hotel-image').appendChild(status);
        }
    });
}

/**
 * Toggle the price comparison dropdown for a specific hotel.
 */
function toggleDropdown(hotelId) {
    const card = document.getElementById(`hotel-${hotelId}`);
    const isAlreadyActive = card.classList.contains('active');

    // Close other dropdowns
    document.querySelectorAll('.hotel-card').forEach(c => {
        c.classList.remove('active');
    });

    // Toggle current
    if (!isAlreadyActive) {
        card.classList.add('active');
        // Smooth scroll on mobile
        if (window.innerWidth < 768) {
            setTimeout(() => {
                card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);
        }
    }
}

/**
 * Open the Rate Shopper comparison table for a specific hotel.
 */
function openRateShopper(hotelId) {
    const hotel = hotelLiveData.find(h => h.id === hotelId);
    if (!hotel) return;

    const section = document.getElementById('rate-shopper-section');
    const hotelNameElem = document.getElementById('shopper-hotel-name');
    const theadRow = document.getElementById('shopper-thead-row');
    const tbody = document.getElementById('shopper-tbody');

    hotelNameElem.innerText = `Rate Shopper - ${hotel.name}`;

    // Clear previous table content
    theadRow.innerHTML = '<th>OTA Name</th>';
    tbody.innerHTML = '';

    // ADDED: New Metric Headers
    const visitorHeader = document.createElement('th');
    visitorHeader.className = 'metric-header';
    visitorHeader.innerHTML = `
        <div class="th-wrapper">
            <i class="ph-users-fill"></i>
            <div class="th-label">
                <span class="main-label">Visitors</span>
                <span class="sub-label">Yesterday</span>
            </div>
        </div>
    `;
    theadRow.appendChild(visitorHeader);

    const bookingHeader = document.createElement('th');
    bookingHeader.className = 'metric-header';
    bookingHeader.innerHTML = `
        <div class="th-wrapper">
            <i class="ph-shopping-cart-fill"></i>
            <div class="th-label">
                <span class="main-label">Bookings</span>
                <span class="sub-label">Yesterday</span>
            </div>
        </div>
    `;
    theadRow.appendChild(bookingHeader);

    // Define OTAs
    const OTAs = [
        "Hotel Website", "Agoda", "Expedia", "Booking.com", "MMT",
        "Goibibo", "Trip.com", "Ticket.com", "Traveloka", "Hotels.com",
        "Airbnb", "Hotelbeds.com", "Tripadvisor", "12go.asia"
    ];

    // Create Table Header: Main Hotel + Top 7 Competitors
    const mainHotelHeader = document.createElement('th');
    mainHotelHeader.innerText = 'Main Hotel Price';
    mainHotelHeader.className = 'main-hotel-col main-header';
    theadRow.appendChild(mainHotelHeader);

    const displayCompetitors = hotel.competitors;
    displayCompetitors.forEach(comp => {
        const th = document.createElement('th');
        th.innerText = comp.name;
        th.className = 'comp-header';
        theadRow.appendChild(th);
    });

    // Populate Table Rows
    OTAs.forEach(ota => {
        const tr = document.createElement('tr');

        // OTA Name Cell
        const otaNameCell = document.createElement('td');
        otaNameCell.className = 'ota-name-cell';
        otaNameCell.innerText = ota;
        tr.appendChild(otaNameCell);

        const otaData = hotel.live_prices[ota];
        const visitors = otaData ? otaData.visitors_yesterday || '-' : '-';
        const bookings = otaData ? otaData.bookings_yesterday || '-' : '-';

        // Visitor Metric Cell
        const visitorCell = document.createElement('td');
        visitorCell.className = 'metric-cell visitor-count';
        visitorCell.innerHTML = `<span>${visitors}</span>`;
        tr.appendChild(visitorCell);

        // Booking Metric Cell
        const bookingCell = document.createElement('td');
        bookingCell.className = 'metric-cell booking-count';
        bookingCell.innerHTML = `<span>${bookings}</span>`;
        tr.appendChild(bookingCell);

        // Main Hotel Price + URL logic
        const mainPriceCell = document.createElement('td');
        mainPriceCell.className = 'main-hotel-col';

        const price = (otaData && typeof otaData === 'object') ? otaData.rate : otaData;
        const url = (otaData && typeof otaData === 'object') ? otaData.url : '#';

        mainPriceCell.innerHTML = `
            <span class="price-val" style="cursor:pointer; color:inherit; text-decoration:underline;" 
                  onclick="window.open('${url}', '_blank')">
                ${formatPrice(price)}
            </span>
        `;
        tr.appendChild(mainPriceCell);

        // Competitors logic
        displayCompetitors.forEach(comp => {
            const compPriceCell = document.createElement('td');
            // Mock price if not exists
            let compBase = comp.current_price || (180 + Math.floor(Math.random() * 120));
            // Slight variance across OTAs
            let variance = (Math.random() * 10 - 5);
            let finalPrice = Math.round(compBase + variance);

            if (finalPrice < price) {
                compPriceCell.className = 'price-lower';
            } else if (finalPrice > price) {
                compPriceCell.className = 'price-higher';
            }

            compPriceCell.innerHTML = `<span class="price-val">${formatPrice(finalPrice)}</span>`;
            tr.appendChild(compPriceCell);
        });

        tbody.appendChild(tr);
    });

    section.style.display = 'block';
    section.scrollIntoView({ behavior: 'smooth' });
}

function closeRateShopper() {
    document.getElementById('rate-shopper-section').style.display = 'none';
}

/**
 * Search for hotels from SerpApi
 */
async function searchHotels(hotelName) {
    if (!hotelName || hotelName.trim().length < 2) {
        showSearchError('Hotel name must be at least 2 characters');
        return;
    }

    showSearchLoading(true);
    hideSearchError();

    try {
        const response = await fetch(`api/search_hotels.php?q=${encodeURIComponent(hotelName)}`);
        const result = await response.json();
        
        showSearchLoading(false);

        if (result.status === 'success' && result.data && result.data.length > 0) {
            displaySearchResults(result.data);
        } else {
            showSearchError(`No hotels found for "${hotelName}"`);
        }
    } catch (error) {
        showSearchLoading(false);
        showSearchError('Error searching hotels. Please try again.');
        console.error('Search error:', error);
    }
}

/**
 * Display search results on the page with main hotel + competitors table
 */
function displaySearchResults(hotels) {
    const container = document.getElementById('search-results');
    const resultsSection = document.getElementById('search-results-container');
    const defaultHotels = document.getElementById('default-hotels-container');
    
    if (!container || !resultsSection) {
        console.error('Search result containers not found');
        return;
    }
    
    container.innerHTML = '';
    
    // Hide default hotels and show results
    if (defaultHotels) defaultHotels.style.display = 'none';
    resultsSection.style.display = 'block';
    resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

    if (!hotels || hotels.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: var(--text-muted);">No hotels found</p>';
        return;
    }

    // Main searched hotel
    const mainHotel = hotels[0];
    const mainHotelCard = document.createElement('div');
    mainHotelCard.className = 'search-result-main-card';
    
    const thumbnailUrl = mainHotel.thumbnail || 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 200"%3E%3Crect fill="%23ddd" width="400" height="200"/%3E%3Ctext x="50%25" y="50%25" font-size="18" text-anchor="middle" dy=".3em" fill="%23999"%3ENo Image%3C/text%3E%3C/svg%3E';
    
    let amenitiesHtml = '';
    if (mainHotel.amenities && Array.isArray(mainHotel.amenities) && mainHotel.amenities.length > 0) {
        const amenities = mainHotel.amenities.slice(0, 5);
        amenitiesHtml = amenities.map(a => {
            const escaped = (a || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return `<span class="amenity-tag">${escaped}</span>`;
        }).join('');
    }
    
    const ratingHtml = mainHotel.rating ? `<div class="rating-display">⭐ ${mainHotel.rating} • ${mainHotel.reviews || 0} reviews</div>` : '';
    const hotelName = (mainHotel.name || 'Hotel').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const source = (mainHotel.source || 'Unknown').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const price = formatPrice(parseFloat(mainHotel.price) || 0);
    const link = (mainHotel.link || '#');
    const linkAttr = link.startsWith('http') ? link : '#';
    
    mainHotelCard.innerHTML = `
        <div style="display: grid; grid-template-columns: 350px 1fr; gap: 2rem; align-items: start;">
            <div>
                <div class="result-thumbnail" style="background-image: url('${thumbnailUrl}'); height: 250px; margin-bottom: 1rem;"></div>
                <a href="${linkAttr}" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="width: 100%; text-align: center;">
                    Book Now
                </a>
            </div>
            <div>
                <h2 style="margin: 0 0 0.5rem 0; font-size: 1.8rem;">${hotelName}</h2>
                <div class="result-source" style="margin-bottom: 0.5rem;">📌 ${source}</div>
                ${ratingHtml}
                <div class="result-price" style="font-size: 2.2rem; margin: 1rem 0;">
                    ${price} <span style="font-size: 1rem; color: var(--text-muted);">per night</span>
                </div>
                <div class="result-amenities" style="margin-bottom: 1rem;">
                    ${amenitiesHtml}
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(mainHotelCard);

    // Competitors table (top 10)
    const competitors = hotels.slice(1, 11);
    if (competitors.length > 0) {
        const tableContainer = document.createElement('div');
        tableContainer.className = 'competitors-table-container';
        tableContainer.innerHTML = `
            <h3 style="margin-top: 3rem; margin-bottom: 1rem; font-size: 1.5rem;">Top Competitors (${competitors.length} hotels)</h3>
            <div class="table-wrapper">
                <table class="competitors-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Hotel Name</th>
                            <th style="width: 20%;">Price</th>
                            <th style="width: 15%;">Rating</th>
                            <th style="width: 15%;">Reviews</th>
                            <th style="width: 15%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        `;

        const tbody = tableContainer.querySelector('tbody');
        
        competitors.forEach((hotel, index) => {
            const row = document.createElement('tr');
            const hotelPrice = formatPrice(parseFloat(hotel.price) || 0);
            const competitorName = (hotel.name || 'N/A').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const competitorLink = hotel.link && hotel.link.startsWith('http') ? hotel.link : '#';
            const ratingDisplay = hotel.rating ? `${hotel.rating} ⭐` : 'N/A';
            const reviewsDisplay = hotel.reviews || 'N/A';
            
            row.innerHTML = `
                <td style="font-weight: 500;">${competitorName}</td>
                <td style="font-size: 1.1rem; font-weight: 600; color: var(--primary);">${hotelPrice}</td>
                <td>${ratingDisplay}</td>
                <td>${reviewsDisplay}</td>
                <td>
                    <a href="${competitorLink}" target="_blank" rel="noopener noreferrer" class="btn btn-small" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                        View
                    </a>
                </td>
            `;
            tbody.appendChild(row);
        });

        container.appendChild(tableContainer);
    }
}

/**
 * Show/hide search loading state
 */
function showSearchLoading(show) {
    const loadingDiv = document.getElementById('search-loading');
    if (loadingDiv) {
        loadingDiv.style.display = show ? 'block' : 'none';
    }
}

/**
 * Show search error message
 */
function showSearchError(message) {
    const errorDiv = document.getElementById('search-error');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
}

/**
 * Hide search error message
 */
function hideSearchError() {
    const errorDiv = document.getElementById('search-error');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

/**
 * Clear search results and show default hotels
 */
function clearSearch() {
    const resultsSection = document.getElementById('search-results-container');
    const defaultHotels = document.getElementById('default-hotels-container');
    const hotelSearch = document.getElementById('hotel-search');
    
    if (hotelSearch) hotelSearch.value = '';
    if (resultsSection) resultsSection.style.display = 'none';
    if (defaultHotels) defaultHotels.style.display = 'block';
    hideSearchError();
    showSearchLoading(false);
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('currency-select').value = currentCurrency;
    fetchLivePrices();
    setInterval(fetchLivePrices, 60000);
    
    // Setup search event listeners
    const searchBtn = document.getElementById('search-btn');
    const hotelSearch = document.getElementById('hotel-search');
    const clearSearchBtn = document.getElementById('clear-search-btn');
    
    if (searchBtn && hotelSearch) {
        searchBtn.addEventListener('click', () => {
            if(hotelSearch.value.trim()) {
                searchHotels(hotelSearch.value.trim());
            }
        });
    }
    
    if (hotelSearch) {
        hotelSearch.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                if(hotelSearch.value.trim()) {
                    searchHotels(hotelSearch.value.trim());
                }
            }
        });
    }
    
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', clearSearch);
    }
});
