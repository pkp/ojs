{**
 * templates/ping.tpl
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * PLN plugin settings
 *}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plnplugin SYSTEM "ping.dtd"> 
<plnplugin>
	<ojsInfo>
		<release>{$ojsVersion|escape}</release>
	</ojsInfo>
	<pluginInfo>
		<release>{$pluginVersion.release|escape}</release>
		<releaseDate>{$pluginVersion.date|escape}</releaseDate>
		<current>{$pluginVersion.version->getCurrent()|escape}</current>
		<prerequisites>
			<phpVersion>{$prerequisites.phpVersion|escape}</phpVersion>
			<curlVersion>not required</curlVersion>
			<zipInstalled>{$prerequisites.zipInstalled|escape}</zipInstalled>
			<tarInstalled>{$prerequisites.tarInstalled|escape}</tarInstalled>
			<acron>{$prerequisites.acron|escape}</acron>
			<tasks>{$prerequisites.tasks|escape}</tasks>
		</prerequisites>
		<terms termsAccepted="{$termsAccepted|escape}">
			{iterate from=termsDisplay item=term}
			<term key="{$term.key|escape}" updated="{$term.updated|escape}" accepted="{$term.accepted|escape}">{$term.term|escape}</term>
			{/iterate}
		</terms>
	</pluginInfo>
	<journalInfo>
		<title>{$currentJournal->getLocalizedName()|escape}</title>
		<articles count="{$publications|@count|escape}">
			{foreach from=$publications item=publication}
			<article pubDate="{$publication->getData('datePublished')|escape}">{$publication->getLocalizedTitle()|escape}</article>
			{/foreach}
		</articles>
	</journalInfo>
</plnplugin>
