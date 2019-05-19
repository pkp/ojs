{**
 * @file plugins/pubIds/doi/templates/doiAssign.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Assign DOI to an object option.
 *}

{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
{assign var=enableObjectDoi value=$pubIdPlugin->getSetting($currentContext->getId(), "enable`$pubObjectType`Doi")}
{if $enableObjectDoi}
	{fbvFormArea id="pubIdDOIFormArea" class="border" title="plugins.pubIds.doi.editor.doi"}
	{if $pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
		{fbvFormSection}
			<p class="pkp_help">{translate key="plugins.pubIds.doi.editor.assignDoi.assigned" pubId=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}</p>
		{/fbvFormSection}
	{else}
		{assign var=pubId value=$pubIdPlugin->getPubId($pubObject)}
		{if !$canBeAssigned}
			{fbvFormSection}
				{if !$pubId}
					<p class="pkp_help">{translate key="plugins.pubIds.doi.editor.assignDoi.emptySuffix"}</p>
				{else}
					<p class="pkp_help">{translate key="plugins.pubIds.doi.editor.assignDoi.pattern" pubId=$pubId}</p>
				{/if}
			{/fbvFormSection}
		{else}
			{assign var=templatePath value=$pubIdPlugin->getTemplateResource('doiAssignCheckBox.tpl')}
			{include file=$templatePath pubId=$pubId pubObjectType=$pubObjectType}
		{/if}
	{/if}
	{/fbvFormArea}
{/if}
