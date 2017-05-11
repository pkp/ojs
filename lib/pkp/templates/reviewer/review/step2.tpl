{**
 * templates/reviewer/review/step2.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the step 2 review page
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#reviewStep2Form').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler'
		);
	{rdelim});
</script>

<form class="pkp_form" id="reviewStep2Form" method="post" action="{url page="reviewer" op="saveStep" path=$submission->getId() step="2" escape=false}">
{csrf}
{include file="common/formErrors.tpl"}

{fbvFormArea id="reviewStep2"}
	{fbvFormSection label="reviewer.submission.reviewerGuidelines"}
		<p>{$reviewerGuidelines}</p>
	{/fbvFormSection}

	{url|assign:cancelUrl page="reviewer" op="submission" path=$submission->getId() step=1 escape=false}
	{fbvFormButtons submitText="reviewer.submission.continueToStepThree" cancelText="navigation.goBack" cancelUrl=$cancelUrl cancelUrlTarget="_self" submitDisabled=$reviewIsComplete}
{/fbvFormArea}
</form>
