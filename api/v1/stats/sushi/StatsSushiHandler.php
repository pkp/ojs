<?php

/**
 * @file api/v1/stats/sushi/StatsSushiHandler.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsSushiHandler
 *
 * @ingroup api_v1_stats
 *
 * @brief Handle API requests for COUNTER R5 SUSHI statistics.
 *
 */

namespace APP\API\v1\stats\sushi;

use APP\sushi\IR;
use APP\sushi\IR_A1;
use APP\sushi\TR;
use APP\sushi\TR_J3;
use PKP\core\APIResponse;
use Slim\Http\Request as SlimHttpRequest;

class StatsSushiHandler extends \PKP\API\v1\stats\sushi\PKPStatsSushiHandler
{
    /**
     * Get this API's endpoints definitions
     */
    protected function getGETDefinitions(array $roles = null): array
    {
        return array_merge(
            parent::getGETDefinitions($roles),
            [
                [
                    'pattern' => $this->getEndpointPattern() . '/reports/tr',
                    'handler' => [$this, 'getReportsTR'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/reports/tr_j3',
                    'handler' => [$this, 'getReportsTRJ3'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/reports/ir',
                    'handler' => [$this, 'getReportsIR'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/reports/ir_a1',
                    'handler' => [$this, 'getReportsIRA1'],
                    'roles' => $roles
                ],
            ]
        );
    }

    /**
     * COUNTER 'Title Master Report' [TR].
     * A customizable report detailing activity at the journal level
     * that allows the user to apply filters and select other configuration options for the report.
     */
    public function getReportsTR(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        return $this->getReportResponse(new TR(), $slimRequest, $response, $args);
    }

    /**
     * COUNTER 'Journal Usage by Access Type' [TR_J3].
     * This is a Standard View of Title Master Report that reports on usage of journal content for all Metric_Types broken down by Access_Type.
     */
    public function getReportsTRJ3(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        return $this->getReportResponse(new TR_J3(), $slimRequest, $response, $args);
    }

    /**
     * COUNTER 'Item Master Report' [IR].
     * A customizable report detailing activity at the article level
     * that allows the user to apply filters and select other configuration options for the report.
     */
    public function getReportsIR(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        return $this->getReportResponse(new IR(), $slimRequest, $response, $args);
    }

    /**
     * COUNTER 'Journal Article Requests' [IR_A1].
     * This is a Standard View of Item Master Report that reports on journal article requests at the article level.
     */
    public function getReportsIRA1(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        return $this->getReportResponse(new IR_A1(), $slimRequest, $response, $args);
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
