{**
 * plugins/pubIds/doi/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
<form class="pkp_form" id="doiSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="pubIds" plugin=$pluginName verb="save"}">
	{csrf}
	{include file="common/formErrors.tpl"}
	{fbvFormArea id="doiObjectsFormArea" title="plugins.pubIds.doi.manager.settings.doiObjects"}
		{fbvFormSection list="true"}
			<p class="pkp_help">{translate key="plugins.pubIds.doi.manager.settings.explainDois"}</p>
			{fbvElement type="checkbox" id="enablePublicationDoi" label="plugins.pubIds.doi.manager.settings.enablePublicationDoi" maxlength="40" checked=$enablePublicationDoi|compare:true}
			{fbvElement type="checkbox" id="enableRepresentationDoi" label="plugins.pubIds.doi.manager.settings.enableRepresentationDoi" maxlength="40" checked=$enableRepresentationDoi|compare:true}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="doiPrefixFormArea" title="plugins.pubIds.doi.manager.settings.doiPrefix"}
		{fbvFormSection}
			<p class="pkp_help">{translate key="plugins.pubIds.doi.manager.settings.doiPrefix.description"}</p>
			{fbvElement type="text" id="doiPrefix" value=$doiPrefix required="true" label="plugins.pubIds.doi.manager.settings.doiPrefix" maxlength="40" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="doiSuffixFormArea" title="plugins.pubIds.doi.manager.settings.doiSuffix"}
		<p class="pkp_help">{translate key="plugins.pubIds.doi.manager.settings.doiSuffix.description"}</p>
		{fbvFormSection list="true"}
			{if !in_array($doiSuffix, array("pattern", "customId"))}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="radio" id="doiSuffixDefault" name="doiSuffix" value="default" required="true" label="plugins.pubIds.doi.manager.settings.doiSuffixDefault" checked=$checked}
			<span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixDefault.description"}</span>
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{fbvElement type="radio" id="doiSuffixCustomId" name="doiSuffix" value="customId" required="true" label="plugins.pubIds.doi.manager.settings.doiSuffixCustomIdentifier" checked=$doiSuffix|compare:"customId"}
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{fbvElement type="radio" id="doiSuffixPattern" name="doiSuffix" value="pattern" label="plugins.pubIds.doi.manager.settings.doiSuffixPattern" checked=$doiSuffix|compare:"pattern"}
			<p class="pkp_help">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixPattern.example"}</p>
			{fbvElement type="text" id="doiPublicationSuffixPattern" value=$doiPublicationSuffixPattern label="plugins.pubIds.doi.manager.settings.doiSuffixPattern.submissions" maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" id="doiRepresentationSuffixPattern" value=$doiRepresentationSuffixPattern label="plugins.pubIds.doi.manager.settings.doiSuffixPattern.representations" maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="doiReassignFormArea" title="plugins.pubIds.doi.manager.settings.doiReassign"}
		{fbvFormSection}
			<div class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiReassign.description"}</div>
			{include file="linkAction/linkAction.tpl" action=$clearPubIdsLinkAction contextId="doiSettingsForm"}
		{/fbvFormSection}
	{/fbvFormArea}
	{if ($enablePublicationDoi || $enableRepresentationDoi) && $doiPrefix && $doiSuffix && $doiSuffix != 'customId' }
		{fbvFormArea id="doiAssignJournalWideFormArea" title="plugins.pubIds.doi.manager.settings.doiAssignJournalWide"}
			{fbvFormSection}
				<div class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiAssignJournalWide.description"}</div>
				{include file="linkAction/linkAction.tpl" action=$assignJournalWidePubIdsLinkAction contextId="doiSettingsForm"}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}
	{fbvFormButtons submitText="common.save"}
</form>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
