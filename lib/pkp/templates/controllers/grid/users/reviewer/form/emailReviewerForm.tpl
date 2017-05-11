{**
 * controllers/grid/users/reviewer/form/emailReviewerForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display form to send user an email.
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#emailReviewerForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="emailReviewerForm" method="post" action="{url op="sendEmail" params=$requestArgs}" >
	{csrf}
	<input type="hidden" name="reviewAssignmentId" value="{$reviewAssignmentId|escape}" />

	{fbvFormSection title="email.to" size=$fbvStyles.size.MEDIUM}
		{fbvElement type="text" id="user" value=$userFullName disabled="true"}
	{/fbvFormSection}

	{fbvFormSection title="email.subject" for="subject" required="true"}
		{fbvElement type="text" id="subject" value=$subject required="true"}
	{/fbvFormSection}

	{fbvFormSection title="email.body" for="message" required="true"}
		{fbvElement type="textarea" id="message" value=$message rich=true required="true"}
	{/fbvFormSection}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
	{fbvFormButtons submitText="common.sendEmail"}
</form>
