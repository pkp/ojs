{**
 * @file plugins/pubIds/doi/templates/doiSuffixEdit.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit DOI meta-data.
 *}

{if $pubObject}
	{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
	{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
	{fbvFormArea id="pubIdDOIFormArea" class="border" title="plugins.pubIds.doi.editor.doi"}
	{if !$excludeDoi}
		{if $pubIdPlugin->getSetting($currentJournal->getId(), 'doiSuffix') == 'customId' || $storedPubId}
			{if empty($storedPubId)} {* edit custom suffix *}
					{fbvFormSection}
						<p class="pkp_help">{translate key="plugins.pubIds.doi.manager.settings.doiSuffix.description"}</p>
						{fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiPrefix" id="doiPrefix" disabled=true value=$pubIdPlugin->getSetting($currentJournal->getId(), 'doiPrefix') size=$fbvStyles.size.SMALL}
						{fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiSuffix" id="doiSuffix" value=$doiSuffix size=$fbvStyles.size.MEDIUM}
					{/fbvFormSection}
			{else} {* stored pub id and clear option *}
				<p>{$storedPubId|escape}</p>
				{fbvFormSection list="true"}
					{capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
					{capture assign=clearCheckBoxLabel}{translate key="plugins.pubIds.doi.editor.doiReassign.description" pubObjectType=$translatedObjectType}{/capture}
					{fbvElement type="checkbox" id="clear_doi" name="clear_doi" value="1" label=$clearCheckBoxLabel translate=false}
				{/fbvFormSection}
			{/if}
		{else} {* pub id preview *}
			<p>{$pubIdPlugin->getPubId($pubObject, true)|escape}</p>
			{capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
			<p class="pkp_help">{translate key="plugins.pubIds.doi.editor.doiNotYetGenerated" pubObjectType=$translatedObjectType}</p>
		{/if}
	{/if}

	{* exclude option *}
	{fbvFormSection list="true"}
		{if $excludeDoi}
			{assign var="checked" value=true}
		{else}
			{assign var="checked" value=false}
		{/if}
		{capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
		{capture assign=excludeCheckBoxLabel}{translate key="plugins.pubIds.doi.editor.excludePubObject" pubObjectType=$translatedObjectType}{/capture}
		{fbvElement type="checkbox" id="excludeDoi" name="excludeDoi" value="1" checked=$checked label=$excludeCheckBoxLabel translate=false}
	{/fbvFormSection}

	{* issue pub object *}
	{if $pubObjectType == 'Issue'}
		{assign var=enableArticleDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableArticleDoi")}
		{assign var=enableArticleGalleyDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableArticleGalleyDoi")}
		{if $enableArticleDoi || $enableArticleGalleyDoi}
			{fbvFormSection list="true" description="plugins.pubIds.doi.editor.excludeIssueObjectsDoi.description"}
				{fbvElement type="checkbox" id="excludeIssueObjects_doi" name="excludeIssueObjects_doi" value="1" label="plugins.pubIds.doi.editor.excludeIssueObjectsDoi"}
			{/fbvFormSection}
			{fbvFormSection list="true" description="plugins.pubIds.doi.editor.clearIssueObjectsDoi.description"}
				{fbvElement type="checkbox" id="clearIssueObjects_doi" name="clearIssueObjects_doi" value="1" label="plugins.pubIds.doi.editor.clearIssueObjectsDoi"}
			{/fbvFormSection}
		{/if}
	{/if}
	{/fbvFormArea}
{/if}
