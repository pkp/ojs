{**
 * announcements.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of announcements in journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.announcements"}
{assign var="pageId" value="manager.announcements"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{url op="announcements"}">{translate key="manager.announcements"}</a></li>
	<li><a href="{url op="announcementTypes"}">{translate key="manager.announcementTypes"}</a></li>
</ul>

<br />

<a name="announcements"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="15%">{translate key="manager.announcements.dateExpire"}</td>
		<td width="15%">{translate key="manager.announcements.type"}</td>
		<td width="55%">{translate key="manager.announcements.title"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=announcements item=announcement}
	<tr valign="top">
		<td>{$announcement->getDateExpire()|date_format:$dateFormatShort}</td>
		<td>{$announcement->getTypeName()}</td>
		<td>{$announcement->getTitle()|escape}</td>
		<td><a href="{url op="editAnnouncement" path=$announcement->getAnnouncementId()}" class="action">{translate key="common.edit"}</a> <a href="{url op="deleteAnnouncement" path=$announcement->getAnnouncementId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.announcements.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="4" class="{if $announcements->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $announcements->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="manager.announcements.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$announcements}</td>
		<td colspan="2" align="right">{page_links anchor="announcements" name="announcements" iterator=$announcements}</td>
	</tr>
{/if}
</table>

<a href="{url op="createAnnouncement"}" class="action">{translate key="manager.announcements.create"}</a>

{include file="common/footer.tpl"}
