{**
 * templates/submission/form/index.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Main template for the author's submission pages.
 *}
{strip}
{assign var=pageTitle value="submission.submit.title"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#submitTabs').pkpHandler(
			'$.pkp.pages.submission.SubmissionTabHandler',
			{ldelim}
				submissionProgress: {$submissionProgress},
				selected: {$submissionProgress-1},
				cancelUrl: {url|json_encode page="submissions" escape=false},
				cancelConfirmText: {translate|json_encode key="submission.submit.cancelSubmission"}
			{rdelim}
		);
	{rdelim});
</script>

<div id="submitTabs" class="pkp_controllers_tab">
	<ul>
		{foreach from=$steps key=step item=stepLocaleKey}
			<li><a name="step-{$step|escape}" href="{url op="step" path=$step submissionId=$submissionId}">{$step}. {translate key=$stepLocaleKey}</a></li>
		{/foreach}
	</ul>
</div>

{include file="common/footer.tpl"}
