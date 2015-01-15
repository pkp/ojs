<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plnplugin SYSTEM "ping.dtd"> 
<plnplugin>
	<ojsInfo>
		<release>{$ojsVersion|escape}</release>
	</ojsInfo>
	<pluginInfo>
		<release>{$pluginVersion.release}</release>
		<releaseDate>{$pluginVersion.date}</releaseDate>
		<installed>{$pluginVersion.version->getDateInstalled()}</installed>
		<current>{$pluginVersion.version->getCurrent()}</current>
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
