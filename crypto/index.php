<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>CryptoTrack</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- google fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
        <link rel="icon" href="Homepage/assets/images/favicon.png" type="image/x-icon">
        <link rel="stylesheet" href="Homepage/dist/css/main.css" />
        <style>
            .text-danger {
                color: #d63031; /* Red color for negative changes */
            }
        </style>
    </head>
    <body>
        <div class="page-wrapper bg-dark">
            <!-- header -->
            <div class="header-wrapper">
                <header class="header flex">
                    <div class="container w-full flex-1 flex">
                        <div class="header-content w-full flex-1 flex flex-col">
                            <nav class="navbar flex items-center justify-between">
                                <div class="brand-and-toggler flex items-center justify-between w-full">
                                    <a href="index.php" class="navbar-brand flex items-center">
                                        <img src="Homepage/assets/icons/site_icon.svg">
                                        <span class="brand-text" id="brand-text">CryptoTrack</span>
                                    </a>
                                    <button type="button" class="navbar-show-btn">
                                        <img src="Homepage/assets/icons/menu_icon.svg" />
                                    </button>
                                </div>

                                <div class="navbar-list-wrapper flex items-center">
                                    <ul class="nav-list flex items-center">
                                        <button type="button" class="navbar-hide-btn">
                                            <img src="Homepage/assets/icons/close.svg" />
                                        </button>
                                        <li class="nav-item">
                                            <a href="buy.php" class="nav-link text-base no-wrap">Buy</a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="sell.php" class="nav-link text-base no-wrap">Sell</a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#" class="nav-link text-base no-wrap">Grow</a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#" class="nav-link text-base no-wrap">Market</a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#support" class="nav-link text-base no-wrap">Support</a>
                                        </li>
                                    </ul>

                                    <div class="sign-btns flex items-center">
                                        <a href="login.php" class="btn text-base">Sign in</a>
                                        <a href="register.php" class="btn btn-g-blue-veronica text-base">Sign up</a>
                                    </div>
                                </div>
                            </nav>

                            <div class="header-intro flex-1 flex flex-col items-center justify-center text-center">
                                <h1>We make crypto clear and simple</h1>
                                <a href="register.php" class="btn btn-base btn-g-blue-veronica text-base">Get Started</a>
                            </div>
                        </div>
                    </div>
                </header>
                <div class="info flex items-center justify-center">
                    <div class="container">
                        <div class="info-content grid">
                            <div class="info-item text-center">
                                <img src="Homepage/assets/icons/create_icon.svg" alt="" />
                                <h3 class="info-item-title">Create</h3>
                                <p class="text-base text info-item-text">Set up your crypto portfolio in minutes.
Start building and tracking your assets easily and securely with our intuitive tools.

🔗 Get Started

</p>
                                <a href="#" class="flex-inline items-center btn-link">
                                    <span class="link-text text-lavender text text-base">Get Started</span>
                                    <img src="Homepage/assets/icons/arrow.svg" class="link-icon" />
                                </a>
                            </div>

                            <div class="info-item text-center">
                                <img src="Homepage/assets/icons/login_icon.svg" alt="" />
                                <h3 class="info-item-title">Login</h3>
                                <p class="text-base text info-item-text">👋 Welcome Back!
✨ Log in to:
✅ Access your account
✅ Pick up where you left off
✅ Explore your journey 🚀
💡 Need help? Contact support here: 🔗 Support
Let me know if you'd like to personalize it further! 😊
</p>
                                <a href="login.php" class="flex-inline items-center btn-link">
                                    <span class="link-text text-lavender text text-base">Login</span>
                                    <img src="Homepage/assets/icons/arrow.svg" class="link-icon" />
                                </a>
                            </div>

                            <div class="info-item text-center">
                                <img src="Homepage/assets/icons/manage_icon.svg" alt="" />
                                <h3 class="info-item-title">Manage</h3>
                                <p class="text-base text info-item-text">👛 Manage Your Wallet Effortlessly!
✨ Take control of your finances with ease.
✅ Track your spending
✅ Set goals and budgets
✅ Plan for the future 🚀
Ready to get started?
👉 Tap Get Started below to begin your journey toward smarter financial management!
</p>
                                <a href="register.php" class="flex-inline items-center btn-link">
                                    <span class="link-text text-lavender text text-base">Get Started</span>
                                    <img src="Homepage/assets/icons/arrow.svg" class="link-icon" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end of header -->

            <main>
                <section class="page-sc-one flex items-center justify-center">
                    <div class="container">
                        <div class="sc-one-content text-center">
                            <div class="title-wrapper">
                                <h2 class="large-title">A crypto mining platform that invest in you.</h2>
                                <a href="register.php" class="btn btn-base btn-g-blue-veronica text-base">Get Started</a>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="page-sc-fees flex items-center">
                    <div class="container">
                        <div class="sc-fees-content text-center">
                            <div class="title-wrapper">
                                <h2 class="large-title">Live Market Data</h2>
                                <p class="text text-base">Real-time cryptocurrency prices with simulated updates.</p>
                            </div>

                            <div class="data-table-wrapper">
                                <div class="data-table">
                                    <table class="table" id="cryptoTable">
                                        <thead>
                                            <tr class="grid">
                                                <th class="flex items-center justify-center text-lg">Cryptocurrency</th>
                                                <th class="flex items-center justify-center text-lavender text-lg">Symbol</th>
                                                <th class="flex items-center justify-center text-lg">Price (USD)</th>
                                                <th class="flex items-center justify-center text-lg">24h Change</th>
                                                <th class="flex items-center justify-center text-lg">Graph</th>
                                                <th class="flex items-center justify-center text-lg">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cryptoTableBody">
                                            <!-- Data will be populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="page-sc-invest flex items-center">
                    <div class="container">
                        <div class="sc-two-content">
                            <div class="sc-two-left">
                                <h2 class="large-title">Take your first step into safe, secure crypto investing</h2>
                                <a href="register.php" class="btn btn-base btn-white text-base">Get Started</a>
                            </div>
                            <div class="sc-two-right">
                                <img src="Homepage/assets/images/support.png" />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="page-sc-subscribe">
                    <div class="container">
                        <div class="sc-subscribe-content text-center">
                            <h2 class="large-title" id="support">Receive transmissions</h2>
                            <p class="text text-base">Unsubscribe at any time. <span class="text-white">Privacy policy</span></p>

                            <form class="flex items-center justify-center">
                                <div class="input-group flex items-center justify-between">
                                    <input type="text" class="input-control" placeholder="Email Address">
                                    <button type="submit" class="input-btn flex items-center justify-center">
                                        <img src="Homepage/assets/icons//arrow_white.svg" />
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="footer">
                <div class="container">
                    <div class="footer-content grid">
                        <div class="footer-item">
                            <p class="text text-base">CryptoTrack, a perfect cryptocurrency tracking and management tool, makes it so flipping easy to buy and sell bitcoin via  card, or bank transfer.</p>
                            <p class="text text-base">Sign up to get started.</p>
                            <a href="register.php" class="btn btn-base btn-white text-base">Get Started</a>

                            <form class="flex items-center">
                                <div class="input-group flex items-center justify-between">
                                    <input type="email" class="input-control" placeholder="Email address" />
                                    <button type="submit" class="input-btn flex items-center justify-center">
                                        <img src="Homepage/assets/icons/arrow_white.svg" />
                                    </button>
                                </div>
                            </form>
                            <p class="text text-base"></p>
                        </div>

                        <div class="footer-item">
                        <a href="#brand-text" class="footer-link text-gray text-base">CryptoTrack</a>
                            <ul class="footer-links">
                                <li>
                                    <a href="#" class="footer-link text-gray text-base">About</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            // Store cryptocurrency data
            let cryptoData = JSON.parse(localStorage.getItem('cryptoData')) || [];

            // Function to fetch data from CoinGecko API
            function fetchMarketData() {
                $.ajax({
                    url: 'https://api.coingecko.com/api/v3/coins/markets',
                    method: 'GET',
                    data: {
                        vs_currency: 'usd',
                        ids: 'bitcoin,ethereum,cardano,wax,polkadot',
                        order: 'market_cap_desc',
                        per_page: 5,
                        page: 1,
                        sparkline: false
                    },
                    success: function(data) {
                        cryptoData = data.map(coin => ({
                            name: coin.name,
                            symbol: coin.symbol.toUpperCase(),
                            price: coin.current_price,
                            change: coin.price_change_percentage_24h
                        }));
                        // Store data in localStorage
                        localStorage.setItem('cryptoData', JSON.stringify(cryptoData));
                        updateTable();
                    },
                    error: function(error) {
                        console.error('Error fetching market data:', error);
                        // If fetch fails, use stored data or fallback
                        if (cryptoData.length > 0) {
                            updateTable();
                        }
                    }
                });
            }

            // Function to simulate price updates between API calls
            function simulateMarketUpdates() {
                if (cryptoData.length === 0) return; // Wait until we have data

                cryptoData.forEach(coin => {
                    // Simulate price fluctuation (random change between -1% and +1%)
                    const priceChange = (Math.random() - 0.5) * 2; // -1% to +1%
                    const newPrice = coin.price * (1 + priceChange / 100);
                    coin.price = Math.max(0.01, Number(newPrice.toFixed(2)));

                    // Simulate change percentage fluctuation
                    const changeChange = (Math.random() - 0.5) * 1; // -0.5% to +0.5%
                    let newChange = coin.change + changeChange;
                    newChange = Math.max(-10, Math.min(10, Number(newChange.toFixed(2))));
                    coin.change = newChange;
                });

                updateTable();
            }

            // Function to update the table with current data
            function updateTable() {
                const tableBody = document.getElementById('cryptoTableBody');
                tableBody.innerHTML = '';

                cryptoData.forEach(coin => {
                    const price = coin.price.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
                    const changeClass = coin.change >= 0 ? 'text-mint' : 'text-danger';
                    const row = `
                        <tr class="grid">
                            <td class="flex items-center justify-center text-lg">${coin.name}</td>
                            <td class="flex items-center justify-center text-lavender text-lg">${coin.symbol}</td>
                            <td class="flex items-center justify-center text-lg">${price}</td>
                            <td class="flex items-center justify-center ${changeClass} text-lg">${coin.change.toFixed(2)}%</td>
                            <td class="flex items-center justify-center">
                                <img src="Homepage/assets/images/small-graph1.png" class="graph-img" />
                            </td>
                            <td class="flex items-center justify-center">
                                <a href="#" class="table-link flex items-center">
                                    <span class="link-text no-wrap text-base">Trade Now</span>
                                    <img src="Homepage/assets/icons/arrow_white.svg" class="link-icon" />
                                </a>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            }

            // Fetch fresh data every 60 seconds (to respect CoinGecko rate limits)
            setInterval(fetchMarketData, 60000);
            // Simulate updates every 5 seconds
            setInterval(simulateMarketUpdates, 5000);
            // Initial fetch
            fetchMarketData();
        </script>
    </body>
</html>