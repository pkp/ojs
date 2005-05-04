{**
 * versions.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin version list
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.versions"}
{include file="common/header.tpl"}

<br/>

<table class="listing" width="100%">
	<tr>
		<td align="left">{page_info iterator=$versions}</td>
		<td colspan="2" align="right">{page_links name="versions" iterator=$versions}</td>
	</tr>
	<tr><td class="headseparator" colspan="3">&nbsp;</td></tr>
	<tr valign="top">
		<td class="heading" width="40%">{translate key="rt.version.title"}</td>
		<td class="heading" width="20%">{translate key="rt.version.locale"}</td>
		<td class="heading" width="40%" align="right">&nbsp;</td>
	</tr>
	<tr><td class="headseparator" colspan="3">&nbsp;</td></tr>
	{iterate from=versions item=version}
		<tr valign="top">
			<td>{$version->getTitle()}</td>
			<td>{$version->getLocale()}</td>
			<td align="right"><a href="{$requestPageUrl}/validateUrls/{$version->getVersionId()}" class="action">{translate key="rt.admin.validateUrls.validate"}</a>&nbsp;&nbsp;<a href="{$requestPageUrl}/editVersion/{$version->getVersionId()}" class="action">{translate key="rt.admin.versions.metadata"}</a>&nbsp;&nbsp;<a href="{$requestPageUrl}/contexts/{$version->getVersionId()}" class="action">{translate key="rt.contexts"}</a>&nbsp;&nbsp;<a href="{$requestPageUrl}/exportVersion/{$version->getVersionId()}" class="action">{translate key="rt.admin.versions.export"}</a>&nbsp;&nbsp;<a href="{$requestPageUrl}/deleteVersion/{$version->getVersionId()}" onclick="return confirm('{translate|escape:"javascript" key="rt.admin.versions.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
		</tr>
		<tr><td class="{if $versions->eof()}end{/if}separator" colspan="3"></td></tr>
	{/iterate}
	{if $versions->wasEmpty()}
		<tr valign="top">
			<td class="nodata" colspan="3">{translate key="common.none"}</td>
		</tr>
		<tr><td class="endseparator" colspan="3"></td></tr>
	{else}
		<tr>
			<td align="left">{page_info iterator=$versions}</td>
			<td colspan="2" align="right">{page_links name="versions" iterator=$versions}</td>
		</tr>
	{/if}
	</table>
<br/>

<a href="{$requestPageUrl}/createVersion" class="action">{translate key="rt.admin.versions.createVersion"}</a><br/>

<a href="{$requestPageUrl}/restoreVersions" onclick="return confirm('{translate|escape:"javascript" key="rt.admin.versions.confirmRestore"}')" class="action">{translate key="rt.admin.versions.restoreVersions"}</a>

<br/>
<br/>

<form method="post" action="{$requestPageUrl}/importVersion" enctype="multipart/form-data">
	<input type="file" class="uploadField" name="versionFile" />
	<input type="submit" class="button" value="{translate key="rt.admin.versions.importVersion"}" />
</form>

{include file="common/footer.tpl"}
