{**
 * index.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal index page.
 *
 * $Id$
 *}
{assign var="pageTitleTranslated" value=$siteTitle}
{include file="common/header.tpl"}

<div>{$journalDescription}</div>

{call_hook name="Templates::Index::journal"}

{if $homepageImage}
<br />
<div align="center"><img src="{$publicFilesDir}/{$homepageImage.uploadName|escape:"url"}" width="{$homepageImage.width|escape}" height="{$homepageImage.height|escape}" style="border: 0;" alt="" /></div>
{/if}

{if $additionalHomeContent}
<br />
{$additionalHomeContent}
{/if}

{if $enableAnnouncementsHomepage}
	{* Display announcements *}
	<br />
	<center><h3>{translate key="announcement.announcementsHome"}</h3></center>
	{include file="announcement/list.tpl"}	
	<table width="100%">
		<tr>
			<td>&nbsp;</td>
		<tr>
			<td align="right"><a href="{url page="announcement"}">{translate key="announcement.moreAnnouncements"}</a></td>
		</tr>
	</table>
{/if}

{if $issue}
	{* Display the table of contents or cover page of the current issue. *}
	<br />
	<h3>{$issue->getIssueIdentification()|escape}</h3>
	{include file="issue/view.tpl"}
{/if}

{include file="common/footer.tpl"}
