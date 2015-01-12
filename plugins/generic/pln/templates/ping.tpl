
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
        <title>{$journal->getLocalizedTitle()}</title>
        <articles count='{$articles->getCount()}'>
            {foreach from=$articles->toArray() item=article}
            <article pubDate='{$article->getDatePublished()}'>{$article->getLocalizedTitle()}</article>
            {/foreach}
        </articles>
    </journalInfo>
</plnplugin>
