{**
 * templates/submission/form/step4.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 4 of author submission.
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the JS form handler.
		$('#submitStep4Form').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="submitStep4Form" method="post" action="{url op="saveStep" path=$submitStep}">
	{csrf}
	<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="submitStep4FormNotification"}

	<p>{translate key="submission.confirm.message"}</p>

	{fbvFormButtons id="step4Buttons" submitText="submission.submit.finishSubmission" confirmSubmit="submission.confirmSubmit"}
</form>
