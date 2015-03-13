{**
 * plugins/pubIds/urn/templates/urnSuffixEdit.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit custom URN suffix for an object (issue, article, galley, supp file)
 *}

{if $pubObject}
{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
{assign var=enableObjectURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enable`$pubObjectType`URN")}
{if $enableObjectURN}
	<script type="text/javascript">
		{literal}
		<!--
			function toggleURNClear() {
				if ($('#excludeURN').is(':checked')) {
					var $element = document.getElementById('other::urn');
					$element.setAttribute('checked', 'checked');
					$element.setAttribute('disabled', 'disabled');
				} else {
					var $element = document.getElementById('other::urn');
					$element.removeAttribute('disabled');
				}
			}
		// -->
		{/literal}
	</script>
	<!-- URN -->
	<div id="pub-id::other::urn">
		<h3>{translate key="plugins.pubIds.urn.metadata"}</h3>
		{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
		{if !$excludeURN}
			{assign var=urnSuffixMethod value=$pubIdPlugin->getSetting($currentJournal->getId(), 'urnSuffix')}
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
			{elseif $storedPubId}
				<p>{$storedPubId|escape}</p>
				<input type="checkbox" name="clear_{$pubIdPlugin->getPubIdType()|escape}" id="clear_{$pubIdPlugin->getPubIdType()|escape}" value="1" />
				{capture assign=translatedObjectType}{translate key="plugins.pubIds.urn.editor.urnObjectType"|cat:$pubObjectType}{/capture}
				{translate key="plugins.pubIds.urn.editor.urnClear.description" pubObjectType=$translatedObjectType}<br />
			{else}
				<p>{$pubIdPlugin->getPubId($pubObject, true)|escape}</p>
				{capture assign=translatedObjectType}{translate key="plugins.pubIds.urn.editor.urnObjectType"|cat:$pubObjectType}{/capture}
				{translate key="plugins.pubIds.urn.editor.urnNotYetGenerated" pubObjectType=$translatedObjectType}<br />
			{/if}
			<br />
		{/if}

		<input type="checkbox" name="excludeURN" id="excludeURN" value="1"{if $excludeURN} checked="checked"{/if} onClick="toggleURNClear()"  />
		{capture assign=translatedObjectType}{translate key="plugins.pubIds.urn.editor.urnObjectType"|cat:$pubObjectType}{/capture}
		{translate key="plugins.pubIds.urn.editor.excludePubObject" pubObjectType=$translatedObjectType}<br />

		{if $pubObjectType == 'Issue'}
			{assign var=enableArticleURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableArticleURN")}
			{assign var=enableGalleyURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableGalleyURN")}
			{assign var=enableSuppFileURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableSuppFileURN")}
			{if $enableArticleURN || $enableGalleyURN || $enableSuppFileURN}
				<br />
				<span class="instruct">{translate key="plugins.pubIds.urn.editor.excludeIssueObjectsURN.description"}</span><br/>
				<input type="submit" name="excludeIssueObjects_{$pubIdPlugin->getPubIdType()|escape}" value="{translate key="plugins.pubIds.urn.editor.excludeIssueObjectsURN"}" class="action" /><br />
				<br />
				<span class="instruct">{translate key="plugins.pubIds.urn.editor.clearIssueObjectsURN.description"}</span><br/>
				<input type="submit" name="clearIssueObjects_{$pubIdPlugin->getPubIdType()|escape}" value="{translate key="plugins.pubIds.urn.editor.clearIssueObjectsURN"}" class="action" /><br />
			{/if}
		{/if}

	</div>
	<div class="separator"></div>
	<!-- /URN -->
{/if}
{/if}
