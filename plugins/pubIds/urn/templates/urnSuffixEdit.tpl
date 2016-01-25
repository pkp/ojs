{**
 * plugins/pubIds/urn/templates/urnSuffixEdit.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit custom URN suffix for an object (issue, article, galley)
 *
 *}
<script src="{$baseUrl}/plugins/pubIds/urn/js/checkNumber.js"></script>
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#checkNo').pkpHandler('$.pkp.plugins.pubIds.urn.js.URNSettingsFormHandler');
	{rdelim});
</script>
{if $pubObject}
	{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
	{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
	{fbvFormArea id="pubIdURNFormArea" class="border" title="plugins.pubIds.urn.editor.urn"}
	{if !$excludeURN}
		{if $pubIdPlugin->getSetting($currentJournal->getId(), 'urnSuffix') == 'customId' || $storedPubId}
			{if empty($storedPubId)} {* edit custom suffix *}
					{fbvFormSection}
						<p class="pkp_help">{translate key="plugins.pubIds.urn.manager.settings.urnSuffix.description"}</p>
						{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnPrefix" id="urnPrefix" disabled=true value=$pubIdPlugin->getSetting($currentJournal->getId(), 'urnPrefix') size=$fbvStyles.size.SMALL}
						{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnSuffix" id="urnSuffix" value=$urnSuffix size=$fbvStyles.size.MEDIUM}
					{/fbvFormSection}
			{else} {* stored pub id and clear option *}
				<p>{$storedPubId|escape}</p>
				{fbvFormSection list="true"}
					{capture assign=translatedObjectType}{translate key="plugins.pubIds.urn.editor.urnObjectType"|cat:$pubObjectType}{/capture}
					{capture assign=clearCheckBoxLabel}{translate key="plugins.pubIds.urn.editor.urnReassign.description" pubObjectType=$translatedObjectType}{/capture}
					{fbvElement type="checkbox" id="clear_urn" name="clear_urn" value="1" label=$clearCheckBoxLabel translate=false}
				{/fbvFormSection}
			{/if}
		{else} {* pub id preview *}
			<p>{$pubIdPlugin->getPubId($pubObject, true)|escape}</p>
			{capture assign=translatedObjectType}{translate key="plugins.pubIds.urn.editor.urnObjectType"|cat:$pubObjectType}{/capture}
			<p class="pkp_help">{translate key="plugins.pubIds.urn.editor.urnNotYetGenerated" pubObjectType=$translatedObjectType}</p>
		{/if}
	{/if}

	{* exclude option *}
	{fbvFormSection list="true"}
		{if $excludeURN}
			{assign var="checked" value=true}
		{else}
			{assign var="checked" value=false}
		{/if}
		{capture assign=translatedObjectType}{translate key="plugins.pubIds.urn.editor.urnObjectType"|cat:$pubObjectType}{/capture}
		{capture assign=excludeCheckBoxLabel}{translate key="plugins.pubIds.urn.editor.excludePubObject" pubObjectType=$translatedObjectType}{/capture}
		{fbvElement type="checkbox" id="excludeURN" name="excludeURN" value="1" checked=$checked label=$excludeCheckBoxLabel translate=false}
	{/fbvFormSection}

	{* issue pub object *}
	{if $pubObjectType == 'Issue'}
		{assign var=enableArticleURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableArticleURN")}
		{assign var=enableArticleGalleyURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableArticleGalleyURN")}
		{if $enableArticleURN || $enableArticleGalleyURN}
			{fbvFormSection list="true" description="plugins.pubIds.urn.editor.excludeIssueObjectsURN.description"}
				{fbvElement type="checkbox" id="excludeIssueObjects_urn" name="excludeIssueObjects_urn" value="1" label="plugins.pubIds.urn.editor.excludeIssueObjectsURN"}
			{/fbvFormSection}
			{fbvFormSection list="true" description="plugins.pubIds.urn.editor.clearIssueObjectsURN.description"}
				{fbvElement type="checkbox" id="clearIssueObjects_urn" name="clearIssueObjects_urn" value="1" label="plugins.pubIds.urn.editor.clearIssueObjectsURN"}
			{/fbvFormSection}
		{/if}
	{/if}
	{/fbvFormArea}
{/if}
