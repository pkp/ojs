{**
 * controllers/grid/settings/user/form/userEmailForm.tpl
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
		$('#sendEmailForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="sendEmailForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.user.UserGridHandler" op="sendEmail"}" >
	{csrf}

	<input type="hidden" name="userId" value="{$userId|escape}" />

	{fbvFormSection title="email.subject" for="subject" required="true" size=$fbvStyles.size.MEDIUM inline=true}
		{fbvElement type="text" id="subject" value=$subject required="true"}
	{/fbvFormSection}

	{fbvFormSection title="email.to" size=$fbvStyles.size.MEDIUM inline=true}
		{fbvElement type="text" id="user" value=$userFullName|concat:" <":$userEmail:">" disabled="true"}
	{/fbvFormSection}

	{fbvFormSection title="email.body" for="message" required="true"}
		{fbvElement type="textarea" id="message" value=$message rich=true required="true"}
	{/fbvFormSection}

	{fbvFormButtons submitText="common.sendEmail"}
</form>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
