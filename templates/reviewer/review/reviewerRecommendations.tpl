{**
 * templates/reviewer/review/reviewerRecommendations.tpl
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Include reviewer recommendations for OJS review assignment responses.
 *}

{fbvFormSection 
	label="reviewer.article.recommendation"
	description=$description|default:"reviewer.article.selectRecommendation"
}
	{fbvElement 
		type="select"
		id="recommendation"
		from=$reviewerRecommendationOptions
		selected=$reviewAssignment->getRecommendation()
		size=$fbvStyles.size.MEDIUM
		required=$required|default:true
		disabled=$readOnly
		translate=false
	}
{/fbvFormSection}
