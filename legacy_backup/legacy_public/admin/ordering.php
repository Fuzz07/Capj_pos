<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!is_logged_in()) header('Location: ' . BASE_PATH . '/login.php');
refresh_user_session($pdo);
$u = current_user();

// Fetch active items from inventory
$items = $pdo->prepare("SELECT id, name, price, stock_qty FROM inventory WHERE is_active=1 ORDER BY name");
$items->execute();
$items = $items->fetchAll();

// Low stock / out of stock items for notification banner
$lowStockItems = $pdo->query("SELECT name, stock_qty FROM inventory WHERE is_active = 1 AND stock_qty > 0 AND stock_qty <= " . LOW_STOCK_THRESHOLD . " ORDER BY stock_qty ASC")->fetchAll();
$outOfStockItems = $pdo->query("SELECT name FROM inventory WHERE is_active = 1 AND (stock_qty = 0 OR stock_qty IS NULL) ORDER BY name ASC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Create Order - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
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
      background-color: #f10000;
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
    .card {
      background: #fff;
      border: none;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      margin-bottom: 1.5rem;
    }
    .btn-sage {
      background-color: #18b318;
      color: #fff;
      border: none;
    }
    .btn-sage:hover {
      background-color: #18b318;
    }
    input[type="number"] {
      width: 70px;
      text-align: center;
    }
    #searchInput {
      max-width: 350px;
      border: 2px solid #c96a6a;
      border-radius: 8px;
    }
    .text-danger {
      color: #b22222 !important;
      font-weight: bold;
    }
    .badge-out {
      background-color: #dc3545;
      color: white;
      font-size: 0.85rem;
      padding: 4px 10px;
      border-radius: 20px;
    }
  </style>
</head>
<body>
  <button class="hamburger">&#9776;</button>
  <div class="sidebar-overlay"></div>
  <!-- Sidebar -->
  <div class="sidebar">
    <div>
      <h5 class="text-center mb-4"><img src="<?= BASE_PATH ?>/images/capj.jpg" alt="Captain J" style="float: center; width: 30%; border-radius: 200px 200px 200px 200px; border: 3px solid #fff; margin-bottom: 15px;"> Captain J <?= ucfirst($u['role']) ?></h5>

      <?php if ($u['role'] === 'admin'): ?>
        <a href="profile.php">Profile</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="inventory.php">Inventory</a>
        <a href="users.php">Users</a>
        <a href="ordering.php?v=2" class="active">Create Order</a>
        <a href="order.php">Orders</a>
      <?php elseif ($u['role'] === 'staff'): ?>
        <a href="profile.php">Profile</a>
        <a href="inventory.php">Inventory</a>
        <a href="ordering.php?v=2" class="active">Create Order</a>
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
    <h4 class="mb-4">Create New Order</h4>

    <?php if (!empty($lowStockItems) || !empty($outOfStockItems)): ?>
      <div class="alert alert-danger py-2 px-3 mb-3" id="stockAlert">
        <?php if (!empty($outOfStockItems)): ?>
          <strong>🛑 Out of Stock:</strong>
          <?= implode(', ', array_map(fn($i) => htmlspecialchars($i['name']), $outOfStockItems)) ?>
          <br>
        <?php endif; ?>
        <?php if (!empty($lowStockItems)): ?>
          <strong>⚠️ Low Stock:</strong>
          <?php foreach ($lowStockItems as $i => $ls): ?>
            <span class="fw-bold"><?= htmlspecialchars($ls['name']) ?></span> (<?= (int)$ls['stock_qty'] ?>)<?= $i < count($lowStockItems) - 1 ? ', ' : '' ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="card p-4">
      <form method="post" action="process-order.php" id="orderForm">

        <!-- Customer Name + Submit Button -->
        <div class="mb-3 d-flex justify-content-end align-items-end gap-2">
          <div>
            <label class="form-label">Customer Name</label>
            <input name="customer_name" id="customer_name" class="form-control" placeholder="Enter customer name" required style="width:480px; border-radius:8px; padding:8px 12px; border:1px solid #ccc;">
          </div>
          <div>
            <button class="btn btn-sage btn-lg">Submit Order</button>
          </div>
        </div>

        <!-- Search bar -->
        <div class="mb-3 d-flex justify-content-start align-items-center gap-2">
          <h6 class="m-0">Available Items</h6>
          <input type="text" id="searchInput" class="form-control" placeholder="Search item name, price, or stock..." style="width:400px; border-radius:8px; padding:8px 12px; border:2px solid #18b318;">
        </div>

        <div class="table-responsive-wrap">
        <table class="table table-bordered align-middle text-center" id="itemsTable">
          <thead class="table-success">
            <tr>
              <th>Item</th>
              <th>Price (₱)</th>
              <th>Available</th>
              <th>Qty</th>
            </tr>
          </thead>
          <tbody id="orderingTableBody">
            <?php foreach($items as $it): ?>
            <tr>
              <td class="text-start" data-name="<?= htmlspecialchars($it['name']) ?>"><?= htmlspecialchars($it['name']) ?></td>
              <td><?= number_format($it['price'], 2) ?></td>
              <td class="stock-cell <?= (int)$it['stock_qty'] <= 5 ? 'text-danger fw-bold' : '' ?>">
                <?php if ((int)$it['stock_qty'] > 0): ?>
                  <?= (int)$it['stock_qty'] ?>
                <?php else: ?>
                  <span class="badge-out">Out of Stock</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="input-group input-group-sm mx-auto" style="width:140px;">
                  <button type="button" class="btn btn-sage btn-minus" data-id="<?= $it['id'] ?>">−</button>
                  <input type="number"
                         name="qty[<?= $it['id'] ?>]"
                         value="0"
                         min="0"
                         max="<?= $it['stock_qty'] ?>"
                         class="form-control text-center qty-input"
                         data-id="<?= $it['id'] ?>"
                         <?= $it['stock_qty'] == 0 ? 'disabled' : '' ?>>
                  <button type="button" class="btn btn-sage btn-add" data-id="<?= $it['id'] ?>">+</button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>

      </form>

      <!-- Pagination (kept outside the form so it never submits the order) -->
      <div class="d-flex justify-content-center align-items-center mt-3 gap-3">
        <button type="button" class="btn btn-outline-secondary" id="orderPrevBtn" disabled>Previous</button>
        <span id="orderPageInfo">Page 1 of 1</span>
        <button type="button" class="btn btn-outline-secondary" id="orderNextBtn" disabled>Next</button>
      </div>
    </div>
  </div>

  <script>
    // === Order Form Validation ===
    document.getElementById('orderForm').addEventListener('submit', function(e) {
      const name = document.getElementById('customer_name').value.trim();
      const qtyInputs = document.querySelectorAll('.qty-input');
      let hasOrder = false;

      if (name.length < 3) {
        alert('Customer name must be at least 3 letters long.');
        e.preventDefault();
        return;
      }

      qtyInputs.forEach(input => {
        if (parseInt(input.value) > 0) hasOrder = true;
      });

      if (!hasOrder) {
        alert('Please select at least one item to order.');
        e.preventDefault();
      }
    });

    // === Pagination (25 per page, respects search filter) ===
    const ORDER_PAGE_SIZE = 25;
    let orderCurrentPage = 1;

    function getSearchVisibleRows() {
      return Array.from(document.querySelectorAll('#orderingTableBody tr'))
        .filter(function(r) { return r.dataset.searchHidden !== 'true'; });
    }

    function paginateOrderTable() {
      const visible = getSearchVisibleRows();
      const totalVisible = visible.length;
      const totalPages = Math.ceil(totalVisible / ORDER_PAGE_SIZE) || 1;

      if (orderCurrentPage > totalPages) orderCurrentPage = totalPages;

      visible.forEach(function(r, i) {
        const onPage = (i >= (orderCurrentPage - 1) * ORDER_PAGE_SIZE && i < orderCurrentPage * ORDER_PAGE_SIZE);
        r.style.display = onPage ? '' : 'none';
      });

      document.getElementById('orderPageInfo').textContent = 'Page ' + orderCurrentPage + ' of ' + totalPages + ' (' + totalVisible + ' items)';
      document.getElementById('orderPrevBtn').disabled = orderCurrentPage <= 1;
      document.getElementById('orderNextBtn').disabled = orderCurrentPage >= totalPages;
    }

    document.getElementById('orderPrevBtn').addEventListener('click', function(e) {
      e.preventDefault();
      if (orderCurrentPage > 1) { orderCurrentPage--; paginateOrderTable(); }
    });

    document.getElementById('orderNextBtn').addEventListener('click', function(e) {
      e.preventDefault();
      var totalPages = Math.ceil(getSearchVisibleRows().length / ORDER_PAGE_SIZE) || 1;
      if (orderCurrentPage < totalPages) { orderCurrentPage++; paginateOrderTable(); }
    });

    // === Live Search ===
    document.getElementById('searchInput').addEventListener('keyup', function() {
      var filter = this.value.toLowerCase();
      var rows = document.querySelectorAll('#orderingTableBody tr');
      rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.dataset.searchHidden = text.includes(filter) ? 'false' : 'true';
      });
      orderCurrentPage = 1;
      paginateOrderTable();
    });

    paginateOrderTable();

    function escapeRegex(str) {
      return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function removeFromBanner(productName) {
      var alert = document.getElementById('stockAlert');
      if (!alert) return;
      var html = alert.innerHTML;
      var lowRegex = new RegExp('<span class="fw-bold">' + escapeRegex(productName) + '<\\/span>\\s*\\(\\d+\\)');
      html = html.replace(lowRegex, '').replace(/,?\s*,/, ',').replace(/,\s*$/, '').replace(/^\s*,/, '');
      // Remove from out of stock section
      var oosRegex = new RegExp(escapeRegex(productName), 'g');
      html = html.replace(oosRegex, '').replace(/,?\s*,/, ',').replace(/,\s*$/, '').replace(/^\s*,/, '');
      // Clean up empty sections
      html = html.replace(/<strong>[^<]*<\/strong>:\s*(<br\s*\/?>)?\s*/g, function(m) { return ''; });
      html = html.trim();
      if (!html) {
        alert.style.display = 'none';
      } else {
        alert.innerHTML = html;
      }
    }

    // === Add / Minus Buttons ===
    document.querySelectorAll('.btn-add, .btn-minus').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const input = document.querySelector(`.qty-input[data-id="${id}"]`);
        const row = this.closest('tr');
        const stockCell = row.querySelector('.stock-cell');
        const nameCell = row.querySelector('td[data-name]');
        const productName = nameCell ? (nameCell.dataset.name || nameCell.textContent.trim()) : '';
        let currentQty = parseInt(input.value) || 0;
        let stockQty = parseInt(stockCell.textContent) || 0;
        const isAdd = this.classList.contains('btn-add');

        if (isAdd && currentQty < input.max && stockQty > 0) {
          currentQty++;
          stockQty--;
          input.value = currentQty;
        } else if (!isAdd && currentQty > 0) {
          currentQty--;
          stockQty++;
          input.value = currentQty;
        } else {
          return;
        }

        if (stockQty > 0) {
          stockCell.textContent = stockQty;
          stockCell.className = 'stock-cell' + (stockQty <= 5 ? ' text-danger fw-bold' : '');
          // If stock went above threshold, remove from banner
          if (stockQty > 5 && productName) {
            removeFromBanner(productName);
          }
        } else {
          stockCell.innerHTML = '<span class="badge-out">Out of Stock</span>';
          stockCell.className = 'stock-cell';
        }
      });
    });
  </script>

  <script src="../js/mobile-sidebar.js"></script>
</body>
</html>
