{**
 * plugins/pubIds/urn/templates/urnSuffixEdit.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit custom URN suffix for an object (issue, article, galley, supp file)
 *
 *}

{if $pubObject}
{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
{assign var=enableObjectURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enable`$pubObjectType`URN")}
{if $enableObjectURN}
	<!-- URN -->
	<div id="pub-id::other::urn">
	<h3>{translate key="plugins.pubIds.urn.metadata"}</h3>

	{assign var=urnSuffixMethod value=$pubIdPlugin->getSetting($currentJournal->getId(), 'urnSuffix')}
	{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}

	{if $urnSuffixMethod == 'customIdentifier' && !$storedPubId}
		{assign var=urnPrefix value=$pubIdPlugin->getSetting($currentJournal->getId(), 'urnPrefix')}
		{assign var=checkNo value=$pubIdPlugin->getSetting($currentJournal->getId(), 'checkNo')}
		<table width="100%" class="data">
		<tr valign="top">
			<td rowspan="2" width="10%" class="label">{fieldLabel name="urnSuffix" key="plugins.pubIds.urn.urnSuffix"}</td>
			<td rowspan="2" width="10%" align="right">{$urnPrefix|escape}</td>
			<td width="80%" class="value"><input type="text" class="textField" name="urnSuffix" id="urnSuffix" value="{$urnSuffix|escape}" size="20" maxlength="20" />
			{if $checkNo}<input type="button" name="checkNo" value="{translate key="plugins.pubIds.urn.calculateCheckNo"}" class="button" onClick="javascript:calculateCheckNo('{$urnPrefix|escape}')"><script src="{$baseUrl}/plugins/pubIds/urn/js/checkNumber.js" type="text/javascript"></script>{/if}</td>
		</tr>
		<tr valign="top">
			<td colspan="3"><span class="instruct">{translate key="plugins.pubIds.urn.urnSuffix.description"}</span></td>
		</tr>
		</table>
		</div>
	{elseif $storedPubId}
		{$storedPubId|escape}
	{else}
		{$pubIdPlugin->getPubId($pubObject, true)|escape}<br />
			<br />
			{capture assign=translatedObjectType}{translate key="plugins.pubIds.urn.editor.urnObjectType"|cat:$pubObjectType}{/capture}
			{translate key="plugins.pubIds.urn.editor.urnNotYetGenerated" pubObjectType=$translatedObjectType}
	{/if}
	<div class="separator"></div>
	<!-- /URN -->
{/if}
{/if}
