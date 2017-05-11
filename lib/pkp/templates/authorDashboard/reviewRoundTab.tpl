{**
 * templates/authorDashboard/reviewRoundTab.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Build a review round tab markup (for any review stage).
 *}
<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#{$reviewRoundTabsId}').pkpHandler(
			'$.pkp.controllers.TabHandler',
			{ldelim}
				{assign var=roundIndex value=$lastReviewRoundNumber-1}
				selected: {$roundIndex}
			{rdelim}
		);
	{rdelim});
</script>
<div id="{$reviewRoundTabsId}" class="pkp_controllers_tab">
	<ul>
		{iterate from=reviewRounds item=reviewRound}
			<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.authorDashboard.AuthorDashboardReviewRoundTabHandler" op="fetchReviewRoundInfo" submissionId=$submission->getId() stageId=$reviewRound->getStageId() reviewRoundId=$reviewRound->getId() escape=false}">{translate key="submission.round" round=$reviewRound->getRound()}</a></li>
		{/iterate}
	</ul>
</div>
