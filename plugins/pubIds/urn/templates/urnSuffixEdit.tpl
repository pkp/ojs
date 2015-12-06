{**
 * plugins/pubIds/urn/templates/urnSuffixEdit.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit custom URN suffix for an object (issue, article, file)
 *
 *}
<script src="{$baseUrl}/plugins/pubIds/urn/js/checkNumber.js"></script>

{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
{assign var=enableObjectURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enable`$pubObjectType`URN")}
{if $enableObjectURN}
	{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
	{fbvFormArea id="pubIdURNFormArea" class="border" title="plugins.pubIds.urn.editor.urn"}
		{assign var=formArea value=true}
		{if $pubIdPlugin->getSetting($currentJournal->getId(), 'urnSuffix') == 'customId' || $storedPubId}
			{if empty($storedPubId)} {* edit custom suffix *}
				{fbvFormSection}
					{assign var=checkNo value=$pubIdPlugin->getSetting($currentJournal->getId(), 'urnCheckNo')}
					<p class="pkp_help">{translate key="plugins.pubIds.urn.manager.settings.urnSuffix.description"}</p>
					{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnPrefix" id="urnPrefix" disabled=true value=$pubIdPlugin->getSetting($currentJournal->getId(), 'urnPrefix') size=$fbvStyles.size.SMALL inline=true }
					{fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnSuffix" id="urnSuffix" value=$urnSuffix size=$fbvStyles.size.MEDIUM inline=true }
					{if $checkNo}{fbvElement type="button" label="plugins.pubIds.urn.editor.addCheckNo" id="checkNo" inline=true}{/if}
				{/fbvFormSection}
				{if $canBeAssigned}
					{assign var=templatePath value=$pubIdPlugin->getTemplatePath()}
					{include file="`$templatePath`urnAssignCheckBox.tpl" pubId="" pubObjectType=$pubObjectType}
				{/if}
			{else} {* stored pub id and clear option *}
				<p>
					{$storedPubId|escape}<br />
					{include file="linkAction/linkAction.tpl" action=$clearPubIdLinkActionURN contextId="publicIdentifiersForm"}
				</p>
			{/if}
		{else} {* pub id preview *}
			<p>{$pubIdPlugin->getPubId($pubObject)|escape}</p>
			{if $canBeAssigned}
				{assign var=templatePath value=$pubIdPlugin->getTemplatePath()}
				{include file="`$templatePath`urnAssignCheckBox.tpl" pubId="" pubObjectType=$pubObjectType}
			{else}
				<p class="pkp_help">{translate key="plugins.pubIds.urn.editor.urnNotYetGenerated"}</p>
			{/if}
		{/if}
	{/fbvFormArea}
{/if}
{* issue pub object *}
{if $pubObjectType == 'Issue'}
	{assign var=enableArticleURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableArticleURN")}
	{assign var=enableRepresentationURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableRepresentationURN")}
	{assign var=enableSubmissionFileURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableSubmissionFileURN")}
	{if $enableArticleURN || $enableRepresentationURN || $enableSubmissionFileURN}
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
