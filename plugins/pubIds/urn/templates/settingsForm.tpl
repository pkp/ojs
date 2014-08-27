{**
 * plugins/pubIds/urn/templates/settingsForm.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * URN plugin settings
 *
 *}

<script src="{$urnSettingsHandlerJsUrl}"></script>
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#urnSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="urnSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="plugin" category="pubIds" plugin=$pluginName verb="settings" save="true"}">
	{include file="common/formErrors.tpl"}
	{fbvFormArea id="urnObjectsFormArea" title="plugins.pubIds.urn.manager.settings.journalContent"}
		{fbvFormSection list="true" description="plugins.pubIds.urn.manager.settings.URNsForJournalContent"}
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
			{if $enableGalleyURN}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" label="plugins.pubIds.urn.manager.settings.enableGalleyURN" id="enableGalleyURN" maxlength="40" checked=$checked}
			{if $enableSuppFileURN}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" label="plugins.pubIds.urn.manager.settings.enableSuppFileURN" id="enableSuppFileURN" maxlength="40" checked=$checked}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="urnPrefixFormArea" class="border" title="plugins.pubIds.urn.manager.settings.urnPrefix"}
		{fbvFormSection title="plugins.pubIds.urn.manager.settings.urnPrefix" for="urnPrefix" description="plugins.pubIds.urn.manager.settings.urnPrefix.description" required=true}
			{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnPrefix" id="urnPrefix" value=$urnPrefix maxlength="40" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="urnSuffixFormArea" class="border" title="plugins.pubIds.urn.manager.settings.urnSuffix"}
		{fbvFormSection list="true" description="plugins.pubIds.urn.manager.settings.urnSuffix.description"}
			{if $urnSuffix eq "pattern"}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="urnSuffixPattern" name="urnSuffix" value="pattern" checked=$checked label="plugins.pubIds.urn.manager.settings.urnSuffix.pattern"}
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnSuffix.pattern.issues" id="urnIssueSuffixPattern" value=$urnIssueSuffixPattern maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnSuffix.pattern.articles" id="urnArticleSuffixPattern" value=$urnArticleSuffixPattern maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnSuffix.pattern.galleys" id="urnGalleySuffixPattern" value=$urnGalleySuffixPattern maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnSuffix.pattern.suppFiles" id="urnSuppFileSuffixPattern" value=$urnSuppFileSuffixPattern maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{if !in_array($doiSuffix, array("pattern", "publisherId", "customIdentifier"))}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="urnSuffixDefault" name="urnSuffix" required="true" value="default" checked=$checked label="plugins.pubIds.urn.manager.settings.urnSuffix.default"}
			{if $doiSuffix eq "publisherId"}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="urnSuffixPublisherId" name="urnSuffix" required="true" value="publisherId" checked=$checked label="plugins.pubIds.urn.manager.settings.urnSuffix.publisherId"}

			{if $doiSuffix eq "customIdentifier"}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="urnSuffixCustomIdentifier" name="urnSuffix" required="true" value="customIdentifier" checked=$checked label="plugins.pubIds.urn.manager.settings.urnSuffix.customIdentifier"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="urnCheckNoFormArea" class="border" title="plugins.pubIds.urn.manager.settings.checkNo"}
		{fbvFormSection list="true"}
			{if $urnCheckNo}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" label="plugins.pubIds.urn.manager.settings.checkNo.label" id="urnCheckNo" checked=$checked}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="urnNamespaceFormArea" class="border" title="plugins.pubIds.urn.manager.settings.namespace"}
		{fbvFormSection description="plugins.pubIds.urn.manager.settings.namespace.description" required=true}
			{fbvElement type="select" id="urnNamespace" from=$urnNamespaces selected=$urnNamespace translate=false disabled=$readOnly size=$fbvStyles.size.MEDIUM required=true}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="urnResolverFormArea" class="border" title="plugins.pubIds.urn.manager.settings.urnResolver"}
		{fbvFormSection description="plugins.pubIds.urn.manager.settings.urnResolver.description"}
			{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnResolver" id="urnResolver" value=$urnResolver required=true}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="urnReassignFormArea" class="border" title="plugins.pubIds.doi.manager.settings.doiReassign"}
		{fbvFormSection}
			{include file="linkAction/linkAction.tpl" action=$clearPubIdsLinkAction contextId="urnSettingsForm"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

