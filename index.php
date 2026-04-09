<?php include 'hotels.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Price Comparison | SkyCompare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/phosphor-icons"></script>
</head>
<body>
    <div class="background-blobs"></div>
    <header>
        <div class="container nav-container">
            <div class="logo">
                <i class="ph-buildings-fill"></i>
                <span>SkyCompare</span>
            </div>
            <nav>
                <a href="#">Home</a>
                <a href="#">Deals</a>
                <a href="#">About</a>
                <div class="currency-selector">
                    <i class="ph-currency-circle-dollar"></i>
                    <select id="currency-select" onchange="changeCurrency(this.value)">
                        <option value="THB" selected>THB (฿)</option>
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="GBP">GBP (£)</option>
                        <option value="JPY">JPY (¥)</option>
                        <option value="SGD">SGD (S$)</option>
                        <option value="AUD">AUD (A$)</option>
                        <option value="INR">INR (₹)</option>
                    </select>
                </div>
                <button class="btn btn-primary">Sign In</button>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-content">
                <h1>Find the Best Hotel Deals Instantly</h1>
                <p>Compare prices across top travel websites and discover the best value for your next stay.</p>
                
                <!-- Search Form -->
                <div class="search-box">
                    <div class="search-input-group">
                        <i class="ph-magnifying-glass"></i>
                        <input type="text" id="hotel-search" placeholder="Search for a hotel..." />
                        <button id="search-btn" class="btn btn-primary">Search</button>
                    </div>
                    <div id="search-loading" style="display:none; text-align: center; margin-top: 10px;">
                        <p>Searching hotels...</p>
                    </div>
                    <div id="search-error" style="display:none; color: #e74c3c; margin-top: 10px; text-align: center;"></div>
                </div>
            </div>
        </section>

        <section class="hotel-list-section">
            <div class="container">
                <div id="search-results-container" style="display:none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2>Search Results</h2>
                        <button id="clear-search-btn" class="btn btn-secondary" style="font-size: 0.9rem; padding: 0.5rem 1rem;">Back to All Hotels</button>
                    </div>
                    <div id="search-results"></div>
                </div>
                
                <div id="default-hotels-container">
                    <div class="hotel-grid">
                    <?php foreach ($hotels as $hotel): ?>
                        <div class="hotel-card" id="hotel-<?php echo $hotel['id']; ?>">
                            <div class="hotel-image" style="background-image: url('<?php echo $hotel['image']; ?>');">
                                <div class="rating-badge">
                                    <i class="ph-star-fill"></i>
                                    <?php echo $hotel['rating']; ?>
                                </div>
                            </div>
                            <div class="hotel-info">
                                <h3 class="hotel-name" onclick="toggleDropdown(<?php echo $hotel['id']; ?>)">
                                    <?php echo $hotel['name']; ?>
                                    <i class="ph-caret-down toggle-icon"></i>
                                </h3>
                                <p class="hotel-location">
                                    <i class="ph-map-pin"></i>
                                    <?php echo $hotel['location']; ?>
                                </p>
                                
                                <div class="dropdown-content" id="dropdown-<?php echo $hotel['id']; ?>">
                                    <div class="comparison-grid">
                                        <div class="price-comparison">
                                            <h4>Current Market Prices</h4>
                                            <ul class="price-list">
                                                <!-- Populated by JS -->
                                            </ul>
                                        </div>
                                        <div class="competitor-comparison">
                                            <div class="competitors-sidebar">
                                                <h4><i class="ph-users-three"></i> Close Competitors</h4>
                                                <div class="comp-mini-list">
                                                    <!-- Populated by JS -->
                                                </div>
                                                <button class="btn btn-secondary comp-btn-full" 
                                                        onclick="openRateShopper(<?php echo $hotel['id']; ?>)">
                                                    <i class="ph-table"></i> Rate Shopper Table
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                </div>
            </div>
        </section>

        <section class="rate-shopper-section" id="rate-shopper-section" style="display: none;">
            <div class="container">
                <div class="rate-shopper-card">
                    <div class="rate-shopper-header">
                        <div class="header-info">
                            <h2 id="shopper-hotel-name">Rate Shopper - JW Marriott</h2>
                            <p><i class="ph-clock-fill"></i> Real-time price comparison across all major OTAs and competitors. <span class="sync-badge">Last Synced: Just now</span></p>
                        </div>
                        <div class="header-actions">
                            <button class="btn btn-export"><i class="ph-file-pdf"></i> PDF</button>
                            <button class="btn btn-export"><i class="ph-file-xls"></i> Excel</button>
                            <button class="btn btn-primary" onclick="closeRateShopper()"><i class="ph-x"></i> Close</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="rate-shopper-table" id="rate-shopper-table">
                            <thead>
                                <tr id="shopper-thead-row">
                                    <!-- Populated by JS -->
                                </tr>
                            </thead>
                            <tbody id="shopper-tbody">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <section class="market-intelligence">
             <div class="container">
                <div class="intel-card">
                    <div class="intel-header">
                        <h2><i class="ph-chart-line-up-fill"></i> Market Intelligence Overview</h2>
                        <p>Real-time performance tracking of Top 10 Industry competitors within 20km.</p>
                    </div>
                    <div class="intel-grid">
                        <div class="intel-item">
                            <span class="intel-label">Total Competitors Tracked</span>
                            <span class="intel-value">40+ Properties</span>
                        </div>
                        <div class="intel-item">
                            <span class="intel-label">Market Sync Frequency</span>
                            <span class="intel-value">60 Seconds</span>
                        </div>
                        <div class="intel-item">
                            <span class="intel-label">Scanning Intensity</span>
                            <span class="intel-value">14+ Global Sources</span>
                        </div>
                    </div>
                </div>
             </div>
        </section>
    </main>

    <!-- Modal for Competitors -->
    <div id="comp-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header">
                <h2>Top 10 Market Competitors</h2>
                <span class="modal-subtitle">Direct competitors within a 20km radius</span>
            </div>
            <div id="competitor-list" class="competitor-grid">
                <!-- Competitors will show up here -->
            </div>
        </div>
    </div>

    <footer>
        <div class="container footer-content">
            <p>&copy; 2026 SkyCompare. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js?v=2.0"></script>
</body>
</html>
