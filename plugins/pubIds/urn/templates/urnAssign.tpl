{**
 * @file plugins/pubIds/urn/templates/urnAssign.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Assign URN to an object option.
 *}

{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
{assign var=enableObjectURN value=$pubIdPlugin->getSetting($currentContext->getId(), "enable`$pubObjectType`URN")}
{if $enableObjectURN}
	{fbvFormArea id="pubIdURNFormArea" class="border" title="plugins.pubIds.urn.editor.urn"}
	{if $pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
		{fbvFormSection}
			<p class="pkp_help">{translate key="plugins.pubIds.urn.editor.assignURN.assigned" pubId=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}</p>
		{/fbvFormSection}
	{else}
		{assign var=pubId value=$pubIdPlugin->getPubId($pubObject)}
		{if !$canBeAssigned}
			{fbvFormSection}
				{if !$pubId}
					<p class="pkp_help">{translate key="plugins.pubIds.urn.editor.assignURN.emptySuffix"}</p>
				{else}
					<p class="pkp_help">{translate key="plugins.pubIds.urn.editor.assignURN.pattern" pubId=$pubId}</p>
				{/if}
			{/fbvFormSection}
		{else}
			{assign var=templatePath value=$pubIdPlugin->getTemplateResource('urnAssignCheckBox.tpl')}
			{include file=$templatePath pubId=$pubId pubObjectType=$pubObjectType}
		{/if}
	{/if}
	{/fbvFormArea}
{/if}
