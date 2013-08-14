{**
 * templates/reviewer/review/reviewerRecommendations.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Include reviewer recommendations for OJS review assignment responses.
 *}


{fbvFormSection label="reviewer.article.recommendation" description="reviewer.article.selectRecommendation"}
	{fbvElement type="select" id="recommendation" from=$reviewerRecommendationOptions selected=$reviewAssignment->getRecommendation() required=true disabled=$readOnly}
{/fbvFormSection}