{**
 * templates/reviewer/review/reviewStepHeader.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Header for the submission review pages.
 *}
{strip}
{translate|assign:"review" key='submission.review'}
{assign var="submissionTitle" value=$submission->getLocalizedTitle()|strip_unsafe_html}
{assign var="pageTitleTranslated" value="$review: <em>$submissionTitle</em>"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#reviewTabs').pkpHandler(
			'$.pkp.pages.reviewer.ReviewerTabHandler',
			{ldelim}
				reviewStep: {$reviewStep|escape},
				selected: {$selected|escape}
			{rdelim}
		);
	{rdelim});
</script>

<div id="reviewTabs" class="pkp_controllers_tab">
	<ul>
		<li><a href="{url op="step" path=$submission->getId() step=1}">{translate key="reviewer.reviewSteps.request"}</a></li>
		<li><a href="{url op="step" path=$submission->getId() step=2}">{translate key="reviewer.reviewSteps.guidelines"}</a></li>
		<li><a href="{url op="step" path=$submission->getId() step=3}">{translate key="reviewer.reviewSteps.download"}</a></li>
		<li><a href="{url op="step" path=$submission->getId() step=4}">{translate key="reviewer.reviewSteps.completion"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
