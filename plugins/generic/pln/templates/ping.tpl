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
		<terms termsAccepted="{$termsAccepted|escape}">
			{iterate from=termsDisplay item=term}
			<term key="{$term.key|escape}" updated="{$term.updated|escape}" accepted="{$term.accepted|escape}">{$term.term|escape}</term>
			{/iterate}
		</terms>
	</pluginInfo>
	<journalInfo>
		<title>{$journal->getLocalizedTitle()|escape}</title>
		<articles count="{$articles->getCount()|escape}">
			{iterate from=articles item=article}
			<article pubDate="{$article->getDatePublished()|escape}">{$article->getLocalizedTitle()|escape}</article>
			{/iterate}
		</articles>
	</journalInfo>
</plnplugin>
