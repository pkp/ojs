{**
 * index.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of announcements. 
 *
 * $Id$
 *}
{assign var="pageTitle" value="announcement.announcements"}
{assign var="pageId" value="announcement.announcements"}
{include file="common/header.tpl"}

<a name="announcements"></a>

<table width="100%" class="listing">
{if $announcementsIntroduction != null}
	<tr>
		<td colspan="2">{$announcementsIntroduction|nl2br}</td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
{/if}
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=announcements item=announcement}
	<tr valign="top">
	{if $announcement->getTypeId() != null}
		<td width="80%"><h4>{$announcement->getAnnouncementTypeName()}: {$announcement->getAnnouncementTitle()}</h4></td>
	{else}
		<td width="80%"><h4>{$announcement->getAnnouncementTitle()}</h4></td>
	{/if}
		<td width="20%">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>{$announcement->getAnnouncementDescriptionShort()|nl2br}</td>
		<td valign="bottom" align="right"><a href="{url op="view" path=$announcement->getAnnouncementId()}">{translate key="announcement.viewLink"}</a></td>
	</tr>
	<tr>
		<td colspan="2" class="{if $announcements->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $announcements->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="announcement.noneExist"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$announcements}</td>
		<td align="right">{page_links anchor="announcements" name="announcements" iterator=$announcements}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
