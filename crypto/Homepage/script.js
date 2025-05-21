// script.js - Comprehensive fixes for crypto selector and all features

// CoinGecko API endpoints
const COIN_MARKET_API =
  "https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=10&page=1&sparkline=false&price_change_percentage=24h,7d";
const COIN_DETAIL_API = "https://api.coingecko.com/api/v3/coins/";

// Cache keys for localStorage
const CACHE_KEY = "crypto_market_data";
const CACHE_EXPIRY = 5 * 60 * 1000; // 5 minutes

// Rate limiting for API calls
let lastApiCall = 0;
const minInterval = 1000; // 1 second
async function fetchWithRateLimit(url, retries = 3, delay = 2000) {
  const now = Date.now();
  if (now - lastApiCall < minInterval) {
    await new Promise((resolve) =>
      setTimeout(resolve, minInterval - (now - lastApiCall))
    );
  }
  lastApiCall = Date.now();
  try {
    const response = await fetch(url);
    if (response.status === 429 && retries > 0) {
      console.warn("Rate limit exceeded, retrying after delay...");
      await new Promise((resolve) => setTimeout(resolve, delay));
      return fetchWithRateLimit(url, retries - 1, delay * 2); // Exponential backoff
    }
    if (!response.ok) throw new Error(`API error: ${response.status}`);
    return response;
  } catch (error) {
    throw error;
  }
}

// Cache API responses
function getCachedData(key) {
  const cached = localStorage.getItem(key);
  if (!cached) return null;
  const { data, timestamp } = JSON.parse(cached);
  if (Date.now() - timestamp > CACHE_EXPIRY) {
    localStorage.removeItem(key);
    return null;
  }
  return data;
}

function setCachedData(key, data) {
  localStorage.setItem(key, JSON.stringify({ data, timestamp: Date.now() }));
}

// Fallback coin list (removed Solana)
const fallbackCoins = [
  {
    id: "bitcoin",
    name: "Bitcoin",
    symbol: "BTC",
    image: "/assets/images/default-crypto.png",
  },
  {
    id: "ethereum",
    name: "Ethereum",
    symbol: "ETH",
    image: "/assets/images/default-crypto.png",
  },
  {
    id: "dogecoin",
    name: "Dogecoin",
    symbol: "DOGE",
    image: "/assets/images/default-crypto.png",
  },
];

// Format numbers with appropriate suffixes
function formatNumber(num, decimals = 2) {
  if (num === undefined || num === null) return "N/A";
  if (num >= 1e12) return "$" + (num / 1e12).toFixed(decimals) + "T";
  if (num >= 1e9) return "$" + (num / 1e9).toFixed(decimals) + "B";
  if (num >= 1e6) return "$" + (num / 1e6).toFixed(decimals) + "M";
  if (num >= 1e3) return "$" + (num / 1e3).toFixed(decimals) + "K";
  return "$" + num.toFixed(decimals);
}

// Format date as "Month Day, Hour:Minute"
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

// Calculate time difference in human-readable format
function getTimeDifference(dateString) {
  const date = new Date(dateString);
  const now = new Date();
  const diff = Math.floor((now - date) / 1000);

  if (diff < 60) return `${diff} seconds ago`;
  if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`;
  return `${Math.floor(diff / 86400)} days ago`;
}

// Sample transactions data - replace with API call (removed Solana)
const transactionsData = [
  {
    id: 1,
    type: "buy",
    crypto: "Bitcoin",
    symbol: "BTC",
    amount: 0.05,
    price: 67245.32,
    date: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
    usdValue: 3362.27,
  },
  {
    id: 2,
    type: "sell",
    crypto: "Ethereum",
    symbol: "ETH",
    amount: 0.5,
    price: 3100.75,
    date: new Date(Date.now() - 8 * 60 * 60 * 1000).toISOString(),
    usdValue: 1550.38,
  },
  {
    id: 3,
    type: "buy",
    crypto: "Dogecoin",
    symbol: "DOGE",
    amount: 1000,
    price: 0.14,
    date: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString(),
    usdValue: 140.0,
  },
];

// Fetch market data from CoinGecko API
async function fetchCryptoData() {
  try {
    const cachedData = getCachedData(CACHE_KEY);
    if (cachedData) return cachedData;

    const response = await fetchWithRateLimit(COIN_MARKET_API);
    const data = await response.json();
    setCachedData(CACHE_KEY, data);
    return data;
  } catch (error) {
    console.error("Error fetching cryptocurrency data:", error);
    return getCachedData(CACHE_KEY) || fallbackCoins;
  }
}

// Fetch historical price data for a specific coin
async function fetchHistoricalData(coinId, days = 1) {
  try {
    const response = await fetchWithRateLimit(
      `${COIN_DETAIL_API}${coinId}/market_chart?vs_currency=usd&days=${days}`
    );
    return await response.json();
  } catch (error) {
    console.error("Error fetching historical data:", error);
    return { prices: [] };
  }
}

// Fetch transactions (placeholder for real API)
async function fetchTransactions() {
  try {
    // Replace with actual API endpoint
    // const response = await fetch('/api/transactions');
    // if (!response.ok) throw new Error('Failed to fetch transactions');
    // return await response.json();
    return transactionsData; // Fallback to sample data
  } catch (error) {
    console.error("Error fetching transactions:", error);
    return transactionsData;
  }
}

// Populate crypto selector with API data
async function populateCryptoSelector() {
  const selector = document.getElementById("cryptoSelector");
  const selectorError = document.getElementById("selectorError");
  const selectorContainer = document.querySelector(".chart-selector");
  if (!selector || !selectorError || !selectorContainer) return;

  selectorContainer.classList.add("loading");
  try {
    const data = await fetchCryptoData();
    console.log("API Data for Selector:", data); // Debug: Log API response

    if (!data || data.length === 0) {
      console.warn("No data received, using fallback coins");
      selector.innerHTML = fallbackCoins
        .map(
          (coin) => `
        <option value="${coin.id}">${
            coin.name
          } (${coin.symbol.toUpperCase()})</option>
      `
        )
        .join("");
      selectorError.textContent = "Failed to load coins. Using fallback list.";
      selectorError.style.display = "block";
      return;
    }

    selector.innerHTML = data
      .map(
        (coin) => `
      <option value="${coin.id}">${
          coin.name
        } (${coin.symbol.toUpperCase()})</option>
    `
      )
      .join("");
    selectorError.style.display = "none";
  } catch (error) {
    console.error("Error populating crypto selector:", error);
    selector.innerHTML = fallbackCoins
      .map(
        (coin) => `
      <option value="${coin.id}">${
          coin.name
        } (${coin.symbol.toUpperCase()})</option>
    `
      )
      .join("");
    selectorError.textContent = "Error loading coins. Please try again later.";
    selectorError.style.display = "block";
  } finally {
    selectorContainer.classList.remove("loading");
  }
}

// Populate the cryptocurrency table with live data
async function populateCryptoTable() {
  const tableBody = document.getElementById("cryptoTableBody");
  if (!tableBody) return;
  tableBody.innerHTML =
    '<tr><td colspan="8" class="loading-data"><div class="loading-spinner"></div><p>Loading cryptocurrency data...</p></td></tr>';

  try {
    const data = await fetchCryptoData();
    tableBody.innerHTML = "";

    if (!data || data.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="8" class="text-center">No data available. Using fallback data.</td></tr>`;
      return;
    }

    data.forEach((crypto, index) => {
      const row = document.createElement("tr");
      row.innerHTML = `
        <td>${index + 1}</td>
        <td>
          <div class="crypto-name">
            <img src="${
              crypto.image || "/assets/images/default-crypto.png"
            }" alt="${crypto.name}">
            <div>
              <div>${crypto.name}</div>
              <span class="symbol">${crypto.symbol.toUpperCase()}</span>
            </div>
          </div>
        </td>
        <td>$${crypto.current_price.toLocaleString(undefined, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })}</td>
        <td class="${
          crypto.price_change_percentage_24h >= 0 ? "positive" : "negative"
        }">
          <i class="fas fa-caret-${
            crypto.price_change_percentage_24h >= 0 ? "up" : "down"
          }"></i>
          ${Math.abs(crypto.price_change_percentage_24h || 0).toFixed(2)}%
        </td>
        <td class="${
          crypto.price_change_percentage_7d_in_currency >= 0
            ? "positive"
            : "negative"
        }">
          <i class="fas fa-caret-${
            crypto.price_change_percentage_7d_in_currency >= 0 ? "up" : "down"
          }"></i>
          ${Math.abs(
            crypto.price_change_percentage_7d_in_currency || 0
          ).toFixed(2)}%
        </td>
        <td>${formatNumber(crypto.market_cap)}</td>
        <td>${formatNumber(crypto.total_volume)}</td>
        <td>
          <div class="action-btns">
            <button class="btn-action" title="Buy" data-coin="${
              crypto.id
            }" data-action="buy"><i class="fas fa-arrow-down"></i></button>
            <button class="btn-action" title="Sell" data-coin="${
              crypto.id
            }" data-action="sell"><i class="fas fa-arrow-up"></i></button>
            <button class="btn-action view-details" title="View Details" data-coin="${
              crypto.id
            }"><i class="fas fa-chart-line"></i></button>
          </div>
        </td>
      `;
      tableBody.appendChild(row);
    });

    // Add event listeners for action buttons
    document.querySelectorAll(".btn-action").forEach((button) => {
      button.addEventListener("click", () => {
        const coinId = button.getAttribute("data-coin");
        const action = button.getAttribute("data-action");
        if (action === "buy" || action === "sell") {
          showActionModal(action, coinId);
        } else if (action === "view-details") {
          updateChartForCoin(coinId);
        }
      });
    });

    if (data.length > 0) {
      updateCurrentCoinInfo(data[0]);
    }
  } catch (error) {
    console.error("Error in populateCryptoTable:", error);
    tableBody.innerHTML = `<tr><td colspan="8" class="text-center">Error loading data. Please try again later.</td></tr>`;
  }
}

// Show Buy/Sell modal
function showActionModal(action, coinId) {
  const modal = document.getElementById("actionModal");
  const modalTitle = document.getElementById("modalTitle");
  const modalMessage = document.getElementById("modalMessage");
  const modalConfirm = document.getElementById("modalConfirm");
  const modalCancel = document.getElementById("modalCancel");

  if (!modal || !modalTitle || !modalMessage || !modalConfirm || !modalCancel)
    return;

  modalTitle.textContent = `${
    action.charAt(0).toUpperCase() + action.slice(1)
  } ${coinId}`;
  modalMessage.textContent = `Are you sure you want to ${action} ${coinId}?`;
  modal.style.display = "flex";

  modalConfirm.onclick = () => {
    alert(`${action} for ${coinId} confirmed!`);
    modal.style.display = "none";
    // Implement actual buy/sell logic here
  };

  modalCancel.onclick = () => {
    modal.style.display = "none";
  };
}

// Update the chart for a specific coin
async function updateChartForCoin(coinId) {
  try {
    const response = await fetchWithRateLimit(`${COIN_DETAIL_API}${coinId}`);
    if (!response.ok) throw new Error(`API error: ${response.status}`);
    const coinData = await response.json();
    if (!coinData || !coinData.id) {
      console.error("Invalid coin data:", coinData);
      return;
    }
    updateCurrentCoinInfo(coinData);
    const timeFilter =
      document.querySelector(".time-filter button.active")?.innerText || "24h";
    const days = timeFilterToDays(timeFilter);
    const historicalData = await fetchHistoricalData(coinId, days);
    if (historicalData?.prices?.length) {
      createPriceChart(historicalData.prices);
    } else {
      console.warn("No historical price data available");
      createPriceChart(); // Fallback to placeholder data
    }
  } catch (error) {
    console.error("Error updating chart:", error);
    createPriceChart(); // Fallback to placeholder chart
  }
}

// Convert time filter text to days parameter
function timeFilterToDays(timeFilter) {
  switch (timeFilter) {
    case "24h":
      return 1;
    case "7d":
      return 7;
    case "30d":
      return 30;
    case "1y":
      return 365;
    default:
      return 1;
  }
}

// Update the current selected coin information
function updateCurrentCoinInfo(coinData) {
  const priceHeader = document.querySelector(".price-header");
  const priceValue = document.querySelector(".price-value");
  const priceStats = document.querySelector(".price-stats");
  if (!coinData || !priceHeader || !priceValue || !priceStats) return;

  const coin = coinData.id
    ? {
        name: coinData.name,
        symbol: coinData.symbol,
        image: coinData.image?.thumb || "/assets/images/default-crypto.png",
        current_price: coinData.market_data?.current_price?.usd || 0,
        price_change_percentage_24h:
          coinData.market_data?.price_change_percentage_24h || 0,
        market_cap: coinData.market_data?.market_cap?.usd || 0,
        total_volume: coinData.market_data?.total_volume?.usd || 0,
        circulating_supply: coinData.market_data?.circulating_supply || 0,
        ath: coinData.market_data?.ath?.usd || 0,
      }
    : {
        name: coinData.name,
        symbol: coinData.symbol,
        image: coinData.image || "/assets/images/default-crypto.png",
        current_price: coinData.current_price || 0,
        price_change_percentage_24h: coinData.price_change_percentage_24h || 0,
        market_cap: coinData.market_cap || 0,
        total_volume: coinData.total_volume || 0,
        circulating_supply: coinData.circulating_supply || 0,
        ath: coinData.ath || 0,
      };

  const cryptoSelector = document.getElementById("cryptoSelector");
  if (cryptoSelector && coin.id) {
    cryptoSelector.value = coin.id;
  }

  priceHeader.innerHTML = `
    <img src="${coin.image}" alt="${coin.name} logo" class="crypto-icon">
    <div>
      <h3>${coin.name}</h3>
      <span class="symbol">${coin.symbol.toUpperCase()}</span>
    </div>
  `;

  priceValue.innerHTML = `
    <h2>$${coin.current_price.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })}</h2>
    <p class="change ${
      coin.price_change_percentage_24h >= 0 ? "positive" : "negative"
    }">
      <i class="fas fa-caret-${
        coin.price_change_percentage_24h >= 0 ? "up" : "down"
      }"></i> ${Math.abs(coin.price_change_percentage_24h).toFixed(2)}%
    </p>
  `;

  priceStats.innerHTML = `
    <div class="stat">
      <span class="label">Market Cap</span>
      <span class="value">${formatNumber(coin.market_cap)}</span>
    </div>
    <div class="stat">
      <span class="label">Volume (24h)</span>
      <span class="value">${formatNumber(coin.total_volume)}</span>
    </div>
    <div class="stat">
      <span class="label">Circulating Supply</span>
      <span class="value">${(coin.circulating_supply / 1e6).toFixed(
        2
      )}M ${coin.symbol.toUpperCase()}</span>
    </div>
    <div class="stat">
      <span class="label">All-time High</span>
      <span class="value">$${coin.ath.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })}</span>
    </div>
  `;
}

// Show transactions in the transaction list
function populateTransactions(transactions) {
  const transactionsList = document.getElementById("transactionsList");
  if (!transactionsList) return;

  transactionsList.innerHTML = "";

  if (!transactions || transactions.length === 0) {
    transactionsList.innerHTML = `<div class="transaction-empty">No recent transactions.</div>`;
    return;
  }

  transactions.forEach((tx) => {
    const item = document.createElement("div");
    item.className = "transaction-item";
    let iconClass =
      tx.type === "buy"
        ? "fa-arrow-down"
        : tx.type === "sell"
        ? "fa-arrow-up"
        : "fa-arrow-right";

    item.innerHTML = `
      <div class="transaction-info">
        <div class="transaction-icon ${
          tx.type
        }"><i class="fas ${iconClass}"></i></div>
        <div class="transaction-details">
          <h4>${tx.type.charAt(0).toUpperCase() + tx.type.slice(1)} ${
      tx.crypto
    }</h4>
          <div class="transaction-meta">
            <span>${getTimeDifference(
              tx.date
            )}</span><span>•</span><span>${formatDate(tx.date)}</span>
          </div>
        </div>
      </div>
      <div class="transaction-amount">
        <div class="amount">${tx.type === "sell" ? "-" : "+"} ${tx.amount} ${
      tx.symbol
    }</div>
        <div class="usd-value">≈ $${tx.usdValue.toLocaleString(undefined, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })}</div>
      </div>`;
    transactionsList.appendChild(item);
  });
}

// Create portfolio distribution chart using dynamic data from dashboard.php
function createPortfolioDistribution(labels = [], data = [], colors = []) {
  const portfolioDistribution = document.getElementById(
    "portfolioDistribution"
  );
  if (!portfolioDistribution) return;

  portfolioDistribution.innerHTML = "";
  const canvas = document.createElement("canvas");
  canvas.id = "portfolioDistributionChart";
  canvas.style.width = "100%";
  canvas.style.height = "180px";
  portfolioDistribution.appendChild(canvas);

  try {
    new Chart(canvas, {
      type: "doughnut",
      data: {
        labels: labels.length ? labels : ["No Data"],
        datasets: [
          {
            data: data.length ? data : [100],
            backgroundColor: colors.length ? colors : ["#e0e0e0"],
            borderWidth: 0,
            cutout: "70%",
            hoverOffset: 8,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (context) {
                return `${context.label}: ${context.raw}%`;
              },
            },
          },
        },
      },
    });
  } catch (error) {
    console.error("Error creating portfolio distribution chart:", error);
    portfolioDistribution.innerHTML =
      '<div class="chart-error">Error loading chart</div>';
  }
}

// Create price chart with historical data
function createPriceChart(priceData = []) {
  const chartContainer = document.querySelector(".price-chart-container");
  if (!chartContainer) return;

  chartContainer.innerHTML = '<canvas id="priceChart"></canvas>';
  const ctx = document.getElementById("priceChart").getContext("2d");

  if (!priceData || priceData.length === 0) {
    const labels = [];
    const data = [];
    const base = 67000;
    for (let i = 0; i < 24; i++) {
      labels.push(`${i}:00`);
      data.push(base + Math.random() * 400 - 200);
    }
    priceData = data.map((price, index) => [
      Date.now() - (24 - index) * 3600000,
      price,
    ]);
  }

  const labels = priceData.map((item) => {
    const date = new Date(item[0]);
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  });

  const prices = priceData.map((item) => item[1]);

  const gradient = ctx.createLinearGradient(0, 0, 0, 400);
  gradient.addColorStop(0, "rgba(108, 92, 231, 0.2)");
  gradient.addColorStop(1, "rgba(108, 92, 231, 0)");

  try {
    new Chart(ctx, {
      type: "line",
      data: {
        labels,
        datasets: [
          {
            data: prices,
            borderColor: "#6c5ce7",
            fill: true,
            backgroundColor: gradient,
            tension: 0.4,
            pointRadius: 0,
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            mode: "index",
            intersect: false,
            callbacks: {
              label: function (context) {
                return `$${context.raw.toLocaleString(undefined, {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                })}`;
              },
            },
          },
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              maxRotation: 0,
              autoSkip: true,
              maxTicksLimit: 8,
            },
          },
          y: {
            beginAtZero: false,
            grid: { color: "rgba(0, 0, 0, 0.05)" },
            ticks: {
              callback: function (value) {
                return "$" + value.toLocaleString();
              },
            },
          },
        },
        interaction: {
          mode: "nearest",
          axis: "x",
          intersect: false,
        },
      },
    });
  } catch (error) {
    console.error("Error creating price chart:", error);
    chartContainer.innerHTML =
      '<div class="chart-error">Error loading chart</div>';
  }
}

// Setup time filter buttons
function setupTimeFilters() {
  const timeButtons = document.querySelectorAll(".time-filter button");
  if (!timeButtons.length) return;

  timeButtons.forEach((button) => {
    button.addEventListener("click", async () => {
      timeButtons.forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");

      const selector = document.getElementById("cryptoSelector");
      const coinId = selector ? selector.value : "bitcoin";
      await updateChartForCoin(coinId);
    });
  });
}

// Setup crypto selector dropdown
function setupCryptoSelector() {
  const selector = document.getElementById("cryptoSelector");
  if (!selector) return;

  selector.addEventListener("change", async () => {
    const coinId = selector.value;
    if (coinId) await updateChartForCoin(coinId);
  });
}

// Setup sidebar toggle
function setupSidebarToggle() {
  const toggleButton = document.querySelector(".sidebar-toggle");
  const sidebar = document.querySelector(".sidebar");
  if (!toggleButton || !sidebar) return;

  toggleButton.addEventListener("click", () => {
    sidebar.classList.toggle("active");
  });

  // Close sidebar when clicking outside on mobile
  document.addEventListener("click", (e) => {
    if (
      window.innerWidth <= 768 &&
      sidebar.classList.contains("active") &&
      !sidebar.contains(e.target) &&
      !toggleButton.contains(e.target)
    ) {
      sidebar.classList.remove("active");
    }
  });
}

// Add API status indicator
function addApiStatusIndicator() {
  const header = document.querySelector(".main-header");
  if (!header) return;

  const statusElement = document.createElement("div");
  statusElement.className = "api-status";
  statusElement.innerHTML = `
    <span class="status-dot connecting"></span>
    <span class="status-text">Connecting to API...</span>
  `;
  header.appendChild(statusElement);

  fetchWithRateLimit("https://api.coingecko.com/api/v3/ping")
    .then((response) => {
      if (response.ok) {
        statusElement.innerHTML = `
          <span class="status-dot connected"></span>
          <span class="status-text">API Connected</span>
        `;
      } else {
        statusElement.innerHTML = `
          <span class="status-dot error"></span>
          <span class="status-text">API Error</span>
        `;
      }
    })
    .catch(() => {
      statusElement.innerHTML = `
        <span class="status-dot error"></span>
        <span class="status-text">API Unreachable</span>
      `;
    });
}

// Initialize the dashboard
async function initDashboard() {
  try {
    await populateCryptoSelector(); // Populate selector first
    await populateCryptoTable();
    const transactions = await fetchTransactions();
    populateTransactions(transactions);

    // Fetch portfolio data from the hidden element
    const portfolioDataElement = document.getElementById("portfolioData");
    if (portfolioDataElement) {
      const labels = JSON.parse(portfolioDataElement.dataset.labels || "[]");
      const data = JSON.parse(portfolioDataElement.dataset.data || "[]");
      const colors = JSON.parse(portfolioDataElement.dataset.colors || "[]");
      createPortfolioDistribution(labels, data, colors);
    } else {
      createPortfolioDistribution();
    }

    createPriceChart();
    setupTimeFilters();
    setupCryptoSelector();
    setupSidebarToggle();
    await updateChartForCoin("bitcoin");
  } catch (error) {
    console.error("Error initializing dashboard:", error);
  }
}

// Event handler for document ready
document.addEventListener("DOMContentLoaded", () => {
  addApiStatusIndicator();
  initDashboard();

  const refreshBtn = document.querySelector(".btn-refresh");
  if (refreshBtn) {
    refreshBtn.addEventListener("click", async () => {
      await initDashboard();
    });
  }
});
