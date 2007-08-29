{**
 * list.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of announcements without site header or footer. 
 *
 * $Id$
 *}

<table width="100%" class="listing">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=announcements item=announcement}
	<tr valign="top">
	{if $announcement->getTypeId() != null}
		<td width="80%"><h4>{$announcement->getAnnouncementTypeName()|escape}: {$announcement->getAnnouncementTitle()|escape}</h4></td>
	{else}
		<td width="80%"><h4>{$announcement->getAnnouncementTitle()|escape}</h4></td>
	{/if}
		<td width="20%">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>{$announcement->getAnnouncementDescriptionShort()|nl2br}</td>
		<td valign="bottom" align="right"><a href="{url page="announcement" op="view" path=$announcement->getAnnouncementId()}">{translate key="announcement.viewLink"}</a></td>
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
{/if}
</table>
