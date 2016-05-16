{**
 * templates/frontend/objects/article_details.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief View of an Galley which displays all details about the galley.
 *
 * @uses $article Article This article
 * @uses $issue Issue The issue this article is assigned to
 * @uses $pubIdPlugins @todo
 *}
<galley class="obj_galley_details">
	<h1 class="page_title">{$article->getLocalizedTitle()|escape}</h1>

	{translate key="article.view.interstitial" galleyUrl=$fileUrl}
	<ul class="galleys_links">
		{foreach from=$galley->getLatestGalleyFiles() item=galleyFile}
			<li>
				<a class="obj_galley_link" href="{url op="download" path=$article->getBestArticleId()|to_array:$galley->getBestGalleyId():$galleyFile->getFileId() escape=false}">{$galleyFile->getLocalizedName()|escape}</a>
			</li>
		{/foreach}
	</ul>

	{* PubIds (requires plugins) *}
	{* @todo this hasn't been tested or styled *}
	{foreach from=$pubIdPlugins item=pubIdPlugin}
		{if $issue->getPublished()}
			{assign var=pubId value=$galley->getStoredPubId($pubIdPlugin->getPubIdType())}
		{else}
			{assign var=pubId value=$pubIdPlugin->getPubId($galley)}{* Preview pubId *}
		{/if}
		{if $pubId}
			<div class="pubid">
				<div class="type">
					{$pubIdPlugin->getPubIdDisplayType()|escape}
				</div>
				<div class="id">
					{if $pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
						<a id="pub-id::{$pubIdPlugin->getPubIdType()|escape}" href="{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}">
							{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
						</a>
					{else}
						{$pubId|escape}
					{/if}
				</div>
			</div>
		{/if}
	{/foreach}

</galley>
