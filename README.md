CryptoTrack - A CryptoCurrency Tracking and Management System


Track and manage your cryptocurrency investments with ease!

📖 Overview
CryptoTrack is a user-friendly web application designed to simplify cryptocurrency portfolio management. Built as part of the DBMS_SEE project at RV University, this system allows users to monitor real-time cryptocurrency prices, manage their portfolios, execute buy/sell transactions, and customize their experience with settings like themes and notifications. The application supports three popular cryptocurrencies—Bitcoin (BTC), Ethereum (ETH), and Dogecoin (DOGE)—and integrates with the CoinGecko API for live price data. With a secure MySQL backend and a responsive PHP frontend, CryptoTrack ensures a seamless and secure experience for novice and experienced investors alike.

✨ Features
User Authentication: Secure registration and login with session-based authentication.
Real-Time Price Tracking: Fetches live prices for BTC, ETH, and DOGE using the CoinGecko API.
Portfolio Management: View your total portfolio value, asset distribution (via pie chart), and price trends (via line chart).
Buy/Sell Transactions: Easily buy or sell cryptocurrencies, with transactions logged for transparency.
Transaction History: Keep track of all your buy/sell activities with timestamps and details.
Customizable Settings: Personalize your experience with options like light/dark theme and email notification preferences.
Responsive Design: Access the app seamlessly on desktops, tablets, and mobile devices.
API Caching: Efficiently handles API rate limits by caching price data for improved performance.
Secure Database: Stores user data, wallets, transactions, and settings in a MySQL database with proper security measures.
🛠️ Technologies Used
Frontend: HTML, CSS, JS
Backend: PHP
Database: MySQL
API: CoinGecko API (for live cryptocurrency prices)
Charting Library: Chart.js (for portfolio and price trend visualizations)
Development Environment: XAMPP/LAMP stack
🚀 Getting Started
Follow these steps to set up and run CryptoTrack on your local machine.

Prerequisites
PHP 7.4 or higher installed
MySQL Server installed
XAMPP or LAMP stack for local development
A web browser (e.g., Chrome, Firefox)
Internet connection (to fetch live prices via the CoinGecko API)
Installation
Clone the Repository:
git clone https://github.com/your-username/DBMS_SEE-project.git
(Replace your-username with your GitHub username or the actual repository URL.)
Navigate to the Project Directory:
cd DBMS_SEE-project
Set Up the Database:
Start your XAMPP/LAMP server and open phpMyAdmin.
Create a new database named crypto_db.
Import the database.sql file (located in the sql/ folder) to set up the required tables:
sql

Users, Wallets, Cryptocurrencies, WalletCryptos, Transactions, Settings
Configure the Database Connection:
Open config.php in the project root.
Update the database credentials (host, username, password, database name) to match your setup:
php

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'crypto_db';
Start the Local Server:
Move the project folder to your XAMPP/LAMP htdocs directory (e.g., /xampp/htdocs/DBMS_SEE-project).
Start the Apache and MySQL services in XAMPP/LAMP.
Open your browser and navigate to:
http://localhost/DBMS_SEE-project
Usage
Register an Account: Click on the "Register" link on the homepage, fill in your details (username, email, password), and create an account.
Log In: Use your credentials to log in and access the dashboard.
Explore the Dashboard: View your portfolio value, asset distribution, price trends, and recent transactions.
Buy/Sell Cryptocurrencies: Navigate to the Buy or Sell pages, select a cryptocurrency (BTC, ETH, DOGE), and specify the amount to trade.
Manage Settings: Go to the Settings page to toggle email notifications or switch between light/dark themes.
View Transaction History: Check your transaction history for a detailed log of all activities.


🤝 Contributors
Rajath M S (1RUA24CSE0354) -Frontend Developer
Pratham Datta (1RUA24CSE0324) - Backend Developer, API Integration
Preetham Gowda GN (1RUA24CSE0330) - DataBase Connection Manager

Prof. Arathi B N - Project Guide, Assistant Professor, School of CSE, RV University
📬 Contact
For any queries or feedback, reach out to us at:

Email: rajathmsbtech24@rvu.edu.in 

🙏Acknowledgments
Thanks to RV University for providing the opportunity to work on this project.
Gratitude to the CoinGecko team for their free API, which powers the live price data.
Appreciation for the open-source community for tools like Chart.js and PHP.
