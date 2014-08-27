{**
 * @file plugins/pubIds/doi/templates/doiSuffixEdit.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit DOI meta-data.
 *}

{if $pubObject}
	{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
	{assign var=enableObjectDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enable`$pubObjectType`Doi")}
	{if $enableObjectDoi}
		{fbvFormArea id="doiSuffix" title="plugins.pubIds.doi.editor.doi" class="border"}
			{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
			{if $pubIdPlugin->getSetting($currentJournal->getId(), 'doiSuffix') == 'customId' || $storedPubId}
				{if empty($storedPubId)}
					{fbvFormSection inline=true}{$pubIdPlugin->getSetting($currentJournal->getId(), 'doiPrefix')}/{/fbvFormSection}
					{fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiSuffix" name="doiSuffix" id="doiSuffix" value=$doiSuffix maxlength="20" size=$fbvStyles.size.SMALL inline=true readonly=$readOnly}
					{fbvFormSection description="plugins.pubIds.doi.manager.settings.doiSuffixDescription"}{/fbvFormSection}

				{else}
					{$storedPubId|escape}
				{/if}
			{else}
				{$pubIdPlugin->getPubId($pubObject, true)|escape} <br />
				<br />
				{capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
				{translate key="plugins.pubIds.doi.editor.doiNotYetGenerated" pubObjectType=$translatedObjectType}
			{/if}
		{/fbvFormArea}
	{/if}
{/if}
