<?php

namespace App\Http\Controllers;

use App\Actions\Reports\BuildActivityTrend;
use App\Actions\Reports\BuildReportSummary;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    private const TREND_DAYS = 30;

    /**
     * Return the fixed "at a glance" dashboard payload: an unfiltered
     * report summary plus a fixed 30-day activity trend. Unlike
     * ReportController::summary, this endpoint takes no date-range input
     * — the dashboard is a snapshot view, not a user-configurable report.
     * No policy/authorization check — same reasoning as ReportController.
     */
    public function overview(BuildReportSummary $buildReportSummary, BuildActivityTrend $buildActivityTrend): JsonResponse
    {
        return response()->json([
            'summary' => $buildReportSummary->handle(null, null),
            'trend' => $buildActivityTrend->handle(self::TREND_DAYS),
        ]);
    }
}
