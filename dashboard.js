// Global variables for charts
let candlestickChart = null;
let lineChart = null;
let donutChart = null;

// Initialize all charts when page loads
document.addEventListener('DOMContentLoaded', function () {
    initCandlestickChart();
    initLineChart();
    initDonutChart();
    initOrdersTable(); // Initialize orders table

    // Add event listeners for filters
    document.getElementById('candlestick-period').addEventListener('change', function () {
        updateCandlestickChart(this.value);
    });

    document.getElementById('revenue-period').addEventListener('change', function () {
        updateLineChart(this.value);
    });

    document.getElementById('sales-period').addEventListener('change', function () {
        updateDonutChart(this.value);
    });
});

// ========== CHART INITIALIZATION FUNCTIONS ==========

// 1. CANDLESTICK CHART: Daily Order Performance with REAL high/low/average
function initCandlestickChart() {
    const ctx = document.getElementById('candlestickChart');
    if (!ctx) return;

    // Check if we have real data from PHP
    if (!orderStatsData || orderStatsData.length === 0) {
        console.warn('No order stats data available');
        return;
    }

    // Use REAL daily order counts from PHP
    const labels = orderStatsData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });

    const ordersData = orderStatsData.map(item => item.orders);

    // Calculate REAL statistics from actual data
    const orderValues = ordersData.map(item => item);
    const highestDay = Math.max(...orderValues);
    const lowestDay = Math.min(...orderValues);
    const averageDay = orderValues.reduce((a, b) => a + b, 0) / orderValues.length;

    // Create high, low, avg datasets from REAL data
    const highData = Array(ordersData.length).fill(highestDay);
    const lowData = Array(ordersData.length).fill(lowestDay);
    const avgData = Array(ordersData.length).fill(averageDay.toFixed(1));

    // Destroy existing chart
    if (candlestickChart) candlestickChart.destroy();

    // Create candlestick-like chart with REAL data
    candlestickChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: `Highest Day: ${highestDay} orders`,
                    data: highData,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    pointRadius: 0
                },
                {
                    label: `Lowest Day: ${lowestDay} orders`,
                    data: lowData,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    pointRadius: 0
                },
                {
                    label: `Average: ${averageDay.toFixed(1)} orders`,
                    data: avgData,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    pointRadius: 0
                },
                {
                    label: 'Daily Orders',
                    data: ordersData,
                    borderColor: '#9b59b6',
                    backgroundColor: 'rgba(155, 89, 182, 0.2)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                    pointHoverRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function (context) {
                            if (context.datasetIndex === 3) {
                                return `Orders: ${context.parsed.y}`;
                            }
                            return `${context.dataset.label}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Orders',
                        color: '#718096',
                        font: { size: 13, weight: '600' }
                    },
                    grid: { color: 'rgba(226, 232, 240, 0.8)' }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date',
                        color: '#718096',
                        font: { size: 13, weight: '600' }
                    },
                    grid: { color: 'rgba(226, 232, 240, 0.8)' }
                }
            }
        }
    });
}

// 2. LINE CHART: Revenue Trend - REAL DATA
function initLineChart() {
    const ctx = document.getElementById('lineChart');
    if (!ctx) return;

    // Check if we have real data from PHP
    if (!revenueTrendData || revenueTrendData.length === 0) {
        console.warn('No revenue trend data available');
        return;
    }

    // Format dates from REAL data
    const labels = revenueTrendData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });

    const revenueData = revenueTrendData.map(item => item.revenue);

    // Destroy existing chart
    if (lineChart) lineChart.destroy();

    // Create new chart with REAL data
    lineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Daily Revenue',
                data: revenueData,
                borderColor: '#9b59b6',
                backgroundColor: 'rgba(155, 89, 182, 0.15)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointBackgroundColor: '#9b59b6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function (context) {
                            return `Revenue: $${context.parsed.y.toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return '$' + value.toLocaleString();
                        }
                    },
                    title: {
                        display: true,
                        text: 'Revenue ($)',
                        color: '#718096',
                        font: { size: 13, weight: '600' }
                    },
                    grid: { color: 'rgba(226, 232, 240, 0.8)' }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date',
                        color: '#718096',
                        font: { size: 13, weight: '600' }
                    },
                    grid: { color: 'rgba(226, 232, 240, 0.8)' }
                }
            }
        }
    });
}

// 3. DONUT CHART: Sales by Product Type - REAL DATA
function initDonutChart() {
    const ctx = document.getElementById('donutChart');
    if (!ctx) return;

    // Check if we have real data from PHP
    if (!salesByTypeData || !salesByTypeData.data || salesByTypeData.data.length === 0) {
        console.warn('No sales by type data available');
        return;
    }

    const types = salesByTypeData.data.map(item => item.type);
    const revenues = salesByTypeData.data.map(item => item.revenue);
    const percentages = salesByTypeData.data.map(item => item.percentage);

    // Color palette
    const colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6'];

    // Update legend
    updateDonutLegend(types, revenues, percentages, colors);

    // Destroy existing chart
    if (donutChart) donutChart.destroy();

    // Create new chart with REAL data
    donutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: types,
            datasets: [{
                data: revenues,
                backgroundColor: colors.slice(0, types.length),
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const value = context.parsed;
                            const percentage = percentages[context.dataIndex];
                            return `${context.label}: $${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// ========== CHART UPDATE FUNCTIONS (WITH AJAX FOR REAL DATA) ==========

// Update candlestick chart with REAL data via AJAX
async function updateCandlestickChart(days) {
    try {
        const response = await fetch(`dashboard_ajax.php?action=order_stats&days=${days}`);
        const data = await response.json();

        // Update chart with REAL data
        const labels = data.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });

        const ordersData = data.map(item => item.orders);

        // Calculate REAL statistics
        const orderValues = ordersData.map(item => item);
        const highestDay = Math.max(...orderValues);
        const lowestDay = Math.min(...orderValues);
        const averageDay = orderValues.reduce((a, b) => a + b, 0) / orderValues.length;

        // Create high, low, avg datasets
        const highData = Array(ordersData.length).fill(highestDay);
        const lowData = Array(ordersData.length).fill(lowestDay);
        const avgData = Array(ordersData.length).fill(averageDay.toFixed(1));

        // Update chart
        candlestickChart.data.labels = labels;
        candlestickChart.data.datasets[0].data = highData;
        candlestickChart.data.datasets[1].data = lowData;
        candlestickChart.data.datasets[2].data = avgData;
        candlestickChart.data.datasets[3].data = ordersData;

        // Update chart labels
        candlestickChart.data.datasets[0].label = `Highest Day: ${highestDay} orders`;
        candlestickChart.data.datasets[1].label = `Lowest Day: ${lowestDay} orders`;
        candlestickChart.data.datasets[2].label = `Average: ${averageDay.toFixed(1)} orders`;

        // Update subtitle
        document.querySelector('.Candlestick .chart-subtitle').textContent =
            `Actual high/low/average orders per day (Last ${days} days)`;

        candlestickChart.update();
    } catch (error) {
        console.error('Error updating candlestick chart:', error);
    }
}

// Update line chart with REAL data via AJAX
async function updateLineChart(days) {
    try {
        const response = await fetch(`dashboard_ajax.php?action=revenue_trend&days=${days}`);
        const data = await response.json();

        // Update chart with REAL data
        const labels = data.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });

        const revenueData = data.map(item => item.revenue);

        lineChart.data.labels = labels;
        lineChart.data.datasets[0].data = revenueData;

        // Update subtitle
        document.querySelector('.Line-Chart .chart-subtitle').textContent =
            `Daily revenue for last ${days} days`;

        lineChart.update();
    } catch (error) {
        console.error('Error updating line chart:', error);
    }
}

// Update donut chart with REAL data via AJAX
async function updateDonutChart(days) {
    try {
        const response = await fetch(`dashboard_ajax.php?action=sales_by_type&days=${days}`);
        const salesData = await response.json();

        const types = salesData.data.map(item => item.type);
        const revenues = salesData.data.map(item => item.revenue);
        const percentages = salesData.data.map(item => item.percentage);

        // Update chart with REAL data
        donutChart.data.labels = types;
        donutChart.data.datasets[0].data = revenues;

        // Update legend
        updateDonutLegend(types, revenues, percentages, ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6']);

        // Update subtitle
        document.querySelector('.Pie-Chart .chart-subtitle').textContent =
            `Revenue distribution by product type (Last ${days} days)`;

        donutChart.update();
    } catch (error) {
        console.error('Error updating donut chart:', error);
    }
}

// Update Donut Chart Legend
function updateDonutLegend(types, revenues, percentages, colors) {
    const legendContainer = document.getElementById('donut-legend');
    if (!legendContainer) return;

    legendContainer.innerHTML = '';

    types.forEach((type, index) => {
        const legendItem = document.createElement('div');
        legendItem.className = 'legend-item';
        legendItem.innerHTML = `
            <span class="legend-color" style="background-color: ${colors[index]};"></span>
            <span><strong>${type}</strong>: $${revenues[index].toLocaleString()} (${percentages[index]}%)</span>
        `;
        legendContainer.appendChild(legendItem);
    });
}

// ========== ORDERS TABLE FUNCTIONALITY ==========

// Initialize orders table functionality
function initOrdersTable() {
    const table = document.getElementById('orders-table');
    const searchInput = document.getElementById('order-search');
    const statusFilter = document.getElementById('status-filter');
    const prevButton = document.getElementById('prev-page');
    const nextButton = document.getElementById('next-page');

    if (!table) return;

    // Table state
    let currentPage = 1;
    let rowsPerPage = 10;
    let filteredRows = Array.from(table.querySelectorAll('tbody tr'));
    let sortColumn = -1;
    let sortDirection = 1; // 1 for ascending, -1 for descending

    // Update display
    updateTableDisplay();

    // Search functionality
    searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        filterTable(searchTerm, statusFilter.value);
    });

    // Status filter functionality
    statusFilter.addEventListener('change', function () {
        filterTable(searchInput.value.toLowerCase(), this.value);
    });

    // Pagination
    prevButton.addEventListener('click', function () {
        if (currentPage > 1) {
            currentPage--;
            updateTableDisplay();
        }
    });

    nextButton.addEventListener('click', function () {
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            updateTableDisplay();
        }
    });

    // Filter table function
    function filterTable(searchTerm, status) {
        const rows = table.querySelectorAll('tbody tr');
        filteredRows = [];

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length === 0) return;

            const orderId = cells[0].textContent.toLowerCase();
            const customer = cells[2].textContent.toLowerCase();
            const email = cells[3].textContent.toLowerCase();
            const statusCell = cells[6].querySelector('.status-badge');
            const statusText = statusCell ? statusCell.textContent.trim().toLowerCase() : '';
            const products = cells[7].textContent.toLowerCase();

            const matchesSearch = !searchTerm ||
                orderId.includes(searchTerm) ||
                customer.includes(searchTerm) ||
                email.includes(searchTerm) ||
                products.includes(searchTerm);

            const matchesStatus = !status ||
                statusText === status.toLowerCase();

            if (matchesSearch && matchesStatus) {
                filteredRows.push(row);
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        currentPage = 1;
        updateTableDisplay();
    }

    // Update table display with pagination
    function updateTableDisplay() {
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);

        // Calculate start and end indices
        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = Math.min(startIndex + rowsPerPage, totalRows);

        // Hide all rows
        filteredRows.forEach(row => row.style.display = 'none');

        // Show rows for current page
        for (let i = startIndex; i < endIndex; i++) {
            if (filteredRows[i]) {
                filteredRows[i].style.display = '';
            }
        }

        // Update pagination controls
        prevButton.disabled = currentPage === 1;
        nextButton.disabled = currentPage === totalPages || totalPages === 0;

        // Update page info
        document.getElementById('page-info').textContent = `Page ${currentPage}${totalPages > 0 ? ` of ${totalPages}` : ''}`;

        // Update showing count
        const showingCount = totalRows > 0 ? `${startIndex + 1}-${endIndex}` : '0';
        document.getElementById('showing-count').textContent = showingCount;
        document.getElementById('total-count').textContent = totalRows;
    }

    // Sorting functionality
    window.sortTable = function (columnIndex) {
        const rows = Array.from(table.querySelectorAll('tbody tr'));

        // Remove previous sort indicators
        table.querySelectorAll('th').forEach(th => {
            th.classList.remove('sorted-asc', 'sorted-desc');
        });

        // If clicking the same column, reverse direction
        if (sortColumn === columnIndex) {
            sortDirection *= -1;
        } else {
            sortColumn = columnIndex;
            sortDirection = 1;
        }

        // Add sort indicator
        const header = table.querySelectorAll('th')[columnIndex];
        header.classList.add(sortDirection === 1 ? 'sorted-asc' : 'sorted-desc');

        // Sort rows
        rows.sort((a, b) => {
            const aCell = a.querySelectorAll('td')[columnIndex];
            const bCell = b.querySelectorAll('td')[columnIndex];

            let aValue = aCell ? aCell.textContent.trim() : '';
            let bValue = bCell ? bCell.textContent.trim() : '';

            // Special handling for different columns
            switch (columnIndex) {
                case 0: // Order ID
                    aValue = parseInt(aValue.replace('#', '')) || 0;
                    bValue = parseInt(bValue.replace('#', '')) || 0;
                    break;
                case 1: // Date
                    const aDate = aCell.getAttribute('data-date') ? new Date(aCell.getAttribute('data-date')) : new Date(aValue);
                    const bDate = bCell.getAttribute('data-date') ? new Date(bCell.getAttribute('data-date')) : new Date(bValue);
                    aValue = aDate.getTime();
                    bValue = bDate.getTime();
                    break;
                case 4: // Items (number)
                    aValue = parseInt(aValue) || 0;
                    bValue = parseInt(bValue) || 0;
                    break;
                case 5: // Amount (currency)
                    aValue = parseFloat(aValue.replace('$', '').replace(',', '')) || 0;
                    bValue = parseFloat(bValue.replace('$', '').replace(',', '')) || 0;
                    break;
                case 6: // Status (special handling)
                    aValue = aCell.querySelector('.status-badge') ? aCell.querySelector('.status-badge').textContent.toLowerCase() : '';
                    bValue = bCell.querySelector('.status-badge') ? bCell.querySelector('.status-badge').textContent.toLowerCase() : '';
                    break;
            }

            if (aValue < bValue) return -1 * sortDirection;
            if (aValue > bValue) return 1 * sortDirection;
            return 0;
        });

        // Reorder table rows
        const tbody = table.querySelector('tbody');
        rows.forEach(row => tbody.appendChild(row));

        // Update filtered rows for pagination
        filteredRows = rows.filter(row => row.style.display !== 'none');
        updateTableDisplay();
    };

    // Add data-date attribute to date cells for sorting
    table.querySelectorAll('tbody tr').forEach(row => {
        const dateCell = row.querySelectorAll('td')[1];
        if (dateCell) {
            const dateText = dateCell.textContent;
            const date = new Date(dateText);
            if (!isNaN(date.getTime())) {
                dateCell.setAttribute('data-date', date.toISOString());
            }
        }
    });
}