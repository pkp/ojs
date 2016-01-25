{**
 * plugins/pubIds/urn/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * URN plugin settings
 *
 *}

<div id="description">{translate key="plugins.pubIds.urn.manager.settings.description"}</div>

<script src="{$baseUrl}/plugins/pubIds/urn/js/URNSettingsFormHandler.js"></script>
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#urnSettingsForm').pkpHandler('$.pkp.plugins.pubIds.urn.js.URNSettingsFormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="urnSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="pubIds" plugin=$pluginName verb="settings" save="true"}">
	{include file="common/formErrors.tpl"}
	{fbvFormArea id="urnObjectsFormArea" title="plugins.pubIds.urn.manager.settings.urnObjects"}
		{fbvFormSection list="true" description="plugins.pubIds.urn.manager.settings.explainURNs"}
			{if $enableIssueURN}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" label="plugins.pubIds.urn.manager.settings.enableIssueURN" id="enableIssueURN" maxlength="40" checked=$checked}
			{if $enableArticleURN}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" label="plugins.pubIds.urn.manager.settings.enableArticleURN" id="enableArticleURN" maxlength="40" checked=$checked}
			{if $enableArticleGalleyURN}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" label="plugins.pubIds.urn.manager.settings.enableGalleyURN" id="enableArticleGalleyURN" maxlength="40" checked=$checked}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="urnPrefixFormArea" title="plugins.pubIds.urn.manager.settings.urnPrefix"}
		{fbvFormSection description="plugins.pubIds.urn.manager.settings.urnPrefix.description"}
			{fbvElement type="text" id="urnPrefix" value=$urnPrefix required="true" label="plugins.pubIds.urn.manager.settings.urnPrefix" maxlength="40" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="urnSuffixFormArea" title="plugins.pubIds.urn.manager.settings.urnSuffix"}
		{fbvFormSection list="true" label="plugins.pubIds.urn.manager.settings.urnSuffix.description"}
			{if $urnSuffix eq "pattern"}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="urnSuffixPattern" name="urnSuffix" value="pattern" label="plugins.pubIds.urn.manager.settings.urnSuffixPattern" checked=$checked}
		{/fbvFormSection}
		{fbvFormSection}
			<p class="pkp_help">{translate key="plugins.pubIds.urn.manager.settings.urnSuffixPattern.example"}</p>
			{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnSuffixPattern.issues" id="urnIssueSuffixPattern" value=$urnIssueSuffixPattern maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnSuffixPattern.articles" id="urnArticleSuffixPattern" value=$urnArticleSuffixPattern maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnSuffixPattern.galleys" id="urnArticleGalleySuffixPattern" value=$urnArticleGalleySuffixPattern maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{if !in_array($urnSuffix, array("pattern", "customId"))}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="urnSuffixDefault" name="urnSuffix" value="default" label="plugins.pubIds.urn.manager.settings.urnSuffixDefault" checked=$checked}
			<blockquote><span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.urnSuffixDefault.description"}</span></blockquote>
			{if $urnSuffix eq "customId"}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="urnSuffixCustomId" name="urnSuffix" value="customId" label="plugins.pubIds.urn.manager.settings.urnSuffixCustomIdentifier" checked=$checked}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="urnCheckNoFormArea" title="plugins.pubIds.urn.manager.settings.checkNo"}
		{fbvFormSection list="true" }
			{if $urnCheckNo}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" id="urnCheckNo" name="urnCheckNo" label="plugins.pubIds.urn.manager.settings.checkNo.label" checked=$checked}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="urnNamespaceFormArea" title="plugins.pubIds.urn.manager.settings.namespace"}
		{fbvFormSection description="plugins.pubIds.urn.manager.settings.namespace.description"}
			{fbvElement type="select" id="urnNamespace" required="true" from=$urnNamespaces selected=$urnNamespace translate=false size=$fbvStyles.size.MEDIUM label="plugins.pubIds.urn.manager.settings.namespace"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="urnResolverFormArea" title="plugins.pubIds.urn.manager.settings.urnResolver"}
		{fbvFormSection description="plugins.pubIds.urn.manager.settings.urnResolver.description"}
			{fbvElement type="text" id="urnResolver" value=$urnResolver required="true" label="plugins.pubIds.urn.manager.settings.urnResolver"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="urnReassignFormArea" title="plugins.pubIds.urn.manager.settings.urnReassign"}
		{fbvFormSection}
			<span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.urnReassign.description"}</span><br/>
			{include file="linkAction/linkAction.tpl" action=$clearPubIdsLinkAction contextId="urnSettingsForm"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>