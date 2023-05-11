<?php

/**
 * @file api/v1/stats/publications/StatsPublicationHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsPublicationHandler
 *
 * @ingroup api_v1_stats
 *
 * @brief Handle API requests for publication statistics.
 *
 */

namespace APP\API\v1\stats\publications;

class StatsPublicationHandler extends \PKP\API\v1\stats\publications\PKPStatsPublicationHandler
{
    /** @var string The name of the section ids query param for this application */
    public $sectionIdsQueryParam = 'sectionIds';

    public function getSectionIdsQueryParam()
    {
        return $this->sectionIdsQueryParam;
    }

    protected function getManyAllowedParams()
    {
        $params = parent::getManyAllowedParams();
        $params[] = 'issueIds';
        return $params;
    }

    protected function getManyTimelineAllowedParams()
    {
        $params = parent::getManyTimelineAllowedParams();
        $params[] = 'issueIds';
        return $params;
    }

    protected function _processParam(string $requestParam, mixed $value): array
    {
        if ($requestParam == 'issueIds') {
            $returnParams = [];
            if (is_string($value) && str_contains($value, ',')) {
                $value = explode(',', $value);
            } elseif (!is_array($value)) {
                $value = [$value];
            }
            $returnParams[$requestParam] = array_map('intval', $value);
        } else {
            $returnParams = parent::_processParam($requestParam, $value);
        }
        return $returnParams;
    }
}
