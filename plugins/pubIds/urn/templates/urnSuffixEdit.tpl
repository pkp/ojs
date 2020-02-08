{**
 * plugins/pubIds/urn/templates/urnSuffixEdit.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Edit custom URN suffix for an object (issue, submission, file)
 *
 *}
{load_script context="publicIdentifiersForm" scripts=$scripts}

{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
{assign var=enableObjectURN value=$pubIdPlugin->getSetting($currentContext->getId(), "enable`$pubObjectType`URN")}
{if $enableObjectURN}
	{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
	{fbvFormArea id="pubIdURNFormArea" class="border" title="plugins.pubIds.urn.editor.urn"}
		{assign var=formArea value=true}
		{if $pubIdPlugin->getSetting($currentJournal->getId(), 'urnSuffix') == 'customId' || $storedPubId}
			{if empty($storedPubId)} {* edit custom suffix *}
				{fbvFormSection}
					{assign var=checkNo value=$pubIdPlugin->getSetting($currentContext->getId(), 'urnCheckNo')}
					<p class="pkp_help">{translate key="plugins.pubIds.urn.manager.settings.urnSuffix.description"}</p>
					{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnPrefix" id="urnPrefix" disabled=true value=$pubIdPlugin->getSetting($currentContext->getId(), 'urnPrefix') size=$fbvStyles.size.SMALL inline=true }
					{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnSuffix" id="urnSuffix" value=$urnSuffix size=$fbvStyles.size.MEDIUM inline=true }
					{if $checkNo}{fbvElement type="button" label="plugins.pubIds.urn.editor.addCheckNo" id="checkNo" inline=true}{/if}
				{/fbvFormSection}
				{if $canBeAssigned}
					<p class="pkp_help">{translate key="plugins.pubIds.urn.editor.canBeAssigned"}</p>
					{assign var=templatePath value=$pubIdPlugin->getTemplateResource('urnAssignCheckBox.tpl')}
					{include file=$templatePath pubId="" pubObjectType=$pubObjectType}
				{else}
					<p class="pkp_help">{translate key="plugins.pubIds.urn.editor.customSuffixMissing"}</p>
				{/if}
			{else} {* stored pub id and clear option *}
				<p>
					{$storedPubId|escape}<br />
					{capture assign=translatedObjectType}{translate key="plugins.pubIds.urn.editor.urnObjectType"|cat:$pubObjectType}{/capture}
					{capture assign=assignedMessage}{translate key="plugins.pubIds.urn.editor.assigned" pubObjectType=$translatedObjectType}{/capture}
					<p class="pkp_help">{$assignedMessage}</p>
					{include file="linkAction/linkAction.tpl" action=$clearPubIdLinkActionURN contextId="publicIdentifiersForm"}
				</p>
			{/if}
		{else} {* pub id preview *}
			<p>{$pubIdPlugin->getPubId($pubObject)|escape}</p>
			{if $canBeAssigned}
				<p class="pkp_help">{translate key="plugins.pubIds.urn.editor.canBeAssigned"}</p>
				{assign var=templatePath value=$pubIdPlugin->getTemplateResource('urnAssignCheckBox.tpl')}
				{include file=$templatePath pubId="" pubObjectType=$pubObjectType}
			{else}
				<p class="pkp_help">{translate key="plugins.pubIds.urn.editor.patternNotResolved"}</p>
			{/if}
		{/if}
	{/fbvFormArea}
{/if}
{* issue pub object *}
{if $pubObjectType == 'Issue'}
	{assign var=enablePublicationURN value=$pubIdPlugin->getSetting($currentContext->getId(), "enablePublicationURN")}
	{assign var=enableRepresentationURN value=$pubIdPlugin->getSetting($currentContext->getId(), "enableRepresentationURN")}
	{if $enablePublicationURN || $enableRepresentationURN}
		{if !$formArea}
			{assign var="formAreaTitle" value="plugins.pubIds.urn.editor.urn"}
		{else}
			{assign var="formAreaTitle" value=""}
		{/if}
		{fbvFormArea id="pubIdURNIssueobjectsFormArea" class="border" title=$formAreaTitle}
			{fbvFormSection list="true" description="plugins.pubIds.urn.editor.clearIssueObjectsURN.description"}
				{include file="linkAction/linkAction.tpl" action=$clearIssueObjectsPubIdsLinkActionURN contextId="publicIdentifiersForm"}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}
{/if}
