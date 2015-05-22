{**
 * templates/notification/maillist.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays the notification settings page and unchecks  
 *
 *}
{strip}
{assign var="pageTitle" value="notification.mailList"}
{include file="common/header.tpl"}
{/strip}

<p><span class="instruct">{translate key="notification.mailListDescription"}</span></p>

{if $isError}
<p>
	<span class="formError">{translate key="form.errorsOccurred"}:</span>
	<ul class="formErrorList">
	{foreach key=field item=message from=$errors}
			<li>{$message}</li>
	{/foreach}
	</ul>
</p>
{/if}

{if $success}
	<p><span class="formSuccess">{translate key="$success"}</span></p>
{/if}

<form id="notificationSettings" method="post" action="{url op="saveSubscribeMailList"}">

<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="5%">{fieldLabel name="email" key="user.email"}</td>
		<td class="value" width="45%"><input type="text" id="email" name="email" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label" width="5%">{fieldLabel name="confirmEmail" key="user.confirmEmail"}</td>
		<td class="value" width="45%"><input type="text" id="confirmEmail" name="confirmEmail" size="30" maxlength="90" class="textField" /></td>
	</tr>
	{if $captchaEnabled && $reCaptchaHtml}
	<tr>
		<td class="label" valign="top">{fieldLabel name="recaptcha_challenge_field" required="true" key="common.captchaField"}</td>
		<td class="value">
			{$reCaptchaHtml}
		</td>
	</tr>
	{elseif $captchaEnabled}
	<tr>
		<td class="label" valign="top">{fieldLabel name="captcha" required="true" key="common.captchaField"}</td>
		<td class="value">
			<img src="{url page="user" op="viewCaptcha" path=$captchaId}" alt="{translate key="common.captchaField.altText"}" /><br />
			<span class="instruct">{translate key="common.captchaField.description"}</span><br />
			<input name="captcha" id="captcha" value="" size="20" maxlength="32" class="textField" />
			<input type="hidden" name="captchaId" value="{$captchaId|escape:"quoted"}" />
		</td>
	</tr>
	{/if}{* $captchaEnabled *}
	<tr valign="top">
		<td width="5%">&nbsp;</td>
		<td><p><input type="submit" value="{translate key="form.submit"}" class="button defaultButton" /></p></td>
	</tr>
</table>
</form>
<h5 style="margin-left:10%">{translate key="notification.mailList.register"}</h5>
<ul style="margin-left:10%">
	{if $settings.allowRegReviewer}
		{url|assign:"url" page="user" op="register"}
		<li>{translate key="notification.mailList.review" reviewUrl=$url} </li>
	{/if}
	{if $settings.allowRegAuthor}
		{url|assign:"url" page="information" op="authors"}
		<li>{translate key="notification.mailList.submit" submitUrl=$url} </li>
	{/if}
	{if $settings.subscriptionsEnabled}
		{url|assign:"url" page="user" op="register"}
		<li>{translate key="notification.mailList.protectedContent" subscribeUrl=$url}
	{/if}
<li><a href="{url page="about" op="submissions" anchor="privacyStatement"}">{translate key="about.privacyStatement"}</a></li>
</ul>

</form>

{include file="common/footer.tpl"}

