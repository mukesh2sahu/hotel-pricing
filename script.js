let hotelLiveData = [];

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
        
        for (const [site, price] of Object.entries(hotel.live_prices)) {
            const li = document.createElement('li');
            li.setAttribute('data-site', site);
            li.innerHTML = `
                <span class="site-name">${site}</span>
                <span class="price-tag">$${price} <small class="live-pulse">LIVE</small></span>
                <a href="#" class="view-btn">View Deal</a>
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
                    <span class="comp-mini-price">$${comp.current_price}</span>
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
 * @param {number} hotelId - The ID of the hotel to toggle.
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
    }
}

/**
 * Wrapper function for the competitor button click.
 * @param {HTMLElement} btn - The button that was clicked.
 */
function handleCompetitorClick(btn) {
    // Get the hotel ID from the parent card
    const card = btn.closest('.hotel-card');
    const hotelId = parseInt(card.id.split('-')[1]);
    
    // Find the hotel in our live data
    const hotel = hotelLiveData.find(h => h.id === hotelId);
    if (hotel) {
        showCompetitors(hotel.competitors);
    }
}

/**
 * Show competitor hotels in a modal.
 * @param {Array} competitors - List of competitor objects with live prices.
 */
function showCompetitors(competitors) {
    const modal = document.getElementById('comp-modal');
    const compGrid = document.getElementById('competitor-list');
    
    // Clear list
    compGrid.innerHTML = '';
    
    // Add competitors to grid
    if (competitors && competitors.length > 0) {
        competitors.forEach(comp => {
            const item = document.createElement('div');
            item.className = 'competitor-item';
            item.innerHTML = `
                <div class="comp-info">
                    <i class="ph-buildings-fill"></i>
                    <span>${comp.name}</span>
                </div>
                <div class="comp-price">
                    $${comp.current_price}
                </div>
            `;
            compGrid.appendChild(item);
        });
    } else {
        compGrid.innerHTML = '<p class="text-muted">No competitors found for this hotel.</p>';
    }
    
    // Show modal
    modal.classList.add('show');
}

// Close modal when clicking X
document.querySelector('.close-modal').addEventListener('click', () => {
    document.getElementById('comp-modal').classList.remove('show');
});

// Close modal when clicking outside content
window.addEventListener('click', (event) => {
    const modal = document.getElementById('comp-modal');
    if (event.target == modal) {
        modal.classList.remove('show');
    }
});

/**
 * Open the Rate Shopper comparison table for a specific hotel.
 * @param {number} hotelId - The ID of the hotel to analyze.
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

    const displayCompetitors = hotel.competitors.slice(0, 7);
    displayCompetitors.forEach((comp, index) => {
        const th = document.createElement('th');
        th.innerText = comp.name; // Show the actual competitor name
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

        // Main Hotel Price for this OTA
        const mainPriceCell = document.createElement('td');
        mainPriceCell.className = 'main-hotel-col';
        let basePrice = hotel.live_prices[ota] || (200 + Math.floor(Math.random() * 100));
        mainPriceCell.innerHTML = `<span class="price-val">$${basePrice}</span>`;
        tr.appendChild(mainPriceCell);

        // Competitor Prices for this OTA
        displayCompetitors.forEach(comp => {
            const compPriceCell = document.createElement('td');
            // Simulate variability across OTAs for competitors
            let compBase = comp.current_price || (180 + Math.floor(Math.random() * 120));
            let variance = (Math.random() * 10 - 5); // +/- $5 variance across OTAs
            let finalPrice = Math.round(compBase + variance);
            
            // Highlight if cheaper than main hotel
            if (finalPrice < basePrice) {
                compPriceCell.className = 'price-lower';
            } else if (finalPrice > basePrice) {
                compPriceCell.className = 'price-higher';
            }

            compPriceCell.innerHTML = `<span class="price-val">$${finalPrice}</span>`;
            tr.appendChild(compPriceCell);
        });

        tbody.appendChild(tr);
    });

    // Show section
    section.style.display = 'block';
    section.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Close the Rate Shopper section.
 */
function closeRateShopper() {
    const section = document.getElementById('rate-shopper-section');
    section.style.display = 'none';
}

// Load live data on initialization
document.addEventListener('DOMContentLoaded', () => {
    fetchLivePrices();
    
    // Refresh prices every 60 seconds for simulation effect
    setInterval(fetchLivePrices, 60000);
});

