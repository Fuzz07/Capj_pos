<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!is_logged_in()) header('Location: ' . APP_BASE_URL . '/login.php');
refresh_user_session($pdo);
$u = current_user();

$orderType = $_GET['type'] ?? 'Dine-in';
$customer = $_GET['customer'] ?? '';
$paymentMethod = $_GET['payment_method'] ?? 'GCash';
$itemsOrdered = json_decode($_GET['items'] ?? '[]', true);
$grandTotal = (float)($_GET['total'] ?? 0);

if (empty($itemsOrdered) || $grandTotal <= 0) {
    echo "Invalid order data.";
    exit;
}

$totalFormatted = number_format($grandTotal, 2);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cashless Payment - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
  <style>
    body {
      background: linear-gradient(135deg, #faf9f6, #f8f8ff);
      font-family: 'Poppins', sans-serif;
      color: #2f3b2f;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .card-custom {
      background: #f6f7f2;
      border: none;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      max-width: 480px;
      width: 100%;
    }
    .card-header-custom {
      background: #f10000;
      color: #fff;
      border-top-left-radius: 15px;
      border-top-right-radius: 15px;
      padding: 1rem;
      text-align: center;
      font-weight: 600;
    }
    .form-control, .form-select {
      border-radius: 10px;
      border: 1px solid #ef9a9a;
    }
    .form-control:focus, .form-select:focus {
      border-color: #ef9a9a;
      box-shadow: 0 0 5px rgba(138,154,91,0.3);
    }
    .btn-custom {
      background-color: #f10000;
      border: none;
      color: #fff;
      font-weight: 500;
      border-radius: 10px;
    }
    .btn-custom:hover {
      background-color: #ef9a9a;
      transform: scale(1.03);
    }
    .total-display {
      font-size: 1.5rem;
      font-weight: bold;
      color: #f10000;
      text-align: center;
    }
  </style>

</head>
<body>
  <div class="container">
    <div class="card card-custom shadow-lg mx-auto">
      <div class="card-header-custom"><?= htmlspecialchars($paymentMethod) ?> Payment</div>
      <div class="card-body p-4">
        <div class="total-display">Total: ₱<?= $totalFormatted ?></div>
        <hr>
        <form method="post" action="process-order-final.php" enctype="multipart/form-data">
          <input type="hidden" name="type" value="<?= htmlspecialchars($orderType) ?>">
          <input type="hidden" name="customer" value="<?= htmlspecialchars($customer) ?>">
          <input type="hidden" name="items" value="<?= htmlspecialchars(json_encode($itemsOrdered)) ?>">
          <input type="hidden" name="payment_method" value="<?= htmlspecialchars($paymentMethod) ?>">
          <input type="hidden" name="total_amount" value="<?= $grandTotal ?>">

          <div class="mb-3">
            <label class="form-label fw-semibold">Amount Paid (based on screenshot)</label>
            <div class="input-group">
              <span class="input-group-text">₱</span>
              <input name="paid" type="number" class="form-control" id="amountPaidInput" required step="0.01" min="<?= $grandTotal ?>" value="<?= $grandTotal ?>" placeholder="Enter amount from screenshot">
            </div>
            <div class="form-text">Enter the exact amount shown in the screenshot.</div>
            <div id="changePreview" class="mt-2 p-2 text-center" style="border-radius:8px;font-weight:bold;display:none;"></div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Upload Screenshot (required)</label>
            <input name="screenshot" type="file" class="form-control" id="screenshotInput" required accept="image/png,image/jpeg,image/jpg,image/gif">
            <div class="form-text">PNG, JPG, or GIF only. Max 2MB.</div>
            <div id="screenshotPreview" class="mt-2 text-center" style="display:none;">
              <img id="previewImage" src="#" alt="Preview" style="max-width:100%;max-height:200px;border:1px solid #ddd;border-radius:4px;">
              <p class="mt-1 mb-0" style="font-size:12px;color:#666;">Verify this is a valid <?= htmlspecialchars($paymentMethod) ?> payment screenshot</p>
            </div>
          </div>

          <div id="screenshotValidation" class="mt-2 p-2 text-center" style="border-radius:8px;font-weight:bold;display:none;"></div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="confirmScreenshot" required>
            <label class="form-check-label" for="confirmScreenshot">I confirm this is a valid <?= htmlspecialchars($paymentMethod) ?> payment screenshot</label>
          </div>

          <script>
          document.getElementById('screenshotInput').addEventListener('change', function() {
            if (this.files && this.files[0]) {
              var file = this.files[0];

              var reader = new FileReader();
              reader.onload = function(e) {
                var preview = document.getElementById('screenshotPreview');
                var img = document.getElementById('previewImage');
                img.src = e.target.result;
                preview.style.display = 'block';

                Tesseract.recognize(img.src, 'eng', {
                  logger: function(m) {
                    if (m.status === 'recognizing text') {
                      document.getElementById('screenshotInput').placeholder = 'Reading... ' + Math.round(m.progress * 100) + '%';
                    }
                  }
                }).then(function(result) {
                  var text = result.data.text;
                  var validateDiv = document.getElementById('screenshotValidation');

                  // Extract payment amount from screenshot
                  var amountInput = document.getElementById('amountPaidInput');
                  var totalAmount = <?= json_encode($grandTotal) ?>;
                  var candidates = [];

                  // Pattern 1: Look for ₱ followed by number
                  var pesoMatches = text.match(/₱\s*([0-9,]+(?:\.[0-9]{1,2})?)/g);
                  if (pesoMatches) {
                    pesoMatches.forEach(function(m) {
                      var num = parseFloat(m.replace(/[₱\s,]/g, ''));
                      if (!isNaN(num) && num > 0) candidates.push(num);
                    });
                  }

                  // Pattern 2: Look for PHP followed by number
                  var phpMatches = text.match(/PHP\s*([0-9,]+(?:\.[0-9]{1,2})?)/gi);
                  if (phpMatches) {
                    phpMatches.forEach(function(m) {
                      var num = parseFloat(m.replace(/PHP\s*/gi, '').replace(/,/g, ''));
                      if (!isNaN(num) && num > 0) candidates.push(num);
                    });
                  }

                  // Pattern 3: Look for "P" followed by space and number (OCR often reads ₱ as P)
                  var pMatches = text.match(/\bP\s+([0-9,]+(?:\.[0-9]{1,2})?)\b/g);
                  if (pMatches) {
                    pMatches.forEach(function(m) {
                      var num = parseFloat(m.replace(/P\s*/i, '').replace(/,/g, ''));
                      if (!isNaN(num) && num > 0) candidates.push(num);
                    });
                  }

                  // Pattern 4: Any number with 2 decimals near keywords context
                  var lines = text.split('\n');
                  lines.forEach(function(line) {
                    var lc = line.toLowerCase();
                    if (lc.indexOf('amount') >= 0 || lc.indexOf('total') >= 0 ||
                        lc.indexOf('paid') >= 0 || lc.indexOf('sent') >= 0 ||
                        lc.indexOf('received') >= 0 || lc.indexOf('₱') >= 0 ||
                        lc.indexOf('php') >= 0) {
                      var nums = line.match(/\b([0-9,]+\.\d{2})\b/g);
                      if (nums) {
                        nums.forEach(function(n) {
                          var num = parseFloat(n.replace(/,/g, ''));
                          if (!isNaN(num) && num > 0) candidates.push(num);
                        });
                      }
                    }
                  });

                  // Validate amount: check if detected amount is under total
                  if (candidates.length > 0) {
                    var sorted = candidates.filter(function(v) { return v >= totalAmount * 0.9; }).sort(function(a, b) { return a - b; });
                    var bestAmount = sorted.length > 0 ? sorted[0] : Math.max.apply(null, candidates);
                    if (bestAmount < totalAmount) {
                      validateDiv.style.display = 'block';
                      validateDiv.style.background = '#f8d7da';
                      validateDiv.style.color = '#721c24';
                      validateDiv.innerHTML = '&#10060; Amount in screenshot (₱' + bestAmount.toFixed(2) + ') is less than the total (₱' + totalAmount.toFixed(2) + '). Please upload a correct screenshot.';
                      return;
                    }
                    amountInput.value = bestAmount.toFixed(2);
                    amountInput.dispatchEvent(new Event('input'));
                  } else {
                    validateDiv.style.display = 'block';
                    validateDiv.style.background = '#fff3cd';
                    validateDiv.style.color = '#856404';
                    validateDiv.innerHTML = '&#9888; Could not read amount from screenshot. Please enter the amount manually.';
                  }
                }).catch(function() {});

                document.getElementById('screenshotInput').placeholder = 'Screenshot uploaded';
              };
              reader.readAsDataURL(file);
            }
          });

          document.getElementById('amountPaidInput').addEventListener('input', function() {
            var total = <?= json_encode($grandTotal) ?>;
            var val = parseFloat(this.value);
            if (val < total) {
              this.setCustomValidity('Amount paid cannot be less than ₱' + total.toFixed(2));
            } else {
              this.setCustomValidity('');
            }

            var preview = document.getElementById('changePreview');
            if (val > total) {
              var change = val - total;
              preview.style.display = 'block';
              preview.style.background = '#d4edda';
              preview.style.color = '#155724';
              preview.innerHTML = 'Change due: ₱' + change.toFixed(2);
            } else if (val == total) {
              preview.style.display = 'block';
              preview.style.background = '#fff3cd';
              preview.style.color = '#856404';
              preview.innerHTML = 'Exact amount &mdash; no change';
            } else {
              preview.style.display = 'none';
            }
          });

          document.getElementById('amountPaidInput').dispatchEvent(new Event('input'));
          </script>

          <div id="submitError" class="text-center mb-2" style="color:#dc3545;font-weight:bold;display:none;"></div>
          <div class="d-grid">
            <button class="btn btn-custom btn-lg" id="confirmBtn">Confirm Payment</button>
          </div>
        </form>
        <script>
        document.querySelector('form').addEventListener('submit', function(e) {
          var valDiv = document.getElementById('screenshotValidation');
          if (valDiv.style.display !== 'none' && valDiv.innerHTML.indexOf('Not a valid GCash') >= 0) {
            e.preventDefault();
            document.getElementById('submitError').style.display = 'block';
            document.getElementById('submitError').textContent = 'Please upload a valid GCash screenshot first.';
          }
          var paid = parseFloat(document.getElementById('amountPaidInput').value);
          var total = <?= json_encode($grandTotal) ?>;
          if (paid < total) {
            e.preventDefault();
            document.getElementById('submitError').style.display = 'block';
            document.getElementById('submitError').textContent = 'Amount paid cannot be less than total.';
          }
        });
        </script>
      </div>
    </div>
  </div>
</body>
</html>
