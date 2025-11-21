<?php

namespace App\Http\Controllers;

use App\Services\OrderMetricsService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request, OrderMetricsService $service)
    {
        $metrics = $service->getMetrics();

        $orders = collect($metrics['orders_table'] ?? []);

        // Busca (ID, nome, email)
        $search = $request->input('q');
        if ($search) {
            $lower = Str::lower($search);
            $orders = $orders->filter(function ($row) use ($lower) {
                return Str::contains(Str::lower((string)($row['id'] ?? '')), $lower)
                    || Str::contains(Str::lower($row['name'] ?? ''), $lower)
                    || Str::contains(Str::lower($row['email'] ?? ''), $lower);
            });
        }

        // Paginação manual
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $total = $orders->count();
        $results = $orders->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        $metrics['orders'] = $paginator;
        $metrics['search'] = $search;

        return view('dashboard.index', $metrics);
    }
}
