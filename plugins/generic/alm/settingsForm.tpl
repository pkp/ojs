{**
 * plugins/generic/alm/settingsForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ALM plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.alm.displayName"}
{include file="common/header.tpl"}
{/strip}
<div id="almPlugin">
<div id="description">{translate key="plugins.generic.alm.description"}</div>

<div class="separator">&nbsp;</div>

<form class="pkp_form" method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

{fbvFormArea id="almSettingsFormArea"}
	{translate key="plugins.generic.alm.settings.apiKey.description"}
	{fbvFormSection title="plugins.generic.alm.settings.apiKey" for="name" inline=true size=$fbvStyles.size.MEDIUM}
		{fbvElement type="text" name="apiKey" id="apiKey" value=$apiKey}
	{/fbvFormSection}
	{fbvFormSection list=true}
		{if $depositArticles}{assign var="deposit" value="checked"}{/if}
		{fbvElement type="checkbox" id="depositArticles" value="1" checked=$deposit label="plugins.generic.alm.settings.depositArticles" }
	{/fbvFormSection}
	{fbvFormSection title="plugins.generic.alm.settings.depositUrl" for="depositUrl" inline=true size=$fbvStyles.size.MEDIUM}
		{fbvElement type="text" name="depositUrl" id="depositUrl" value=$depositUrl label="plugins.generic.alm.settings.depositUrl.description"}
	{/fbvFormSection}
{/fbvFormArea}

{translate key="plugins.generic.alm.settings.ipAddress"  ip=$smarty.server.SERVER_ADDR}

<br/>
<br/>
<input type="submit" name="save" class="button defaultButton" style="width:auto" value="{translate key="common.save"}"/> <input type="button" class="button" style="width:auto" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
