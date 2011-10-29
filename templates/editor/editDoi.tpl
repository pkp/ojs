{**
 * @file templates/editor/editDoi.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit DOI meta-data.
 *
 * FIXME: Will be moved to a PID-plug-in in the next release.
 *}

{assign var=enableDoiSetting value="enable"|cat:$objectType|cat:"Doi"}
{if $currentJournal->getSetting($enableDoiSetting) == '1'}
	{if $objectType == "Issue"}
		<div class="separator"> </div>
	{/if}
	<div id="pub-id::doi">
		<h3>{translate key="manager.setup.doi"}</h3>
		{if $currentJournal->getSetting('doiSuffix') == 'customId' || $storedDoi}
			{if empty($storedDoi)}
				<table width="100%" class="data">
					<tr valign="top">
						<td rowspan="2" width="10%" class="label">{fieldLabel name="doiSuffix" key="manager.setup.doiSuffix"}</td>
						<td rowspan="2" width="10%" align="right">{$currentJournal->getSetting('doiPrefix')|escape}/</td>
						<td width="80%" class="value"><input type="text" class="textField" name="doiSuffix" id="doiSuffix" value="{$doiSuffix|escape}" size="20" maxlength="20" />
					</tr>
					<tr valign="top">
						<td colspan="3"><span class="instruct">{translate key="manager.setup.doiSuffixDescription"}</span></td>
					</tr>
				</table>
			{else}
				{$storedDoi|escape}
			{/if}
		{else}
			{capture assign=translatedObjectType}{translate key="manager.setup.doiObjectType"|cat:$objectType}{/capture}
			{translate key="manager.setup.doiNotYetGenerated" objectType=$translatedObjectType}
		{/if}
		<br /><br />
	</div>
	{if $objectType == "Article" || $objectType == "SuppFile"}
		<div class="separator"> </div>
	{/if}
{/if}
