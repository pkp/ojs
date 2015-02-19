{**
 * plugins/generic/referral/referralForm.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Referral form.
 *
 *}
{strip}
{if $referralId}
	{assign var="pageTitle" value="plugins.generic.referral.editReferral"}
{else}
	{assign var="pageTitle" value="plugins.generic.referral.createReferral"}
{/if}
{include file="common/header.tpl"}
{/strip}

<br/>

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#referral').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" name="referral" id="referral" method="post" action="{url op="updateReferral"}">
{if $referralId}
<input type="hidden" name="referralId" value="{$referralId|escape}" />
{/if}
<input type="hidden" name="articleId" value="{$article->getId()|escape}" />

{include file="common/formErrors.tpl"}

<table class="data">
{if count($formLocales) > 1}
	<tr>
		<td class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td class="value">
			{if $referralId}{url|assign:"referralUrl" op="editReferral" path=$referralId escape=false}
			{else}{url|assign:"referralUrl" op="createReferral"}
			{/if}
			{form_language_chooser form="referral" url=$referralUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr>
	<td class="label">{translate key="article.article"}</td>
	<td class="value"><a target="_new" href="{url page="article" op="view" path=$article->getBestArticleId()}">{$article->getLocalizedTitle()|strip_unsafe_html}</a></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="name" required="true" key="common.title"}</td>
	<td class="value"><input type="text" name="name[{$formLocale|escape}]" value="{$name[$formLocale]|escape}" size="40" id="name" maxlength="80" class="textField" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="url" required="true" key="common.url"}</td>
	<td class="value"><input type="text" name="url" id="url" value="{$url|escape}" size="40" maxlength="80" class="textField" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="status" key="common.status"}</td>
	<td class="value">
		<select name="status" id="status" class="selectMenu">
			<option {if $status == REFERRAL_STATUS_NEW}selected="selected" {/if}value="{$smarty.const.REFERRAL_STATUS_NEW}">{translate key="plugins.generic.referral.status.new"}</option>
			<option {if $status == REFERRAL_STATUS_ACCEPT}selected="selected" {/if}value="{$smarty.const.REFERRAL_STATUS_ACCEPT}">{translate key="plugins.generic.referral.status.accept"}</option>
			<option {if $status == REFERRAL_STATUS_DECLINE}selected="selected" {/if}value="{$smarty.const.REFERRAL_STATUS_DECLINE}">{translate key="plugins.generic.referral.status.decline"}</option>
		</select>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $referralId}<input type="submit" name="createAnother" value="{translate key="manager.referrals.form.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="referrals" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
