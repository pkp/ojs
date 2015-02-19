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

<script src="{$doiSettingsHandlerJsUrl}"></script>

<div id="description">{translate key="plugins.pubIds.doi.manager.settings.description"}</div>

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#doiSettingsForm').pkpHandler('$.pkp.plugins.pubIds.doi.js.DOISettingsFormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="doiSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="plugin" category="pubIds" plugin=$pluginName verb="settings" save="true"}">
	{include file="common/formErrors.tpl"}
	{fbvFormArea id="doiObjectsFormArea" title="plugins.pubIds.doi.manager.settings.doiObjects"}
		{fbvFormSection list="true" description="plugins.pubIds.doi.manager.settings.explainDois"}
			{if $enableIssueDoi}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" label="plugins.pubIds.doi.manager.settings.enableIssueDoi" id="enableIssueDoi" checked=$checked maxlength="40" checked=$checked}
			{if $enableArticleDoi}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" label="plugins.pubIds.doi.manager.settings.enableArticleDoi" id="enableArticleDoi" checked=$checked maxlength="40" checked=$checked}
			{if $enableGalleyDoi}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" label="plugins.pubIds.doi.manager.settings.enableGalleyDoi" id="enableGalleyDoi" checked=$checked maxlength="40" checked=$checked}
		{/fbvFormSection}
	{/fbvFormArea}
		<table class="data">
	{fbvFormArea id="enableDoiSettingsFormArea" class="border" title="plugins.pubIds.doi.manager.settings.doiSettings"}
		{fbvFormSection}
			<p class="pkp_help">{translate key="plugins.pubIds.doi.manager.settings.doiPrefixPattern"}</p>
			{fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiPrefix" required="true" id="doiPrefix" value=$doiPrefix maxlength="40" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="doiSuffixPatternFormArea" class="border" title="plugins.pubIds.doi.manager.settings.doiSuffix"}
		{fbvFormSection label="plugins.pubIds.doi.manager.settings.doiSuffixDescription" list="true"}
			{if $doiSuffix eq "pattern"}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="doiSuffix" name="doiSuffix" value="pattern" checked=$checked label="plugins.pubIds.doi.manager.settings.doiSuffixPattern"}
		{/fbvFormSection}
		{fbvFormSection}
			<p class="pkp_help">{fieldLabel name="doiSuffixPattern" key="plugins.pubIds.doi.manager.settings.doiSuffixPattern.example"}</p>
			{fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiSuffixPattern.issues" id="doiIssueSuffixPattern" value=$doiIssueSuffixPattern maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiSuffixPattern.articles" id="doiArticleSuffixPattern" value=$doiArticleSuffixPattern maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiSuffixPattern.galleys" id="doiGalleySuffixPattern" value=$doiGalleySuffixPattern maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{if !in_array($doiSuffix, array("pattern", "publisherId", "customId"))}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="doiSuffixDefault" name="doiSuffix" required="true" value="default" checked=$checked label="plugins.pubIds.doi.manager.settings.doiSuffixDefault"}
			<br /><blockquote><span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixDefault.description"}</span></blockquote>
			{if $doiSuffix eq "publisherId"}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="doiSuffixPublisherId" name="doiSuffix" required="true" value="publisherId" checked=$checked label="plugins.pubIds.doi.manager.settings.doiSuffixPublisherId"}

			{if $doiSuffix eq "customId"}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="doiSuffixCustomIdentifier" name="doiSuffix" required="true" value="customId" checked=$checked label="plugins.pubIds.doi.manager.settings.doiSuffixCustomIdentifier"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="doiSuffixReassignFormArea" class="border" title="plugins.pubIds.doi.manager.settings.doiReassign"}
		{fbvFormSection}
			<span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiReassign.description"}</span><br/>
			{include file="linkAction/linkAction.tpl" action=$clearPubIdsLinkAction contextId="doiSettingsForm"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
