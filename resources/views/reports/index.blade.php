@extends('layouts.app')

@section('title', 'Sales & Reports - CAPTAiN J POS')

@push('styles')
<style>
    .card-custom {
        background: #ffffff;
        border: none;
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }
    .kpi-tile {
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 0.9rem;
        padding: 1rem;
        height: 100%;
        box-shadow: 0 4px 12px -6px rgba(0, 0, 0, 0.08);
    }
    .kpi-tile-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .kpi-tile-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: #64748b;
        margin: 0;
    }
    .kpi-tile-value {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        line-height: 1.2;
        word-break: break-word;
    }
    .kpi-trend-up { color: #16a34a; font-weight: 700; }
    .kpi-trend-down { color: #dc2626; font-weight: 700; }
    .kpi-trend-flat { color: #94a3b8; font-weight: 700; }

    .period-tab {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
        font-weight: 600;
        font-size: 0.82rem;
        padding: 0.45rem 1.1rem;
        border-radius: 2rem;
        text-decoration: none;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .period-tab:hover {
        border-color: #f10000;
        color: #f10000;
    }
    .period-tab.active {
        background: linear-gradient(135deg, #ff1e1e 0%, #b30000 100%);
        border-color: #b30000;
        color: #ffffff;
        box-shadow: 0 4px 10px -3px rgba(241, 0, 0, 0.45);
    }

    .report-chart-wrap {
        position: relative;
        height: 320px;
        width: 100%;
    }
    .rank-badge {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.72rem;
        background: #f1f5f9;
        color: #475569;
        flex-shrink: 0;
    }
    .rank-badge.top { background: #fef3c7; color: #b45309; }

    .share-track {
        height: 5px;
        border-radius: 3px;
        background: #f1f5f9;
        overflow: hidden;
        min-width: 60px;
    }
    .share-fill {
        height: 100%;
        border-radius: 3px;
        background: linear-gradient(90deg, #ff1e1e, #b30000);
    }

    @media (max-width: 991.98px) {
        .report-chart-wrap { height: 260px; }
    }
    @media (max-width: 575.98px) {
        .report-chart-wrap { height: 230px; }
        .kpi-tile-value { font-size: 1.1rem; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark"><i class="fa-solid fa-chart-line text-primary me-2"></i> Sales &amp; Reports</h3>
            <p class="text-secondary small m-0">Owner view &mdash; revenue, items sold, best sellers and full sales history.</p>
        </div>
        <span class="badge bg-dark-subtle text-dark fw-semibold px-3 py-2 rounded-pill">
            <i class="fa-regular fa-calendar me-1"></i>
            {{ $rangeStart->format('M j, Y') }} &ndash; {{ $rangeEnd->format('M j, Y') }}
        </span>
    </div>

    <!-- Period Tabs & Date Filter -->
    <div class="card card-custom p-3 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap gap-2">
                @foreach(['daily' => 'Daily Sales', 'weekly' => 'Weekly Sales', 'monthly' => 'Monthly Sales'] as $key => $text)
                    <a href="{{ route('reports.index', array_filter(['period' => $key, 'date_from' => $filter_date_from, 'date_to' => $filter_date_to])) }}"
                       class="period-tab {{ $period === $key ? 'active' : '' }}">
                        {{ $text }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('reports.index') }}" class="d-flex flex-wrap align-items-end gap-2">
                <input type="hidden" name="period" value="{{ $period }}">
                <div>
                    <label class="form-label small fw-semibold text-secondary mb-1">From</label>
                    <input type="date" name="date_from" value="{{ $filter_date_from }}" class="form-control form-control-sm">
                </div>
                <div>
                    <label class="form-label small fw-semibold text-secondary mb-1">To</label>
                    <input type="date" name="date_to" value="{{ $filter_date_to }}" class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-primary btn-sm fw-semibold px-3">
                    <i class="fa-solid fa-filter me-1"></i> Apply
                </button>
                @if($filter_date_from || $filter_date_to)
                    <a href="{{ route('reports.index', ['period' => $period]) }}" class="btn btn-light border btn-sm fw-semibold px-3">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <!-- KPI Row -->
    @php
        $kpis = [
            ['label' => 'Total Revenue', 'value' => '₱' . number_format($kpi_revenue, 2), 'icon' => 'fa-peso-sign', 'bg' => '#16a34a', 'growth' => $kpi_revenue_growth],
            ['label' => 'Items Sold', 'value' => number_format($kpi_items_sold), 'icon' => 'fa-cubes-stacked', 'bg' => '#2563eb', 'growth' => $kpi_items_growth],
            ['label' => 'Total Orders', 'value' => number_format($kpi_orders), 'icon' => 'fa-receipt', 'bg' => '#f10000', 'growth' => $kpi_orders_growth],
            ['label' => 'Avg Order Value', 'value' => '₱' . number_format($kpi_avg_order, 2), 'icon' => 'fa-scale-balanced', 'bg' => '#7c3aed', 'growth' => $kpi_avg_growth],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach($kpis as $kpi)
            <div class="col-6 col-lg-3">
                <div class="kpi-tile d-flex align-items-start gap-3">
                    <div class="kpi-tile-icon" style="background: {{ $kpi['bg'] }};">
                        <i class="fa-solid {{ $kpi['icon'] }}"></i>
                    </div>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <p class="kpi-tile-label">{{ $kpi['label'] }}</p>
                        <p class="kpi-tile-value">{{ $kpi['value'] }}</p>
                        <div class="small mt-1">
                            @if(is_null($kpi['growth']))
                                <span class="kpi-trend-flat">&mdash;</span> <span class="text-muted">no prior data</span>
                            @elseif($kpi['growth'] >= 0)
                                <span class="kpi-trend-up">&#9650; {{ number_format(abs($kpi['growth']), 1) }}%</span> <span class="text-muted">vs previous</span>
                            @else
                                <span class="kpi-trend-down">&#9660; {{ number_format(abs($kpi['growth']), 1) }}%</span> <span class="text-muted">vs previous</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <!-- Sales Trend Chart -->
        <div class="col-12 col-xl-8">
            <div class="card card-custom p-4 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="fw-bold m-0 text-dark">{{ $period_label }} Sales Trend</h5>
                    <div class="small text-muted">
                        <span class="badge bg-danger-subtle text-danger">Revenue</span>
                        <span class="badge bg-primary-subtle text-primary">Items Sold</span>
                    </div>
                </div>
                @if(array_sum($chart_revenue) > 0)
                    <div class="report-chart-wrap">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                @else
                    <div class="text-center text-muted py-5">
                        <i class="fa-solid fa-chart-column fs-1 opacity-25 mb-3 d-block"></i>
                        No completed sales in this period.
                    </div>
                @endif
            </div>
        </div>

        <!-- Best Seller + Payment Mix -->
        <div class="col-12 col-xl-4">
            <div class="card card-custom p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-crown text-warning me-2"></i> Best Seller</h6>
                @if($best_product)
                    <h4 class="fw-bold text-dark mb-1">{{ $best_product['name'] }}</h4>
                    <div class="d-flex flex-wrap gap-3 mt-3">
                        <div>
                            <div class="kpi-tile-label">Qty Sold</div>
                            <div class="fw-bold fs-5 text-primary">{{ number_format($best_product['qty_sold']) }}</div>
                        </div>
                        <div>
                            <div class="kpi-tile-label">Revenue</div>
                            <div class="fw-bold fs-5 text-success">₱{{ number_format($best_product['revenue'], 2) }}</div>
                        </div>
                        <div>
                            <div class="kpi-tile-label">Share</div>
                            <div class="fw-bold fs-5 text-dark">{{ $best_product['share'] }}%</div>
                        </div>
                    </div>
                @else
                    <p class="text-muted small m-0">No products sold in this period.</p>
                @endif
            </div>

            <div class="card card-custom p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-credit-card text-info me-2"></i> Payment Methods</h6>
                @forelse($payment_rows as $row)
                    <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <div class="fw-semibold text-dark">{{ $row['method'] }}</div>
                            <div class="small text-muted">{{ number_format($row['orders_count']) }} {{ Str::plural('order', $row['orders_count']) }}</div>
                        </div>
                        <div class="fw-bold text-dark">₱{{ number_format($row['revenue'], 2) }}</div>
                    </div>
                @empty
                    <p class="text-muted small m-0">No payments recorded.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Sales Report Breakdown -->
    <div class="card card-custom p-4 mt-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="fw-bold m-0 text-dark">{{ $period_label }} Sales Report</h5>
                @php $periodNoun = ['daily' => 'day', 'weekly' => 'week', 'monthly' => 'month'][$period]; @endphp
                <p class="text-secondary small m-0">Revenue and items sold for each {{ $periodNoun }} in the selected range.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2">{{ count($report_rows) }} periods</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem; min-width: 640px;">
                <thead class="table-light">
                    <tr>
                        <th>{{ $period === 'monthly' ? 'Month' : ($period === 'weekly' ? 'Week' : 'Date') }}</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Items Sold</th>
                        <th class="text-end">Avg Order</th>
                        <th class="text-end">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report_rows as $row)
                        <tr>
                            <td>
                                <span class="fw-semibold text-dark">{{ $row['label'] }}</span>
                                <div class="small text-muted">{{ $row['sub_label'] }}</div>
                            </td>
                            <td class="text-end">{{ number_format($row['orders_count']) }}</td>
                            <td class="text-end fw-semibold text-primary">{{ number_format($row['items_sold']) }}</td>
                            <td class="text-end text-muted">₱{{ number_format($row['avg_order'], 2) }}</td>
                            <td class="text-end fw-bold text-success">₱{{ number_format($row['revenue'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No sales data for this range.</td></tr>
                    @endforelse
                </tbody>
                @if(count($report_rows))
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td class="text-end">{{ number_format($kpi_orders) }}</td>
                            <td class="text-end text-primary">{{ number_format($kpi_items_sold) }}</td>
                            <td class="text-end text-muted">₱{{ number_format($kpi_avg_order, 2) }}</td>
                            <td class="text-end text-success">₱{{ number_format($kpi_revenue, 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Most Sold Products -->
    <div class="card card-custom p-4 mt-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="fw-bold m-0 text-dark"><i class="fa-solid fa-fire text-danger me-2"></i> Most Sold Products</h5>
                <p class="text-secondary small m-0">Top 10 products by quantity sold in the selected range.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem; min-width: 620px;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">Rank</th>
                        <th>Product</th>
                        <th class="text-end">Qty Sold</th>
                        <th class="text-end">Revenue</th>
                        <th style="width: 160px;">Revenue Share</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($top_products as $i => $product)
                        <tr>
                            <td><span class="rank-badge {{ $i < 3 ? 'top' : '' }}">{{ $i + 1 }}</span></td>
                            <td class="fw-semibold text-dark">{{ $product['name'] }}</td>
                            <td class="text-end fw-bold text-primary">{{ number_format($product['qty_sold']) }}</td>
                            <td class="text-end fw-semibold text-success">₱{{ number_format($product['revenue'], 2) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="share-track flex-grow-1">
                                        <div class="share-fill" style="width: {{ min(100, $product['share']) }}%;"></div>
                                    </div>
                                    <span class="small text-muted" style="width: 42px;">{{ $product['share'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No products sold in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sales History -->
    <div class="card card-custom p-4 mt-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="fw-bold m-0 text-dark"><i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i> Sales History</h5>
                <p class="text-secondary small m-0">Every completed transaction in the selected range.</p>
            </div>
            <span class="text-muted small">
                Showing {{ $history->count() }} of {{ number_format($history->total()) }} transactions
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem; min-width: 780px;">
                <thead class="table-light">
                    <tr>
                        <th>Order</th>
                        <th>Date &amp; Time</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Payment</th>
                        <th class="text-end">Items</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $order)
                        <tr>
                            <td class="fw-bold text-dark">#{{ $order->id }}</td>
                            <td class="text-muted">{{ $order->created_at->format('M j, Y') }} <span class="small">{{ $order->created_at->format('g:i A') }}</span></td>
                            <td>{{ $order->customer_name ?: 'Walk-in' }}</td>
                            <td class="text-muted">{{ $order->user->full_name ?? $order->user->username ?? 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $order->payment_method === 'cash' ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info' }} fw-semibold">
                                    {{ ucfirst($order->payment_method) }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold text-primary">{{ number_format($order->items_sum_qty ?? 0) }}</td>
                            <td class="text-end fw-bold text-success">₱{{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No transactions in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($history->hasPages())
            <div class="mt-3">
                {{ $history->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('salesTrendChart');
        if (!canvas || typeof Chart === 'undefined') return;

        const peso = v => '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        new Chart(canvas, {
            data: {
                labels: @json($chart_labels),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Revenue',
                        data: @json($chart_revenue),
                        backgroundColor: 'rgba(241, 0, 0, 0.75)',
                        borderRadius: 4,
                        yAxisID: 'y',
                        order: 2
                    },
                    {
                        type: 'line',
                        label: 'Items Sold',
                        data: @json($chart_items),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.12)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#2563eb',
                        tension: 0.35,
                        fill: true,
                        yAxisID: 'y1',
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                if (ctx.dataset.label === 'Revenue') {
                                    return ' Revenue: ' + peso(ctx.parsed.y);
                                }
                                return ' Items Sold: ' + Number(ctx.parsed.y).toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        position: 'left',
                        beginAtZero: true,
                        title: { display: true, text: 'Revenue (₱)', font: { size: 10 } },
                        ticks: {
                            font: { size: 10 },
                            callback: v => '₱' + Number(v).toLocaleString()
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        title: { display: true, text: 'Items Sold', font: { size: 10 } },
                        ticks: { font: { size: 10 }, precision: 0 },
                        grid: { drawOnChartArea: false }
                    },
                    x: {
                        ticks: { font: { size: 10 }, maxRotation: 60, minRotation: 0, autoSkip: true, maxTicksLimit: 16 },
                        grid: { display: false }
                    }
                }
            }
        });
    });
</script>
@endpush
