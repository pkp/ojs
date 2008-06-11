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
<table class="announcements">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=announcements item=announcement}
	<tr class="title">
	{if $announcement->getTypeId() != null}
		<td class="title"><h4>{$announcement->getAnnouncementTypeName()|escape}: {$announcement->getAnnouncementTitle()|escape}</h4></td>
	{else}
		<td class="title"><h4>{$announcement->getAnnouncementTitle()|escape}</h4></td>
	{/if}
		<td class="more">&nbsp;</td>
	</tr>
	<tr class="description">
		<td class="description">{$announcement->getAnnouncementDescriptionShort()|nl2br}</td>
		<td class="more">&nbsp;</td>
	</tr>
	<tr class="details">
		<td class="posted">{translate key="announcement.posted"}: {$announcement->getDatePosted()}</td>
		<td class="more"><a href="{url page="announcement" op="view" path=$announcement->getAnnouncementId()}">{translate key="announcement.viewLink"}</a></td>
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
