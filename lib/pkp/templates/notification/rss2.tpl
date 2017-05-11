{**
 * templates/notification/rss2.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RSS 2 feed template
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rss version="2.0">
	<channel>
		{* required elements *}
		<title>{$siteTitle} {translate key="notification.notifications"}</title>
		<link>{$selfUrl|escape}</link>

		{* optional elements *}
		<language>{$locale|replace:'_':'-'|strip|escape:"html"}</language>
		<generator>{translate key=$appName} {$version|escape}</generator>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<ttl>60</ttl>

		{$formattedNotifications}
	</channel>
</rss>
