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

        // Update each site's price in the dropdown
        const priceList = card.querySelector('.price-list');
        priceList.innerHTML = '';
        
        for (const [site, price] of Object.entries(hotel.live_prices)) {
            const li = document.createElement('li');
            li.innerHTML = `
                <span class="site-name">${site}</span>
                <span class="price-tag">$${price} <small class="live-pulse">LIVE</small></span>
                <a href="#" class="view-btn">View Deal</a>
            `;
            priceList.appendChild(li);
        }

        // Add a visual indicator for live data if not present
        if (!card.querySelector('.live-status')) {
            const status = document.createElement('div');
            status.className = 'live-status';
            status.innerHTML = '<span class="pulse-dot"></span> Real-time prices';
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

// Load live data on initialization
document.addEventListener('DOMContentLoaded', () => {
    fetchLivePrices();
    
    // Refresh prices every 30 seconds for simulation effect
    setInterval(fetchLivePrices, 30000);
});

