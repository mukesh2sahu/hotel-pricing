// State management
let hotelLiveData = [];
let currentCurrency = 'SGD';
let lastSearchQuery = null;
let refreshCountdown = 60;
let refreshInterval = null;

const exchangeRates = {
    'SGD': 1.0,
    'USD': 0.74,
    'THB': 26.5,
    'INR': 63.4
};

const currencySymbols = {
    'SGD': 'SGD',
    'USD': 'USD',
    'THB': 'THB',
    'INR': '₹'
};

/**
 * Update Dashboard Data
 */
async function refreshDashboard() {
    showLoading(true);
    try {
        const response = await fetch('api/get_prices.php');
        const result = await response.json();
        if (result.status === 'success' && result.data && result.data.length > 0) {
            hotelLiveData = result.data;
            renderDashboard(hotelLiveData[0]);
            resetTimer();
        }
    } catch (error) {
        console.error("Fetch error:", error);
    } finally {
        showLoading(false);
    }
}

/**
 * Smart Refresh - decides what to refresh
 */
async function smartRefresh() {
    if (lastSearchQuery) {
        await performSearch(lastSearchQuery);
    } else {
        await refreshDashboard();
    }
}

/**
 * Timer Logic
 */
function initTimer() {
    if (refreshInterval) clearInterval(refreshInterval);
    
    refreshInterval = setInterval(() => {
        refreshCountdown--;
        
        if (refreshCountdown < 0) {
            refreshCountdown = 60; // Hard reset to prevent negative numbers
        }

        // Update UI
        const timerEl = document.querySelector('.update-timer span');
        if (timerEl) {
            timerEl.innerText = `${refreshCountdown}s`;
        }
        
        if (refreshCountdown === 0) {
            smartRefresh();
            refreshCountdown = 60; // Reset immediately after triggering refresh
        }
    }, 1000);
}

function resetTimer() {
    refreshCountdown = 60;
    const timerEl = document.querySelector('.update-timer span');
    if (timerEl) {
        timerEl.innerText = `60s`;
    }
}

/**
 * Perform Search
 */
async function performSearch(query) {
    if (!query) return;
    lastSearchQuery = query;
    
    showLoading(true);
    try {
        const response = await fetch(`api/search_hotels.php?q=${encodeURIComponent(query)}`);
        const result = await response.json();
        
        if (result.status === 'success' && result.data && result.data.length > 0) {
            // Process data to match dashboard expectations
            hotelLiveData = result.data.map(hotel => {
                // Ensure competitors exist for UI
                if (!hotel.competitors) {
                    hotel.competitors = [
                        { name: 'Competitor A', price: 245, img: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=100&q=80' },
                        { name: 'Competitor B', price: 310, img: 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=100&q=80' },
                        { name: 'Competitor C', price: 198, img: 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=100&q=80' }
                    ];
                }
                
                // Ensure live_prices is consistent
                if (!hotel.live_prices) hotel.live_prices = {};
                
                // If "Hotel Website" is missing, add it
                if (!hotel.live_prices['Hotel Website']) {
                    const firstPrice = Object.values(hotel.live_prices)[0];
                    const baseRate = firstPrice ? firstPrice.rate : 250;
                    hotel.live_prices['Hotel Website'] = {
                        rate: Math.round(baseRate * 0.95),
                        url: '#'
                    };
                }
                
                return hotel;
            });
            
            renderDashboard(hotelLiveData[0]);
            resetTimer();
            
            // Switch to dashboard view if search was performed from another view
            switchView('dashboard', document.querySelector('.nav-link:first-child'));
        } else {
            alert(result.message || "No hotels found. Try a different search term.");
        }
    } catch (error) {
        console.error("Search error:", error);
        alert("An error occurred while searching. Please try again.");
    } finally {
        showLoading(false);
    }
}

/**
 * Render all dashboard components
 */
function renderDashboard(hotel) {
    if (!hotel) return;

    // 1. Update Title and Monitoring Info
    const monitoringText = document.getElementById('monitoring-text');
    if (monitoringText) {
        monitoringText.innerText = `Monitoring ${hotel.name} across ${Object.keys(hotel.live_prices).length} OTA platforms`;
    }

    // 2. Metrics & KPIs
    const yourRate = hotel.live_prices['Hotel Website'] ? hotel.live_prices['Hotel Website'].rate : 378;
    const marketRates = Object.values(hotel.live_prices).map(p => p.rate).filter(r => r > 0);
    const avgRate = marketRates.length > 0 ? marketRates.reduce((a, b) => a + b, 0) / marketRates.length : yourRate;

    updateMetric('your-rate-val', yourRate);
    updateMetric('market-avg-val', avgRate);
    updateMetric('recommended-val', yourRate * 1.06); 
    document.getElementById('parity-issues-val').innerText = Math.floor(Math.random() * 10) + 5;

    // 3. Rate Chart
    const chartContainer = document.getElementById('rate-overview-container');
    chartContainer.innerHTML = '';
    
    const maxVal = Math.max(...marketRates, yourRate);

    Object.entries(hotel.live_prices).forEach(([ota, data]) => {
        const displayName = ota === 'Hotel Website' ? 'Website' : ota;
        const rate = data.rate || 0;
        const width = (rate / maxVal) * 100;
        const diff = ((rate - yourRate) / yourRate * 100).toFixed(1);
        const diffClass = diff >= 0 ? 'trend-up' : 'trend-down';
        const diffPrefix = diff >= 0 ? '+' : '';

        const row = document.createElement('div');
        row.className = 'chart-row';
        row.innerHTML = `
            <div class="ota-info">${displayName}</div>
            <div class="bar-wrapper">
                <div class="bar-fill" style="width: ${width}%"></div>
            </div>
            <div class="price-text">${formatWithSymbol(rate)}</div>
            <div class="trend-text ${diffClass}">${diffPrefix}${diff}%</div>
        `;
        chartContainer.appendChild(row);
    });

    // 4. Alerts
    renderAlerts();

    // 5. Competitors
    renderCompetitors(hotel.competitors);
}

function updateMetric(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    const symbol = currencySymbols[currentCurrency];
    const converted = value * exchangeRates[currentCurrency];
    el.innerText = `${symbol} ${Math.round(converted).toLocaleString()}`;
}

function renderAlerts() {
    const container = document.getElementById('alerts-container');
    container.innerHTML = '';

    const mockAlerts = [
        { type: 'high', icon: 'trend-down', hotel: 'Marina Bay Sands', msg: 'Agoda dropped rate by 12% to SGD 458', range: 'Agoda SGD 520 → SGD 458' },
        { type: 'critical', icon: 'warning', hotel: 'The Grand Palace Hotel', msg: 'Your website rate is 8% higher than Booking.com', range: 'Booking.com SGD 390 → SGD 359' },
        { type: 'medium', icon: 'shield', hotel: 'Raffles Hotel', msg: 'Trip.com showing SGD 645 vs website SGD 680', range: 'Trip.com SGD 680 → SGD 645' },
        { type: 'low', icon: 'users', hotel: 'Fullerton Hotel', msg: '3 competitors reduced rates in last hour', range: 'Multiple' }
    ];

    mockAlerts.forEach(alert => {
        const item = document.createElement('div');
        item.className = `alert-item alert-${alert.type}`;
        item.innerHTML = `
            <div class="alert-icon-box">
                <i class="ph-fill ph-${alert.icon}"></i>
            </div>
            <div class="alert-content">
                <h4>${alert.hotel} <span class="badge" style="font-size: 0.65rem; background:rgba(0,0,0,0.05); padding:2px 6px; border-radius:4px; margin-left:5px">${alert.type}</span></h4>
                <p>${alert.msg}</p>
                <div class="alert-range">${alert.range}</div>
            </div>
            <div class="alert-dot"></div>
        `;
        container.appendChild(item);
    });
}

function renderCompetitors(competitors) {
    const container = document.getElementById('competitors-row-container');
    container.innerHTML = '';
    
    if (!competitors || competitors.length === 0) return;

    competitors.forEach(comp => {
        const card = document.createElement('div');
        card.className = 'comp-card';
        card.innerHTML = `
            <div class="comp-image" style="background-image: url('${comp.img}')"></div>
            <div class="comp-info">
                <h4>${comp.name}</h4>
                <p>${formatWithSymbol(comp.price)}</p>
                <span class="comp-unit">${currentCurrency} avg</span>
            </div>
        `;
        container.appendChild(card);
    });
}

function changeCurrency(val) {
    currentCurrency = val;
    if (hotelLiveData.length > 0) {
        // Find which view is currently active
        const activeView = document.querySelector('.view-section[style*="display: block"]');
        const viewId = activeView ? activeView.id.replace('-view', '') : 'dashboard';
        
        // Re-render the relevant part
        renderDashboard(hotelLiveData[0]);
        
        // If we are in a special view, re-render its specific data
        if (viewId === 'rate-comparison') renderRateComparison();
        if (viewId === 'smart-pricing') renderSmartPricing();
        if (viewId === 'rate-parity') renderRateParity();
        if (viewId === 'properties') renderProperties();
    }
}

function showLoading(show) {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.style.display = show ? 'flex' : 'none';
}

/**
 * Switch between Dashboard and Rate Comparison views
 */
function switchView(viewId, element) {
    // 1. Hide all views
    document.querySelectorAll('.view-section').forEach(view => {
        view.style.display = 'none';
    });

    // 2. Show selected view
    document.getElementById(`${viewId}-view`).style.display = 'block';

    // 3. Update active nav link
    if (element) {
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        element.classList.add('active');
    }

    // 4. Handle specific view rendering
    if (viewId === 'rate-comparison') {
        renderRateComparison();
    } else if (viewId === 'smart-pricing') {
        renderSmartPricing();
    } else if (viewId === 'rate-parity') {
        renderRateParity();
    } else if (viewId === 'heatmap') {
        renderHeatmap();
    } else if (viewId === 'alerts') {
        renderFullAlerts();
    } else if (viewId === 'properties') {
        renderProperties();
    } else if (viewId === 'export') {
        // Simple static view, but can be cleared/reset
    }
}

/**
 * Render Property Management List
 */
function renderProperties() {
    const container = document.getElementById('properties-container');
    container.innerHTML = '';

    if (!hotelLiveData || hotelLiveData.length === 0) return;

    hotelLiveData.forEach(hotel => {
        const card = document.createElement('div');
        card.className = 'panel property-item';
        card.style.flexDirection = 'row';
        card.style.alignItems = 'center';
        card.style.justifyContent = 'space-between';
        card.style.marginBottom = '1.5rem';

        card.innerHTML = `
            <div class="property-main-info" style="display:flex; align-items:center; gap:1.5rem">
                <div class="property-icon-box" style="width:60px; height:60px; background:var(--primary-soft); color:var(--primary); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem">
                    <i class="ph-fill ph-house"></i>
                </div>
                <div>
                    <h3 style="margin:0">${hotel.name}</h3>
                    <p style="color:var(--text-light); margin:0">${hotel.location}</p>
                </div>
            </div>
            <div class="property-stats" style="display:flex; gap:2rem">
                <div style="text-align:center">
                    <span style="font-size:0.75rem; color:var(--text-light); display:block">Monitored OTAs</span>
                    <strong style="font-size:1.1rem">${Object.keys(hotel.live_prices).length}</strong>
                </div>
                <div style="text-align:center">
                    <span style="font-size:0.75rem; color:var(--text-light); display:block">Parity Score</span>
                    <strong style="font-size:1.1rem; color:#10b981">94%</strong>
                </div>
            </div>
            <div class="property-actions" style="display:flex; gap:0.5rem">
                <button class="btn-refresh" style="background:#f3f4f6; color:var(--text-main); font-size:0.8rem">Edit</button>
                <button class="btn-refresh" style="background:#fee2e2; color:#ef4444; font-size:0.8rem">Disable</button>
            </div>
        `;
        container.appendChild(card);
    });
}

/**
 * Render Rate Parity Data
 */
function renderRateParity() {
    const container = document.getElementById('parity-container');
    container.innerHTML = '';

    if (!hotelLiveData || hotelLiveData.length === 0) return;

    const table = document.createElement('table');
    table.className = 'comparison-matrix'; // Reuse matrix styles
    table.innerHTML = `
        <thead>
            <tr>
                <th>Hotel Name</th>
                <th>Website Rate</th>
                <th>Lowest OTA Rate</th>
                <th>Parity Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="parity-tbody"></tbody>
    `;
    container.appendChild(table);
    const tbody = document.getElementById('parity-tbody');

    hotelLiveData.forEach(hotel => {
        const yourRate = hotel.live_prices['Hotel Website'] ? hotel.live_prices['Hotel Website'].rate : 300;
        const otaPrices = Object.entries(hotel.live_prices)
            .filter(([site]) => site !== 'Hotel Website')
            .map(([site, data]) => data.rate);
        const minOta = Math.min(...otaPrices);
        
        const isParityViolated = minOta < yourRate;
        const statusClass = isParityViolated ? 'trend-down' : 'trend-up';
        const statusText = isParityViolated ? 'Violation (OTA Lower)' : 'In Parity';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="font-weight:700">${hotel.name}</td>
            <td style="color:var(--primary); font-weight:700">${formatWithSymbol(yourRate)}</td>
            <td>${formatWithSymbol(minOta)}</td>
            <td><span class="${statusClass}" style="font-weight:700">${statusText}</span></td>
            <td><button class="btn-apply" style="padding: 0.4rem 0.8rem; font-size:0.75rem;">Fix Parity</button></td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Render Market Heatmap
 */
function renderHeatmap() {
    const container = document.getElementById('heatmap-container');
    container.innerHTML = '';

    // Create a 30-day grid
    for (let i = 1; i <= 30; i++) {
        const day = document.createElement('div');
        day.className = 'heatmap-cell';
        // Mock intensity
        const intensity = Math.floor(Math.random() * 100);
        day.style.backgroundColor = `rgba(79, 70, 229, ${intensity / 100})`;
        day.innerHTML = `
            <span class="day-num">${i}</span>
            <span class="intensity-val">${intensity}%</span>
        `;
        day.title = `Day ${i}: ${intensity}% Demand Intensity`;
        container.appendChild(day);
    }
}

/**
 * Render Full Alerts Center
 */
function renderFullAlerts() {
    const container = document.getElementById('alerts-full-container');
    container.innerHTML = '';

    const historicalAlerts = [
        { type: 'critical', hotel: 'The Grand Palace', msg: 'Price mismatch on Booking.com for May 15-20', time: '2 hours ago' },
        { type: 'high', hotel: 'Marina Bay Sands', msg: 'Competitor dropped price by 15%', time: '5 hours ago' },
        { type: 'medium', hotel: 'Fullerton', msg: 'Parity restored on Agoda', time: 'Yesterday' },
        { type: 'critical', hotel: 'Raffles Hotel', msg: 'Rate Parity Violation Detected on Expedia', time: '2 days ago' },
        { type: 'medium', hotel: 'The Grand Palace', msg: 'Market demand surge predicted for next weekend', time: '3 days ago' }
    ];

    historicalAlerts.forEach(alert => {
        const item = document.createElement('div');
        item.className = `alert-item alert-${alert.type}`;
        item.style.marginBottom = '1rem';
        item.innerHTML = `
            <div class="alert-icon-box">
                <i class="ph-fill ph-${alert.type === 'critical' ? 'warning-octagon' : 'warning'}"></i>
            </div>
            <div class="alert-content">
                <h4>${alert.hotel} <small style="color:var(--text-light)">• ${alert.time}</small></h4>
                <p>${alert.msg}</p>
            </div>
            <button class="btn-apply" style="width:auto; padding: 0.4rem 1rem; margin-left:auto">View Details</button>
        `;
        container.appendChild(item);
    });
}

/**
 * Render the Smart Pricing Recommendations
 */
function renderSmartPricing() {
    const container = document.getElementById('smart-pricing-container');
    container.innerHTML = '';

    if (!hotelLiveData || hotelLiveData.length === 0) return;

    hotelLiveData.forEach(hotel => {
        const yourRate = hotel.live_prices['Hotel Website'] ? hotel.live_prices['Hotel Website'].rate : 300;
        const marketRates = Object.values(hotel.live_prices).map(p => p.rate).filter(r => r > 0);
        const avgRate = marketRates.reduce((a, b) => a + b, 0) / marketRates.length;
        
        // Logic for suggestion: if current is lower than avg, suggest increasing slowly.
        // If current is higher, suggest decreasing to stay competitive.
        let suggestion = Math.round(avgRate * 0.98); 
        const diff = suggestion - yourRate;
        const diffText = diff >= 0 ? `+${diff}` : `${diff}`;
        const diffClass = diff >= 0 ? 'text-green' : 'text-red';

        const card = document.createElement('div');
        card.className = 'smart-card';
        card.innerHTML = `
            <div class="smart-card-header">
                <h3>${hotel.name}</h3>
                <span class="conf-badge">98% Confidence</span>
            </div>
            <div class="smart-pricing-body">
                <div class="pricing-stat-box">
                    <span class="label">Current Rate</span>
                    <span class="value">${formatWithSymbol(yourRate)}</span>
                </div>
                <div class="pricing-divider">
                    <i class="ph ph-arrow-right"></i>
                </div>
                <div class="pricing-stat-box highlight">
                    <span class="label">Suggested Rate</span>
                    <span class="value">${formatWithSymbol(suggestion)}</span>
                    <span class="change-tag ${diffClass}">${diffText}</span>
                </div>
            </div>
            <div class="smart-card-details">
                <div class="detail-row">
                    <span>Market Average</span>
                    <span>${formatWithSymbol(avgRate)}</span>
                </div>
                <div class="detail-row">
                    <span>Competitor Avg</span>
                    <span>${formatWithSymbol(avgRate - 10)}</span>
                </div>
            </div>
            <div class="smart-card-actions">
                <button class="btn-apply" onclick="alert('Pricing updated for ${hotel.name}!')">Apply Recommendation</button>
            </div>
        `;
        container.appendChild(card);
    });
}

function formatWithSymbol(val) {
    const symbol = currencySymbols[currentCurrency];
    const converted = val * exchangeRates[currentCurrency];
    return `${symbol} ${Math.round(converted).toLocaleString()}`;
}

/**
 * Render the Rate Comparison Matrix (Excel-style)
 */
function renderRateComparison() {
    const table = document.getElementById('comparison-matrix-table');
    const headerRow = document.getElementById('matrix-header-row');
    const tbody = document.getElementById('matrix-body');
    
    if (!hotelLiveData || hotelLiveData.length === 0) return;

    // OTAs are the rows
    const OTAs = ["Hotel Website", "Agoda", "Booking.com", "Trip.com", "Expedia", "Traveloka", "MakeMyTrip", "Airbnb"];
    
    // Hotels are the columns
    headerRow.innerHTML = '<th>OTA Platforms</th>';
    hotelLiveData.slice(0, 10).forEach(hotel => { // Limit to 10 hotels for performance
        const th = document.createElement('th');
        th.innerText = hotel.name;
        headerRow.appendChild(th);
    });

    tbody.innerHTML = '';
    
    OTAs.forEach(ota => {
        const tr = document.createElement('tr');
        
        // OTA Name Cell
        const otaTd = document.createElement('td');
        otaTd.className = 'ota-name-cell';
        otaTd.innerText = ota;
        tr.appendChild(otaTd);
        
        // Hotel Price Cells
        hotelLiveData.slice(0, 10).forEach(hotel => {
            const td = document.createElement('td');
            const data = hotel.live_prices[ota];
            const rate = data ? data.rate : 0;
            
            if (rate > 0) {
                const symbol = currencySymbols[currentCurrency];
                const converted = rate * exchangeRates[currentCurrency];
                td.innerText = `${symbol} ${Math.round(converted).toLocaleString()}`;
                
                // Highlight logic: if this is the lowest price for this hotel across OTAs
                const pricesForHotel = Object.values(hotel.live_prices).map(p => p.rate).filter(r => r > 0);
                const minForHotel = Math.min(...pricesForHotel);
                if (rate === minForHotel) {
                    td.classList.add('lowest-price-flag');
                }
            } else {
                td.innerText = '-';
            }
            tr.appendChild(td);
        });
        
        tbody.appendChild(tr);
    });
}

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    refreshDashboard();
    initTimer();

    const refreshBtn = document.getElementById('refresh-now');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            smartRefresh();
        });
    }

    const searchBtn = document.getElementById('search-btn');
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            const query = document.getElementById('hotel-search').value;
            if (query) {
                performSearch(query);
                resetTimer();
            }
        });
    }

    // Allow search on Enter key
    const searchInput = document.getElementById('hotel-search');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch(searchInput.value);
            }
        });
    }
});
