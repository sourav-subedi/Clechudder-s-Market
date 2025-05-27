<?php
session_start();
require "../../../Backend/connect.php";
include_once "sidebar.php";

// Get connection
$conn = getDBConnection();

// Add error handling for session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../loginRegister/php/login.php");
    exit;
}

// Get trader information
$user_id = $_SESSION['user_id'];
$shop_query = "SELECT shop_id, shop_name FROM shops WHERE user_id = :user_id";
$shop_stmt = oci_parse($conn, $shop_query);
oci_bind_by_name($shop_stmt, ":user_id", $user_id);
oci_execute($shop_stmt);
$shop = oci_fetch_assoc($shop_stmt);

// Add error handling for shop information
if (!$shop) {
    echo "No shop found for this user. Please create a shop first.";
    exit;
}

$shop_id = $shop['SHOP_ID'];
$shop_name = $shop['SHOP_NAME'];

// Get date ranges
$today = new DateTime();
$end_date = $today->format('Y-m-d');

// Default to current month
$start_date = (new DateTime())->modify('first day of this month')->format('Y-m-d');

// Handle custom date range if provided
if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
}

// Query for sales data
$sales_query = "
SELECT 
    p.PRODUCT_ID,
    p.PRODUCT_NAME,
    SUM(pc.QUANTITY) as TOTAL_QUANTITY,
    SUM(p.PRICE * pc.QUANTITY) as TOTAL_SALES,
    COUNT(DISTINCT o.ORDER_ID) as ORDER_COUNT
FROM 
    product p
JOIN 
    product_cart pc ON p.PRODUCT_ID = pc.PRODUCT_ID
JOIN 
    cart c ON pc.CART_ID = c.CART_ID
JOIN 
    orders o ON c.CART_ID = o.CART_ID
WHERE 
    p.SHOP_ID = :shop_id
    AND o.ORDER_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD') + 1
    AND o.STATUS = 'completed'
GROUP BY 
    p.PRODUCT_ID, p.PRODUCT_NAME
ORDER BY 
    TOTAL_SALES DESC
";

$sales_stmt = oci_parse($conn, $sales_query);
oci_bind_by_name($sales_stmt, ":shop_id", $shop_id);
oci_bind_by_name($sales_stmt, ":start_date", $start_date);
oci_bind_by_name($sales_stmt, ":end_date", $end_date);
oci_execute($sales_stmt);

// Query for total revenue and orders
$revenue_query = "
SELECT 
    SUM(p.PRICE * pc.QUANTITY) as TOTAL_REVENUE,
    COUNT(DISTINCT o.ORDER_ID) as TOTAL_ORDERS,
    AVG(p.PRICE * pc.QUANTITY) as AVERAGE_ORDER_VALUE,
    COUNT(DISTINCT o.USER_ID) as UNIQUE_CUSTOMERS
FROM 
    product p
JOIN 
    product_cart pc ON p.PRODUCT_ID = pc.PRODUCT_ID
JOIN 
    cart c ON pc.CART_ID = c.CART_ID
JOIN 
    orders o ON c.CART_ID = o.CART_ID
WHERE 
    p.SHOP_ID = :shop_id
    AND o.ORDER_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD') + 1
    AND o.STATUS = 'completed'
";

$revenue_stmt = oci_parse($conn, $revenue_query);
oci_bind_by_name($revenue_stmt, ":shop_id", $shop_id);
oci_bind_by_name($revenue_stmt, ":start_date", $start_date);
oci_bind_by_name($revenue_stmt, ":end_date", $end_date);
oci_execute($revenue_stmt);
$revenue_data = oci_fetch_assoc($revenue_stmt);

// Query for daily sales breakdown
$daily_sales_query = "
SELECT 
    TO_CHAR(o.ORDER_DATE, 'YYYY-MM-DD') as SALE_DATE,
    COUNT(DISTINCT o.ORDER_ID) as ORDERS_COUNT,
    SUM(p.PRICE * pc.QUANTITY) as DAILY_REVENUE,
    COUNT(DISTINCT o.USER_ID) as UNIQUE_CUSTOMERS
FROM 
    product p
JOIN 
    product_cart pc ON p.PRODUCT_ID = pc.PRODUCT_ID
JOIN 
    cart c ON pc.CART_ID = c.CART_ID
JOIN 
    orders o ON c.CART_ID = o.CART_ID
WHERE 
    p.SHOP_ID = :shop_id
    AND o.ORDER_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD') + 1
    AND o.STATUS = 'completed'
GROUP BY 
    TO_CHAR(o.ORDER_DATE, 'YYYY-MM-DD')
ORDER BY 
    TO_CHAR(o.ORDER_DATE, 'YYYY-MM-DD')
";

$daily_stmt = oci_parse($conn, $daily_sales_query);
oci_bind_by_name($daily_stmt, ":shop_id", $shop_id);
oci_bind_by_name($daily_stmt, ":start_date", $start_date);
oci_bind_by_name($daily_stmt, ":end_date", $end_date);
oci_execute($daily_stmt);

// Fetch all daily sales data
$daily_sales_data = [];
while ($row = oci_fetch_assoc($daily_stmt)) {
    $daily_sales_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Report - <?php echo htmlspecialchars($shop_name); ?></title>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
        }
        
        .content {
            padding: 20px;
            margin-left: 520px;
        }
        
        .report-header {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .report-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .date-picker {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: #007BFF;
        }
        
        .chart-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .tables-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .table-card {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .btn-print {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-print:hover {
            background-color: #0056b3;
        }

        form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        input[type="date"], button[type="submit"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        
        @media print {
            .sidebar, .report-title button, .date-picker {
                display: none;
            }
            
            .content {
                margin-left: 0;
            }
            
            .chart-container, .stat-card, .table-card {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
    
    <div class="content">
        <div class="report-header">
            <div class="report-title">
                <h1>Custom Sales Report</h1>
                <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
            </div>
            <p>Shop: <?php echo htmlspecialchars($shop_name); ?></p>
            <p>Report Period: <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
        </div>
        
        <div class="date-picker">
            <form method="post">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                
                <button type="submit">Generate Report</button>
            </form>
        </div>
        
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="stat-value">£<?php echo number_format($revenue_data['TOTAL_REVENUE'] ?? 0, 2); ?></div>
                <p>For the selected period</p>
            </div>
            
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="stat-value"><?php echo $revenue_data['TOTAL_ORDERS'] ?? 0; ?></div>
                <p>Completed orders</p>
            </div>
            
            <div class="stat-card">
                <h3>Average Order Value</h3>
                <div class="stat-value">£<?php echo number_format($revenue_data['AVERAGE_ORDER_VALUE'] ?? 0, 2); ?></div>
                <p>Revenue per order</p>
            </div>
            
            <div class="stat-card">
                <h3>Unique Customers</h3>
                <div class="stat-value"><?php echo $revenue_data['UNIQUE_CUSTOMERS'] ?? 0; ?></div>
                <p>Different customers</p>
            </div>
        </div>
        
        <div class="chart-container">
            <h2>Daily Sales Performance</h2>
            <canvas id="dailySalesChart"></canvas>
        </div>
        
        <div class="tables-container">
            <div class="table-card">
                <h2>Product Performance</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity Sold</th>
                            <th>Total Sales</th>
                            <th>Order Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        oci_execute($sales_stmt); // Re-execute to reset cursor position
                        $has_sales = false;
                        while ($row = oci_fetch_assoc($sales_stmt)) {
                            $has_sales = true;
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['PRODUCT_NAME']) . "</td>";
                            echo "<td>" . $row['TOTAL_QUANTITY'] . "</td>";
                            echo "<td>£" . number_format($row['TOTAL_SALES'], 2) . "</td>";
                            echo "<td>" . $row['ORDER_COUNT'] . "</td>";
                            echo "</tr>";
                        }
                        
                        if (!$has_sales) {
                            echo "<tr><td colspan='4' style='text-align:center'>No sales data available for the selected period</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="table-card">
                <h2>Daily Sales Breakdown</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                            <th>Unique Customers</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($daily_sales_data)) {
                            echo "<tr><td colspan='4' style='text-align:center'>No daily sales data available for the selected period</td></tr>";
                        } else {
                            foreach ($daily_sales_data as $day) {
                                echo "<tr>";
                                echo "<td>" . date('M d, Y', strtotime($day['SALE_DATE'])) . "</td>";
                                echo "<td>" . $day['ORDERS_COUNT'] . "</td>";
                                echo "<td>£" . number_format($day['DAILY_REVENUE'], 2) . "</td>";
                                echo "<td>" . $day['UNIQUE_CUSTOMERS'] . "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
    // Convert PHP data to JavaScript
    const dailyData = <?php echo !empty($daily_sales_data) ? json_encode($daily_sales_data) : '[]'; ?>;
    
    // Extract dates and values
    const dates = dailyData.map(day => day.SALE_DATE);
    const revenues = dailyData.map(day => day.DAILY_REVENUE);
    const orderCounts = dailyData.map(day => day.ORDERS_COUNT);
    const uniqueCustomers = dailyData.map(day => day.UNIQUE_CUSTOMERS);
    
    // Create chart
    const ctx = document.getElementById('dailySalesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates.map(date => {
                const d = new Date(date);
                return d.toLocaleDateString('en-GB', { month: 'short', day: 'numeric' });
            }),
            datasets: [
                {
                    label: 'Daily Revenue (£)',
                    data: revenues,
                    borderColor: 'rgba(0, 123, 255, 1)',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Number of Orders',
                    data: orderCounts,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.4,
                    yAxisID: 'y1'
                },
                {
                    label: 'Unique Customers',
                    data: uniqueCustomers,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0)',
                    borderWidth: 2,
                    borderDash: [3, 3],
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue (£)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                    title: {
                        display: true,
                        text: 'Count'
                    }
                }
            }
        }
    });

    // Date range validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const startDate = new Date(document.getElementById('start_date').value);
        const endDate = new Date(document.getElementById('end_date').value);
        
        if (endDate < startDate) {
            e.preventDefault();
            alert('End date must be later than or equal to start date');
        }
    });
    </script>
</body>
</html> 