<?php include 'hotels.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RateIntel | Hotel Intelligence Dashboard</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body class="dashboard-body">
    <div class="app-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="ph-fill ph-buildings"></i>
                    <div class="brand-info">
                        <span class="brand-name">RateIntel</span>
                        <span class="brand-tagline">HOTEL INTELLIGENCE</span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="javascript:void(0)" class="nav-link active" onclick="switchView('dashboard', this)">
                    <i class="ph-fill ph-squares-four"></i>
                    <span>Dashboard</span>
                </a>
                <a href="javascript:void(0)" class="nav-link" onclick="switchView('rate-comparison', this)">
                    <i class="ph-fill ph-chart-bar"></i>
                    <span>Rate Comparison</span>
                </a>
                <a href="javascript:void(0)" class="nav-link" onclick="switchView('smart-pricing', this)">
                    <i class="ph-fill ph-lightning"></i>
                    <span>Smart Pricing</span>
                </a>
                <a href="javascript:void(0)" class="nav-link" onclick="switchView('rate-parity', this)">
                    <i class="ph-fill ph-arrows-left-right"></i>
                    <span>Rate Parity</span>
                </a>
                <a href="javascript:void(0)" class="nav-link" onclick="switchView('heatmap', this)">
                    <i class="ph-fill ph-grid-nine"></i>
                    <span>Heatmap</span>
                </a>
                <a href="javascript:void(0)" class="nav-link" onclick="switchView('alerts', this)">
                    <i class="ph-fill ph-bell"></i>
                    <span>Alerts</span>
                    <span class="nav-badge">3</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="javascript:void(0)" class="nav-link" onclick="switchView('properties', this)">
                    <i class="ph-fill ph-house"></i>
                    <span>Properties</span>
                </a>
                <a href="javascript:void(0)" class="nav-link" onclick="switchView('export', this)">
                    <i class="ph-fill ph-export"></i>
                    <span>Export</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation -->
            <header class="top-nav">
                <div class="live-status">
                    <span class="pulse-dot"></span>
                    <span>Live - Refreshing every 60s</span>
                </div>
                
                <div class="search-bar">
                    <div class="search-field">
                        <i class="ph ph-magnifying-glass"></i>
                        <input type="text" id="hotel-search" placeholder="Hotel name (e.g. Burj Al Arab)">
                    </div>
                    <div class="search-field">
                        <i class="ph ph-map-pin"></i>
                        <input type="text" id="location-search" placeholder="City or country">
                    </div>
                    <div class="search-field date-field">
                        <i class="ph ph-calendar"></i>
                        <input type="text" id="checkin-date" placeholder="dd/mm/yyyy" onfocus="(this.type='date')">
                    </div>
                    <div class="search-field date-field">
                        <i class="ph ph-calendar"></i>
                        <input type="text" id="checkout-date" placeholder="dd/mm/yyyy" onfocus="(this.type='date')">
                    </div>
                    <button class="search-submit" id="search-btn">
                        <i class="ph ph-magnifying-glass"></i>
                        <span>Search</span>
                    </button>
                </div>
                
                <div class="header-actions">
                    <div class="notification-bell">
                        <i class="ph-fill ph-bell"></i>
                        <span class="bell-dot"></span>
                    </div>
                    <div class="currency-selector">
                        <select id="currency-select" onchange="changeCurrency(this.value)">
                            <option value="SGD">SGD</option>
                            <option value="USD">USD</option>
                            <option value="THB">THB</option>
                            <option value="INR">INR</option>
                        </select>
                        <i class="ph ph-caret-down"></i>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div id="dashboard-view" class="view-section">
                <div class="dashboard-header">
                    <div class="header-left">
                        <h1>Dashboard</h1>
                        <p id="monitoring-text">Monitoring 11 hotels across 14 OTA platforms</p>
                    </div>
                    <div class="header-right">
                        <div class="update-timer">
                            <i class="ph ph-clock"></i>
                            <span>61s</span>
                        </div>
                        <button class="btn-refresh" id="refresh-now">
                            Refresh Now
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card your-rate">
                        <div class="kpi-content">
                            <span class="kpi-label">YOUR RATE</span>
                            <h2 id="your-rate-val">SGD 378</h2>
                            <p class="kpi-trend trend-up">
                                <i class="ph ph-trend-up"></i>
                                1.5% vs 24h ago
                            </p>
                        </div>
                        <div class="kpi-icon">
                            <i class="ph-fill ph-currency-dollar"></i>
                        </div>
                    </div>
                    
                    <div class="kpi-card market-avg">
                        <div class="kpi-content">
                            <span class="kpi-label">MARKET AVG</span>
                            <h2 id="market-avg-val">SGD 390</h2>
                            <p class="kpi-sub">Across all OTAs</p>
                        </div>
                        <div class="kpi-icon">
                            <i class="ph-fill ph-chart-line"></i>
                        </div>
                    </div>
                    
                    <div class="kpi-card recommended">
                        <div class="kpi-content">
                            <span class="kpi-label">RECOMMENDED</span>
                            <h2 id="recommended-val">SGD 401</h2>
                            <p class="kpi-sub">AI-powered suggestion</p>
                        </div>
                        <div class="kpi-icon">
                            <i class="ph-fill ph-sparkle"></i>
                        </div>
                    </div>
                    
                    <div class="kpi-card parity-issues">
                        <div class="kpi-content">
                            <span class="kpi-label">PARITY ISSUES</span>
                            <h2 id="parity-issues-val">12</h2>
                            <p class="kpi-trend trend-down">Needs attention</p>
                        </div>
                        <div class="kpi-icon">
                            <i class="ph-fill ph-warning"></i>
                        </div>
                    </div>
                </div>

                <!-- Charts and Alerts Grid -->
                <div class="main-grid">
                    <!-- Rate Overview -->
                    <div class="panel rate-overview">
                        <div class="panel-header">
                            <h3>Your Property Rate Overview</h3>
                            <a href="#" class="panel-link">View All <i class="ph ph-arrow-right"></i></a>
                        </div>
                        <div class="panel-body">
                            <div class="property-title">The Grand Palace Hotel</div>
                            <div class="rate-chart-container" id="rate-overview-container">
                                <!-- Bars will be injected here -->
                            </div>
                        </div>
                    </div>

                    <!-- Recent Alerts -->
                    <div class="panel recent-alerts">
                        <div class="panel-header">
                            <h3>Recent Alerts <span class="badge" style="background:#ef4444; color:white; font-size:0.65rem; padding:2px 8px; border-radius:20px; font-weight:700; margin-left:8px">3 new</span></h3>
                            <a href="#" class="panel-link">View All <i class="ph ph-arrow-right"></i></a>
                        </div>
                        <div class="panel-body">
                            <div class="alerts-list" id="alerts-container">
                                <!-- Alerts will be injected here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Competitors Section -->
                <div class="competitors-section">
                    <div class="section-header">
                        <h3>Top Competitors</h3>
                        <a href="#" class="panel-link">Full Comparison <i class="ph ph-caret-right"></i></a>
                    </div>
                    <div class="competitors-grid" id="competitors-row-container">
                        <!-- Competitor cards will be injected here -->
                    </div>
                </div>
            </div>

            <!-- Rate Comparison View -->
            <div id="rate-comparison-view" class="view-section" style="display: none;">
                <div class="dashboard-header">
                    <div class="header-left">
                        <h1>Rate Comparison Matrix</h1>
                        <p>Real-time comparison of all monitored properties across all platforms</p>
                    </div>
                    <div class="header-right">
                        <div class="header-actions">
                            <button class="btn-refresh" style="background: #10b981; margin-right: 0.5rem;"><i class="ph ph-file-xls"></i> Export Excel</button>
                            <button class="btn-refresh" onclick="refreshDashboard()">Refresh Data</button>
                        </div>
                    </div>
                </div>
                
                <div class="matrix-container">
                    <div class="table-responsive">
                        <table class="comparison-matrix" id="comparison-matrix-table">
                            <thead>
                                <tr id="matrix-header-row">
                                    <!-- Populated by JS -->
                                </tr>
                            </thead>
                            <tbody id="matrix-body">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Smart Pricing View -->
            <div id="smart-pricing-view" class="view-section" style="display: none;">
                <div class="dashboard-header">
                    <div class="header-left">
                        <h1>Smart Pricing Engine</h1>
                        <p>AI-driven price recommendations based on real-time market data</p>
                    </div>
                </div>
                
                <div class="smart-pricing-grid" id="smart-pricing-container">
                    <!-- Pricing cards will be injected here -->
                </div>
            </div>

            <!-- Rate Parity View -->
            <div id="rate-parity-view" class="view-section" style="display: none;">
                <div class="dashboard-header">
                    <div class="header-left">
                        <h1>Rate Parity Analysis</h1>
                        <p>Analyze price consistency across all OTA platforms and your direct channel</p>
                    </div>
                </div>
                <div class="panel parity-matrix-panel">
                    <div id="parity-container">
                        <!-- Parity violations will be injected here -->
                    </div>
                </div>
            </div>

            <!-- Heatmap View -->
            <div id="heatmap-view" class="view-section" style="display: none;">
                <div class="dashboard-header">
                    <div class="header-left">
                        <h1>Market Heatmap</h1>
                        <p>Visualizing pricing intensity and demand forecast for the next 30 days</p>
                    </div>
                </div>
                <div class="panel heatmap-panel">
                    <div id="heatmap-container" class="heatmap-grid">
                        <!-- Heatmap cells will be injected here -->
                    </div>
                    <div class="heatmap-legend">
                        <span>Low Intensity</span>
                        <div class="legend-bar"></div>
                        <span>High Intensity</span>
                    </div>
                </div>
            </div>

            <!-- Alerts View -->
            <div id="alerts-view" class="view-section" style="display: none;">
                <div class="dashboard-header">
                    <div class="header-left">
                        <h1>Global Alerts Center</h1>
                        <p>Track all price drops, parity violations, and competitor strategy shifts</p>
                    </div>
                </div>
                <div class="alerts-full-list" id="alerts-full-container">
                    <!-- Historical alerts will be injected here -->
                </div>
            </div>

            <!-- Properties Management View -->
            <div id="properties-view" class="view-section" style="display: none;">
                <div class="dashboard-header">
                    <div class="header-left">
                        <h1>Property Management</h1>
                        <p>Manage your hotel portfolio and monitoring settings</p>
                    </div>
                    <div class="header-right">
                        <button class="btn-refresh" style="background:#10b981"><i class="ph ph-plus"></i> Add New Property</button>
                    </div>
                </div>
                <div class="properties-grid" id="properties-container">
                    <!-- Property cards will be injected here -->
                </div>
            </div>

            <!-- Export Data View -->
            <div id="export-view" class="view-section" style="display: none;">
                <div class="dashboard-header">
                    <div class="header-left">
                        <h1>Export & Reports</h1>
                        <p>Generate intelligence reports for stakeholders</p>
                    </div>
                </div>
                <div class="export-options-grid">
                    <div class="panel export-card">
                        <i class="ph ph-file-pdf"></i>
                        <h3>PDF Executive Summary</h3>
                        <p>Complete dashboard overview with charts</p>
                        <button class="btn-refresh" onclick="alert('PDF Generated!')">Generate PDF</button>
                    </div>
                    <div class="panel export-card">
                        <i class="ph ph-file-xls"></i>
                        <h3>Excel Price Matrix</h3>
                        <p>Raw pricing data across all OTAs</p>
                        <button class="btn-refresh" style="background:#10b981" onclick="alert('Excel Exported!')">Download Excel</button>
                    </div>
                    <div class="panel export-card">
                        <i class="ph ph-file-csv"></i>
                        <h3>CSV Parity Log</h3>
                        <p>Historical parity violation records</p>
                        <button class="btn-refresh" style="background:#64748b" onclick="alert('CSV Downloaded!')">Download CSV</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
