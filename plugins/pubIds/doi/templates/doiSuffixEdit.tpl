{**
 * @file plugins/pubIds/doi/templates/doiSuffixEdit.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit DOI meta-data.
 *}

{if $pubObject}
{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
{assign var=enableObjectDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enable`$pubObjectType`Doi")}
{if $enableObjectDoi}
	<script type="text/javascript">
		{literal}
		<!--
			function toggleDOIClear() {
				if ($('#excludeDoi').is(':checked')) {
					$('#clear_doi').attr('checked', true);
					$('#clear_doi').attr('disabled', true);
				} else {
					$('#clear_doi').attr('disabled', false);
				}
			}
		// -->
		{/literal}
	</script>
	<div id="pub-id::doi">
		<h3>{translate key="plugins.pubIds.doi.editor.doi"}</h3>
		{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
		{if !$excludeDoi}
			{if $pubIdPlugin->getSetting($currentJournal->getId(), 'doiSuffix') == 'customId' || $storedPubId}
				{if empty($storedPubId)}
					<table width="100%" class="data">
						<tr valign="top">
							<td rowspan="2" width="10%" class="label">{fieldLabel name="doiSuffix" key="plugins.pubIds.doi.manager.settings.doiSuffix"}</td>
							<td rowspan="2" width="10%" align="right">{$pubIdPlugin->getSetting($currentJournal->getId(), 'doiPrefix')|escape}/</td>
							<td width="80%" class="value"><input type="text" class="textField" name="doiSuffix" id="doiSuffix" value="{$doiSuffix|escape}" size="20" maxlength="255" />
						</tr>
						<tr valign="top">
							<td colspan="3"><span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixDescription"}</span></td>
						</tr>
					</table>
				{else}
					<p>{$storedPubId|escape}</p>
					<input type="checkbox" name="clear_doi" id="clear_doi" value="1" />
					{capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
					{translate key="plugins.pubIds.doi.editor.doiReassign.description" pubObjectType=$translatedObjectType}<br />
				{/if}
			{else}
				<p>{$pubIdPlugin->getPubId($pubObject, true)|escape}</p>
				{capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
				{translate key="plugins.pubIds.doi.editor.doiNotYetGenerated" pubObjectType=$translatedObjectType}<br />
			{/if}
			<br />
		{/if}

		<input type="checkbox" name="excludeDoi" id="excludeDoi" value="1"{if $excludeDoi} checked="checked"{/if} onClick="toggleDOIClear()" />
		{capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
		{translate key="plugins.pubIds.doi.editor.excludePubObject" pubObjectType=$translatedObjectType}<br />

		{if $pubObjectType == 'Issue'}
			{assign var=enableArticleDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableArticleDoi")}
			{assign var=enableGalleyDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableGalleyDoi")}
			{assign var=enableSuppFileDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableSuppFileDoi")}
			{if $enableArticleDoi || $enableGalleyDoi || $enableSuppFileDoi}
				<br />
				<span class="instruct">{translate key="plugins.pubIds.doi.editor.excludeIssueObjectsDoi.description"}</span><br/>
				<input type="submit" name="excludeIssueObjects_{$pubIdPlugin->getPubIdType()|escape}" value="{translate key="plugins.pubIds.doi.editor.excludeIssueObjectsDoi"}" class="action" /><br />
				<br />
				<span class="instruct">{translate key="plugins.pubIds.doi.editor.clearIssueObjectsDoi.description"}</span><br/>
				<input type="submit" name="clearIssueObjects_{$pubIdPlugin->getPubIdType()|escape}" value="{translate key="plugins.pubIds.doi.editor.clearIssueObjectsDoi"}" class="action" /><br />
			{/if}
		{/if}

	</div>
	<div class="separator"> </div>
{/if}
{/if}
