{**
 * templates/controllers/grid/settings/preparedEmails/form/emailTemplateForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit or create a prepared email
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#managePreparedEmailForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" method="post" id="managePreparedEmailForm" action="{url op="updatePreparedEmail"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="emailTemplateFormNotification"}

	{if $isNewTemplate}
		{fbvFormArea id="emailTemplateData"}
			<h3>{translate key="manager.emails.data"}</h3>
			{fbvFormSection title="common.name" required="true" for="emailKey"}
				{fbvElement type="text" name="emailKey" id="emailKey" maxlength="120" required="true"}
			{/fbvFormSection}
		{/fbvFormArea}
	{else}
		{fbvFormArea id="emailTemplateData"}
			<h3>{translate key="manager.emails.data"}</h3>
			{if $description}
				{fbvFormSection title="common.description"}
					<p>{$description|escape}</p>
				{/fbvFormSection}
			{/if}

			{fbvFormSection title="manager.emails.emailKey" for="emailKey"}
				{fbvElement type="text" name="emailKey" value=$emailKey id="emailKey" disabled=true}
				<input type="hidden" name="emailKey" value="{$emailKey|escape}" />
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}

	{fbvFormArea id="emailTemplateDetails"}
		<h3>{translate key="manager.emails.details"}</h3>
		{fbvFormSection title="email.subject" required="true" for="subject"}
			{fbvElement type="text" multilingual="true" name="subject" id="subject" value=$subject maxlength="120" required="true"}
		{/fbvFormSection}

		{fbvFormSection title="email.body" required="true" for="body"}
			{fbvElement type="textarea" multilingual="true" name="body" id="body" value=$body rich=true required="true"}
		{/fbvFormSection}
	{/fbvFormArea}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

	{fbvFormButtons submitText="common.save"}
</form>
