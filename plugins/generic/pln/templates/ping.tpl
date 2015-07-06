<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plnplugin SYSTEM "ping.dtd"> 
<plnplugin>
	<ojsInfo>
		<release>{$ojsVersion|escape}</release>
	</ojsInfo>
	<pluginInfo>
		<release>{$pluginVersion.release}</release>
		<releaseDate>{$pluginVersion.date}</releaseDate>
		<current>{$pluginVersion.version->getCurrent()}</current>
		<terms termsAccepted="{$termsAccepted|escape}">
			{iterate from=termsDisplay item=term}
			<term key='{$term.key}' updated='{$term.updated}' accepted='{$term.accepted}'>{$term.term|escape}</term>
			{/iterate}
		</terms>
	</pluginInfo>
	<journalInfo>
		<title>{$journal->getLocalizedTitle()|escape}</title>
		<articles count='{$articles->getCount()}'>
			{iterate from=articles item=article}
			<article pubDate='{$article->getDatePublished()}'>{$article->getLocalizedTitle()|escape}</article>
			{/iterate}
		</articles>
	</journalInfo>
</plnplugin>
