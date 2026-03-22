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
                <button class="btn btn-primary">Sign In</button>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-content">
                <h1>Find the Best Hotel Deals Instantly</h1>
                <p>Compare prices across top travel websites and discover the best value for your next stay.</p>
            </div>
        </section>

        <section class="hotel-list-section">
            <div class="container">
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
                                    <div class="price-comparison">
                                        <h4>Price Comparison</h4>
                                        <ul class="price-list">
                                            <?php foreach ($hotel['prices'] as $site => $price): ?>
                                                <li>
                                                    <span class="site-name"><?php echo $site; ?></span>
                                                    <span class="price-tag">$<?php echo $price; ?></span>
                                                    <a href="#" class="view-btn">View Deal</a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <div class="competitors-section">
                                        <button class="btn btn-secondary comp-btn" 
                                                data-competitors='<?php echo htmlspecialchars(json_encode($hotel['competitors']), ENT_QUOTES, 'UTF-8'); ?>'
                                                onclick="handleCompetitorClick(this)">
                                            <i class="ph-users-three"></i>
                                            View Competitor Hotels
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal for Competitors -->
    <div id="comp-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Nearby Competitor Hotels</h2>
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

    <script src="script.js"></script>
</body>
</html>
