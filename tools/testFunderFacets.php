<?php

/**
 * @file tools/testFunderFacets.php
 *
 * @brief Throwaway script to sanity-check the funder facet concept:
 *   Repo::funder()->getUniqueFunderNames() and Collector::filterByFunder().
 *   Not meant to be kept — delete once the concept is validated.
 *
 * Usage: php tools/testFunderFacets.php <contextId> [funderValue]
 */

require(dirname(__FILE__) . '/bootstrap.php');

use APP\facades\Repo;
use PKP\submission\PKPSubmission;

$contextId = (int) ($argv[1] ?? 0);
if (!$contextId) {
    echo "Usage: php tools/testFunderFacets.php <contextId> [funderValue]\n";
    exit(1);
}

echo "== getUniqueFunderNames({$contextId}) ==\n";
$options = Repo::funder()->getUniqueFunderNames($contextId);
foreach ($options as $option) {
    echo "  value={$option['value']}\tlabel={$option['label']}\n";
}
echo '  (' . $options->count() . " facet(s))\n\n";

$funderValue = $argv[2] ?? $options->first()['value'] ?? null;
if (!$funderValue) {
    echo "No funder value to test filterByFunder() with (list above is empty).\n";
    exit(0);
}

echo "== filterByFunder('{$funderValue}') ==\n";
$submissionIds = Repo::submission()->getCollector()
    ->filterByContextIds([$contextId])
    ->filterByStatus([PKPSubmission::STATUS_PUBLISHED])
    ->filterByFunder($funderValue)
    ->getIds();

echo '  matching submission_ids: ' . $submissionIds->implode(', ') . "\n";
echo '  (' . $submissionIds->count() . " submission(s))\n";
