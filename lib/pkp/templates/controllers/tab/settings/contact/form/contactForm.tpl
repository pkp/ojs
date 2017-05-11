{**
 * controllers/tab/settings/contact/form/contactForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Contact management form.
 *
 *}

{* Help Link *}
{help file="settings.md" section="context" class="pkp_help_tab"}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#contactForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="contactForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="saveFormData" tab="contact"}">
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="contactFormNotification"}
	{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

	{if !$wizardMode}
		{fbvFormSection title="common.mailingAddress" required=true}
			{fbvElement type="textarea" id="mailingAddress" value=$mailingAddress height=$fbvStyles.height.SHORT required=true}
		{/fbvFormSection}
	{/if}

	{fbvFormArea title="manager.setup.principalContact" id="principalContactArea"}
		{fbvFormSection description="manager.setup.principalContactDescription"}
			{fbvElement type="text" label="user.name" required=true id="contactName" value=$contactName maxlength="60" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="user.title" multilingual=true name="contactTitle" id="contactTitle" value=$contactTitle maxlength="90" inline=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="text" label="user.email" required=true id="contactEmail" value=$contactEmail maxlength="90" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="user.phone" id="contactPhone" value=$contactPhone maxlength="24" inline=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvElement type="text" label="user.affiliation" multilingual=true name="contactAffiliation" id="contactAffiliation" value=$contactAffiliation maxlength="90"}
	{/fbvFormArea}

	{if !$wizardMode}
		{fbvFormArea title="manager.setup.technicalSupportContact" class=$wizardClass id="technicalContactArea"}
			{fbvFormSection description="manager.setup.technicalSupportContactDescription"}
				{fbvElement type="text" label="user.name" required=true id="supportName" value=$supportName maxlength="60" inline=true size=$fbvStyles.size.MEDIUM}
				{fbvElement type="text" label="user.email" required=true id="supportEmail" value=$supportEmail maxlength="60" inline=true size=$fbvStyles.size.MEDIUM}
			{/fbvFormSection}
			{fbvFormSection}
				{fbvElement type="text" label="user.phone" id="supportPhone" value=$supportPhone maxlength="24" inline=true size=$fbvStyles.size.MEDIUM}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}

	{if !$wizardMode}
		{fbvFormButtons id="contactFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
</form>
