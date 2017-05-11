{**
 * templates/notification/rss.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RSS feed template
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rdf:RDF
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns="http://purl.org/rss/1.0/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/">

	<channel rdf:about="{$baseUrl}">
		<title>{$siteTitle} {translate key="notification.notifications"}</title>
		<link>{$selfUrl|escape}</link>
		<language>{$locale|replace:'_':'-'|strip|escape:"html"}</language>
		<items>
			{foreach from=$notifications item=notification}
			<rdf:Seq>
				<rdf:li rdf:resource="{url page="notification"}"/>
			</rdf:Seq>
			{/foreach}
		</items>
	</channel>

	{$formattedNotifications}
</rdf:RDF>
