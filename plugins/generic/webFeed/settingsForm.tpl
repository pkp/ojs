{**
 * settingsForm.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Web feeds plugin settings
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.generic.webfeed.displayName"}
{include file="common/header.tpl"}

{translate key="plugins.generic.webfeed.description"}

<div class="separator">&nbsp;</div>

<h3>{translate key="plugins.generic.webfeed.settings"}</h3>

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="10%" class="label" align="right"><input type="radio" name="displayPage" id="displayPage" value="all" {if $displayPage eq "all"}checked {/if}/></td>
		<td width="90%" class="value">{translate key="plugins.generic.webfeed.settings.all"}</td>
	</tr>
	<tr valign="top">
		<td width="10%" class="label" align="right"><input type="radio" name="displayPage" id="displayPage" value="issue" {if $displayPage eq "issue"}checked {/if}/></td>
		<td width="90%" class="value">{translate key="plugins.generic.webfeed.settings.issue"}</td>
	</tr>
	<tr>
		<td colspan="2"><div class="separator">&nbsp;</div></td>
	</tr>
	<tr valign="top">
		<td width="10%" class="label" align="right"><input type="radio" name="displayItems" id="displayItems" value="issue" {if $displayItems ne "recent"}checked {/if}/></td>
		<td width="90%" class="value">{translate key="plugins.generic.webfeed.settings.currentIssue"}</td>
	</tr>
	<tr valign="top">
		<td width="10%" class="label" align="right"><input type="radio" name="displayItems" id="displayItems" value="recent" {if $displayItems eq "recent"}checked {/if}/></td>
		<td width="90%" class="value">
		<input type="text" name="recentItems" id="recentItems" value="{$recentItems|escape}" size="2" maxlength="90" class="textField" />
		{translate key="plugins.generic.webfeed.settings.recentArticles"}</td>
	</tr>
	
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/> <input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
