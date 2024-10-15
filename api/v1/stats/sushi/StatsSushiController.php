<?php

/**
 * @file api/v1/stats/sushi/StatsSushiController.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsSushiController
 *
 * @ingroup api_v1_stats
 *
 * @brief Controller class to handle API requests for COUNTER R5 SUSHI statistics.
 *
 */

namespace APP\API\v1\stats\sushi;

use APP\sushi\IR;
use APP\sushi\IR_A1;
use APP\sushi\TR;
use APP\sushi\TR_J3;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatsSushiController extends \PKP\API\v1\stats\sushi\PKPStatsSushiController
{
    /**
     * @copydoc \PKP\core\PKPBaseController::getGroupRoutes()
     */
    public function getGroupRoutes(): void
    {
        parent::getGroupRoutes();

        Route::get('reports/tr', $this->getReportsTR(...))
            ->name('stats.sushi.getReportsTR');

        Route::get('reports/tr_j3', $this->getReportsTRJ3(...))
            ->name('stats.sushi.getReportsTRJ3');

        Route::get('reports/ir', $this->getReportsIR(...))
            ->name('stats.sushi.getReportsIR');

        Route::get('reports/ir_a1', $this->getReportsIRA1(...))
            ->name('stats.sushi.getReportsIRA1');
    }

    /**
     * COUNTER 'Title Master Report' [TR].
     * A customizable report detailing activity at the journal level
     * that allows the user to apply filters and select other configuration options for the report.
     */
    public function getReportsTR(Request $illuminateRequest): JsonResponse|StreamedResponse
    {
        return $this->getReportResponse(new TR(), $illuminateRequest);
    }

    /**
     * COUNTER 'Journal Usage by Access Type' [TR_J3].
     * This is a Standard View of Title Master Report that reports on usage of journal content for all Metric_Types broken down by Access_Type.
     */
    public function getReportsTRJ3(Request $illuminateRequest): JsonResponse|StreamedResponse
    {
        return $this->getReportResponse(new TR_J3(), $illuminateRequest);
    }

    /**
     * COUNTER 'Item Master Report' [IR].
     * A customizable report detailing activity at the article level
     * that allows the user to apply filters and select other configuration options for the report.
     */
    public function getReportsIR(Request $illuminateRequest): JsonResponse|StreamedResponse
    {
        return $this->getReportResponse(new IR(), $illuminateRequest);
    }

    /**
     * COUNTER 'Journal Article Requests' [IR_A1].
     * This is a Standard View of Item Master Report that reports on journal article requests at the article level.
     */
    public function getReportsIRA1(Request $illuminateRequest): JsonResponse|StreamedResponse
    {
        return $this->getReportResponse(new IR_A1(), $illuminateRequest);
    }

    /**
     * Get the application specific list of reports supported by the API
     */
    protected function getReportList(): array
    {
        return array_merge(parent::getReportList(), [
            [
                'Report_Name' => 'Title Master Report',
                'Report_ID' => 'TR',
                'Release' => '5',
                'Report_Description' => __('sushi.reports.tr.description'),
                'Path' => 'reports/tr'
            ],
            [
                'Report_Name' => 'Journal Usage by Access Type',
                'Report_ID' => 'TR_J3',
                'Release' => '5',
                'Report_Description' => __('sushi.reports.tr_j3.description'),
                'Path' => 'reports/tr_j3'
            ],
            [
                'Report_Name' => 'Item Master Report',
                'Report_ID' => 'IR',
                'Release' => '5',
                'Report_Description' => __('sushi.reports.ir.description'),
                'Path' => 'reports/ir'
            ],
            [
                'Report_Name' => 'Journal Article Requests',
                'Report_ID' => 'IR_A1',
                'Release' => '5',
                'Report_Description' => __('sushi.reports.ir_a1.description'),
                'Path' => 'reports/ir_a1'
            ],
        ]);
    }
}
