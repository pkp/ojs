{**
 * lib/pkp/templates/announcement/announcements.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of announcements in management.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.announcements"}
{assign var="pageId" value="manager.announcements"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li class="current"><a href="{url op="announcements"}">{translate key="manager.announcements"}</a></li>
	<li><a href="{url op="announcementTypes"}">{translate key="manager.announcementTypes"}</a></li>
</ul>

{url|assign:announcementGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.announcements.ManageAnnouncementGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="announcementGridContainer" url=$announcementGridUrl}

{include file="common/footer.tpl"}
