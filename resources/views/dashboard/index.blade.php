@extends('layouts.app')

@section('title', 'Admin Dashboard - CAPTAiN J')

@push('styles')
  <style>
    /* Prevent any page scrolling globally on dashboard */
    body,
    html {
      margin: 0;
      padding: 0;
      overflow: hidden !important;
      background-color: #f8fafc !important;
    }

    .main-content {
      height: 100vh;
      overflow: hidden !important;
      display: flex;
      flex-direction: column;
      padding: 0.5rem 1rem !important;
      background-color: #f8fafc !important;
      justify-content: space-between;
      gap: 0.4rem;
    }

    /* Welcome Alert */
    .welcome-msg {
      background: #18b318;
      padding: 0.35rem 1rem;
      border-radius: 6px;
      text-align: center;
      color: #fff;
      font-weight: 500;
      font-size: 0.75rem;
      margin-bottom: 0;
      flex-shrink: 0;
    }

    /* Top Row: Title & Dates */
    .dashboard-top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-shrink: 0;
    }

    .dashboard-title {
      font-size: 1.1rem;
      font-weight: 800;
      color: #0f172a;
      margin: 0;
    }

    .filter-bar {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: #fff;
      border-radius: 6px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
      padding: 0.25rem 0.5rem;
      border: 1px solid #f1f5f9;
      margin-bottom: 0;
    }

    .filter-group {
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }

    .filter-group label {
      font-size: 0.6rem;
      font-weight: 700;
      text-transform: uppercase;
      color: #64748b;
      margin: 0;
    }

    .filter-group input {
      border: 1px solid #cbd5e1;
      border-radius: 4px;
      padding: 0.15rem 0.3rem;
      font-size: 0.7rem;
      color: #334155;
      background: #fff;
      outline: none;
    }

    .btn-filter {
      background: #f10000;
      color: #fff;
      border: none;
      border-radius: 4px;
      padding: 0.2rem 0.6rem;
      font-size: 0.7rem;
      font-weight: 700;
      cursor: pointer;
    }

    .btn-clear {
      background: #e2e8f0;
      color: #475569;
      border-radius: 4px;
      padding: 0.2rem 0.5rem;
      font-size: 0.7rem;
      text-decoration: none;
      font-weight: 600;
    }

    /* KPI Row */
    .kpi-row {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 0.4rem;
      flex-shrink: 0;
    }

    .kpi-card {
      background: #fff;
      border-radius: 6px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
      border: 1px solid #f1f5f9;
      padding: 0.35rem 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .kpi-icon {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      flex-shrink: 0;
    }

    .kpi-icon svg {
      width: 12px;
      height: 12px;
    }

    .kpi-info {
      overflow: hidden;
    }

    .kpi-label {
      font-size: 0.52rem;
      font-weight: 700;
      letter-spacing: 0.2px;
      color: #64748b;
      text-transform: uppercase;
      margin: 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .kpi-value {
      font-size: 0.85rem;
      font-weight: 800;
      color: #0f172a;
      margin: 0;
      line-height: 1.1;
    }

    .kpi-footer {
      font-size: 0.52rem;
      color: #94a3b8;
    }

    .kpi-footer .up {
      color: #10b981;
      font-weight: 700;
    }

    .kpi-footer .down {
      color: #ef4444;
      font-weight: 700;
    }

    /* Chart Rows */
    .chart-row-flex {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0.4rem;
      flex: 1 1 0;
      /* Let rows flex and shrink equally */
      min-height: 0;
      /* Vital for flexbox canvas shrinking */
    }

    .chart-container {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
      border: 1px solid #f1f5f9;
      padding: 0.4rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100%;
      min-height: 0;
      cursor: pointer;
    }

    .chart-container h6 {
      font-size: 0.72rem;
      font-weight: 800;
      color: #1e293b;
      margin: 0 0 0.15rem 0;
      text-align: center;
    }

    .chart-canvas-wrapper {
      flex: 1;
      min-height: 0;
      position: relative;
      width: 100%;
      height: 100%;
    }

    /* Panels Grid */
    .panels-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 0.4rem;
      flex: 1.2 1 0;
      /* Sized slightly higher than chart rows for tables visibility */
      min-height: 0;
    }

    @media (max-width: 1400px) {
      .panels-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width: 992px) {
      .panels-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .panels-grid {
        grid-template-columns: 1fr;
      }
    }

    .data-panel {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
      border: 1px solid #f1f5f9;
      display: flex;
      flex-direction: column;
      height: 100%;
      min-height: 0;
      overflow: hidden;
    }

    .data-panel .panel-header {
      padding: 0.35rem 0.5rem;
      color: #fff;
      font-size: 0.6rem;
      font-weight: 800;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      flex-shrink: 0;
    }

    .table-wrapper {
      flex: 1;
      overflow-y: auto;
      /* Enable vertical scroll ONLY inside tables if they overflow */
      min-height: 0;
    }

    .data-panel table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.65rem;
    }

    .data-panel th {
      font-weight: 700;
      text-transform: uppercase;
      padding: 0.3rem 0.45rem;
      background-color: #f8fafc;
      color: #475569;
      border-bottom: 1px solid #e2e8f0;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .data-panel td {
      padding: 0.25rem 0.45rem;
      border-bottom: 1px solid #f1f5f9;
      color: #334155;
    }

    .data-panel tbody tr:nth-child(odd) {
      background: #fafafa;
    }

    .data-panel tbody tr:hover td {
      background: #f1f5f9;
    }

    .panel-footer {
      padding: 0.2rem 0.5rem;
      font-size: 0.52rem;
      color: #94a3b8;
      border-top: 1px solid #f1f5f9;
      text-align: right;
      flex-shrink: 0;
    }

    .star-icon {
      color: #eab308;
      margin-right: 1px;
    }

    .growth-up {
      color: #10b981;
      font-weight: 700;
    }

    .growth-dash {
      color: #cbd5e1;
    }

    /* Footer Status Line */
    .footer-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #fff;
      border-radius: 6px;
      border: 1px solid #f1f5f9;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
      padding: 0.3rem 0.75rem;
      font-size: 0.62rem;
      color: #64748b;
      flex-shrink: 0;
    }

    .footer-block-compact {
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    /* Zoom Modal Custom styles */
    .chart-modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(15, 23, 42, 0.75);
      z-index: 9999;
      justify-content: center;
      align-items: center;
      backdrop-filter: blur(4px);
    }

    .chart-modal-content {
      background: #fff;
      border-radius: 12px;
      padding: 1.5rem;
      width: 80vw;
      height: 75vh;
      position: relative;
      display: flex;
      flex-direction: column;
    }

    .chart-modal-close {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 1.5rem;
      cursor: pointer;
      color: #94a3b8;
    }

    .chart-modal-close:hover {
      color: #0f172a;
    }
  </style>
@endpush

@section('content')

  @if(session('status') == 'login_success')
    <div id="welcome" class="welcome-msg">
      👋 Welcome, <span style="font-weight:700">{{ auth()->user()->full_name ?? auth()->user()->username }}</span>!
    </div>
  @endif

  <!-- Top Header Row -->
  <div class="dashboard-top-bar">
    <h4 class="dashboard-title"><i class="fa-solid fa-chart-pie me-2 text-danger"></i>Dashboard Overview</h4>

    <form method="GET" action="{{ route('dashboard') }}" class="filter-bar">
      <div class="filter-group">
        <label>From</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}">
      </div>
      <div class="filter-group">
        <label>To</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}">
      </div>
      <button type="submit" class="btn-filter shadow-sm">Apply</button>
      @if(request('date_from') || request('date_to'))
        <a href="{{ route('dashboard') }}" class="btn-clear border">Clear</a>
      @endif
    </form>
  </div>

  <!-- Compact Row of 6 KPI Cards -->
  <div class="kpi-row">
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#e74c3c;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="9" cy="21" r="1" />
          <circle cx="20" cy="21" r="1" />
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
        </svg>
      </div>
      <div class="kpi-info">
        <div class="kpi-label">Sales Today</div>
        <div class="kpi-value">₱{{ number_format($sales_today, 2) }}</div>
        <div class="kpi-footer">
          @php
            $today_pct = $sales_yesterday > 0 ? round((($sales_today - $sales_yesterday) / $sales_yesterday) * 100, 1) : 0;
            $dir = $today_pct >= 0 ? 'up' : 'down';
            $arrow = $today_pct >= 0 ? '&#9650;' : '&#9660;';
          @endphp
          <span class="{{ $dir }}">{!! $arrow !!} {{ abs($today_pct) }}%</span> vs yesterday
        </div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon" style="background:#27ae60;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <rect x="3" y="12" width="4" height="9" />
          <rect x="10" y="7" width="4" height="14" />
          <rect x="17" y="3" width="4" height="18" />
        </svg>
      </div>
      <div class="kpi-info">
        <div class="kpi-label">Monthly Revenue</div>
        <div class="kpi-value">₱{{ number_format($monthly_revenue, 2) }}</div>
        <div class="kpi-footer">
          @php
            $m_rev_pct = $monthly_revenue_prev > 0 ? round((($monthly_revenue - $monthly_revenue_prev) / $monthly_revenue_prev) * 100, 1) : 0;
            $m_dir = $m_rev_pct >= 0 ? 'up' : 'down';
            $m_arrow = $m_rev_pct >= 0 ? '&#9650;' : '&#9660;';
          @endphp
          <span class="{{ $m_dir }}">{!! $m_arrow !!} {{ abs($m_rev_pct) }}%</span> vs last month
        </div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon" style="background:#3498db;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
          <line x1="3" y1="6" x2="21" y2="6" />
          <path d="M16 10a4 4 0 01-8 0" />
        </svg>
      </div>
      <div class="kpi-info">
        <div class="kpi-label">Total Orders</div>
        <div class="kpi-value">{{ number_format($orders_this_month) }}</div>
        <div class="kpi-footer">
          @php
            $o_pct = $orders_last_month > 0 ? round((($orders_this_month - $orders_last_month) / $orders_last_month) * 100, 1) : 0;
            $o_dir = $o_pct >= 0 ? 'up' : 'down';
            $o_arrow = $o_pct >= 0 ? '&#9650;' : '&#9660;';
          @endphp
          <span class="{{ $o_dir }}">{!! $o_arrow !!} {{ abs($o_pct) }}%</span> vs last month
        </div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon" style="background:#f39c12;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path
            d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" />
          <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
          <line x1="12" y1="22.08" x2="12" y2="12" />
        </svg>
      </div>
      <div class="kpi-info">
        <div class="kpi-label">Total Products</div>
        <div class="kpi-value">{{ $total_products }}</div>
        <div class="kpi-footer">
          @php
            $a_pct = $total_products_prev > 0 ? round((($total_products - $total_products_prev) / $total_products_prev) * 100, 1) : 0;
            $a_dir = $a_pct >= 0 ? 'up' : 'down';
            $a_arrow = $a_pct >= 0 ? '&#9650;' : '&#9660;';
          @endphp
          <span class="{{ $a_dir }}">{!! $a_arrow !!} {{ abs($a_pct) }}%</span> vs last month
        </div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon" style="background:#9b59b6;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M12 15l-2 5l9-9l-5 2l2-5l-9 9z" />
        </svg>
      </div>
      <div class="kpi-info">
        <div class="kpi-label">Best Seller</div>
        <div class="kpi-value" style="font-size:0.75rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
          title="{{ $best_product_name }}">{{ $best_product_name }}</div>
        <div class="kpi-footer">{{ number_format($best_product_count) }} Orders</div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon" style="background:#1abc9c;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
          <polyline points="17 6 23 6 23 12" />
        </svg>
      </div>
      <div class="kpi-info">
        <div class="kpi-label">Sales Growth</div>
        <div class="kpi-value" style="color: {{ $sales_growth >= 0 ? '#27ae60' : '#e74c3c' }};">
          {!! $sales_growth >= 0 ? '&#9650;' : '&#9660;' !!} {{ abs($sales_growth) }}%
        </div>
        <div class="kpi-footer">vs last month</div>
      </div>
    </div>
  </div>

  <!-- Row 1 of Charts (3 Columns) -->
  <div class="chart-row-flex">
    <div class="chart-container" onclick="openZoom('barChart')">
      <h6>Sales per Product</h6>
      <div class="chart-canvas-wrapper">
        <canvas id="barChart"></canvas>
      </div>
    </div>
    <div class="chart-container" onclick="openZoom('lineChart')">
      <h6>Monthly Sales Trend</h6>
      <div class="chart-canvas-wrapper">
        <canvas id="lineChart"></canvas>
      </div>
    </div>
    <div class="chart-container" onclick="openZoom('pieChart')">
      <h6>Sales Share</h6>
      <div class="chart-canvas-wrapper">
        <canvas id="pieChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Row 2 of Charts (3 Columns) -->
  <div class="chart-row-flex">
    <div class="chart-container" onclick="openZoom('dailyChart')">
      <h6>Daily Sales (Last 7 Days)</h6>
      <div class="chart-canvas-wrapper">
        <canvas id="dailyChart"></canvas>
      </div>
    </div>
    <div class="chart-container" onclick="openZoom('weeklyChart')">
      <h6>Weekly Sales (Last 4 Weeks)</h6>
      <div class="chart-canvas-wrapper">
        <canvas id="weeklyChart"></canvas>
      </div>
    </div>
    <div class="chart-container" onclick="openZoom('peakHoursChart')">
      <h6>Peak Sales Hours</h6>
      <div class="chart-canvas-wrapper">
        <canvas id="peakHoursChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Bottom Row: 4 Data Panels -->
  <div class="panels-grid">
    <!-- Panel 1: Top 5 Best-Selling Products -->
    <div class="data-panel" style="cursor:pointer;" onclick="openPanelZoom(this)">
      <div class="panel-header" style="background:#990000;">Top 5 Best-Selling Products</div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr style="background:#990000; color:#fff;">
              <th>Rank</th>
              <th>Product</th>
              <th>Qty Sold</th>
              <th>Revenue</th>
            </tr>
          </thead>
          <tbody>
            @forelse($top5_products as $i => $row)
              <tr>
                <td>
                  @if($i < 3)<span class="star-icon">&#9733;</span>@endif
                  {{ $i + 1 }}
                </td>
                <td class="fw-bold">{{ $row['name'] }}</td>
                <td>{{ number_format($row['qty_sold']) }}</td>
                <td>₱{{ number_format($row['revenue'], 2) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" style="text-align:center;color:#aaa;">No data</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Panel 2: Top 5 Least-Selling Products -->
    <div class="data-panel" style="cursor:pointer;" onclick="openPanelZoom(this)">
      <div class="panel-header" style="background:#333;">Top 5 Least-Selling Products</div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr style="background:#333; color:#fff;">
              <th>Rank</th>
              <th>Product</th>
              <th>Qty Sold</th>
              <th>Revenue</th>
            </tr>
          </thead>
          <tbody>
            @forelse($least5_products as $i => $row)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td class="fw-bold">{{ $row['name'] }}</td>
                <td>{{ number_format($row['qty_sold']) }}</td>
                <td>₱{{ number_format($row['revenue'], 2) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" style="text-align:center;color:#aaa;">No data</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Panel 3: Sales Growth Summary -->
    <div class="data-panel" style="cursor:pointer;" onclick="openPanelZoom(this)">
      <div class="panel-header" style="background:#990000;">Sales Growth Summary</div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr style="background:#990000; color:#fff;">
              <th>Period</th>
              <th>Sales</th>
              <th>Growth</th>
            </tr>
          </thead>
          <tbody>
            @forelse($growth_rows as $i => $row)
              <tr>
                <td class="fw-bold">{{ $row['period'] }}</td>
                <td>₱{{ number_format($row['total'], 2) }}</td>
                <td>
                  @if($i === 0)
                    <span class="growth-dash">--</span>
                  @else
                    @php
                      $prev_total = $growth_rows[$i - 1]['total'];
                      $g = $prev_total > 0 ? round((($row['total'] - $prev_total) / $prev_total) * 100, 2) : 0;
                    @endphp
                    <span class="growth-up">&#9650; {{ number_format($g, 2) }}%</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="3" style="text-align:center;color:#aaa;">No data</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="panel-footer">*As of {{ date('F j, Y') }}</div>
    </div>

    <!-- Panel 4: Peak Sales Day Ranking -->
    <div class="data-panel" style="cursor:pointer;" onclick="openPanelZoom(this)">
      <div class="panel-header" style="background:#333;">Peak Sales Day Ranking</div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr style="background:#333; color:#fff;">
              <th>Day</th>
              <th>Sales</th>
              <th>% Share</th>
            </tr>
          </thead>
          <tbody>
            @forelse($peakday_rows as $row)
              @php $pct = $total_all_sales > 0 ? round(($row['total'] / $total_all_sales) * 100, 1) : 0; @endphp
              <tr>
                <td class="fw-bold">{{ $row['day_name'] }}</td>
                <td>₱{{ number_format($row['total'], 2) }}</td>
                <td>{{ number_format($pct, 1) }}%</td>
              </tr>
            @empty
              <tr>
                <td colspan="3" style="text-align:center;color:#aaa;">No data</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Panel 5: Daily Sales Summary -->
    <div class="data-panel" style="cursor:pointer;" onclick="openPanelZoom(this)">
      <div class="panel-header" style="background:#0f172a;">Daily Sales Summary</div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr style="background:#0f172a; color:#fff;">
              <th>Date</th>
              <th>Orders</th>
              <th>Revenue</th>
            </tr>
          </thead>
          <tbody>
            @forelse($daily_rows as $row)
              <tr>
                <td class="fw-bold">{{ $row['date_label'] }}</td>
                <td>{{ number_format($row['orders_count']) }}</td>
                <td>₱{{ number_format($row['total_sales'], 2) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="3" style="text-align:center;color:#aaa;">No data</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Compact Footer Status Line -->
  <div class="footer-row">
    <div class="footer-block-compact">
      <i class="fa-solid fa-calendar-days text-primary me-1"></i>
      <strong>Date Range:</strong> {{ $footer_date_start }} &mdash; {{ $footer_date_end }}
    </div>
    <div class="footer-block-compact">
      <i class="fa-solid fa-users text-info me-1"></i>
      <strong>Total Customers:</strong> {{ number_format($footer_customers) }}
    </div>
    <div class="footer-block-compact">
      <i class="fa-solid fa-credit-card text-success me-1"></i>
      <strong>Payment methods:</strong> {{ implode(' • ', $payment_breakdown) ?: 'N/A' }}
    </div>
    <div class="footer-block-compact">
      <i class="fa-solid fa-clock text-danger me-1"></i>
      <strong>Last Updated:</strong> {{ $footer_last_updated }}
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

@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3"></script>
  <script>
    const products = {!! json_encode($products) !!};
    const sales = {!! json_encode($sales) !!};
    const salesPercent = {!! json_encode($sales_percent) !!};
    const months = {!! json_encode($months) !!};
    const monthSales = {!! json_encode($month_sales) !!};
    const dailyLabels = {!! json_encode($daily_labels) !!};
    const dailySales = {!! json_encode($daily_sales) !!};
    const weeklyLabels = {!! json_encode($weekly_labels) !!};
    const weeklySales = {!! json_encode($weekly_sales) !!};
    const hourLabels = {!! json_encode($hour_labels) !!};
    const hourSales = {!! json_encode($hour_sales) !!};

    const charts = {};

    function uniqueColors(count, offset = 0, alpha = 1) {
      const colors = [];
      const phi = 137.507764;
      for (let i = 0; i < count; i++) {
        const hue = Math.round((offset + i * phi) % 360);
        const sat = 75 + (i % 3) * 6; // 75%, 81%, 87%
        const light = 50 + (i % 2) * 8; // 50%, 58%
        colors.push(alpha === 1
          ? `hsl(${hue}, ${sat}%, ${light}%)`
          : `hsla(${hue}, ${sat}%, ${light}%, ${alpha})`);
      }
      return colors;
    }

    // Bar Chart (Sales per Product - Enhanced like Weekly Sales design with unique colors)
    const productColors = uniqueColors(products.length, 25, 0.85);
    const productBorderColors = uniqueColors(products.length, 25, 1);

    charts.barChart = new Chart(document.getElementById('barChart'), {
      type: 'bar',
      data: {
        labels: products,
        datasets: [{
          label: 'Sales (₱)',
          data: sales,
          backgroundColor: productColors,
          borderColor: productBorderColors,
          borderWidth: 1.5,
          borderRadius: 6
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (context) {
                return ` Sales: ₱${context.raw.toLocaleString()}`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => '₱' + value.toLocaleString()
            },
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            }
          },
          x: {
            grid: { display: false }
          }
        }
      }
    });

    // Line Chart (Monthly Sales Trend - Enhanced)
    charts.lineChart = new Chart(document.getElementById('lineChart'), {
      type: 'line',
      data: {
        labels: months,
        datasets: [{
          label: 'Sales (₱)',
          data: monthSales,
          borderColor: '#2563eb', // Vibrant ocean blue
          backgroundColor: 'rgba(37, 99, 235, 0.15)', // Smooth gradient glow fill
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointRadius: 4,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#2563eb',
          pointBorderWidth: 2.5,
          pointHoverRadius: 7
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (context) {
                return ` Sales: ₱${context.raw.toLocaleString()}`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => '₱' + value.toLocaleString()
            },
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            }
          },
          x: {
            grid: { display: false }
          }
        }
      }
    });

    // Doughnut / Pie Chart (Sales Share - Enhanced with unique individual colors)
    charts.pieChart = new Chart(document.getElementById('pieChart'), {
      type: 'doughnut',
      data: {
        labels: products,
        datasets: [{
          data: sales,
          backgroundColor: uniqueColors(products.length, 195, 0.90),
          borderColor: '#ffffff',
          borderWidth: 3,
          borderRadius: 4,
          hoverOffset: 8
        }]
      },
      options: {
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
          tooltip: {
            callbacks: {
              label: function (context) {
                const label = context.label || '';
                const value = context.raw || 0;
                const percent = salesPercent[context.dataIndex];
                return ` ${label}: ₱${value.toLocaleString()} (${percent}%)`;
              }
            }
          },
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 12,
              font: { size: 10.5, weight: '600' },
              padding: 10,
              generateLabels: function (chart) {
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

    // Daily Sales Chart
    charts.dailyChart = new Chart(document.getElementById('dailyChart'), {
      type: 'line',
      data: {
        labels: dailyLabels,
        datasets: [{
          label: 'Sales (₱)',
          data: dailySales,
          borderColor: '#38bdf8', // Baby blue line
          backgroundColor: 'rgba(56, 189, 248, 0.15)', // Soft baby blue fill
          borderWidth: 2.5,
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#38bdf8',
          pointBorderWidth: 2,
          pointHoverRadius: 5
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (context) {
                return ` Sales: ₱${context.raw.toLocaleString()}`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => '₱' + value.toLocaleString()
            },
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            }
          },
          x: {
            grid: { display: false }
          }
        }
      }
    });

    // Weekly Sales — Waterfall (Bridge) Chart
    const waterfallData = [];
    const waterfallColors = [];
    const waterfallBorderColors = [];

    for (let i = 0; i < weeklySales.length; i++) {
      if (i === 0) {
        // First week starts at 0 and rises to first week's sales value
        waterfallData.push([0, weeklySales[0]]);
        waterfallColors.push('rgba(54, 162, 235, 0.65)'); // Neutral brand blue
        waterfallBorderColors.push('#36a2eb');
      } else {
        const prev = weeklySales[i - 1];
        const curr = weeklySales[i];
        waterfallData.push([prev, curr]);
        if (curr >= prev) {
          waterfallColors.push('rgba(16, 185, 129, 0.7)'); // Emerald green for growth
          waterfallBorderColors.push('#10b981');
        } else {
          waterfallColors.push('rgba(239, 68, 68, 0.7)'); // Ruby red for decline
          waterfallBorderColors.push('#ef4444');
        }
      }
    }

    charts.weeklyChart = new Chart(document.getElementById('weeklyChart'), {
      type: 'bar',
      data: {
        labels: weeklyLabels,
        datasets: [{
          label: 'Weekly Flow',
          data: waterfallData,
          backgroundColor: waterfallColors,
          borderColor: waterfallBorderColors,
          borderWidth: 1.5,
          borderRadius: 4
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (context) {
                const raw = context.raw;
                if (!Array.isArray(raw)) return '';
                const start = raw[0];
                const end = raw[1];
                const diff = end - start;
                const sign = diff >= 0 ? '▲ +' : '▼ -';
                return ` Flow: ₱${start.toLocaleString()} ➔ ₱${end.toLocaleString()} (${sign}₱${Math.abs(diff).toLocaleString()})`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => '₱' + value.toLocaleString()
            },
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            }
          },
          x: {
            grid: { display: false }
          }
        }
      }
    });

    // Peak Sales Hours — Dynamic Threshold Area Chart
    const maxHourlySales = Math.max(...hourSales) || 1;

    charts.peakHoursChart = new Chart(document.getElementById('peakHoursChart'), {
      type: 'line',
      data: {
        labels: hourLabels,
        datasets: [{
          label: 'Sales (₱)',
          data: hourSales,
          borderColor: '#38bdf8', // Vibrant baby blue line
          backgroundColor: 'rgba(56, 189, 248, 0.20)', // Soft baby blue gradient area fill
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointRadius: 4,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#38bdf8',
          pointBorderWidth: 2.5,
          pointHoverRadius: 7,
          pointHoverBackgroundColor: '#0284c7',
          pointHoverBorderColor: '#ffffff',
          pointHoverBorderWidth: 2
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (context) {
                const val = context.raw;
                let status = '';
                if (maxHourlySales > 0 && val >= maxHourlySales * 0.70) status = '🔥 High Rush Hour';
                else if (maxHourlySales > 0 && val >= maxHourlySales * 0.30) status = '⛅ Steady Flow';
                else status = '💤 Quiet Period';
                return ` Sales: ₱${val.toLocaleString()} (${status})`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => '₱' + value.toLocaleString()
            },
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            }
          },
          x: {
            grid: { display: false }
          }
        }
      }
    });

    // --- Zoom Modal ---
    let zoomChart = null;

    function deepCloneKeepFns(obj) {
      if (typeof obj === 'function') return obj;
      if (obj === null || typeof obj !== 'object') return obj;
      if (Array.isArray(obj)) return obj.map(deepCloneKeepFns);
      const out = {};
      for (const k in obj) out[k] = deepCloneKeepFns(obj[k]);
      return out;
    }

    window.openZoom = function (id) {
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

    window.closeZoom = function (e) {
      if (e && e.target !== e.currentTarget && e.target.className !== 'chart-modal-close') return;
      document.getElementById('chartModal').style.display = 'none';
      if (zoomChart) { zoomChart.destroy(); zoomChart = null; }
    }

    // --- Panel Zoom ---
    window.openPanelZoom = function (el) {
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

    window.closePanelZoom = function (e) {
      if (e && e.target !== e.currentTarget && e.target.className !== 'chart-modal-close') return;
      document.getElementById('panelModal').style.display = 'none';
    }

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && document.getElementById('chartModal').style.display === 'flex') {
        closeZoom(e);
      }
      if (e.key === 'Escape' && document.getElementById('panelModal').style.display === 'flex') {
        closePanelZoom(e);
      }
    });

    // Auto-hide welcome message
    setTimeout(() => {
      const msg = document.getElementById('welcome');
      if (msg) msg.style.display = 'none';
    }, 3000);
  </script>
@endpush