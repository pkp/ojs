{**
 * templates/user/changePassword.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to change a user's password.
 *}

{* Help Link *}
{help file="user-profile.md" class="pkp_help_tab"}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#changePasswordForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="changePasswordForm" method="post" action="{url op="savePassword"}">
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="changePasswordFormNotification"}

	<p><span class="instruct">{translate key="user.profile.changePasswordInstructions"}</span></p>

	{fbvFormArea id="changePasswordFormArea"}
		{fbvFormSection label="user.profile.oldPassword"}
			{fbvElement type="text" password="true" id="oldPassword" value=$oldPassword maxLength="32" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection label="user.profile.newPassword"}
			{translate|assign:"passwordLengthRestriction" key="user.register.form.passwordLengthRestriction" length=$minPasswordLength}
			{fbvElement type="text" password="true" id="password" value=$oldPassword label=$passwordLengthRestriction subLabelTranslate=false maxLength="32" size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" password="true" id="password2" value=$oldPassword maxLength="32" label="user.profile.repeatNewPassword" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}

		{fbvFormButtons submitText="common.save"}
	{/fbvFormArea}
</form>
