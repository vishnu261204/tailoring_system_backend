<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $stats = Order::selectRaw("
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'trial_ready' THEN 1 ELSE 0 END) as trial,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = 'delivered' AND delivery_date = ? THEN 1 ELSE 0 END) as today_deliveries
        ", [today()])->first();

        return response()->json([
            'stats' => [
                'total_orders' => (int) $stats->total_orders,
                'pending' => (int) $stats->pending,
                'in_progress' => (int) $stats->in_progress,
                'trial' => (int) $stats->trial,
                'completed' => (int) $stats->completed,
                'delivered' => (int) $stats->delivered,
                'cancelled' => (int) $stats->cancelled,
                'today_deliveries' => (int) $stats->today_deliveries,
            ],
        ]);
    }
}
