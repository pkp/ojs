{**
 * @file plugins/pubIds/doi/templates/doiSuffixEdit.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit custom DOI suffix for an object (issue, submission, galley)
 *}

{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
{assign var=enableObjectDoi value=$pubIdPlugin->getSetting($currentContext->getId(), "enable`$pubObjectType`Doi")}
{if $enableObjectDoi}
	{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
	{fbvFormArea id="pubIdDOIFormArea" class="border" title="plugins.pubIds.doi.editor.doi"}
		{assign var=formArea value=true}
		{if $pubIdPlugin->getSetting($currentContext->getId(), 'doiSuffix') == 'customId' || $storedPubId}
			{if empty($storedPubId)} {* edit custom suffix *}
				{fbvFormSection}
					<p class="pkp_help">{translate key="plugins.pubIds.doi.manager.settings.doiSuffix.description"}</p>
					{fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiPrefix" id="doiPrefix" disabled=true value=$pubIdPlugin->getSetting($currentContext->getId(), 'doiPrefix') size=$fbvStyles.size.SMALL}
					{fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiSuffix" id="doiSuffix" value=$doiSuffix size=$fbvStyles.size.MEDIUM}
				{/fbvFormSection}
				{if $canBeAssigned}
					{assign var=templatePath value=$pubIdPlugin->getTemplateResource('doiAssignCheckBox.tpl')}
					{include file=$templatePath pubId="" pubObjectType=$pubObjectType}
				{else}
					<p class="pkp_help">{translate key="plugins.pubIds.doi.editor.customSuffixMissing"}</p>
				{/if}
			{else} {* stored pub id and clear option *}
			{fbvFormSection}
				<p>
					{$storedPubId|escape}<br />
					{capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
					{capture assign=assignedMessage}{translate key="plugins.pubIds.doi.editor.assigned" pubObjectType=$translatedObjectType}{/capture}
					<p class="pkp_help">{$assignedMessage}</p>
					{include file="linkAction/linkAction.tpl" action=$clearPubIdLinkActionDoi contextId="publicIdentifiersForm"}
				</p>
				{/fbvFormSection}
			{/if}
		{else} {* pub id preview *}
			<p>{$pubIdPlugin->getPubId($pubObject)|escape}</p>
			{if $canBeAssigned}
				<p class="pkp_help">{translate key="plugins.pubIds.doi.editor.canBeAssigned"}</p>
				{assign var=templatePath value=$pubIdPlugin->getTemplateResource('doiAssignCheckBox.tpl')}
				{include file=$templatePath pubId="" pubObjectType=$pubObjectType}
			{else}
				<p class="pkp_help">{translate key="plugins.pubIds.doi.editor.patternNotResolved"}</p>
			{/if}
		{/if}
	{/fbvFormArea}
{/if}
{* issue pub object *}
{if $pubObjectType == 'Issue'}
	{assign var=enableSubmissionDoi value=$pubIdPlugin->getSetting($currentContext->getId(), "enableSubmissionDoi")}
	{assign var=enableRepresentationDoi value=$pubIdPlugin->getSetting($currentContext->getId(), "enableRepresentationDoi")}
	{if $enableSubmissionDoi || $enableRepresentationDoi}
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