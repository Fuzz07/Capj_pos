<?php 
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!is_logged_in()) header('Location: ' . BASE_PATH . '/login.php');
refresh_user_session($pdo);
$u = current_user();

if (!in_array($u['role'], ['admin', 'staff'])) {
    echo 'Access denied';
    exit;
}

$items = $pdo->query("SELECT * FROM inventory ORDER BY name")->fetchAll();

// Low stock / out of stock items for notification banner
$lowStockItems = $pdo->query("SELECT name, stock_qty FROM inventory WHERE is_active = 1 AND stock_qty > 0 AND stock_qty <= " . LOW_STOCK_THRESHOLD . " ORDER BY stock_qty ASC")->fetchAll();
$outOfStockItems = $pdo->query("SELECT name FROM inventory WHERE is_active = 1 AND (stock_qty = 0 OR stock_qty IS NULL) ORDER BY name ASC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Inventory Management - Captain J</title>
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
.table thead {
  background-color: #fa9898;
  color: white;
}
.btn-success {
  background-color: #18b318;
  border: none;
}
.btn-success:hover {
  background-color: #18b318;
}
.search-bar {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
}
.search-bar input {
  flex: 1;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
  margin-right: 8px;
}
.toast-notification {
  position: fixed; top: 20px; right: 20px; z-index: 9999;
  padding: 12px 24px; border-radius: 8px; color: #fff;
  font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  opacity: 0; transform: translateX(100%); transition: all 0.4s ease;
}
.toast-notification.show {
  opacity: 1; transform: translateX(0);
}
.toast-success { background: #28a745; }
.toast-error { background: #dc3545; }
</style>
</head>
<body>
  <div id="toast" class="toast-notification"></div>
  <button class="hamburger">&#9776;</button>
  <div class="sidebar-overlay"></div>
  <!-- Sidebar -->
  <div class="sidebar">
    <div>
      <h5 class="text-center mb-4"><img src="<?= BASE_PATH ?>/images/capj.jpg" alt="Captain J" style="float: center; width: 30%; border-radius: 200px 200px 200px 200px; border: 3px solid #fff; margin-bottom: 15px;"> Captain J  <?= ucfirst($u['role']) ?></h5>

      <?php if ($u['role'] === 'admin'): ?>
        <a href="profile.php">Profile</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="inventory.php" class="active">Inventory</a>
        <a href="users.php">Users</a>
        <a href="ordering.php?v=2">Create Order</a>
        <a href="order.php">Orders</a>
      <?php elseif ($u['role'] === 'staff'): ?>
        <a href="profile.php">Profile</a>
        <a href="inventory.php" class="active">Inventory</a>
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
    <h4 class="mb-4">Inventory Management</h4>

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

    <!-- ✅ Live Search + Add Button -->
    <div class="d-flex justify-content-end align-items-center gap-2 mb-3">
      <input type="text" id="searchBox" placeholder="Search item name or description..." style="width:400px; border-radius:8px; padding:8px 12px; border:1px solid #ccc;">
      <?php if ($u['role'] === 'admin'): ?>
        <a href="inventory_add.php" class="btn btn-success">➕ Add New Item</a>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-header bg-success bg-opacity-25 fw-semibold">Product List</div>
      <div class="card-body">
        <div class="table-responsive-wrap">
        <table class="table table-bordered align-middle">
          <thead>
            <tr><th>Name</th><th>Price</th><th>Stock</th><th class="text-center">Actions</th></tr>
          </thead>
          <tbody id="inventoryTable">
            <?php foreach ($items as $it): ?>
              <tr>
                <td data-name="<?= htmlspecialchars($it['name']) ?>"><?= htmlspecialchars($it['name']) ?></td>
                <td>₱<?= number_format($it['price'], 2) ?></td>
                <td class="<?= $it['stock_qty'] <= 5 ? 'text-danger fw-bold' : '' ?>"><?= $it['stock_qty'] > 0 ? htmlspecialchars($it['stock_qty']) : '<span class="text-danger">Out of Stock</span>' ?></td>
                <td class="text-center">
                  <button class="btn btn-sm btn-success add-stock-btn" data-id="<?= $it['id'] ?>">+1 Stock</button>
                  <?php if ($u['role'] === 'admin'): ?>
                    <a href="inventory_edit.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="inventory_delete.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
              <tr><td colspan="4" class="text-center text-muted">No items found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center align-items-center mt-3 gap-3">
          <button class="btn btn-outline-secondary" id="prevBtn" disabled>Previous</button>
          <span id="pageInfo">Page 1 of 1</span>
          <button class="btn btn-outline-secondary" id="nextBtn" disabled>Next</button>
        </div>
      </div>
    </div>
  </div>

<script>
const PAGE_SIZE = 25;
let currentPage = 1;

function paginateTable() {
  const rows = document.querySelectorAll('#inventoryTable tr');
  const totalItems = rows.length;
  const totalPages = Math.ceil(totalItems / PAGE_SIZE) || 1;

  if (currentPage > totalPages) currentPage = totalPages;

  rows.forEach((row, index) => {
    row.style.display = (index >= (currentPage - 1) * PAGE_SIZE && index < currentPage * PAGE_SIZE) ? '' : 'none';
  });

  document.getElementById('pageInfo').textContent = 'Page ' + currentPage + ' of ' + totalPages + ' (' + totalItems + ' items)';
  document.getElementById('prevBtn').disabled = currentPage <= 1;
  document.getElementById('nextBtn').disabled = currentPage >= totalPages;
}

document.getElementById('prevBtn').addEventListener('click', function() {
  if (currentPage > 1) { currentPage--; paginateTable(); }
});

document.getElementById('nextBtn').addEventListener('click', function() {
  const rows = document.querySelectorAll('#inventoryTable tr');
  const totalPages = Math.ceil(rows.length / PAGE_SIZE);
  if (currentPage < totalPages) { currentPage++; paginateTable(); }
});

// ✅ Live search using AJAX
document.getElementById('searchBox').addEventListener('input', function() {
  const query = this.value.trim();
  fetch('inventory_search.php?search=' + encodeURIComponent(query))
    .then(res => res.text())
    .then(html => {
      document.getElementById('inventoryTable').innerHTML = html;
      currentPage = 1;
      paginateTable();
    });
});

function showToast(message, type) {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.className = 'toast-notification toast-' + type + ' show';
  setTimeout(function() { toast.classList.remove('show'); }, 3000);
}

// Show toast on page load for edit/delete actions
var urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('updated')) { showToast('Item updated successfully!', 'success'); }
if (urlParams.has('deleted')) { showToast('Item deleted successfully!', 'success'); }
if (urlParams.has('updated') || urlParams.has('deleted')) {
  window.history.replaceState({}, '', window.location.pathname);
}

// ✅ Add +1 stock handler with color refresh and notification cleanup
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('add-stock-btn')) {
    const id = e.target.dataset.id;
    const btn = e.target;
    const row = btn.closest('tr');
    const nameCell = row.querySelector('td:nth-child(1)');
    const stockCell = row.querySelector('td:nth-child(3)');
    const productName = nameCell ? (nameCell.dataset.name || nameCell.textContent.trim()) : '';

    fetch('inventory_add_stock.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'id=' + encodeURIComponent(id)
    })
    .then(res => res.text())
    .then(newQty => {
      if (newQty) {
        const qty = parseInt(newQty);
        stockCell.textContent = qty;
        stockCell.className = qty <= 5 ? 'text-danger fw-bold' : '';
        showToast('Stock added successfully!', 'success');

        // Remove from notification banner if stock is now above threshold
        if (qty > 5 && productName) {
          const alert = document.getElementById('stockAlert');
          if (alert) {
            let html = alert.innerHTML;
            // Remove from low stock section: "ProductName (X)"
            const lowRegex = new RegExp('<span class="fw-bold">' + escapeRegex(productName) + '<\\/span>\\s*\\(\\d+\\)');
            html = html.replace(lowRegex, '').replace(/,?\s*,/, ',').replace(/,\s*$/, '').replace(/^\s*,/, '');
            // Remove from out of stock section
            const oosRegex = new RegExp(escapeRegex(productName), 'g');
            html = html.replace(oosRegex, '').replace(/,?\s*,/, ',').replace(/,\s*$/, '').replace(/^\s*,/, '');
            // Clean up empty sections
            html = html.replace(/<strong>[^<]*<\/strong>:\s*(<br\s*\/?>)?\s*/g, function(match) {
              return '';
            });
            html = html.trim();
            if (!html) {
              alert.style.display = 'none';
            } else {
              alert.innerHTML = html;
            }
          }
        }
      }
    })
    .catch(err => console.error('Error updating stock:', err));
  }
});

function escapeRegex(str) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Initialize pagination on page load
paginateTable();
</script>

<script src="../js/mobile-sidebar.js"></script>
</body>
</html>
