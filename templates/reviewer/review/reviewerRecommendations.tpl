{**
 * templates/reviewer/review/reviewerRecommendations.tpl
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Include reviewer recommendations for OJS review assignment responses.
 *}

{foreach from=$reviewerRecommendationOptions item=item key=key}
	{$reviewerRecommendationOptions.$key = $item|escape}
{/foreach}

{fbvFormSection 
	label="reviewer.article.recommendation"
	description=$description|default:"reviewer.article.selectRecommendation"
}
	{fbvElement 
		type="select"
		id="reviewerRecommendationId"
		from=$reviewerRecommendationOptions
		selected=$reviewAssignment->getReviewerRecommendationId()
		size=$fbvStyles.size.MEDIUM
		required=$required|default:true
		disabled=$readOnly
		translate=false
		defaultValue=" "
		defaultLabel="common.chooseOne"|translate
	}
{/fbvFormSection}
