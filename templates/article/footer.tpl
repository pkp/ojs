{**
 * templates/article/footer.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View -- Footer component.
 *
 * Available data:
 *  $article Article The article object for the current article view
 *  $galley ArticleGalley The (optional!) galley object for the current view
 *  $galleys array The list of galleys available for this article
 *}
{if $currentJournal->getSetting('includeCopyrightStatement')}
	<br/><br/>
	{translate key="submission.copyrightStatement" copyrightYear=$article->getCopyrightYear()|escape copyrightHolder=$article->getLocalizedCopyrightHolder()|escape}
{/if}
{if $currentJournal->getSetting('includeLicense') && $ccLicenseBadge}
	<br /><br />
	{$ccLicenseBadge}
{/if}

{call_hook name="Templates::Article::Footer::PageFooter"}
{if $pageFooter}
	<br /><br />
	{$pageFooter}
{/if}

{include file="common/footer.tpl"}
