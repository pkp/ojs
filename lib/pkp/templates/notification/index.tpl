{**
 * templates/notification/index.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of notifications.
 *
 *}
{strip}
{assign var="pageTitle" value="notification.notifications"}
{include file="frontend/components/header.tpl"}
{/strip}

<table>
	<tr>
		<td>{if $isUserLoggedIn}
				<p>{translate key="notification.notificationsDescription" unreadCount=$unread readCount=$read settingsUrl=$url}</p>
			{else}
				<p>{translate key="notification.notificationsPublicDescription" emailUrl=$emailUrl}</p>
			{/if}
		</td>
		<td><ul class="plain">
			<li><a href="{url op="getNotificationFeedUrl" path="rss"}" class="icon"><img src="{$baseUrl|escape}/lib/pkp/templates/images/rss10_logo.gif" alt="RSS 1.0"/></a></li>
			<li><a href="{url op="getNotificationFeedUrl" path="rss2"}" class="icon"><img src="{$baseUrl}/lib/pkp/templates/images/rss20_logo.gif" alt="RSS 2.0"/></a></li>
			<li><a href="{url op="getNotificationFeedUrl" path="atom"}" class="icon"><img src="{$baseUrl}/lib/pkp/templates/images/atom10_logo.gif" alt="Atom 1.0"/></a></li>
		</ul></td>
	</tr>
</table>

<br/>

{if $isUserLoggedIn}
	<div id="normalNotifications">
		{url|assign:notificationsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.notifications.NormalNotificationsGridHandler" op="fetchGrid" escape=false}
		{load_url_in_div id="normalNotificationsGridContainer" url=$notificationsGridUrl}
	</div>
{/if}

{include file="frontend/components/footer.tpl"}

