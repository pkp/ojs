{**
 * templates/controllers/grid/users/reviewer/readReview.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Screen to let user read a review.
 *
 *}
{capture assign="reviewerRecommendations"}
	{include file="reviewer/review/reviewerRecommendations.tpl" description="reviewer.article.selectRecommendation.byEditor" required=false}
{/capture}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#readReviewForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

{include file="core:controllers/grid/users/reviewer/readReview.tpl"}
