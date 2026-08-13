<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Owner sales report: KPIs, daily/weekly/monthly breakdown,
     * best-selling products and sales history.
     */
    public function index(Request $request)
    {
        $period = in_array($request->input('period'), ['daily', 'weekly', 'monthly'], true)
            ? $request->input('period')
            : 'daily';

        $filter_date_from = $request->input('date_from', '');
        $filter_date_to = $request->input('date_to', '');

        // Resolve the reporting window. A custom range always wins; otherwise
        // each period gets a sensible default span.
        if ($filter_date_from && $filter_date_to) {
            $rangeStart = Carbon::parse($filter_date_from)->startOfDay();
            $rangeEnd = Carbon::parse($filter_date_to)->endOfDay();
            if ($rangeEnd->lt($rangeStart)) {
                [$rangeStart, $rangeEnd] = [$rangeEnd->startOfDay(), $rangeStart->endOfDay()];
            }
        } else {
            $rangeEnd = today()->endOfDay();
            $rangeStart = match ($period) {
                'weekly' => today()->subWeeks(11)->startOfWeek()->startOfDay(),
                'monthly' => today()->subMonths(11)->startOfMonth()->startOfDay(),
                default => today()->subDays(29)->startOfDay(),
            };
        }

        // Same-length window immediately before, used for the growth figures
        $spanSeconds = $rangeEnd->getTimestamp() - $rangeStart->getTimestamp();
        $prevEnd = $rangeStart->copy()->subSecond();
        $prevStart = $prevEnd->copy()->subSeconds($spanSeconds);

        $orders = $this->completedOrdersBetween($rangeStart, $rangeEnd);
        $prevOrders = $this->completedOrdersBetween($prevStart, $prevEnd);

        $items = $this->soldItemsBetween($rangeStart, $rangeEnd);
        $prevItems = $this->soldItemsBetween($prevStart, $prevEnd);

        // ---------------- KPIs ----------------
        $kpi_revenue = (float) $orders->sum('total_amount');
        $kpi_items_sold = (int) $items->sum('qty');
        $kpi_orders = $orders->count();
        $kpi_avg_order = $kpi_orders > 0 ? $kpi_revenue / $kpi_orders : 0.0;

        $prev_revenue = (float) $prevOrders->sum('total_amount');
        $prev_items_sold = (int) $prevItems->sum('qty');
        $prev_orders = $prevOrders->count();
        $prev_avg_order = $prev_orders > 0 ? $prev_revenue / $prev_orders : 0.0;

        $kpi_revenue_growth = $this->growth($kpi_revenue, $prev_revenue);
        $kpi_items_growth = $this->growth($kpi_items_sold, $prev_items_sold);
        $kpi_orders_growth = $this->growth($kpi_orders, $prev_orders);
        $kpi_avg_growth = $this->growth($kpi_avg_order, $prev_avg_order);

        // ---------------- Most sold products ----------------
        $totalItemRevenue = (float) $items->sum('line_total') ?: 1.0;
        $top_products = $items->groupBy('product')
            ->map(function ($group, $name) use ($totalItemRevenue) {
                $revenue = (float) $group->sum('line_total');
                return [
                    'name' => $name,
                    'qty_sold' => (int) $group->sum('qty'),
                    'revenue' => $revenue,
                    'share' => round(($revenue / $totalItemRevenue) * 100, 1),
                ];
            })
            ->sortByDesc('qty_sold')
            ->take(10)
            ->values();

        $best_product = $top_products->first();

        // ---------------- Period breakdown (daily / weekly / monthly) ----------------
        $ordersByKey = $orders->groupBy(fn($o) => $this->periodKey($o->created_at, $period));
        $itemsByKey = $items->groupBy(fn($i) => $this->periodKey($i->created_at, $period));

        $report_rows = [];
        $chart_labels = [];
        $chart_revenue = [];
        $chart_items = [];

        foreach ($this->timeline($rangeStart, $rangeEnd, $period) as [$key, $label, $subLabel]) {
            $periodOrders = $ordersByKey->get($key, collect());
            $periodItems = $itemsByKey->get($key, collect());

            $revenue = (float) $periodOrders->sum('total_amount');
            $qty = (int) $periodItems->sum('qty');
            $count = $periodOrders->count();

            $report_rows[] = [
                'label' => $label,
                'sub_label' => $subLabel,
                'orders_count' => $count,
                'items_sold' => $qty,
                'revenue' => $revenue,
                'avg_order' => $count > 0 ? $revenue / $count : 0.0,
            ];

            $chart_labels[] = $label;
            $chart_revenue[] = round($revenue, 2);
            $chart_items[] = $qty;
        }

        // Newest period first in the table, oldest first in the chart
        $report_rows = array_reverse($report_rows);

        // ---------------- Payment mix ----------------
        $payment_rows = $orders->groupBy('payment_method')
            ->map(fn($g, $method) => [
                'method' => ucfirst($method),
                'orders_count' => $g->count(),
                'revenue' => (float) $g->sum('total_amount'),
            ])
            ->sortByDesc('revenue')
            ->values();

        // ---------------- Sales history (paginated) ----------------
        $history = Order::with('user')
            ->withCount('items')
            ->withSum('items', 'qty')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $period_label = match ($period) {
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            default => 'Daily',
        };

        return view('reports.index', compact(
            'period', 'period_label',
            'filter_date_from', 'filter_date_to',
            'rangeStart', 'rangeEnd',
            'kpi_revenue', 'kpi_items_sold', 'kpi_orders', 'kpi_avg_order',
            'kpi_revenue_growth', 'kpi_items_growth', 'kpi_orders_growth', 'kpi_avg_growth',
            'top_products', 'best_product',
            'report_rows', 'chart_labels', 'chart_revenue', 'chart_items',
            'payment_rows', 'history'
        ));
    }

    private function completedOrdersBetween(Carbon $start, Carbon $end)
    {
        return Order::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->get(['id', 'created_at', 'total_amount', 'payment_method', 'customer_name']);
    }

    private function soldItemsBetween(Carbon $start, Carbon $end)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('inventory', 'order_items.inventory_id', '=', 'inventory.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'inventory.name as product',
                'order_items.qty',
                'order_items.line_total',
                'orders.created_at'
            )
            ->get();
    }

    private function growth(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return null; // no comparable baseline
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function periodKey($date, string $period): string
    {
        $c = Carbon::parse($date);

        return match ($period) {
            'weekly' => $c->copy()->startOfWeek()->format('Y-m-d'),
            'monthly' => $c->format('Y-m'),
            default => $c->format('Y-m-d'),
        };
    }

    /**
     * Continuous list of [key, label, subLabel] buckets covering the range,
     * so periods with no sales still appear as zero rows.
     */
    private function timeline(Carbon $start, Carbon $end, string $period): array
    {
        $buckets = [];

        if ($period === 'monthly') {
            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $buckets[] = [
                    $cursor->format('Y-m'),
                    $cursor->format('M Y'),
                    $cursor->format('F Y'),
                ];
                $cursor->addMonth();
            }
        } elseif ($period === 'weekly') {
            $cursor = $start->copy()->startOfWeek();
            while ($cursor->lte($end)) {
                $weekEnd = $cursor->copy()->endOfWeek();
                $buckets[] = [
                    $cursor->format('Y-m-d'),
                    'Wk ' . $cursor->format('M j'),
                    $cursor->format('M j') . ' – ' . $weekEnd->format('M j, Y'),
                ];
                $cursor->addWeek();
            }
        } else {
            $cursor = $start->copy()->startOfDay();
            // Guard against very wide custom ranges rendering thousands of rows
            $limit = 120;
            while ($cursor->lte($end) && count($buckets) < $limit) {
                $buckets[] = [
                    $cursor->format('Y-m-d'),
                    $cursor->format('M j'),
                    $cursor->format('l, M j, Y'),
                ];
                $cursor->addDay();
            }
        }

        return $buckets;
    }
}
