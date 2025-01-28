{**
 * templates/frontend/pages/indexSite.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site index.
 *
 *}
{include file="frontend/components/header.tpl" immersionIndexType="indexSite"}

<main class="container main__content" id="immersion_content_main">
	<div class="row">
		<div class="offset-md-1 col-md-10 offset-lg-2 col-lg-8">
			<header class="main__header">
				{if $about}
					<div class="about_site">
						{$about|strip_unsafe_html|nl2br}
					</div>
				{/if}
				<h2 class="main__title">
					{translate key="context.contexts"}
				</h2>
			</header>

			<div class="content-body">
				{if !$journals|@count}
					{translate key="site.noJournals"}
				{else}
					<ul class="index-site__journals">
						{foreach from=$journals item=journal}
							{capture assign="url"}{url journal=$journal->getPath()}{/capture}
							{assign var="thumb" value=$journal->getLocalizedSetting('journalThumbnail')}
							{assign var="description" value=$journal->getLocalizedDescription()}
							<li{if $thumb} class="has_thumb"{/if}>
								{if $thumb}
									<div class="thumb">
										<a class="img-wrapper" href="{$url|escape}">
											<img class="img-thumbnail" src="{$journalFilesPath}{$journal->getId()}/{$thumb.uploadName|escape:"url"}"{if $thumb.altText} alt="{$thumb.altText|escape|default:''}"{/if}>
										</a>
									</div>
								{/if}

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
								<div class="index-site__links">
									<a class="btn btn-primary"  href="{$url|escape}">
										{translate key="site.journalView"}
									</a>
									<a class="btn btn-secondary" href="{url|escape journal=$journal->getPath() page="issue" op="current"}">
										{translate key="site.journalCurrent"}
									</a>
								</div>
							</li>
						{/foreach}
					</ul>
				{/if}
			</div>
		</div>
	</div><!-- .row -->

</main><!-- .page -->

{include file="frontend/components/footer.tpl"}
