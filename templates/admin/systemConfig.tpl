{**
 * systemConfig.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit system configuration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="admin.systemConfiguration"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/admin/saveSystemConfig">
<p>{translate key="admin.editSystemConfigInstructions"}</p>

{foreach from=$configData key=sectionName item=sectionData}
<h3>{$sectionName}</h3>
<table class="data" width="100%">
{foreach from=$sectionData key=settingName item=settingValue}
<tr valign="top">	
	<td width="20%" class="label">{$settingName}</td>
	<td width="80%" class="value"><input type="text" name="{$sectionName}[{$settingName}]" value="{if $settingValue === true}{translate key="common.on"}{elseif $settingValue === false}{translate key="common.off"}{else}{$settingValue|escape}{/if}" size="40" class="textField" /></td>
</tr>
{/foreach}
</table>

<br />
{/foreach}
</table>

<p><input type="submit" value="{translate key="admin.saveSystemConfig"}" class="button defaultButton" /> <input name="display" type="submit" value="{translate key="admin.displayNewSystemConfig"}" class="button" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/admin/systemInfo'" /></p>

</form>

{include file="common/footer.tpl"}
