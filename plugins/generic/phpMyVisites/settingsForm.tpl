{**
 * settingsForm.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * phpMyVisites plugin settings
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.generic.phpmv.manager.phpmvSettings"}
{include file="common/header.tpl"}

{translate key="plugins.generic.phpmv.manager.settings.description"}

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="phpmvUrl" required="true" key="plugins.generic.phpmv.manager.settings.phpmvUrl"}</td>
		<td width="80%" class="value"><input type="text" name="phpmvUrl" id="phpmvUrl" value="{if $phpmvUrl}{$phpmvUrl|escape}{else}http://{/if}" size="30" maxlength="255" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.phpmv.manager.settings.phpmvUrlInstructions"}</span>
	</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="phpmvSiteId" required="true" key="plugins.generic.phpmv.manager.settings.phpmvSiteId"}</td>
		<td class="value"><input type="text" name="phpmvSiteId" id="phpmvSiteId" value="{$phpmvSiteId|escape}" size="10" maxlength="10" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.phpmv.manager.settings.phpmvSiteIdInstructions"}</span>
	</td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
