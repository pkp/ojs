<div class="sections latest_articles">
    <{$heading} class="highlight_first">
        {translate key="submissions.published.latest"}
    </{$heading}>

    <ul class="cmp_article_list articles">
        {foreach from=$articles item=article}
            <li>
                {include file="frontend/objects/article_summary.tpl" heading=$articleHeading}
            </li>
        {/foreach}
        {page_info iterator=$articles}
        {page_links iterator=$articles name="publishedPublications"}
    </ul>
</div>
