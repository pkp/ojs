{**
 * templates/notification/rssContent.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Template for a notification to be displayed in the RSS feed
 *
 *}

<item rdf:about="{url page="notification"}">
	<title>{translate key="notification.notification"} : {$notificationDateCreated|date_format:"%a, %d %b %Y %T %z"}</title>
	<link>
		{if $notificationUrl != null}
			{$notificationUrl|escape}
		{else}
			{url page="notification"}
		{/if}
	</link>
	<description>
		{$notificationContent|escape:"html"}
	</description>
	<dc:creator>{$siteTitle|strip|escape:"html"}</dc:creator>
	<dc:date>{$notificationDateCreated|date_format:"%Y-%m-%d"}</dc:date>
</item>
