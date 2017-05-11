{**
 * templates/notification/atomContent.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Template for a notification to be displayed in the Atom feed
 *
 *}

<entry>
	<id>{$notificationId}</id>
	<title>{translate key="notification.notification"} : {$notificationDateCreated|date_format:"%a, %d %b %Y %T %z"}</title>
	{if $notificationUrl != null}
		<link rel="alternate" href="{$notificationUrl|escape}" />
	{else}
		<link rel="alternate" href="{url page="notification"}" />
	{/if}

	<summary type="html" xml:base="{if $notificationUrl != null}{$notificationUrl|escape}{else}{url page="notification"}{/if}">
		{$notificationContent|escape:"html"}
	</summary>

	<published>{$notificationDateCreated|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</published>
</entry>
