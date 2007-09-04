{**
 * settingsForm.tpl
 *
 * Copyright (c) 2006 Gunther Eysenbach, Juan Pablo Alperin
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for CMS RSS plugin settings.
 *
 *}
{assign var="pageTitle" value="plugins.generic.cmsrss.displayName"}
{include file="common/header.tpl"}

{translate key="plugins.generic.cmsrss.form.introduction"}

<br />
<br />

<form method="post" action="{plugin_url path="settings"}">

{include file="common/formErrors.tpl"}
<br />

<table width="100%" class="data">
<tr valign="top">
	<td width="50%" class="label">{fieldLabel name="months" required="true" key="plugins.generic.cmsrss.months"}</td>
	<td width="50%" class="value"><input type="text" class="textField" name="months" id="months" value="{$months}" size="5" maxlength="5" /></td>
</tr>
<tr valign="top">
	<td width="50%" class="label">{fieldLabel name="aggregate" required="true" key="plugins.generic.cmsrss.aggregate"}</td>
	<td width="50%" class="value"><input type="checkbox" class="checkBox" name="aggregate" id="aggregate" value="1"{if $aggregate} checked="checked"{/if}" /></td>
</tr>
</table>
<br />
<input type="hidden" name="deletedUrls" value="{$deletedUrls|escape}" />

<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
	<td width="20%" align="center">{translate key="plugins.generic.cmsrss.pagename"}</td>
	<td width="80%" align="center" >{translate key="plugins.generic.cmsrss.url"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	
{foreach name=urls from=$urls key=urlIndex item=url}
<input type="hidden" name="urls[{$urlIndex}][urlId]" value="{$url.urlId|escape}" />
<tr valign="top">
	<td width="20%" class="value" align="right"><input type="text" class="textField" name="urls[{$urlIndex}][pageName]" id="urls-{$urlIndex}-pageName" value="{$url.pageName|escape}" size="20" maxlength="40" /></td>
	<td width="80%" class="value" align="right"><input type="text" class="textField" name="urls[{$urlIndex}][url]" id="urls-{$urlIndex}-url" value="{$url.url|escape}" size="60" maxlength="100" /></td>
</tr>
{if $smarty.foreach.urls.total > 1}
<tr valign="top">
	<td colspan="2" align="right"><input type="submit" name="delUrl[{$urlIndex}]" value="{translate key="plugins.generic.cmsrss.deleteUrl"}" class="button" /></td>
</tr>
	<tr>
		<td colspan="4" class="separator">&nbsp;</td>
	</tr>
{/if}

{foreachelse}
<input type="hidden" name="urls[0][urlId]" value="0" />
<tr valign="top">
	<td width="20%" class="value" align="right"><input type="text" class="textField" name="urls[0][pageName]" id="urls-0-pageName" value="{$url.pageName|escape}" size="20" maxlength="40" /></td>
	<td width="80%" class="value" align="right"><input type="text" class="textField" name="urls[0][url]" id="urls-0-url" value="{$url.url|escape}" size="60" maxlength="100" /></td>
</tr>

{/foreach}
</table>
<p>{translate key="plugins.generic.cmsrss.sorting"}</p>
<p><input type="submit" class="button" name="addUrl" value="{translate key="plugins.generic.cmsrss.addUrl"}" />
<input type="submit" class="button" name="save" value="{translate key="common.save"}" />
<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager" op="plugins" escape=false}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
