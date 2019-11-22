{**
 * templates/frontend/pages/indexSite.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site index.
 *
 *}
{include file="frontend/components/header.tpl"}

<div class="page_index_site">

	{if $about}
		<div class="about_site">
			{$about|nl2br}
		</div>
	{/if}

	<div class="journals">
		<h2>
			{translate key="context.contexts"}
		</h2>
		{if $journals->wasEmpty()}
			{translate key="site.noJournals"}
		{else}
			<ul>
				{iterate from=journals item=journal}
					{capture assign="url"}{url journal=$journal->getPath()}{/capture}
					{assign var="thumb" value=$journal->getLocalizedData('serverThumbnail')}
					{assign var="description" value=$journal->getLocalizedDescription()}
					<li{if $thumb} class="has_thumb"{/if}>
						{if $thumb}
							<div class="thumb">
								<a href="{$url|escape}">
									<img src="{$journalFilesPath}{$journal->getId()}/{$thumb.uploadName|escape:"url"}"{if $thumb.altText} alt="{$thumb.altText|escape|default:'null'}"{/if}>
								</a>
							</div>
						{/if}

						<div class="body">
							<h3>
								<a href="{$url|escape}" rel="bookmark">
									{$journal->getLocalizedName()}
								</a>
							</h3>
							{if $description}
								<div class="description">
									{$description|nl2br}
								</div>
							{/if}
							<ul class="links">
								<li class="view">
									<a href="{$url|escape}">
										{translate key="site.journalView"}
									</a>
								</li>
							</ul>
						</div>
					</li>
				{/iterate}
			</ul>
		{/if}
	</div>

</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
