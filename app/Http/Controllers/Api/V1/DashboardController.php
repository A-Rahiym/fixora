<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Dashboard\DashboardRangeRequest;
use App\Http\Responses\ApiResponse;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function summary(DashboardRangeRequest $request, DashboardService $dashboard): JsonResponse
    {
        [$from, $to] = $request->range();
        [$previousFrom, $previousTo] = $request->previousRange();

        return ApiResponse::ok($dashboard->summary($from, $to, $previousFrom, $previousTo));
    }

    public function repairPipeline(DashboardRangeRequest $request, DashboardService $dashboard): JsonResponse
    {
        return ApiResponse::ok($dashboard->repairPipeline());
    }

    public function recentActivity(DashboardRangeRequest $request, DashboardService $dashboard): JsonResponse
    {
        return ApiResponse::ok($dashboard->recentActivity($request->limit()));
    }

    public function lowStock(DashboardRangeRequest $request, DashboardService $dashboard): JsonResponse
    {
        return ApiResponse::ok($dashboard->lowStockItems());
    }
}
