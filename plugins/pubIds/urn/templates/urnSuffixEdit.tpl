{**
 * plugins/pubIds/urn/templates/urnSuffixEdit.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit custom URN suffix for an object (issue, article, galley, supp file)
 *
 *}
<script src="{$urnSettingsHandlerJsUrl}"></script>
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#checkNo').pkpHandler('$.pkp.plugins.pubIds.urn.js.URNSettingsFormHandler');
	{rdelim});
</script>
{if $pubObject}
	{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
	{assign var=enableObjectURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enable`$pubObjectType`URN")}
	{if $enableObjectURN}
		{fbvFormArea id="urnSuffix" title="plugins.pubIds.urn.metadata" class="border"}
			{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
			{assign var=urnSuffixMethod value=$pubIdPlugin->getSetting($currentJournal->getId(), 'urnSuffix')}
			{if $urnSuffixMethod == 'customIdentifier' && !$storedPubId}
				{assign var=urnPrefix value=$pubIdPlugin->getSetting($currentJournal->getId(), 'urnPrefix')}
				{assign var=checkNo value=$pubIdPlugin->getSetting($currentJournal->getId(), 'checkNo')}

					{fbvFormSection inline=true}{$urnPrefix|escape}{/fbvFormSection}
					{fbvElement type="text" label="plugins.pubIds.urn.urnSuffix" name="doiSuffix" id="urnSuffix" value=$urnSuffix maxlength="20" size=$fbvStyles.size.SMALL inline=true readonly=$readOnly}
					{if $checkNo}{fbvElement type="button" label="plugins.pubIds.urn.calculateCheckNo" name="checkNo" id="checkNo" inline=true}<script src="{$baseUrl}/plugins/pubIds/urn/js/checkNumber.js"></script>{/if}
			{elseif $storedPubId}
				{$storedPubId|escape}
			{else}
				{$pubIdPlugin->getPubId($pubObject, true)|escape}<br />
				<br />
				{capture assign=translatedObjectType}{translate key="plugins.pubIds.urn.editor.urnObjectType"|cat:$pubObjectType}{/capture}
				{translate key="plugins.pubIds.urn.editor.urnNotYetGenerated" pubObjectType=$translatedObjectType}
			{/if}
		{/fbvFormArea}
	{/if}
{/if}
