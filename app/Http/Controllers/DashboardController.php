<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filter_date_from = $request->input('date_from', '');
        $filter_date_to = $request->input('date_to', '');

        $query = Order::where('status', 'completed');
        if ($filter_date_from) {
            $query->where('created_at', '>=', $filter_date_from . ' 00:00:00');
        }
        if ($filter_date_to) {
            $query->where('created_at', '<=', $filter_date_to . ' 23:59:59');
        }

        $allOrders = $query->get();
        $baseItemsQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('inventory', 'order_items.inventory_id', '=', 'inventory.id')
            ->where('orders.status', 'completed');

        if ($filter_date_from) {
            $baseItemsQuery->where('orders.created_at', '>=', $filter_date_from . ' 00:00:00');
        }
        if ($filter_date_to) {
            $baseItemsQuery->where('orders.created_at', '<=', $filter_date_to . ' 23:59:59');
        }

        // Base items data for charts/panels
        $orderItems = $baseItemsQuery->select(
            'inventory.id as product_id',
            'inventory.name as product',
            'order_items.qty',
            'order_items.line_total',
            'orders.created_at'
        )->get();

        // Data processing using Collections (DB agnostic)
        $total_users = User::count();
        $total_orders = Order::count();
        $total_inventory = Inventory::count();

        // Sales per product
        $salesPerProduct = $orderItems->groupBy('product')->map(function ($items) {
            return $items->sum('line_total');
        })->sortByDesc(fn($total) => $total);

        $products = $salesPerProduct->keys()->toArray();
        $sales = $salesPerProduct->values()->toArray();

        $total_sales_sum = array_sum($sales) ?: 1;
        $sales_percent = [];
        foreach ($sales as $v) {
            $sales_percent[] = round(($v / $total_sales_sum) * 100, 2);
        }
        $diff = round(array_sum($sales_percent), 2) - 100;
        if (!empty($sales_percent) && abs($diff) > 0.001) {
            $maxKey = array_search(max($sales_percent), $sales_percent);
            $sales_percent[$maxKey] = round($sales_percent[$maxKey] - $diff, 2);
        }

        // Monthly sales trend (last 12 continuous months zero-filled)
        $months = [];
        $month_sales = [];

        if ($filter_date_from && $filter_date_to) {
            $monthlySalesGrp = $allOrders->groupBy(function($order) {
                return Carbon::parse($order->created_at)->format('Y-m');
            })->map(fn($group) => $group->sum('total_amount'))->sortKeys();

            foreach($monthlySalesGrp as $key => $val) {
                $months[] = Carbon::createFromFormat('Y-m', $key)->format('M Y');
                $month_sales[] = (float)$val;
            }
        } else {
            // Generate continuous last 12 months timeline
            for ($i = 11; $i >= 0; $i--) {
                $mDate = today()->subMonths($i);
                $monthKey = $mDate->format('Y-m');
                $label = $mDate->format('M');

                $mSum = $allOrders->filter(function($o) use ($monthKey) {
                    return Carbon::parse($o->created_at)->format('Y-m') === $monthKey;
                })->sum('total_amount');

                $months[] = $label;
                $month_sales[] = (float)$mSum;
            }
        }

        // Daily sales (last 7 days, complete with zero-filled empty days)
        $daily_labels = [];
        $daily_sales = [];
        $daily_rows = [];
        
        if ($filter_date_from && $filter_date_to) {
            $start = Carbon::parse($filter_date_from);
            $end = Carbon::parse($filter_date_to);
            // Limit to max 31 days to prevent UI congestion
            if ($start->diffInDays($end) > 31) {
                $start = $end->copy()->subDays(30);
            }
        } else {
            $start = today()->subDays(6);
            $end = today();
        }

        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            $dateLabel = $current->format('M j');
            $daily_labels[] = $dateLabel;
            
            // Filter orders for this day
            $dayOrders = $allOrders->filter(function($o) use ($dateStr) {
                return Carbon::parse($o->created_at)->format('Y-m-d') === $dateStr;
            });
            
            $daySum = (float)$dayOrders->sum('total_amount');
            $dayCount = $dayOrders->count();
            
            $daily_sales[] = $daySum;
            $daily_rows[] = [
                'date_label' => $dateLabel,
                'orders_count' => $dayCount,
                'total_sales' => $daySum
            ];
            
            $current->addDay();
        }

        // Weekly sales (Last 4 Weeks)
        $weekly_labels = [];
        $weekly_sales = [];

        if ($filter_date_from && $filter_date_to) {
            $weeklySalesGrp = $allOrders->groupBy(function($o) {
                return Carbon::parse($o->created_at)->startOfWeek()->format('Y-m-d');
            })->map(fn($g) => $g->sum('total_amount'))->sortKeys();

            foreach ($weeklySalesGrp as $k => $v) {
                $weekly_labels[] = 'Wk ' . Carbon::parse($k)->format('M j');
                $weekly_sales[] = (float)$v;
            }
        } else {
            // Generate strictly last 4 weeks
            for ($i = 3; $i >= 0; $i--) {
                $wStart = today()->subWeeks($i)->startOfWeek();
                $wEnd = $wStart->copy()->endOfWeek();
                $label = 'Wk ' . $wStart->format('M j');
                
                $sum = $allOrders->filter(function($o) use ($wStart, $wEnd) {
                    $dt = Carbon::parse($o->created_at);
                    return $dt->gte($wStart) && $dt->lte($wEnd);
                })->sum('total_amount');

                $weekly_labels[] = $label;
                $weekly_sales[] = (float)$sum;
            }
        }

        // Peak Sales Hours (operating hours 8 AM to 10 PM continuous zero-filled timeline)
        $hour_labels = [];
        $hour_sales = [];

        if ($filter_date_from && $filter_date_to) {
            $hourSalesGrp = $allOrders->groupBy(function($o) {
                return (int)Carbon::parse($o->created_at)->format('H');
            })->map(fn($g) => $g->sum('total_amount'))->sortKeys();

            foreach ($hourSalesGrp as $h => $v) {
                $hour_labels[] = Carbon::createFromTime($h, 0, 0)->format('g A');
                $hour_sales[] = (float)$v;
            }
        } else {
            // Generate continuous store operating hours timeline
            $minH = 8;
            $maxH = 22;
            foreach ($allOrders as $o) {
                $h = (int)Carbon::parse($o->created_at)->format('H');
                if ($h < $minH) $minH = $h;
                if ($h > $maxH) $maxH = $h;
            }

            for ($h = $minH; $h <= $maxH; $h++) {
                $hourLabel = Carbon::createFromTime($h, 0, 0)->format('g A');
                $hSum = $allOrders->filter(function($o) use ($h) {
                    return (int)Carbon::parse($o->created_at)->format('H') === $h;
                })->sum('total_amount');

                $hour_labels[] = $hourLabel;
                $hour_sales[] = (float)$hSum;
            }
        }

        // KPIs
        $sales_today = $allOrders->filter(fn($o) => Carbon::parse($o->created_at)->isToday())->sum('total_amount');
        $sales_yesterday = $allOrders->filter(fn($o) => Carbon::parse($o->created_at)->isYesterday())->sum('total_amount');
        
        $monthly_revenue = $allOrders->filter(fn($o) => Carbon::parse($o->created_at)->isCurrentMonth())->sum('total_amount');
        $monthly_revenue_prev = $allOrders->filter(fn($o) => Carbon::parse($o->created_at)->format('Y-m') === now()->subMonth()->format('Y-m'))->sum('total_amount');

        $orders_this_month = $allOrders->filter(fn($o) => Carbon::parse($o->created_at)->isCurrentMonth())->count();
        $orders_last_month = $allOrders->filter(fn($o) => Carbon::parse($o->created_at)->format('Y-m') === now()->subMonth()->format('Y-m'))->count();

        $total_products = collect($orderItems)->filter(fn($i) => Carbon::parse($i->created_at)->isCurrentMonth())->pluck('product_id')->unique()->count();
        $total_products_prev = collect($orderItems)->filter(fn($i) => Carbon::parse($i->created_at)->format('Y-m') === now()->subMonth()->format('Y-m'))->pluck('product_id')->unique()->count();

        $bestProductGrp = collect($orderItems)->groupBy('product')->map(fn($g) => $g->count())->sortByDesc(fn($c) => $c);
        $best_product_name = $bestProductGrp->keys()->first() ?? 'N/A';
        $best_product_count = $bestProductGrp->first() ?? 0;

        $sales_growth = $monthly_revenue_prev > 0 ? round((($monthly_revenue - $monthly_revenue_prev) / $monthly_revenue_prev) * 100, 1) : 0;

        // Panels
        $top5_products = collect($orderItems)->groupBy('product')->map(function($g, $name) {
            return [
                'name' => $name,
                'qty_sold' => $g->sum('qty'),
                'revenue' => $g->sum('line_total')
            ];
        })->sortByDesc('qty_sold')->take(5)->values();

        $least5_products = collect($orderItems)->groupBy('product')->map(function($g, $name) {
            return [
                'name' => $name,
                'qty_sold' => $g->sum('qty'),
                'revenue' => $g->sum('line_total')
            ];
        })->sortBy('qty_sold')->take(5)->values();

        // Panel 3: Sales Growth Summary
        $growth_rows = $allOrders->filter(fn($o) => Carbon::parse($o->created_at)->isCurrentYear())
            ->groupBy(fn($o) => Carbon::parse($o->created_at)->format('Y-m'))
            ->sortKeys()
            ->map(function($g, $m) {
                return [
                    'period' => Carbon::createFromFormat('Y-m', $m)->format('F Y'),
                    'total' => $g->sum('total_amount')
                ];
            })->values();

        // Panel 4: Peak Sales Day Ranking
        $peakday_rows = $allOrders->groupBy(fn($o) => Carbon::parse($o->created_at)->format('l'))
            ->map(function($g, $day) {
                return [
                    'day_name' => $day,
                    'total' => $g->sum('total_amount')
                ];
            })->sortByDesc('total')->values();
        $total_all_sales = $allOrders->sum('total_amount') ?: 1;

        // Footer
        $footer_date_start = $allOrders->min('created_at') ? Carbon::parse($allOrders->min('created_at'))->format('M j, Y') : 'N/A';
        $footer_date_end = $allOrders->max('created_at') ? Carbon::parse($allOrders->max('created_at'))->format('M j, Y') : 'N/A';
        $footer_customers = $allOrders->pluck('customer_name')->filter()->unique()->count();
        
        $payment_rows = $allOrders->groupBy('payment_method')->map(fn($g) => $g->count())->sortByDesc(fn($c) => $c);
        $payment_total = $payment_rows->sum() ?: 1;
        $payment_breakdown = [];
        foreach($payment_rows as $method => $cnt) {
            $payment_breakdown[] = ucfirst($method) . ' ' . round(($cnt / $payment_total) * 100) . '%';
        }
        $footer_last_updated = now()->format('M j, Y \a\t g:i A');

        return view('dashboard.index', compact(
            'filter_date_from', 'filter_date_to',
            'total_users', 'total_orders', 'total_inventory',
            'products', 'sales', 'sales_percent',
            'months', 'month_sales',
            'daily_labels', 'daily_sales',
            'weekly_labels', 'weekly_sales',
            'hour_labels', 'hour_sales',
            'sales_today', 'sales_yesterday',
            'monthly_revenue', 'monthly_revenue_prev',
            'orders_this_month', 'orders_last_month',
            'total_products', 'total_products_prev',
            'best_product_name', 'best_product_count',
            'sales_growth',
            'top5_products', 'least5_products',
            'growth_rows', 'peakday_rows', 'daily_rows', 'total_all_sales',
            'footer_date_start', 'footer_date_end', 'footer_customers',
            'payment_breakdown', 'footer_last_updated'
        ));
    }
}
