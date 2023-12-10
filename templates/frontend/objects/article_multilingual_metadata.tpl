{*
 * Show article keywords and abstract in ui, or submission, language by default.
 * Show optional multilingual metadata: titles, keywords, abstracts.
 *}
{foreach from=$pubLocaleData.languages item=lang}
    <section class="metadata multilingual-{$lang}">
    {assign "hLvl" "2"}
    {* Multilingual metadata title *}
    {if $lang !== $pubLocaleData.titleLocale}
        {assign "hLvl" "3"}
        <h2 class="item label page_metadata_title" lang={$lang|replace:"_":"-"}>
            {translate key="plugins.themes.default.submissionMetadataInLanguage" locale=$lang}
        </h2>
        {* Title in other language *}
        {if isset($pubLocaleData.title.text[$lang])}
            <section class="item page_locale_title">
                <h{$hLvl} class="label" lang="{$pubLocaleData.title.hLang[$lang]|replace:"_":"-"}">
                    {translate key="submission.title" locale=$pubLocaleData.title.hLang[$lang]}
                </h{$hLvl}>
                <p lang="{$lang|replace:"_":"-"}">
                    {$pubLocaleData.title.text[$lang]|strip_tags}
                    {if isset($pubLocaleData.subtitle.text[$lang])}
                        {translate key="plugins.themes.default.titleSubtitleSeparator" locale=$pubLocaleData.title.hLang[$lang]}{$pubLocaleData.subtitle.text[$lang]|strip_tags}
                    {/if}
                </p>
            </section>
        {/if}
    {/if}

    {* Keywords *}
    {if isset($pubLocaleData.keywords.text[$lang])}
        <section class="item keywords">
            <h{$hLvl} class="label" lang="{$pubLocaleData.keywords.hLang[$lang]|replace:"_":"-"}">
                {translate key="common.keywords" locale=$pubLocaleData.keywords.hLang[$lang]}
            </h{$hLvl}>
            <p class="value" lang="{$lang|replace:"_":"-"}">
            {foreach from=$pubLocaleData.keywords.text[$lang] item="keyword"}
                {$keyword|escape}{if !$keyword@last}{translate key="common.commaListSeparator" locale=$pubLocaleData.keywords.hLang[$lang]}{/if}
            {/foreach}
            </p>
        </section>
    {/if}

    {* Abstract *}
    {if isset($pubLocaleData.abstract.text[$lang])}
        <section class="item abstract">
            <h{$hLvl} class="label" lang="{$pubLocaleData.abstract.hLang[$lang]|replace:"_":"-"}">
                {translate key="common.abstract" locale=$pubLocaleData.abstract.hLang[$lang]}
            </h{$hLvl}>
            <p lang="{$lang|replace:"_":"-"}">{$pubLocaleData.abstract.text[$lang]|strip_tags}</p>
        </section>
    {/if}

    {call_hook name="Templates::Article::Main"}
    </section>
{/foreach}