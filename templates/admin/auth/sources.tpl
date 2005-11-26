{**
 * sources.tpl
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of authentication sources in site administration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="admin.authSources"}
{include file="common/header.tpl"}

<br />

<form method="post" action="{url op="updateAuthSources"}">

<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="top" class="heading">
		<td width="10%">{translate key="common.default"}</td>
		<td width="30%">{translate key="common.title"}</td>
		<td width="30%">{translate key="common.plugin"}</td>
		<td width="30%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=sources item=auth}
	<tr valign="top">
		<td><input type="radio" id="defaultAuthId_{$auth->getAuthId()}" name="defaultAuthId" value="{$auth->getAuthId()}"{if $auth->getDefault()} checked="checked"{assign var="defaultAuthId" value=$auth->getAuthId()}{/if} /></td>
		<td><label for="defaultAuthId_{$auth->getAuthId()}">{$auth->getTitle()|escape}</label></td>
		<td>{$auth->getPlugin()}</td>
		<td align="right"><a href="{url op="editAuthSource" path=$auth->getAuthId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a class="action" href="{url op="deleteAuthSource" path=$auth->getAuthId()}" onclick="return confirm('{translate|escape:"javascript" key="admin.auth.confirmDelete"}')">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="4" class="{if $smarty.foreach.sources.last}end{/if}separator">&nbsp;</td>
	</tr>
	{/iterate}
	{if $sources->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="admin.auth.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	<tr>
	{else}
		<tr>
			<td colspan="2" align="left">{page_info iterator=$sources}</td>
			<td colspan="2" align="right">{page_links name="sources" iterator=$sources}</td>
		</tr>
	{/if}
	<tr valign="top">
		<td><input type="radio" id="defaultAuthId_0" name="defaultAuthId" value="0"{if !$defaultAuthId} checked="checked"{/if} /></td>
		<td colspan="1"><label for="defaultAuthId_0">{translate key="admin.auth.ojs"}</label></td>
		<td colspan="2" align="right">
			<input type="submit" value="{translate key="common.save"}" class="button" />
		</td>
	</tr>
</table>

</form>

<p>{translate key="admin.auth.defaultSourceDescription"}</p>

<h4>{translate key="admin.auth.create"}</h4>

<form method="post" action="{url op="createAuthSource"}">
	{translate key="common.plugin"}: <select name="plugin" size="1"><option value=""></option>{html_options options=$pluginOptions}</select> <input type="submit" value="{translate key="common.create"}" class="button" />
</form>

{include file="common/footer.tpl"}
