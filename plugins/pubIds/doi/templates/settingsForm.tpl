{**
 * plugins/pubIds/doi/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * DOI plugin settings
 *
 *}

<div id="description">{translate key="plugins.pubIds.doi.manager.settings.description"}</div>

<script src="{$baseUrl}/plugins/pubIds/doi/js/DOISettingsFormHandler.js"></script>
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#doiSettingsForm').pkpHandler('$.pkp.plugins.pubIds.doi.js.DOISettingsFormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="doiSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="pubIds" plugin=$pluginName verb="settings" save="true"}">
	{include file="common/formErrors.tpl"}
	{fbvFormArea id="doiObjectsFormArea" title="plugins.pubIds.doi.manager.settings.doiObjects"}
		{fbvFormSection list="true" description="plugins.pubIds.doi.manager.settings.explainDois"}
			{if $enableIssueDoi}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" id="enableIssueDoi" label="plugins.pubIds.doi.manager.settings.enableIssueDoi" maxlength="40" checked=$checked}
			{if $enableArticleDoi}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" id="enableArticleDoi" label="plugins.pubIds.doi.manager.settings.enableArticleDoi" maxlength="40" checked=$checked}
			{if $enableArticleGalleyDoi}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" id="enableArticleGalleyDoi" label="plugins.pubIds.doi.manager.settings.enableGalleyDoi" maxlength="40" checked=$checked}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="doiPrefixFormArea" title="plugins.pubIds.doi.manager.settings.doiPrefix"}
		{fbvFormSection description="plugins.pubIds.doi.manager.settings.doiPrefixPattern"}
			{fbvElement type="text" id="doiPrefix" value=$doiPrefix required="true" label="plugins.pubIds.doi.manager.settings.doiPrefix" maxlength="40" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="doiSuffixFormArea" title="plugins.pubIds.doi.manager.settings.doiSuffix"}
		{fbvFormSection label="plugins.pubIds.doi.manager.settings.doiSuffix.description" list="true"}
			{if $doiSuffix eq "pattern"}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="doiSuffixPattern" name="doiSuffix" value="pattern" label="plugins.pubIds.doi.manager.settings.doiSuffixPattern" checked=$checked}
		{/fbvFormSection}
		{fbvFormSection}
			<p class="pkp_help">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixPattern.example"}</p>
			{fbvElement type="text" id="doiIssueSuffixPattern" value=$doiIssueSuffixPattern label="plugins.pubIds.doi.manager.settings.doiSuffixPattern.issues" maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" id="doiArticleSuffixPattern" value=$doiArticleSuffixPattern label="plugins.pubIds.doi.manager.settings.doiSuffixPattern.articles" maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" id="doiArticleGalleySuffixPattern" value=$doiArticleGalleySuffixPattern label="plugins.pubIds.doi.manager.settings.doiSuffixPattern.galleys" maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{if !in_array($doiSuffix, array("pattern", "customId"))}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="doiSuffixDefault" name="doiSuffix" value="default" required="true" label="plugins.pubIds.doi.manager.settings.doiSuffixDefault" checked=$checked}
			<blockquote><span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixDefault.description"}</span></blockquote>
			{if $doiSuffix eq "customId"}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="doiSuffixCustomId" name="doiSuffix" value="customId" required="true" label="plugins.pubIds.doi.manager.settings.doiSuffixCustomIdentifier" checked=$checked}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="doiReassignFormArea" title="plugins.pubIds.doi.manager.settings.doiReassign"}
		{fbvFormSection}
			<span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiReassign.description"}</span><br/>
			{include file="linkAction/linkAction.tpl" action=$clearPubIdsLinkAction contextId="doiSettingsForm"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
