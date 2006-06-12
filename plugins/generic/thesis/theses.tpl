{**
 * theses.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of thesis abstracts in plugin management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.generic.thesis.manager.theses"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{plugin_url path="theses"}">{translate key="plugins.generic.thesis.manager.theses"}</a></li>
	<li><a href="{plugin_url path="settings"}">{translate key="plugins.generic.thesis.manager.settings"}</a></li>
</ul>

<br />

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="10%">{translate key="plugins.generic.thesis.manager.status"}</td>
		<td width="15%">{translate key="plugins.generic.thesis.manager.dateApproved"}</td>
		<td width="20%">{translate key="plugins.generic.thesis.manager.studentName"}</td>
		<td width="40%">{translate key="plugins.generic.thesis.manager.title"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=theses item=thesis}
	<tr valign="top">
		<td>{translate key=$thesis->getStatusString()}</td>
		<td>{$thesis->getDateApproved()|date_format:$dateFormatShort}</td>
		<td>{$thesis->getStudentFullName()|escape}</td>
		<td>{$thesis->getTitle()|escape}</td>
		<td><a href="{plugin_url path="edit" id=$thesis->getThesisId()}" class="action">{translate key="common.edit"}</a> <a href="{plugin_url path="delete" id=$thesis->getThesisId()}" onclick="return confirm('{translate|escape:"javascript" key="plugins.generic.thesis.manager.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $theses->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $theses->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="plugins.generic.thesis.manager.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$theses}</td>
		<td colspan="3" align="right">{page_links name="theses" iterator=$theses}</td>
	</tr>
{/if}
</table>

<a href="{plugin_url path="create"}" class="action">{translate key="plugins.generic.thesis.manager.create"}</a>

{include file="common/footer.tpl"}
