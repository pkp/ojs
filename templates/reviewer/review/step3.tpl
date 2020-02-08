{**
 * templates/reviewer/review/step3.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Show the step 3 review page
 *}
{capture assign="additionalFormFields"}
	{include file="reviewer/review/reviewerRecommendations.tpl"}
{/capture}

{include file="core:reviewer/review/step3.tpl"}
