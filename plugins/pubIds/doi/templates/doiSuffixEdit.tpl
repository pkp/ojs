{**
 * @file plugins/pubIds/doi/templates/doiSuffixEdit.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit DOI meta-data.
 *}

{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
{assign var=enableObjectDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enable`$pubObjectType`Doi")}
{if $enableObjectDoi}
	{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
	{fbvFormArea id="pubIdDOIFormArea" class="border" title="plugins.pubIds.doi.editor.doi"}
		{assign var=formArea value=true}
		{if $pubIdPlugin->getSetting($currentJournal->getId(), 'doiSuffix') == 'customId' || $storedPubId}
			{if empty($storedPubId)} {* edit custom suffix *}
				{fbvFormSection}
					<p class="pkp_help">{translate key="plugins.pubIds.doi.manager.settings.doiSuffix.description"}</p>
					{fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiPrefix" id="doiPrefix" disabled=true value=$pubIdPlugin->getSetting($currentJournal->getId(), 'doiPrefix') size=$fbvStyles.size.SMALL}
					{fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiSuffix" id="doiSuffix" value=$doiSuffix size=$fbvStyles.size.MEDIUM}
				{/fbvFormSection}
				{if $canBeAssigned}
					{assign var=templatePath value=$pubIdPlugin->getTemplatePath()}
					{include file="`$templatePath`doiAssignCheckBox.tpl" pubId="" pubObjectType=$pubObjectType}
				{/if}
			{else} {* stored pub id and clear option *}
				<p>
					{$storedPubId|escape}<br />
					{include file="linkAction/linkAction.tpl" action=$clearPubIdLinkActionDoi contextId="publicIdentifiersForm"}
				</p>
			{/if}
		{else} {* pub id preview *}
			<p>{$pubIdPlugin->getPubId($pubObject)|escape}</p>
			{if $canBeAssigned}
				{assign var=templatePath value=$pubIdPlugin->getTemplatePath()}
				{include file="`$templatePath`doiAssignCheckBox.tpl" pubId="" pubObjectType=$pubObjectType}
			{else}
				<p class="pkp_help">{translate key="plugins.pubIds.doi.editor.doiNotYetGenerated"}</p>
			{/if}
		{/if}
	{/fbvFormArea}
{/if}
{* issue pub object *}
{if $pubObjectType == 'Issue'}
	{assign var=enableArticleDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableArticleDoi")}
	{assign var=enableRepresentationDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableRepresentationDoi")}
	{if $enableArticleDoi || $enableRepresentationDoi}
		{if !$formArea}
			{assign var="formAreaTitle" value="plugins.pubIds.doi.editor.doi"}
		{else}
			{assign var="formAreaTitle" value=""}
		{/if}
		{fbvFormArea id="pubIdDOIFormArea" class="border" title=$formAreaTitle}
			{fbvFormSection list="true" description="plugins.pubIds.doi.editor.clearIssueObjectsDoi.description"}
				{include file="linkAction/linkAction.tpl" action=$clearIssueObjectsPubIdsLinkActionDoi contextId="publicIdentifiersForm"}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}
{/if}