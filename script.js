let hotelLiveData = [];
let currentCurrency = 'THB';

const exchangeRates = {
    'THB': 36.5,
    'USD': 1.0,
    'EUR': 0.92,
    'GBP': 0.79,
    'JPY': 151.4,
    'SGD': 1.35,
    'AUD': 1.53
};

const currencySymbols = {
    'THB': '฿',
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'JPY': '¥',
    'SGD': 'S$',
    'AUD': 'A$'
};

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
    
    if (currentCurrency === 'THB' || currentCurrency === 'JPY') {
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

    // Define OTAs
    const OTAs = [
        "Hotel Website", "Agoda", "Expedia", "Booking.com", "MMT", 
        "Goibibo", "Trip.com", "Ticket.com", "Traveloka", "Hotels.com", 
        "Airbnb", "Hotelbeds.com", "Tripadvisor", "12go.asia"
    ];

    // Create Table Header: Main Hotel + Top 7 Competitors
    const mainHotelHeader = document.createElement('th');
    mainHotelHeader.innerText = 'Main Hotel';
    mainHotelHeader.className = 'main-hotel-col';
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

        // Main Hotel Price + URL logic
        const mainPriceCell = document.createElement('td');
        mainPriceCell.className = 'main-hotel-col';
        
        const otaData = hotel.live_prices[ota];
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

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('currency-select').value = currentCurrency;
    fetchLivePrices();
    setInterval(fetchLivePrices, 60000);
});
