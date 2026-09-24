@extends('layouts.app')

@section('title', 'Admin Dashboard - CAPTAiN J')

@push('styles')
  <style>
    /* Card design system matching Sales Report */
    .card-custom {
      background: #ffffff;
      border: none;
      border-radius: 1rem;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }

    /* Welcome Alert */
    .welcome-msg {
      background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
      padding: 0.65rem 1.25rem;
      border-radius: 0.85rem;
      color: #fff;
      font-weight: 600;
      font-size: 0.85rem;
      box-shadow: 0 4px 15px rgba(22, 163, 74, 0.15);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    /* KPI Tiles matching Sales Report */
    .kpi-tile {
      background: #ffffff;
      border-radius: 0.85rem;
      padding: 1.1rem 1.25rem;
      box-shadow: 0 4px 15px -2px rgba(0, 0, 0, 0.04);
      border: 1px solid #f1f5f9;
      display: flex;
      align-items: flex-start;
      gap: 0.85rem;
      transition: transform 0.18s ease, box-shadow 0.18s ease;
      height: 100%;
    }
    .kpi-tile:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px -3px rgba(0, 0, 0, 0.08);
    }
    .kpi-tile-icon {
      width: 46px;
      height: 46px;
      border-radius: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.2rem;
      flex-shrink: 0;
    }
    .kpi-tile-label {
      font-size: 0.68rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      font-weight: 700;
      color: #64748b;
      margin-bottom: 0.2rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .kpi-tile-value {
      font-size: 1.35rem;
      font-weight: 800;
      color: #0f172a;
      margin: 0;
      line-height: 1.2;
    }
    .kpi-trend-up {
      color: #16a34a;
      font-weight: 700;
      font-size: 0.72rem;
    }
    .kpi-trend-down {
      color: #dc2626;
      font-weight: 700;
      font-size: 0.72rem;
    }

    /* Chart Cards */
    .chart-card {
      background: #ffffff;
      border-radius: 1rem;
      border: 1px solid #f1f5f9;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
      padding: 1.25rem;
      cursor: pointer;
      transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .chart-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.08);
      border-color: #cbd5e1;
    }
    .chart-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.85rem;
    }
    .chart-card-header h6 {
      font-size: 0.9rem;
      font-weight: 700;
      color: #0f172a;
      margin: 0;
    }
    .chart-canvas-wrapper {
      position: relative;
      height: 250px;
      width: 100%;
      flex: 1;
      min-height: 240px;
    }

    /* Data Panels */
    .panel-card {
      background: #ffffff;
      border-radius: 1rem;
      border: 1px solid #f1f5f9;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
      overflow: hidden;
      cursor: pointer;
      transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .panel-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.08);
      border-color: #cbd5e1;
    }
    .panel-card .panel-header {
      padding: 0.85rem 1.15rem;
      font-size: 0.85rem;
      font-weight: 800;
      letter-spacing: 0.02em;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .panel-card .table-wrapper {
      max-height: 260px;
      overflow-y: auto;
      flex: 1;
    }
    .panel-card table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.82rem;
    }
    .panel-card th {
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      padding: 0.55rem 0.85rem;
      background-color: #f8fafc;
      color: #475569;
      border-bottom: 1px solid #e2e8f0;
      position: sticky;
      top: 0;
      z-index: 2;
    }
    .panel-card td {
      padding: 0.55rem 0.85rem;
      border-bottom: 1px solid #f1f5f9;
      color: #1e293b;
    }
    .panel-card tr:last-child td {
      border-bottom: none;
    }
    .panel-card tr:hover td {
      background-color: #f8fafc;
    }
    .panel-card .panel-footer {
      font-size: 0.72rem;
      color: #94a3b8;
      padding: 0.4rem 0.85rem;
      border-top: 1px solid #f1f5f9;
      background: #fafafa;
    }

    /* Rank badges */
    .rank-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 22px;
      height: 22px;
      border-radius: 6px;
      font-size: 0.72rem;
      font-weight: 700;
      background: #f1f5f9;
      color: #64748b;
    }
    .rank-badge.top-1 { background: #fef3c7; color: #d97706; }
    .rank-badge.top-2 { background: #f1f5f9; color: #475569; }
    .rank-badge.top-3 { background: #ffedd5; color: #c2410c; }

    /* Zoom Modal */
    .chart-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(4px);
      z-index: 1050;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }
    .chart-modal-content {
      background: #ffffff;
      border-radius: 1.25rem;
      width: 85vw;
      max-width: 1000px;
      height: 75vh;
      max-height: 650px;
      padding: 1.5rem;
      position: relative;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      display: flex;
      flex-direction: column;
    }
    .chart-modal-close {
      position: absolute;
      top: 1rem;
      right: 1.25rem;
      font-size: 1.5rem;
      font-weight: 700;
      color: #64748b;
      cursor: pointer;
      line-height: 1;
      z-index: 10;
      transition: color 0.15s ease;
    }
    .chart-modal-close:hover {
      color: #0f172a;
    }
    #panelModalBody {
      overflow-y: auto;
      flex: 1;
      margin-top: 1rem;
    }
    #panelModalBody table {
      font-size: 0.95rem !important;
    }
    #panelModalBody th,
    #panelModalBody td {
      padding: 0.75rem 1rem !important;
    }
  </style>
@endpush

@section('content')
<div class="container-fluid px-4 py-2">

  @if(session('status') == 'login_success')
    <div id="welcome" class="welcome-msg mb-4">
      <span><i class="fa-solid fa-circle-check me-2"></i> Welcome back, <strong>{{ auth()->user()->full_name ?? auth()->user()->username }}</strong>!</span>
      <button type="button" class="btn-close btn-close-white btn-sm" onclick="this.parentElement.remove()"></button>
    </div>
  @endif

  <!-- Top Header Row -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
      <h3 class="fw-bold m-0 text-dark">
        <i class="fa-solid fa-chart-pie text-danger me-2"></i> Dashboard Overview
      </h3>
      <p class="text-secondary small m-0">Real-time business performance, sales analytics, and inventory metrics.</p>
    </div>
    <span class="badge bg-dark-subtle text-dark fw-semibold px-3 py-2 rounded-pill">
      <i class="fa-regular fa-calendar me-1"></i>
      {{ $footer_date_start }} &ndash; {{ $footer_date_end }}
    </span>
  </div>

  <!-- Filter Card (Matching Sales Report) -->
  <div class="card card-custom p-3 mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <form method="GET" action="{{ route('dashboard') }}" class="d-flex flex-wrap align-items-end gap-2">
        <div>
          <label class="form-label small fw-semibold text-secondary mb-1">From</label>
          <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
        </div>
        <div>
          <label class="form-label small fw-semibold text-secondary mb-1">To</label>
          <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
        </div>
        <button type="submit" class="btn btn-primary btn-sm fw-semibold px-3 shadow-sm">
          <i class="fa-solid fa-filter me-1"></i> Apply Filter
        </button>
        @if(request('date_from') || request('date_to'))
          <a href="{{ route('dashboard') }}" class="btn btn-light border btn-sm fw-semibold px-3">Clear</a>
        @endif
      </form>

      <div class="text-muted small d-flex align-items-center gap-2">
        <i class="fa-solid fa-clock-rotate-left text-primary"></i>
        <span>Last synced: <strong>{{ $footer_last_updated }}</strong></span>
      </div>
    </div>
  </div>

  <!-- Row of 6 KPI Cards (Matching Sales Report Design) -->
  <div class="row g-3 mb-4">
    <!-- KPI 1: Sales Today -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi-tile">
        <div class="kpi-tile-icon" style="background: #16a34a;">
          <i class="fa-solid fa-peso-sign"></i>
        </div>
        <div class="flex-grow-1" style="min-width: 0;">
          <div class="kpi-tile-label">Sales Today</div>
          <div class="kpi-tile-value">₱{{ number_format($sales_today, 2) }}</div>
          <div class="mt-1 small">
            @php
              $today_pct = $sales_yesterday > 0 ? round((($sales_today - $sales_yesterday) / $sales_yesterday) * 100, 1) : 0;
              $dir = $today_pct >= 0 ? 'kpi-trend-up' : 'kpi-trend-down';
              $arrow = $today_pct >= 0 ? '&#9650;' : '&#9660;';
            @endphp
            <span class="{{ $dir }}">{!! $arrow !!} {{ abs($today_pct) }}%</span> <span class="text-muted" style="font-size: 0.7rem;">vs yest</span>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI 2: Monthly Revenue -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi-tile">
        <div class="kpi-tile-icon" style="background: #2563eb;">
          <i class="fa-solid fa-chart-line"></i>
        </div>
        <div class="flex-grow-1" style="min-width: 0;">
          <div class="kpi-tile-label">Monthly Revenue</div>
          <div class="kpi-tile-value">₱{{ number_format($monthly_revenue, 2) }}</div>
          <div class="mt-1 small">
            @php
              $m_rev_pct = $monthly_revenue_prev > 0 ? round((($monthly_revenue - $monthly_revenue_prev) / $monthly_revenue_prev) * 100, 1) : 0;
              $m_dir = $m_rev_pct >= 0 ? 'kpi-trend-up' : 'kpi-trend-down';
              $m_arrow = $m_rev_pct >= 0 ? '&#9650;' : '&#9660;';
            @endphp
            <span class="{{ $m_dir }}">{!! $m_arrow !!} {{ abs($m_rev_pct) }}%</span> <span class="text-muted" style="font-size: 0.7rem;">vs last mo</span>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI 3: Total Orders -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi-tile">
        <div class="kpi-tile-icon" style="background: #dc2626;">
          <i class="fa-solid fa-receipt"></i>
        </div>
        <div class="flex-grow-1" style="min-width: 0;">
          <div class="kpi-tile-label">Total Orders</div>
          <div class="kpi-tile-value">{{ number_format($orders_this_month) }}</div>
          <div class="mt-1 small">
            @php
              $o_pct = $orders_last_month > 0 ? round((($orders_this_month - $orders_last_month) / $orders_last_month) * 100, 1) : 0;
              $o_dir = $o_pct >= 0 ? 'kpi-trend-up' : 'kpi-trend-down';
              $o_arrow = $o_pct >= 0 ? '&#9650;' : '&#9660;';
            @endphp
            <span class="{{ $o_dir }}">{!! $o_arrow !!} {{ abs($o_pct) }}%</span> <span class="text-muted" style="font-size: 0.7rem;">vs last mo</span>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI 4: Total Products -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi-tile">
        <div class="kpi-tile-icon" style="background: #f59e0b;">
          <i class="fa-solid fa-boxes-stacked"></i>
        </div>
        <div class="flex-grow-1" style="min-width: 0;">
          <div class="kpi-tile-label">Total Products</div>
          <div class="kpi-tile-value">{{ $total_products }}</div>
          <div class="mt-1 small">
            @php
              $a_pct = $total_products_prev > 0 ? round((($total_products - $total_products_prev) / $total_products_prev) * 100, 1) : 0;
              $a_dir = $a_pct >= 0 ? 'kpi-trend-up' : 'kpi-trend-down';
              $a_arrow = $a_pct >= 0 ? '&#9650;' : '&#9660;';
            @endphp
            <span class="{{ $a_dir }}">{!! $a_arrow !!} {{ abs($a_pct) }}%</span> <span class="text-muted" style="font-size: 0.7rem;">vs last mo</span>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI 5: Best Seller -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi-tile">
        <div class="kpi-tile-icon" style="background: #8b5cf6;">
          <i class="fa-solid fa-crown"></i>
        </div>
        <div class="flex-grow-1" style="min-width: 0;">
          <div class="kpi-tile-label">Best Seller</div>
          <div class="kpi-tile-value text-truncate" style="font-size: 1rem;" title="{{ $best_product_name }}">
            {{ $best_product_name }}
          </div>
          <div class="mt-1 small text-muted" style="font-size: 0.72rem;">
            <i class="fa-solid fa-bag-shopping me-1 text-primary"></i><strong>{{ number_format($best_product_count) }}</strong> orders
          </div>
        </div>
      </div>
    </div>

    <!-- KPI 6: Sales Growth -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi-tile">
        <div class="kpi-tile-icon" style="background: #06b6d4;">
          <i class="fa-solid fa-arrow-trend-up"></i>
        </div>
        <div class="flex-grow-1" style="min-width: 0;">
          <div class="kpi-tile-label">Sales Growth</div>
          <div class="kpi-tile-value" style="color: {{ $sales_growth >= 0 ? '#16a34a' : '#dc2626' }};">
            {!! $sales_growth >= 0 ? '&#9650;' : '&#9660;' !!} {{ abs($sales_growth) }}%
          </div>
          <div class="mt-1 small text-muted" style="font-size: 0.72rem;">vs last month</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Row 1 of Charts (3 Columns) -->
  <div class="row g-4 mb-4">
    <div class="col-12 col-lg-4">
      <div class="chart-card" onclick="openZoom('barChart')">
        <div class="chart-card-header">
          <h6><i class="fa-solid fa-chart-column text-primary me-2"></i>Sales per Product</h6>
          <span class="badge bg-light text-muted border"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
        </div>
        <div class="chart-canvas-wrapper">
          <canvas id="barChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="chart-card" onclick="openZoom('lineChart')">
        <div class="chart-card-header">
          <h6><i class="fa-solid fa-chart-line text-success me-2"></i>Monthly Sales Trend</h6>
          <span class="badge bg-light text-muted border"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
        </div>
        <div class="chart-canvas-wrapper">
          <canvas id="lineChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="chart-card" onclick="openZoom('pieChart')">
        <div class="chart-card-header">
          <h6><i class="fa-solid fa-chart-pie text-warning me-2"></i>Sales Share</h6>
          <span class="badge bg-light text-muted border"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
        </div>
        <div class="chart-canvas-wrapper">
          <canvas id="pieChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Row 2 of Charts (3 Columns) -->
  <div class="row g-4 mb-4">
    <div class="col-12 col-lg-4">
      <div class="chart-card" onclick="openZoom('dailyChart')">
        <div class="chart-card-header">
          <h6><i class="fa-solid fa-calendar-day text-info me-2"></i>Daily Sales (Last 7 Days)</h6>
          <span class="badge bg-light text-muted border"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
        </div>
        <div class="chart-canvas-wrapper">
          <canvas id="dailyChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="chart-card" onclick="openZoom('weeklyChart')">
        <div class="chart-card-header">
          <h6><i class="fa-solid fa-calendar-week text-danger me-2"></i>Weekly Sales (Last 4 Weeks)</h6>
          <span class="badge bg-light text-muted border"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
        </div>
        <div class="chart-canvas-wrapper">
          <canvas id="weeklyChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="chart-card" onclick="openZoom('peakHoursChart')">
        <div class="chart-card-header">
          <h6><i class="fa-solid fa-fire text-danger me-2"></i>Peak Sales Hours</h6>
          <span class="badge bg-light text-muted border"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
        </div>
        <div class="chart-canvas-wrapper">
          <canvas id="peakHoursChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Bottom Row: 5 Data Panels -->
  <div class="row g-4 mb-4">
    <!-- Panel 1: Top 5 Best-Selling Products -->
    <div class="col-12 col-md-6 col-xl">
      <div class="panel-card" onclick="openPanelZoom(this)">
        <div class="panel-header bg-danger text-white">
          <span><i class="fa-solid fa-crown me-1 text-warning"></i> Best Sellers</span>
          <span class="badge bg-white text-danger">Top 5</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th style="width: 35px;">#</th>
                <th>Product</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Revenue</th>
              </tr>
            </thead>
            <tbody>
              @forelse($top5_products as $i => $row)
                <tr>
                  <td>
                    <span class="rank-badge {{ $i == 0 ? 'top-1' : ($i == 1 ? 'top-2' : ($i == 2 ? 'top-3' : '')) }}">
                      {{ $i + 1 }}
                    </span>
                  </td>
                  <td class="fw-semibold text-dark">{{ $row['name'] }}</td>
                  <td class="text-end fw-bold text-primary">{{ number_format($row['qty_sold']) }}</td>
                  <td class="text-end fw-semibold text-success">₱{{ number_format($row['revenue'], 2) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Panel 2: Top 5 Least-Selling Products -->
    <div class="col-12 col-md-6 col-xl">
      <div class="panel-card" onclick="openPanelZoom(this)">
        <div class="panel-header bg-dark text-white">
          <span><i class="fa-solid fa-arrow-trend-down me-1 text-secondary"></i> Slow Moving</span>
          <span class="badge bg-secondary text-white">Bottom 5</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th style="width: 35px;">#</th>
                <th>Product</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Revenue</th>
              </tr>
            </thead>
            <tbody>
              @forelse($least5_products as $i => $row)
                <tr>
                  <td><span class="rank-badge">{{ $i + 1 }}</span></td>
                  <td class="fw-semibold text-dark">{{ $row['name'] }}</td>
                  <td class="text-end fw-bold text-primary">{{ number_format($row['qty_sold']) }}</td>
                  <td class="text-end fw-semibold text-success">₱{{ number_format($row['revenue'], 2) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Panel 3: Sales Growth Summary -->
    <div class="col-12 col-md-6 col-xl">
      <div class="panel-card" onclick="openPanelZoom(this)">
        <div class="panel-header bg-primary text-white">
          <span><i class="fa-solid fa-chart-column me-1 text-white"></i> Growth Summary</span>
          <span class="badge bg-white text-primary">Periods</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Period</th>
                <th class="text-end">Sales</th>
                <th class="text-end">Growth</th>
              </tr>
            </thead>
            <tbody>
              @forelse($growth_rows as $i => $row)
                <tr>
                  <td class="fw-semibold text-dark">{{ $row['period'] }}</td>
                  <td class="text-end fw-bold text-success">₱{{ number_format($row['total'], 2) }}</td>
                  <td class="text-end">
                    @if($i === 0)
                      <span class="text-muted">&mdash;</span>
                    @else
                      @php
                        $prev_total = $growth_rows[$i - 1]['total'];
                        $g = $prev_total > 0 ? round((($row['total'] - $prev_total) / $prev_total) * 100, 2) : 0;
                      @endphp
                      <span class="{{ $g >= 0 ? 'kpi-trend-up' : 'kpi-trend-down' }}">
                        {!! $g >= 0 ? '&#9650;' : '&#9660;' !!} {{ number_format(abs($g), 1) }}%
                      </span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted py-3">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="panel-footer">*As of {{ date('F j, Y') }}</div>
      </div>
    </div>

    <!-- Panel 4: Peak Sales Day Ranking -->
    <div class="col-12 col-md-6 col-xl">
      <div class="panel-card" onclick="openPanelZoom(this)">
        <div class="panel-header bg-dark-subtle text-dark">
          <span class="fw-bold"><i class="fa-solid fa-calendar-check me-1 text-primary"></i> Peak Days</span>
          <span class="badge bg-primary text-white">Ranking</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Day</th>
                <th class="text-end">Sales</th>
                <th class="text-end">Share</th>
              </tr>
            </thead>
            <tbody>
              @forelse($peakday_rows as $row)
                @php $pct = $total_all_sales > 0 ? round(($row['total'] / $total_all_sales) * 100, 1) : 0; @endphp
                <tr>
                  <td class="fw-semibold text-dark">{{ $row['day_name'] }}</td>
                  <td class="text-end fw-bold text-success">₱{{ number_format($row['total'], 2) }}</td>
                  <td class="text-end fw-semibold text-muted">{{ number_format($pct, 1) }}%</td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted py-3">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Panel 5: Daily Sales Summary -->
    <div class="col-12 col-md-6 col-xl">
      <div class="panel-card" onclick="openPanelZoom(this)">
        <div class="panel-header bg-secondary text-white">
          <span><i class="fa-solid fa-receipt me-1"></i> Daily Summary</span>
          <span class="badge bg-white text-secondary">Latest</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th class="text-end">Orders</th>
                <th class="text-end">Revenue</th>
              </tr>
            </thead>
            <tbody>
              @forelse($daily_rows as $row)
                <tr>
                  <td class="fw-semibold text-dark">{{ $row['date_label'] }}</td>
                  <td class="text-end text-primary fw-bold">{{ number_format($row['orders_count']) }}</td>
                  <td class="text-end fw-bold text-success">₱{{ number_format($row['total_sales'], 2) }}</td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted py-3">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Modern Footer Status Card -->
  <div class="card card-custom p-3 mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 text-secondary" style="font-size: 0.82rem;">
      <div>
        <i class="fa-solid fa-calendar-days text-primary me-1"></i>
        <strong>Date Range:</strong> {{ $footer_date_start }} &mdash; {{ $footer_date_end }}
      </div>
      <div>
        <i class="fa-solid fa-users text-info me-1"></i>
        <strong>Total Customers:</strong> {{ number_format($footer_customers) }}
      </div>
      <div>
        <i class="fa-solid fa-credit-card text-success me-1"></i>
        <strong>Payment Methods:</strong> {{ implode(' • ', $payment_breakdown) ?: 'N/A' }}
      </div>
      <div>
        <i class="fa-solid fa-clock text-danger me-1"></i>
        <strong>Last Synced:</strong> {{ $footer_last_updated }}
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