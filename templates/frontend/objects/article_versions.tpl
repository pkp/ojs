{**
 * templates/frontend/objects/article_versions.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief View of article versions at the article page.
 *
 * @uses $article Article
 * @uses $isPreviousRevision
 * @uses $previousRevisions 
 *}
{assign var=newVersionLink value=$article->getBestArticleId()}
{if $previousRevisions|@count > 0}
	<div class='version_info'>
		<h2>{translate key="submission.versioning.versionHistory"}</h2>
		<ul>
		{foreach from=$previousRevisions item=title key=revision}
			<li><a href="{url op="version" path=$article->getBestArticleId($currentJournal)|to_array:$revision escape=false}">{$title}</a></li>
		{/foreach}
		</ul>
	</div>
{/if}
