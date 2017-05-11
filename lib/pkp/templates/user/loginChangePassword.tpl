{**
 * templates/user/loginChangePassword.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to change a user's password in order to login.
 *
 *}
{strip}
{assign var="pageTitle" value="user.changePassword"}
{url|assign:"currentUrl" page="login" op="changePassword"}
{include file="common/header.tpl"}
{/strip}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#loginChangePassword').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>

{if !$passwordLengthRestrictionLocaleKey}
	{assign var="passwordLengthRestrictionLocaleKey" value="user.register.form.passwordLengthRestriction"}
{/if}

<form class="pkp_form" id="loginChangePassword" method="post" action="{url page="login" op="savePassword"}">
	{csrf}
	{include file="common/formErrors.tpl"}

	<p><span class="instruct">{translate key="user.login.changePasswordInstructions"}</span></p>

	{fbvFormArea id="loginFields"}
		{fbvFormSection label="user.login" for="username"}
			{fbvElement type="text" required=true id="username" value=$username maxlength="32" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection label="user.profile.oldPassword" for="oldPassword"}
			{fbvElement type="text" required=true password=true id="oldPassword" value=$oldPassword maxlength="32" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection label="user.profile.newPassword" for="password"}
			{fbvElement type="text" required=true password=true id="password" value=$password maxlength="32" size=$fbvStyles.size.MEDIUM}
			{fieldLabel translate=true for=password key=$passwordLengthRestrictionLocaleKey length=$minPasswordLength}
		{/fbvFormSection}
		{fbvFormSection label="user.profile.repeatNewPassword" for="password2"}
			{fbvElement type="text" required=true password=true id="password2" value=$password2|escape maxlength="32" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormButtons}
	{/fbvFormArea}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>

{include file="common/footer.tpl"}
