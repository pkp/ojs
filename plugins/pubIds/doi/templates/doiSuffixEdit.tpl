{**
 * @file plugins/pubIds/doi/templates/doiSuffixEdit.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit DOI meta-data.
 *}

{if $pubObject}
{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
{assign var=enableObjectDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enable`$pubObjectType`Doi")}
{if $enableObjectDoi}
	<div id="pub-id::doi">
		<h3>{translate key="plugins.pubIds.doi.editor.doi"}</h3>
		{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
		{if $pubIdPlugin->getSetting($currentJournal->getId(), 'doiSuffix') == 'customId' || $storedPubId}
			{if empty($storedPubId)}
				<table class="data">
					<tr>
						<td rowspan="2" width="10%" class="label">{fieldLabel name="doiSuffix" key="plugins.pubIds.doi.manager.settings.doiSuffix"}</td>
						<td rowspan="2" width="10%" align="right">{$pubIdPlugin->getSetting($currentJournal->getId(), 'doiPrefix')|escape}/</td>
						<td class="value"><input type="text" class="textField" name="doiSuffix" id="doiSuffix" value="{$doiSuffix|escape}" size="20" maxlength="20" />
					</tr>
					<tr>
						<td colspan="3"><span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixDescription"}</span></td>
					</tr>
				</table>
			{else}
				{$storedPubId|escape}
			{/if}
		{else}
			{$pubIdPlugin->getPubId($pubObject, true)|escape} <br />
			<br />
			{capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
			{translate key="plugins.pubIds.doi.editor.doiNotYetGenerated" pubObjectType=$translatedObjectType}
		{/if}
		<br />
	</div>
	<div class="separator"> </div>
{/if}
{/if}
