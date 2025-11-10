<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sales Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="../LogIn/Admin/include/navbar.css" />
  <link rel="stylesheet" href="Sales Report.css" />
</head>
<body>
<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header("Location: ../LogIn/Users/User.php");
    exit();
}
include '../LogIn/Admin/include/navbar.php';
?>

  <!-- Main Content -->
  <div class="main-content">
    <!-- White Background Header -->
    <div class="dashboard-header">
      <h2>ARAT COFFEE SALES DASHBOARD</h2>
      <div class="filter">
        <input type="date" id="startDate" />
        <input type="date" id="endDate" />
        <button id="applyFilter">Apply Filter</button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="cards">
      <div class="card">
        <h4>Gross Sales</h4>
        <p class="value" id="grossSales">₱0</p>
      </div>
      <div class="card">
        <h4>Total Orders</h4>
        <p class="value" id="ordersToday">0 Orders</p>
      </div>
      <div class="card">
        <h4>Net Income</h4>
        <p class="value" id="netIncome">₱0</p>
        <p class="small" id="nextIncome" style="margin-top:6px;color:#555;font-size:0.9rem;">Next Income: ₱0</p>
      </div>
      <div class="card top-product">
        <h4>Top Product</h4>
        <p class="value" id="topProduct"><i class="fa-solid fa-star"></i> N/A</p>
      </div>
    </div>

    <!-- Charts -->
    <div class="charts">
      <div class="chart-box">
        <h3>Sales Trends <span>Daily Sales</span></h3>
        <canvas id="dailyChart"></canvas>
        <p class="no-data" id="dailyNoData" style="display:none;">No data available</p>
      </div>
      <div class="chart-box">
        <h3>Monthly Sales <span id="monthlyStatus">up by 0% Compare last month</span></h3>
        <canvas id="monthlyChart"></canvas>
        <p class="no-data" id="monthlyNoData" style="display:none;">No data available</p>
      </div>
    </div>

      <!-- Extra Sales Dashboard Section -->
  <div class="extra-dashboard">
    <div class="left-side">
      <!-- Product Performance -->
      <div class="extra-box performance-box">
        <h3>Product Performance</h3>
        <table class="performance-table">
          <thead>
            <tr>
              <th>Product Name</th>
              <th>Quantity Sold</th>
              <th>Sales</th>
            </tr>
          </thead>
          <tbody>
            <!-- Data will be populated dynamically -->
          </tbody>
        </table>
      </div>

      <!-- Payment Breakdown -->
      <div class="extra-box payment-box rounded-container">
        <h3 class="payment-title">Payment Breakdown</h3>
        <div class="chart-container">
          <canvas id="paymentChart"></canvas>
          <div class="legend">
            <span><span class="dot cash"></span> Cash</span>
            <span><span class="dot gcash"></span> GCash</span>
            <span><span class="dot qrph"></span> QRPH</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="extra-box orders-box">
      <h3>Recent Orders</h3>
      <table class="orders-table">
        <thead>
          <tr><th>Order #</th><th>Amount</th><th>Date & Time</th></tr>
        </thead>
        <tbody>
          <!-- Data will be populated dynamically -->
        </tbody>
      </table>
    </div>
  </div>

    </div>

  </div>

  <!-- Chart.js for graphs -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Script for interactivity & backend integration -->
  <script>
    // Function to fetch sales data from database
    async function fetchSalesData(startDate = null, endDate = null) {
      try {
        let url = '../backend/fetch_sales_data.php';
        const params = new URLSearchParams();
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        if (params.toString()) url += '?' + params.toString();

        const response = await fetch(url);
        const result = await response.json();

        if (result.status === 'success') {
          return result.data;
        } else {
          console.error('Error fetching data:', result.message);
          return [];
        }
      } catch (error) {
        console.error('Fetch error:', error);
        return [];
      }
    }

    // Function to fetch product performance data
    async function fetchProductPerformance(startDate = null, endDate = null) {
      try {
        let url = '../backend/fetch_product_performance.php';
        const params = new URLSearchParams();
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        if (params.toString()) url += '?' + params.toString();

        const response = await fetch(url);
        const result = await response.json();

        if (result.status === 'success') {
          return result.data;
        } else {
          console.error('Error fetching product performance:', result.message);
          return [];
        }
      } catch (error) {
        console.error('Fetch error:', error);
        return [];
      }
    }

    // Function to fetch payment breakdown data
    async function fetchPaymentBreakdown(startDate = null, endDate = null) {
      try {
        let url = '../backend/fetch_payment_breakdown.php';
        const params = new URLSearchParams();
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        if (params.toString()) url += '?' + params.toString();

        const response = await fetch(url);
        const result = await response.json();

        if (result.status === 'success') {
          return result.data;
        } else {
          console.error('Error fetching payment breakdown:', result.message);
          return [];
        }
      } catch (error) {
        console.error('Fetch error:', error);
        return [];
      }
    }

    // Function to fetch recent orders data
    async function fetchRecentOrders() {
      try {
        console.log('Fetching recent orders...');
        const response = await fetch('../backend/fetch_recent_orders.php?limit=12');
        const result = await response.json();
        console.log('Recent orders response:', result);

        if (result.status === 'success') {
          console.log('Recent orders data:', result.data);
          return result.data;
        } else {
          console.error('Error fetching recent orders:', result.message);
          return [];
        }
      } catch (error) {
        console.error('Fetch error:', error);
        return [];
      }
    }

    // Function to update dashboard with data
    function updateDashboard(data) {
      if (data.length > 0) {
          // Use the most recent data (first in array since ordered by date DESC)
          const latestData = data[0];
          const gross = parseFloat(latestData.gross_sales || 0);
          const reportedNet = latestData.net_income !== undefined ? parseFloat(latestData.net_income) : null;
          const ingredientCost = latestData.ingredient_cost !== undefined ? parseFloat(latestData.ingredient_cost) : null;

          document.getElementById("grossSales").innerText = "₱" + gross.toLocaleString();
          document.getElementById("ordersToday").innerText = (latestData.orders_today || 0) + " Orders";

          // Compute net income: prefer server's net_income; otherwise use gross - ingredient_cost when available
          let netToShow = 0;
          if (reportedNet !== null && !isNaN(reportedNet)) {
            netToShow = reportedNet;
          } else if (ingredientCost !== null && !isNaN(ingredientCost)) {
            netToShow = gross - ingredientCost;
          } else {
            // fallback: use gross as net (if no cost available)
            netToShow = gross;
          }

          document.getElementById("netIncome").innerText = "₱" + netToShow.toLocaleString();
          document.getElementById("topProduct").innerHTML = '<i class="fa-solid fa-star"></i> ' + (latestData.top_product || 'N/A');
        } else {
        // Reset to default values if no data
        document.getElementById("grossSales").innerText = "₱0";
        document.getElementById("ordersToday").innerText = "0 Orders";
        document.getElementById("netIncome").innerText = "₱0";
        document.getElementById("topProduct").innerHTML = '<i class="fa-solid fa-star"></i> N/A';
      }
    }

    // Function to update product performance table
    function updateProductPerformance(data) {
      const tableBody = document.querySelector('.performance-table tbody');
      if (!tableBody) return;

      tableBody.innerHTML = '';

      if (data.length > 0) {
        data.slice(0, 6).forEach(product => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${product.product_name}</td>
            <td>${product.total_quantity}</td>
            <td>₱${parseFloat(product.total_sales).toLocaleString()}</td>
          `;
          tableBody.appendChild(row);
        });
      } else {
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="3">No data available</td>';
        tableBody.appendChild(row);
      }
    }

    // Function to update payment breakdown chart
    function updatePaymentBreakdown(data) {
      if (window.paymentChart && typeof window.paymentChart.destroy === 'function') {
        window.paymentChart.destroy();
      }

      const ctx = document.getElementById("paymentChart").getContext("2d");

        if (data.length > 0) {
        const labels = data.map(item => item.payment_method);
        const percentages = data.map(item => parseFloat(item.percentage));

        // Map colors to payment labels so legend colors remain consistent regardless of label order
        const colorMap = { 'Cash': 'green', 'GCash': 'blue', 'QRPH': 'red', 'Other': 'gray' };
        const backgroundColors = labels.map(l => colorMap[l] || 'gray');

        window.paymentChart = new Chart(ctx, {
          type: "pie",
          data: {
            labels: labels,
            datasets: [{
              data: percentages,
              backgroundColor: backgroundColors,
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                display: false
              }
            }
          }
        });
      } else {
        // Clear the canvas if no data
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        window.paymentChart = null;
      }
    }

    // Function to update recent orders table
    function updateRecentOrders(data) {
      console.log('updateRecentOrders called with data:', data);
      const tableBody = document.querySelector('.orders-table tbody');
      console.log('Table body found:', tableBody);
      if (!tableBody) {
        console.error('Could not find .orders-table tbody element');
        return;
      }

      tableBody.innerHTML = '';

      if (data.length > 0) {
        console.log('Processing', data.length, 'orders');
        data.forEach(order => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${order.order_number}</td>
            <td>₱${parseFloat(order.amount).toLocaleString()}</td>
            <td>${order.formatted_date_time}</td>
          `;
          tableBody.appendChild(row);
        });
        console.log('Orders added to table');
      } else {
        console.log('No orders data, showing empty message');
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="3">No orders available</td>';
        tableBody.appendChild(row);
      }
    }

    // Apply filter button
    document.getElementById("applyFilter").addEventListener("click", async () => {
      const start = document.getElementById("startDate").value;
      const end = document.getElementById("endDate").value;

      if (!end) {
        alert("Please select an end date");
        return;
      }

      // Fetch all data with filters
      const salesData = await fetchSalesData(start, end);
      const productData = await fetchProductPerformance(start, end);
      const paymentData = await fetchPaymentBreakdown(start, end);
      const dailyTrendsData = await fetchDailySalesTrends(start, end);

      // Update all sections
      updateDashboard(salesData);
      updateProductPerformance(productData);
      updatePaymentBreakdown(paymentData);
      updateDailySalesChart(dailyTrendsData);
  // compute next income with monthly trends
  const monthlyTrendsData = await fetchMonthlySalesTrends(start, end);
  const latestNet = (salesData.length > 0) ? (salesData[0].net_income !== undefined && salesData[0].net_income !== null ? parseFloat(salesData[0].net_income) : (parseFloat(salesData[0].gross_sales || 0) - (salesData[0].ingredient_cost !== undefined ? parseFloat(salesData[0].ingredient_cost || 0) : 0))) : 0;
  const next = computeNextIncome(latestNet, monthlyTrendsData);
  document.getElementById('nextIncome').innerText = 'Next Income: ₱' + next.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});

      if (salesData.length === 0) {
        alert("No data found for the selected date range");
      }
    });

    // Store last fetched monthly data for forecasting
    let _lastMonthlyData = [];

    // Function to refresh all dashboard data
    async function refreshDashboardData() {
      try {
        // Fetch all data without filters
        const salesData = await fetchSalesData();
        const productData = await fetchProductPerformance();
        const paymentData = await fetchPaymentBreakdown();
        const ordersData = await fetchRecentOrders();
        const dailyTrendsData = await fetchDailySalesTrends();
        const monthlyTrendsData = await fetchMonthlySalesTrends();

        // cache monthly data for forecasting
        _lastMonthlyData = Array.isArray(monthlyTrendsData) ? monthlyTrendsData : [];

        // Update all sections
        updateDashboard(salesData);
        updateProductPerformance(productData);
        updatePaymentBreakdown(paymentData);
        updateRecentOrders(ordersData);
        updateDailySalesChart(dailyTrendsData);
        updateMonthlySalesChart(monthlyTrendsData);
        // Update next income forecast based on the latest net income and monthly data
  const latestNet = (salesData.length > 0) ? (salesData[0].net_income !== undefined && salesData[0].net_income !== null ? parseFloat(salesData[0].net_income) : (parseFloat(salesData[0].gross_sales || 0) - (salesData[0].ingredient_cost !== undefined ? parseFloat(salesData[0].ingredient_cost || 0) : 0))) : 0;
        const next = computeNextIncome(latestNet, _lastMonthlyData);
        document.getElementById('nextIncome').innerText = 'Next Income: ₱' + next.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
      } catch (error) {
        console.error('Error refreshing dashboard data:', error);
      }
    }

    // Load initial data on page load
    document.addEventListener('DOMContentLoaded', async () => {
      await refreshDashboardData();

      // Set up real-time updates every 30 seconds
      setInterval(refreshDashboardData, 30000);

      // Listen for real-time updates from order completion (cross-tab)
      window.addEventListener('storage', function(e) {
        if (e.key === 'dashboardRefresh') {
          console.log('Dashboard refresh triggered by order completion');
          refreshDashboardData();
        }
      });

      // Also refresh when page becomes visible (same-tab navigation)
      document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
          console.log('Dashboard tab became visible, refreshing data');
          refreshDashboardData();
        }
      });
    });

    // Function to fetch daily sales trends data
    async function fetchDailySalesTrends(startDate = null, endDate = null) {
      try {
        let url = '../backend/fetch_daily_sales_trends.php';
        const params = new URLSearchParams();
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        if (params.toString()) url += '?' + params.toString();

        const response = await fetch(url);
        const result = await response.json();

        if (result.status === 'success') {
          return result.data;
        } else {
          console.error('Error fetching daily sales trends:', result.message);
          return [];
        }
      } catch (error) {
        console.error('Fetch error:', error);
        return [];
      }
    }

    // Function to fetch monthly sales trends data
    async function fetchMonthlySalesTrends() {
      try {
        const response = await fetch('../backend/fetch_monthly_sales_trends.php');
        const result = await response.json();

        if (result.status === 'success') {
          return result.data;
        } else {
          console.error('Error fetching monthly sales trends:', result.message);
          return [];
        }
      } catch (error) {
        console.error('Fetch error:', error);
        return [];
      }
    }

    // Function to update daily sales chart
    function updateDailySalesChart(data) {
      if (window.dailyChart && typeof window.dailyChart.destroy === 'function') {
        window.dailyChart.destroy();
      }

      const ctx = document.getElementById("dailyChart").getContext("2d");

      if (data.length > 0) {
        const labels = data.map(item => item.day_of_week);
        const sales = data.map(item => parseFloat(item.sales));

        window.dailyChart = new Chart(ctx, {
          type: "bar",
          data: {
            labels: labels,
            datasets: [{
              label: "Daily Sales",
              data: sales,
              backgroundColor: "rgba(108, 229, 232, 0.8)",
              borderColor: "#6ce5e8",
              borderWidth: 2,
              borderRadius: 8,
              borderSkipped: false,
              hoverBackgroundColor: "rgba(108, 229, 232, 1)",
              hoverBorderColor: "#4dd0d4"
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                callbacks: {
                  label: function(context) {
                    return '₱' + context.parsed.y.toLocaleString();
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: 'rgba(200, 200, 200, 0.3)'
                },
                ticks: {
                  callback: function(value) {
                    return '₱' + value.toLocaleString();
                  },
                  color: '#666'
                }
              },
              x: {
                grid: {
                  display: false
                },
                ticks: {
                  color: '#666',
                  font: {
                    weight: 'bold'
                  }
                }
              }
            },
            animation: {
              duration: 1000,
              easing: 'easeOutQuart'
            }
          }
        });
      } else {
        // Clear the canvas if no data
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        window.dailyChart = null;
      }
    }

    // Function to update monthly sales chart
    function updateMonthlySalesChart(data) {
      if (window.monthlyChart && typeof window.monthlyChart.destroy === 'function') {
        window.monthlyChart.destroy();
      }

      const ctx = document.getElementById("monthlyChart").getContext("2d");

      if (data.length > 0) {
        const labels = data.map(item => item.month);
        const sales = data.map(item => parseFloat(item.sales));

        window.monthlyChart = new Chart(ctx, {
          type: "bar",
          data: {
            labels: labels,
            datasets: [{
              label: "Monthly Sales",
              data: sales,
              backgroundColor: "rgba(108, 229, 232, 0.8)",
              borderColor: "#6ce5e8",
              borderWidth: 2,
              borderRadius: 8,
              borderSkipped: false,
              hoverBackgroundColor: "rgba(108, 229, 232, 1)",
              hoverBorderColor: "#4dd0d4"
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                callbacks: {
                  label: function(context) {
                    return '₱' + context.parsed.y.toLocaleString();
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: 'rgba(200, 200, 200, 0.3)'
                },
                ticks: {
                  callback: function(value) {
                    return '₱' + value.toLocaleString();
                  },
                  color: '#666'
                }
              },
              x: {
                grid: {
                  display: false
                },
                ticks: {
                  color: '#666',
                  font: {
                    weight: 'bold'
                  }
                }
              }
            },
            animation: {
              duration: 1000,
              easing: 'easeOutQuart'
            }
          }
        });
      } else {
        // Clear the canvas if no data
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        window.monthlyChart = null;
      }
    }

    // Compute next income forecast and update UI using a smoothed growth rate
    // This uses the last 3-6 months (when available) and computes a geometric mean of month-over-month ratios
    // to produce a less noisy growth estimate.
    function computeNextIncome(netIncomeValue, monthlyData) {
      // netIncomeValue: number (current/latest net income)
      // monthlyData: array with {month, sales} objects (order may be latest-first or oldest-first)
      if (!monthlyData || monthlyData.length === 0) {
        return netIncomeValue;
      }

      // Normalize to numeric sales and parse month as Date when possible
      const months = monthlyData.map(m => ({
        month: m.month,
        sales: Math.max(0, parseFloat(m.sales || 0))
      }));

      // Try to determine sort order: if first month date < second, assume ascending (old->new)
      let sorted = months.slice();
      try {
        const a = new Date(sorted[0].month);
        const b = new Date(sorted[1]?.month || sorted[0].month);
        if (!isNaN(a) && !isNaN(b) && a > b) {
          // a > b means first is later than second -> assume array already latest-first
          // keep as-is
        } else if (!isNaN(a) && !isNaN(b) && a <= b) {
          // ascending (older first) -> reverse to make latest-first
          sorted = sorted.reverse();
        }
      } catch (e) {
        // ignore, use as-is
      }

      // Choose up to lastN months (prefer 6, but use at least 2 to compute growth)
      const lastN = Math.min(6, Math.max(2, sorted.length));
      const lastMonths = sorted.slice(0, lastN);

      // Compute ratios for consecutive month pairs (latest / previous). We need at least one valid pair.
      const ratios = [];
      for (let i = 0; i < lastMonths.length - 1; i++) {
        const latest = lastMonths[i].sales;
        const prev = lastMonths[i + 1].sales;
        if (prev > 0) {
          ratios.push(latest / prev);
        }
        // if prev == 0, skip this pair (avoid infinite ratio)
      }

      if (ratios.length === 0) {
        // cannot compute meaningful growth -> return netIncome as-is
        return netIncomeValue;
      }

      // Geometric mean of ratios = product(ratios)^(1/ratios.length)
      const product = ratios.reduce((p, r) => p * r, 1);
      const geomMean = Math.pow(product, 1 / ratios.length);

      if (!isFinite(geomMean) || isNaN(geomMean)) return netIncomeValue;

      // Forecast next income by applying geometric mean multiplier
      const forecast = netIncomeValue * geomMean;
      return Math.max(0, forecast);
    }

  </script>
</body>
</html>
