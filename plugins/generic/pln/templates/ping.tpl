
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
    </journalInfo>
</plnplugin>
