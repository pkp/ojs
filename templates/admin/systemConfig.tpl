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

{translate key="admin.editSystemConfigInstructions"}
<br /><br />

{foreach from=$configData key=sectionName item=sectionData}
<div class="formSectionTitle">{$sectionName}</div>
<div class="formSection">
<table class="form">
{foreach from=$sectionData key=settingName item=settingValue}
<tr>	
	<td class="formLabel">{$settingName}</td>
	<td class="formField"><input type="text" name="{$sectionName}[{$settingName}]" value="{if $settingValue === true}On{elseif $settingValue === false}Off{else}{$settingValue|escape}{/if}" size="40" class="textField" /></td>
</tr>
{/foreach}
</table>
</div>

<br />
{/foreach}

<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="admin.saveSystemConfig"}" class="formButton" /> <input name="display" type="submit" value="{translate key="admin.displayNewSystemConfig"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/admin/systemInfo'" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}
