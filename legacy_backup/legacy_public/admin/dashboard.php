<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!is_logged_in()) header('Location: ' . BASE_PATH . '/login.php');
refresh_user_session($pdo);
$u = current_user();

// ======== FILTER PARAMETERS ========
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to   = $_GET['date_to'] ?? '';

$filter_where = '';
$filter_params = [];

if ($filter_date_from !== '') {
  $filter_where .= ' AND o.created_at >= :date_from';
  $filter_params[':date_from'] = $filter_date_from . ' 00:00:00';
}
if ($filter_date_to !== '') {
  $filter_where .= ' AND o.created_at <= :date_to';
  $filter_params[':date_to'] = $filter_date_to . ' 23:59:59';
}

// ======== FETCH REAL DATA ========

// Total users, orders, inventory
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_inventory = $pdo->query("SELECT COUNT(*) FROM inventory")->fetchColumn();

// Sales per product for bar/pie charts
$stmt = $pdo->prepare("
  SELECT i.name AS product, SUM(oi.line_total) AS total_sales
  FROM order_items oi
  JOIN inventory i ON oi.inventory_id = i.id
  JOIN orders o ON oi.order_id = o.id
  WHERE o.status = 'completed' {$filter_where}
  GROUP BY i.id
  ORDER BY total_sales DESC
");
$stmt->execute($filter_params);
$products = [];
$sales = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $products[] = $row['product'];
  $sales[] = (float)$row['total_sales'];
}

// Monthly sales for line chart (last 6 months)
$stmt2 = $pdo->prepare("
  SELECT DATE_FORMAT(o.created_at, '%b') AS month, SUM(o.total_amount) AS total
  FROM orders o
  WHERE o.status = 'completed' {$filter_where}
  GROUP BY YEAR(o.created_at), MONTH(o.created_at)
  ORDER BY o.created_at DESC
  LIMIT 6
");
$stmt2->execute($filter_params);
$months = [];
$month_sales = [];
while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
  array_unshift($months, $r['month']); // keep in chronological order
  array_unshift($month_sales, (float)$r['total']);
}

// Normalize so percentages total exactly 100.00% (round all, then adjust the largest share)
$total_sales_sum = array_sum($sales) ?: 1;
$sales_percent = [];
foreach ($sales as $v) {
  $sales_percent[] = ($v / $total_sales_sum) * 100;
}
$sales_percent = array_map(fn($v) => round($v, 2), $sales_percent);
$diff = round(array_sum($sales_percent), 2) - 100;
if (!empty($sales_percent) && abs($diff) > 0.001) {
  $maxKey = array_search(max($sales_percent), $sales_percent);
  $sales_percent[$maxKey] = round($sales_percent[$maxKey] - $diff, 2);
}

// Daily sales (last 7 days)
$day_extra = ($filter_date_from === '' && $filter_date_to === '') ? ' AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)' : '';
$stmt3 = $pdo->prepare("
  SELECT DATE(o.created_at) AS day, SUM(o.total_amount) AS total
  FROM orders o
  WHERE o.status = 'completed' {$day_extra} {$filter_where}
  GROUP BY DATE(o.created_at)
  ORDER BY day ASC
");
$stmt3->execute($filter_params);
$daily_labels = [];
$daily_sales = [];
while ($r = $stmt3->fetch(PDO::FETCH_ASSOC)) {
  $daily_labels[] = date('M j', strtotime($r['day']));
  $daily_sales[] = (float)$r['total'];
}

// Weekly sales (last 8 weeks)
$week_extra = ($filter_date_from === '' && $filter_date_to === '') ? ' AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)' : '';
$stmt4 = $pdo->prepare("
  SELECT DATE(o.created_at) - INTERVAL WEEKDAY(o.created_at) DAY AS week_start,
         SUM(o.total_amount) AS total
  FROM orders o
  WHERE o.status = 'completed' {$week_extra} {$filter_where}
  GROUP BY week_start
  ORDER BY week_start ASC
");
$stmt4->execute($filter_params);
$weekly_labels = [];
$weekly_sales = [];
while ($r = $stmt4->fetch(PDO::FETCH_ASSOC)) {
  $weekly_labels[] = 'Wk ' . date('M j', strtotime($r['week_start']));
  $weekly_sales[] = (float)$r['total'];
}

// Peak sales hours
$stmt5 = $pdo->prepare("
  SELECT HOUR(o.created_at) AS hour, SUM(o.total_amount) AS total
  FROM orders o
  WHERE o.status = 'completed' {$filter_where}
  GROUP BY hour
  ORDER BY hour ASC
");
$stmt5->execute($filter_params);
$hour_labels = [];
$hour_sales = [];
while ($r = $stmt5->fetch(PDO::FETCH_ASSOC)) {
  $h = (int)$r['hour'];
  $hour_labels[] = date('g A', mktime($h, 0, 0));
  $hour_sales[] = (float)$r['total'];
}

// KPI: Total Sales Today
$stmt_kpi1 = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders o WHERE o.status = 'completed' AND DATE(o.created_at) = CURDATE() {$filter_where}");
$stmt_kpi1->execute($filter_params);
$sales_today = (float)$stmt_kpi1->fetchColumn();

// KPI: Sales Yesterday (for comparison)
$stmt_kpi1y = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders o WHERE o.status = 'completed' AND DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) {$filter_where}");
$stmt_kpi1y->execute($filter_params);
$sales_yesterday = (float)$stmt_kpi1y->fetchColumn();

// KPI: Monthly Revenue
$stmt_kpi2 = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders o WHERE o.status = 'completed' AND YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE()) {$filter_where}");
$stmt_kpi2->execute($filter_params);
$monthly_revenue = (float)$stmt_kpi2->fetchColumn();

// KPI: Monthly Revenue Last Month (for comparison)
$stmt_kpi2p = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders o WHERE o.status = 'completed' AND YEAR(o.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(o.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) {$filter_where}");
$stmt_kpi2p->execute($filter_params);
$monthly_revenue_prev = (float)$stmt_kpi2p->fetchColumn();

// KPI: Total Orders This Month
$stmt_kpi3 = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE o.status = 'completed' AND YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE()) {$filter_where}");
$stmt_kpi3->execute($filter_params);
$orders_this_month = (int)$stmt_kpi3->fetchColumn();

// KPI: Total Orders Last Month (for comparison)
$stmt_kpi3p = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE o.status = 'completed' AND YEAR(o.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(o.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) {$filter_where}");
$stmt_kpi3p->execute($filter_params);
$orders_last_month = (int)$stmt_kpi3p->fetchColumn();

// KPI: Total Products Sold
$stmt_kpi4 = $pdo->prepare("SELECT COUNT(DISTINCT oi.inventory_id) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status = 'completed' AND YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE()) {$filter_where}");
$stmt_kpi4->execute($filter_params);
$total_products = (int)$stmt_kpi4->fetchColumn();

// KPI: Total Products Sold Last Month (for comparison)
$stmt_kpi4p = $pdo->prepare("SELECT COUNT(DISTINCT oi.inventory_id) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status = 'completed' AND YEAR(o.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(o.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) {$filter_where}");
$stmt_kpi4p->execute($filter_params);
$total_products_prev = (int)$stmt_kpi4p->fetchColumn();

// KPI: Best-Selling Product
$stmt_kpi5 = $pdo->prepare("
  SELECT i.name, COUNT(*) AS order_count
  FROM order_items oi
  JOIN inventory i ON oi.inventory_id = i.id
  JOIN orders o ON oi.order_id = o.id
  WHERE o.status = 'completed' {$filter_where}
  GROUP BY i.id
  ORDER BY order_count DESC
  LIMIT 1
");
$stmt_kpi5->execute($filter_params);
$best_product_row = $stmt_kpi5->fetch(PDO::FETCH_ASSOC);
$best_product_name = $best_product_row['name'] ?? 'N/A';
$best_product_count = (int)($best_product_row['order_count'] ?? 0);

// KPI: Sales Growth (this month vs last month)
$sales_growth = $monthly_revenue_prev > 0
  ? round((($monthly_revenue - $monthly_revenue_prev) / $monthly_revenue_prev) * 100, 1)
  : 0;

// Panel 1: Top 5 Best-Selling Products
$stmt_top5 = $pdo->prepare("
  SELECT i.name, SUM(oi.qty) AS qty_sold, SUM(oi.line_total) AS revenue
  FROM order_items oi
  JOIN inventory i ON oi.inventory_id = i.id
  JOIN orders o ON oi.order_id = o.id
  WHERE o.status = 'completed' {$filter_where}
  GROUP BY i.id
  ORDER BY qty_sold DESC
  LIMIT 5
");
$stmt_top5->execute($filter_params);
$top5_products = $stmt_top5->fetchAll(PDO::FETCH_ASSOC);

// Panel 2: Top 5 Least-Selling Products
$stmt_least5 = $pdo->prepare("
  SELECT i.name, SUM(oi.qty) AS qty_sold, SUM(oi.line_total) AS revenue
  FROM order_items oi
  JOIN inventory i ON oi.inventory_id = i.id
  JOIN orders o ON oi.order_id = o.id
  WHERE o.status = 'completed' {$filter_where}
  GROUP BY i.id
  ORDER BY qty_sold ASC
  LIMIT 5
");
$stmt_least5->execute($filter_params);
$least5_products = $stmt_least5->fetchAll(PDO::FETCH_ASSOC);

// Panel 3: Sales Growth Summary (current year monthly)
$stmt_growth = $pdo->prepare("
  SELECT DATE_FORMAT(o.created_at, '%M %Y') AS period,
         YEAR(o.created_at) AS yr, MONTH(o.created_at) AS mo,
         SUM(o.total_amount) AS total
  FROM orders o
  WHERE o.status = 'completed' AND YEAR(o.created_at) = YEAR(CURDATE()) {$filter_where}
  GROUP BY yr, mo
  ORDER BY yr ASC, mo ASC
");
$stmt_growth->execute($filter_params);
$growth_rows = $stmt_growth->fetchAll(PDO::FETCH_ASSOC);

// Panel 4: Peak Sales Day Ranking
$stmt_peakday = $pdo->prepare("
  SELECT DAYNAME(o.created_at) AS day_name, DAYOFWEEK(o.created_at) AS dow,
         SUM(o.total_amount) AS total
  FROM orders o
  WHERE o.status = 'completed' {$filter_where}
  GROUP BY day_name, dow
  ORDER BY total DESC
");
$stmt_peakday->execute($filter_params);
$peakday_rows = $stmt_peakday->fetchAll(PDO::FETCH_ASSOC);
$total_all_sales = array_sum(array_column($peakday_rows, 'total')) ?: 1;

// Footer: Date Range (first and last order dates)
$stmt_dr = $pdo->prepare("SELECT MIN(created_at) AS first_date, MAX(created_at) AS last_date FROM orders o WHERE o.status = 'completed' {$filter_where}");
$stmt_dr->execute($filter_params);
$date_range = $stmt_dr->fetch(PDO::FETCH_ASSOC);
$footer_date_start = $date_range['first_date'] ? date('M j, Y', strtotime($date_range['first_date'])) : 'N/A';
$footer_date_end = $date_range['last_date'] ? date('M j, Y', strtotime($date_range['last_date'])) : 'N/A';

// Footer: Total Customers (distinct customer names from completed orders)
$stmt_cust = $pdo->prepare("SELECT COUNT(DISTINCT customer_name) FROM orders o WHERE o.status = 'completed' {$filter_where}");
$stmt_cust->execute($filter_params);
$footer_customers = (int)$stmt_cust->fetchColumn();

// Footer: Payment Methods breakdown
$stmt_pay = $pdo->prepare("SELECT payment_method, COUNT(*) AS cnt FROM orders o WHERE o.status = 'completed' {$filter_where} GROUP BY payment_method ORDER BY cnt DESC");
$stmt_pay->execute($filter_params);
$payment_rows = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);
$payment_total = array_sum(array_column($payment_rows, 'cnt')) ?: 1;
$payment_breakdown = [];
foreach ($payment_rows as $pr) {
  $payment_breakdown[] = $pr['payment_method'] . ' ' . round(($pr['cnt'] / $payment_total) * 100) . '%';
}

// Footer: Last Updated
$footer_last_updated = date('M j, Y \a\t g:i A');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/responsive.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3"></script>
  <style>
    /* === Sage Green Theme === */
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f5f7f2;
      color: #2f3b2f;
      display: flex;
      height: 100vh;
      margin: 0;
    }
    .sidebar {
      width: 230px;
      background-color:#f10000;
      color: #fff;
      padding-top: 1.5rem;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
    }
    .sidebar a {
      color: #fff;
      text-decoration: none;
      display: block;
      padding: 12px 20px;
      transition: 0.3s;
      border-left: 4px solid transparent;
    }
    .sidebar a:hover, .sidebar a.active {
      background-color: #ef9a9a;
      border-left: 4px solid #fff;
    }
    .sidebar-footer {
      padding: 1rem;
      text-align: center;
      border-top: 1px solid rgba(255,255,255,0.2);
    }
    .main-content {
      margin-left: 230px;
      padding: 2rem;
      width: 100%;
    }
    .chart-container {
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 1rem;
      margin-bottom: 1.5rem;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .chart-container:hover {
      transform: scale(1.02);
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    .chart-container canvas {
      width: 100% !important;
      height: 220px !important;
    }
    .chart-modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.7);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }
    .chart-modal-content {
      background: #fff;
      border-radius: 12px;
      padding: 2rem;
      width: 90vw;
      height: 85vh;
      position: relative;
      display: flex;
      flex-direction: column;
    }
    .chart-modal-content canvas {
      flex: 1;
      width: 100% !important;
      height: 100% !important;
    }
    .chart-modal-close {
      position: absolute;
      top: 10px;
      right: 20px;
      font-size: 2rem;
      cursor: pointer;
      color: #666;
      line-height: 1;
      z-index: 10;
    }
    .chart-modal-close:hover {
      color: #000;
    }
    .welcome-msg {
      background: #18b318;
      padding: 1rem;
      border-radius: 10px;
      text-align: center;
      color: #fff;
      font-weight: 500;
      animation: fadeOut 1s ease 3s forwards;
    }
    @keyframes fadeOut {
      to { opacity: 0; visibility: hidden; }
    }
    .kpi-row {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
    }
    .kpi-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      padding: 0.1rem 1rem;
      flex: 1;
      min-width: 140px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .kpi-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 14px rgba(0,0,0,0.12);
    }
    .kpi-icon {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 0.4rem;
      font-size: 0.9rem;
      color: #fff;
    }
    .kpi-icon svg {
      width: 16px;
      height: 16px;
    }
    .kpi-label {
      font-size: 0.6rem;
      font-weight: 600;
      letter-spacing: 0.5px;
      color: #888;
      text-transform: uppercase;
      margin-bottom: 0.15rem;
    }
    .kpi-value {
      font-size: 1.1rem;
      font-weight: 700;
      color: #2f3b2f;
      margin-bottom: 0.2rem;
      line-height: 1.2;
    }
    .kpi-footer {
      font-size: 0.62rem;
      color: #999;
    }
    .kpi-footer .up { color: #27ae60; font-weight: 600; }
    .kpi-footer .down { color: #e74c3c; font-weight: 600; }

    .panels-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .data-panel {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      overflow: hidden;
    }
    .data-panel .panel-header {
      padding: 0.5rem 0.75rem;
      color: #fff;
      font-size: 0.65rem;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }
    .data-panel table {
      width: 100%;
      border-collapse: collapse;
    }
    .data-panel th {
      font-size: 0.58rem;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      padding: 0.4rem 0.6rem;
      text-align: left;
      font-weight: 600;
    }
    .data-panel td {
      font-size: 0.62rem;
      padding: 0.35rem 0.6rem;
      border-top: 1px solid #f0f0f0;
      white-space: nowrap;
    }
    .data-panel tbody tr:nth-child(odd) { background: #fafafa; }
    .data-panel tbody tr:hover { background: #f5f5f5; }
    .data-panel .panel-footer {
      padding: 0.35rem 0.75rem;
      font-size: 0.55rem;
      color: #aaa;
      font-style: italic;
    }
    .star-icon {
      color: #f1c40f;
      font-size: 0.7rem;
      margin-right: 2px;
    }
    .growth-up { color: #1abc9c; font-weight: 600; }
    .growth-dash { color: #ccc; }

    .footer-row {
      display: flex;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    .footer-block {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      padding: 0.8rem 1rem;
      flex: 1;
      display: flex;
      align-items: center;
      gap: 0.8rem;
    }
    .footer-icon {
      width: 38px;
      height: 38px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      color: #fff;
    }
    .footer-icon svg {
      width: 18px;
      height: 18px;
    }
    .footer-text .footer-title {
      font-size: 0.72rem;
      font-weight: 600;
      color: #2f3b2f;
      margin-bottom: 1px;
    }
    .footer-text .footer-sub {
      font-size: 0.6rem;
      color: #999;
    }

    .filter-bar {
      display: flex;
      align-items: flex-end;
      gap: 3rem;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      padding: 0.3rem 1rem;
      margin-bottom: 1.2rem;
      flex-wrap: wrap;
    }
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .filter-group label {
      font-size: 0.6rem;
      font-weight: 600;
      text-transform: uppercase;
      color: #888;
      letter-spacing: 0.3px;
    }
    .filter-group input,
    .filter-group select {
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 0.35rem 0.5rem;
      font-size: 0.68rem;
      font-family: inherit;
      color: #333;
      background: #fafafa;
      outline: none;
      transition: border-color 0.2s;
    }
    .filter-group input:focus,
    .filter-group select:focus {
      border-color: #990000;
    }
    .filter-actions {
      display: flex;
      gap: 0.4rem;
      align-items: flex-end;
    }
    .btn-filter {
      background: #990000;
      color: #fff;
      border: none;
      border-radius: 6px;
      padding: 0.38rem 1rem;
      font-size: 0.68rem;
      font-weight: 600;
      cursor: pointer;
      font-family: inherit;
      transition: background 0.2s;
    }
    .btn-filter:hover { background: #7a0000; }
    .btn-clear {
      background: #eee;
      color: #666;
      border: none;
      border-radius: 6px;
      padding: 0.38rem 0.8rem;
      font-size: 0.68rem;
      text-decoration: none;
      font-family: inherit;
      transition: background 0.2s;
    }
    .btn-clear:hover { background: #ddd; }

    #panelModalBody .data-panel {
      box-shadow: none;
      margin-bottom: 0;
    }
    #panelModalBody .data-panel th {
      font-size: 0.78rem;
      padding: 0.65rem 0.8rem;
    }
    #panelModalBody .data-panel td {
      font-size: 0.82rem;
      padding: 0.55rem 0.8rem;
    }
    #panelModalBody .data-panel .panel-header {
      font-size: 0.85rem;
      padding: 0.7rem 1rem;
    }
  </style>
</head>
<body>
 <button class="hamburger">&#9776;</button>
 <div class="sidebar-overlay"></div>
 <!-- Sidebar -->
  <div class="sidebar">
    <div>
      
      <h5 class="text-center mb-4"><img src="<?= BASE_PATH ?>/images/capj.jpg" alt="Captain J" style="float: center; width: 30%; border-radius: 200px 200px 200px 200px; border: 3px solid #fff; margin-bottom: 15px;"> Captain J <?= ucfirst($u['role']) ?> </h5>

      <?php if ($u['role'] === 'admin'): ?>
        <a href="profile.php" >Profile</a>
        <a href="dashboard.php"class="active">Dashboard</a>
        <a href="inventory.php">Inventory</a>
        <a href="users.php">Users</a>
        <a href="ordering.php?v=2">Create Order</a>
        <a href="order.php">Orders</a>

      <?php elseif ($u['role'] === 'staff'): ?>
       <a href="profile.php" >Profile</a>
        <a href="inventory.php"class="active">Inventory</a>
        <a href="ordering.php?v=2">Create Order</a>
        <a href="order.php">Orders</a>
      <?php endif; ?>

    </div>
    <div class="sidebar-footer">
      <p class="mb-1 small">Logged in as:</p>
      <strong><?= htmlspecialchars($u['full_name'] ?? $u['username']) ?></strong><br>
      <a href="<?= BASE_PATH ?>/logout.php" class="btn btn-secondary btn-sm mt-2">Logout</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div id="welcome" class="welcome-msg">
      👋 Welcome, <span style="color:#fff"><?= htmlspecialchars($u['full_name'] ?? $u['username']) ?></span>!
    </div>

    <h4 class="mb-4">Dashboard Overview</h4>

    <form method="GET" class="filter-bar">
      <div class="filter-group">
        <label>Date From</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($filter_date_from) ?>">
      </div>
      <div class="filter-group">
        <label>Date To</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($filter_date_to) ?>">
      </div>
      <div class="filter-actions">
        <button type="submit" class="btn-filter">Apply</button>
        <?php if ($filter_date_from !== '' || $filter_date_to !== ''): ?>
          <a href="dashboard.php" class="btn-clear">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-icon" style="background:#e74c3c;">
          <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        </div>
        <div class="kpi-label">Total Sales Today</div>
        <div class="kpi-value">₱ <?= number_format($sales_today, 2) ?></div>
        <div class="kpi-footer">
          <?php
            $today_pct = $sales_yesterday > 0 ? round((($sales_today - $sales_yesterday) / $sales_yesterday) * 100, 1) : 0;
            $dir = $today_pct >= 0 ? 'up' : 'down';
            $arrow = $today_pct >= 0 ? '&#9650;' : '&#9660;';
          ?>
          <span class="<?= $dir ?>"><?= $arrow ?> <?= abs($today_pct) ?>%</span> vs yesterday
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon" style="background:#27ae60;">
          <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="12" width="4" height="9"/><rect x="10" y="7" width="4" height="14"/><rect x="17" y="3" width="4" height="18"/></svg>
        </div>
        <div class="kpi-label">Monthly Revenue</div>
        <div class="kpi-value">₱ <?= number_format($monthly_revenue, 2) ?></div>
        <div class="kpi-footer">
          <?php
            $m_rev_pct = $monthly_revenue_prev > 0 ? round((($monthly_revenue - $monthly_revenue_prev) / $monthly_revenue_prev) * 100, 1) : 0;
            $m_dir = $m_rev_pct >= 0 ? 'up' : 'down';
            $m_arrow = $m_rev_pct >= 0 ? '&#9650;' : '&#9660;';
          ?>
          <span class="<?= $m_dir ?>"><?= $m_arrow ?> <?= abs($m_rev_pct) ?>%</span> vs last month
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon" style="background:#3498db;">
          <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        </div>
        <div class="kpi-label">Total Orders</div>
        <div class="kpi-value"><?= number_format($orders_this_month) ?></div>
        <div class="kpi-footer">
          <?php
            $o_pct = $orders_last_month > 0 ? round((($orders_this_month - $orders_last_month) / $orders_last_month) * 100, 1) : 0;
            $o_dir = $o_pct >= 0 ? 'up' : 'down';
            $o_arrow = $o_pct >= 0 ? '&#9650;' : '&#9660;';
          ?>
          <span class="<?= $o_dir ?>"><?= $o_arrow ?> <?= abs($o_pct) ?>%</span> vs last month
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon" style="background:#f39c12;">
          <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        </div>
        <div class="kpi-label">Total Products</div>
        <div class="kpi-value"><?= $total_products ?></div>
        <div class="kpi-footer">
          <?php
            $a_pct = $total_products_prev > 0 ? round((($total_products - $total_products_prev) / $total_products_prev) * 100, 1) : 0;
            $a_dir = $a_pct >= 0 ? 'up' : 'down';
            $a_arrow = $a_pct >= 0 ? '&#9650;' : '&#9660;';
          ?>
          <span class="<?= $a_dir ?>"><?= $a_arrow ?> <?= abs($a_pct) ?>%</span> vs last month
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon" style="background:#9b59b6;">
          <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15l-2 5l9-9l-5 2l2-5l-9 9z"/></svg>
        </div>
        <div class="kpi-label">Best-Selling Product</div>
        <div class="kpi-value"><?= htmlspecialchars($best_product_name) ?></div>
        <div class="kpi-footer"><?= number_format($best_product_count) ?> Orders</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon" style="background:#1abc9c;">
          <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        </div>
        <div class="kpi-label">Sales Growth</div>
        <div class="kpi-value" style="color: <?= $sales_growth >= 0 ? '#27ae60' : '#e74c3c' ?>;">
          <?= $sales_growth >= 0 ? '&#9650;' : '&#9660;' ?> <?= abs($sales_growth) ?>%
        </div>
        <div class="kpi-footer">vs last month</div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4">
        <div class="chart-container" onclick="openZoom('barChart')">
          <h6 class="text-center">Sales per Product </h6>
          <canvas id="barChart"></canvas>
        </div>
      </div>
      <div class="col-md-4">
        <div class="chart-container" onclick="openZoom('lineChart')">
          <h6 class="text-center">Monthly Sales Trend </h6>
          <canvas id="lineChart"></canvas>
        </div>
      </div>
      <div class="col-md-4">
        <div class="chart-container" onclick="openZoom('pieChart')">
          <h6 class="text-center">Sales Share</h6>
          <canvas id="pieChart"></canvas>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4">
        <div class="chart-container" onclick="openZoom('dailyChart')">
          <h6 class="text-center">Daily Sales (Last 7 Days)</h6>
          <canvas id="dailyChart"></canvas>
        </div>
      </div>
      <div class="col-md-4">
        <div class="chart-container" onclick="openZoom('weeklyChart')">
          <h6 class="text-center">Weekly Sales (Last 8 Weeks)</h6>
          <canvas id="weeklyChart"></canvas>
        </div>
      </div>
      <div class="col-md-4">
        <div class="chart-container" onclick="openZoom('peakHoursChart')">
          <h6 class="text-center">Peak Sales Hours</h6>
          <canvas id="peakHoursChart"></canvas>
        </div>
      </div>
    </div>

    <div class="panels-grid">
      <!-- Panel 1: Top 5 Best-Selling Products -->
      <div class="data-panel" style="cursor:pointer;" onclick="openPanelZoom(this)">
        <div class="panel-header" style="background:#990000;">Top 5 Best-Selling Products</div>
        <table>
          <thead>
            <tr style="background:#990000; color:#fff;">
              <th>Rank</th><th>Product</th><th>Qty Sold</th><th>Revenue</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($top5_products as $i => $row): ?>
            <tr>
              <td>
                <?php if ($i < 3): ?><span class="star-icon">&#9733;</span><?php endif; ?>
                <?= $i + 1 ?>
              </td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= number_format($row['qty_sold']) ?></td>
              <td>₱<?= number_format($row['revenue'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($top5_products)): ?>
            <tr><td colspan="4" style="text-align:center;color:#aaa;">No data</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Panel 2: Top 5 Least-Selling Products -->
      <div class="data-panel" style="cursor:pointer;" onclick="openPanelZoom(this)">
        <div class="panel-header" style="background:#333;">Top 5 Least-Selling Products</div>
        <table>
          <thead>
            <tr style="background:#333; color:#fff;">
              <th>Rank</th><th>Product</th><th>Qty Sold</th><th>Revenue</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($least5_products as $i => $row): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= number_format($row['qty_sold']) ?></td>
              <td>₱<?= number_format($row['revenue'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($least5_products)): ?>
            <tr><td colspan="4" style="text-align:center;color:#aaa;">No data</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Panel 3: Sales Growth Summary -->
      <div class="data-panel" style="cursor:pointer;" onclick="openPanelZoom(this)">
        <div class="panel-header" style="background:#990000;">Sales Growth Summary</div>
        <table>
          <thead>
            <tr style="background:#990000; color:#fff;">
              <th>Period</th><th>Sales</th><th>Growth</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($growth_rows as $i => $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['period']) ?></td>
              <td>₱<?= number_format($row['total'], 2) ?></td>
              <td>
                <?php if ($i === 0): ?>
                  <span class="growth-dash">--</span>
                <?php else: ?>
                  <?php
                    $prev_total = $growth_rows[$i - 1]['total'];
                    $g = $prev_total > 0 ? round((($row['total'] - $prev_total) / $prev_total) * 100, 2) : 0;
                  ?>
                  <span class="growth-up">&#9650; <?= number_format($g, 2) ?>%</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($growth_rows)): ?>
            <tr><td colspan="3" style="text-align:center;color:#aaa;">No data</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <div class="panel-footer">*As of <?= date('F j, Y') ?></div>
      </div>

      <!-- Panel 4: Peak Sales Day Ranking -->
      <div class="data-panel" style="cursor:pointer;" onclick="openPanelZoom(this)">
        <div class="panel-header" style="background:#333;">Peak Sales Day Ranking</div>
        <table>
          <thead>
            <tr style="background:#333; color:#fff;">
              <th>Day</th><th>Sales</th><th>% Share</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($peakday_rows as $row): ?>
            <?php $pct = $total_all_sales > 0 ? round(($row['total'] / $total_all_sales) * 100, 1) : 0; ?>
            <tr>
              <td><?= htmlspecialchars($row['day_name']) ?></td>
              <td>₱<?= number_format($row['total'], 2) ?></td>
              <td><?= number_format($pct, 1) ?>%</td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($peakday_rows)): ?>
            <tr><td colspan="3" style="text-align:center;color:#aaa;">No data</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="footer-row">
      <div class="footer-block">
        <div class="footer-icon" style="background:#5b6abf;">
          <svg fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="footer-text">
          <div class="footer-title">Date Range</div>
          <div class="footer-sub"><?= $footer_date_start ?> — <?= $footer_date_end ?></div>
        </div>
      </div>

      <div class="footer-block">
        <div class="footer-icon" style="background:#3498db;">
          <svg fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div class="footer-text">
          <div class="footer-title">Total Customers</div>
          <div class="footer-sub"><?= number_format($footer_customers) ?> Customers</div>
        </div>
      </div>

      <div class="footer-block">
        <div class="footer-icon" style="background:#1abc9c;">
          <svg fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        </div>
        <div class="footer-text">
          <div class="footer-title">Payment Methods</div>
          <div class="footer-sub"><?= implode(' • ', $payment_breakdown) ?: 'N/A' ?></div>
        </div>
      </div>

      <div class="footer-block">
        <div class="footer-icon" style="background:#e74c3c;">
          <svg fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="footer-text">
          <div class="footer-title">Last Updated</div>
          <div class="footer-sub"><?= $footer_last_updated ?></div>
        </div>
      </div>
    </div>

  </div>

  <!-- Zoom Modal -->
  <div class="chart-modal-overlay" id="chartModal" onclick="closeZoom(event)">
    <div class="chart-modal-content" onclick="event.stopPropagation()">
      <span class="chart-modal-close" onclick="closeZoom(event)">&times;</span>
      <canvas id="modalChart"></canvas>
    </div>
  </div>

  <!-- Panel Zoom Modal -->
  <div class="chart-modal-overlay" id="panelModal" onclick="closePanelZoom(event)">
    <div class="chart-modal-content" onclick="event.stopPropagation()">
      <span class="chart-modal-close" onclick="closePanelZoom(event)">&times;</span>
      <div id="panelModalBody"></div>
    </div>
  </div>

  <!-- Chart.js Script -->
  <script>
  const products = <?= json_encode($products) ?>;
  const sales = <?= json_encode($sales) ?>;
  const salesPercent = <?= json_encode($sales_percent) ?>;
  const months = <?= json_encode($months) ?>;
  const monthSales = <?= json_encode($month_sales) ?>;
  const dailyLabels = <?= json_encode($daily_labels) ?>;
  const dailySales = <?= json_encode($daily_sales) ?>;
  const weeklyLabels = <?= json_encode($weekly_labels) ?>;
  const weeklySales = <?= json_encode($weekly_sales) ?>;
  const hourLabels = <?= json_encode($hour_labels) ?>;
  const hourSales = <?= json_encode($hour_sales) ?>;

  const charts = {};

  // Generate unique colors using golden angle — every chart gets distinct hues
  function uniqueColors(count, offset = 0, alpha = 1) {
    const colors = [];
    for (let i = 0; i < count; i++) {
      const hue = (offset + i) * 137.508 % 360;
      colors.push(alpha === 1
        ? `hsl(${hue}, 65%, 55%)`
        : `hsla(${hue}, 65%, 55%, ${alpha})`);
    }
    return colors;
  }

  // Bar Chart
  charts.barChart = new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
      labels: products,
      datasets: [{
        label: 'Sales (₱)',
        data: sales,
        backgroundColor: uniqueColors(products.length, 0)
      }]
    },
    options: {
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: value => '₱' + value.toLocaleString()
          }
        }
      }
    }
  });

  // Line Chart
  charts.lineChart = new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
      labels: months,
      datasets: [{
        label: 'Sales (₱)',
        data: monthSales,
        borderColor: uniqueColors(1, 400)[0],
        backgroundColor: uniqueColors(1, 400, 0.12)[0],
        fill: true,
        tension: 0.3
      }]
    },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
  });

  // Pie Chart
  charts.pieChart = new Chart(document.getElementById('pieChart'), {
    type: 'pie',
    data: {
      labels: products,
      datasets: [{
        data: sales,
        backgroundColor: uniqueColors(products.length, 100)
      }]
    },
    options: {
      maintainAspectRatio: false,
      plugins: {
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.raw || 0;
              const percent = salesPercent[context.dataIndex];
              return `${label}: ₱${value.toLocaleString()} (${percent}%)`;
            }
          }
        },
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 14,
            font: { size: 11 },
            generateLabels: function(chart) {
              const data = chart.data;
              return data.labels.map((label, i) => ({
                text: `${label} (${salesPercent[i]}%)`,
                fillStyle: data.datasets[0].backgroundColor[i],
                strokeStyle: 'transparent',
                index: i
              }));
            }
          }
        }
      }
    }
  });

  // Daily Sales Chart (Doughnut)
  charts.dailyChart = new Chart(document.getElementById('dailyChart'), {
    type: 'doughnut',
    data: {
      labels: dailyLabels.map((d, i) => `${d}: ₱${dailySales[i].toLocaleString()}`),
      datasets: [{
        data: dailySales,
        backgroundColor: uniqueColors(dailyLabels.length, 200)
      }]
    },
    options: {
      maintainAspectRatio: false,
      cutout: '50%',
      plugins: {
        tooltip: {
          callbacks: {
            label: function(context) {
              return `${context.label}: ₱${context.raw.toLocaleString()}`;
            }
          }
        },
        legend: {
          position: 'bottom',
          labels: { boxWidth: 14, font: { size: 11 } }
        }
      }
    }
  });

  // Weekly Sales — Horizontal Bar Chart (each bar gets its own distinct color, like Peak Sales Hours)
  const weeklyAvg = weeklySales.reduce((a, b) => a + b, 0) / (weeklySales.length || 1);
  charts.weeklyChart = new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: {
      labels: weeklyLabels,
      datasets: [{
        label: 'Sales (₱)',
        data: weeklySales,
        backgroundColor: uniqueColors(weeklyLabels.length, 350),
        borderRadius: 4
      }]
    },
    options: {
      indexAxis: 'y',
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        annotation: {
          annotations: {
            targetLine: {
              type: 'line',
              xMin: weeklyAvg,
              xMax: weeklyAvg,
              borderColor: '#2c3e50',
              borderWidth: 3,
              borderDash: [6, 3],
              label: { display: false }
            }
          }
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          ticks: {
            callback: value => '₱' + value.toLocaleString()
          }
        }
      }
    }
  });

  // Peak Sales Hours — Bar Chart (each bar gets its own distinct color, like Sales per Product)
  const peakColors = uniqueColors(hourLabels.length, 300);
  charts.peakHoursChart = new Chart(document.getElementById('peakHoursChart'), {
    type: 'bar',
    data: {
      labels: hourLabels,
      datasets: [{
        label: 'Sales (₱)',
        data: hourSales,
        backgroundColor: peakColors,
        borderRadius: 4
      }]
    },
    options: {
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: value => '₱' + value.toLocaleString()
          }
        }
      }
    }
  });

  // --- Zoom Modal ---
  let zoomChart = null;

  // Deep-clone config while keeping function references (tooltip/legend callbacks) intact
  function deepCloneKeepFns(obj) {
    if (typeof obj === 'function') return obj;
    if (obj === null || typeof obj !== 'object') return obj;
    if (Array.isArray(obj)) return obj.map(deepCloneKeepFns);
    const out = {};
    for (const k in obj) out[k] = deepCloneKeepFns(obj[k]);
    return out;
  }

  function openZoom(id) {
    const src = charts[id];
    if (!src) return;

    const modal = document.getElementById('chartModal');
    const canvas = document.getElementById('modalChart');

    if (zoomChart) { zoomChart.destroy(); zoomChart = null; }

    const data = deepCloneKeepFns(src.config.data);
    const type = src.config.type;
    let opts = deepCloneKeepFns(src.config.options);
    opts.responsive = true;
    opts.maintainAspectRatio = false;
    if (opts.scales) {
      Object.values(opts.scales).forEach(s => {
        if (s.ticks && s.ticks.callback) delete s.ticks.callback;
      });
    }

    zoomChart = new Chart(canvas, { type, data, options: opts });
    modal.style.display = 'flex';
  }

  function closeZoom(e) {
    if (e && e.target !== e.currentTarget && e.target.className !== 'chart-modal-close') return;
    document.getElementById('chartModal').style.display = 'none';
    if (zoomChart) { zoomChart.destroy(); zoomChart = null; }
  }

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && document.getElementById('chartModal').style.display === 'flex') {
      closeZoom(e);
    }
    if (e.key === 'Escape' && document.getElementById('panelModal').style.display === 'flex') {
      closePanelZoom(e);
    }
  });

  // --- Panel Zoom ---
  function openPanelZoom(el) {
    const modal = document.getElementById('panelModal');
    const body = document.getElementById('panelModalBody');
    const clone = el.cloneNode(true);
    clone.style.cursor = 'default';
    clone.onclick = null;
    clone.style.width = '100%';
    body.innerHTML = '';
    body.appendChild(clone);
    modal.style.display = 'flex';
  }

  function closePanelZoom(e) {
    if (e && e.target !== e.currentTarget && e.target.className !== 'chart-modal-close') return;
    document.getElementById('panelModal').style.display = 'none';
  }

  // Auto-hide welcome message
  setTimeout(() => {
    const msg = document.getElementById('welcome');
    if (msg) msg.style.display = 'none';
  }, 3000);
</script>

<script src="../js/mobile-sidebar.js"></script>
</body>
</html>
