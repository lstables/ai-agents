<?php

namespace App\Http\Controllers;

use App\Actions\Reports\BuildReportSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Return the aggregated report summary. No policy/authorization check
     * here — there is no Eloquent model to authorize against, and every
     * other policy in this app already returns true unconditionally (no
     * roles exist yet). ResolveDemoUser already ensures every request has
     * a resolved user, which is the only "authorization" this app has.
     */
    public function summary(Request $request, BuildReportSummary $buildReportSummary): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return response()->json(
            $buildReportSummary->handle($validated['from'] ?? null, $validated['to'] ?? null)
        );
    }
}
