{**
 * templates/issue/view.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View issue -- This displays the issue TOC or title page, as appropriate,
 * *without* header or footer HTML (see viewPage.tpl)
 *}
<div class="ojs_issue">

	{* Display an issue without a Table of Contents *}
	{if !$showToc && $issue}
		{if $issueId}
			{url|assign:"currentUrl" page="issue" op="view" path=$issueId|to_array:"showToc"}
		{else}
			{url|assign:"currentUrl" page="issue" op="current" path="showToc"}
		{/if}
		<ul class="menu">
			<li><a href="{$currentUrl}">{translate key="issue.toc"}</a></li>
		</ul>
		{if $coverPagePath}<div id="issueCoverImage"><a href="{$currentUrl}"><img src="{$coverPagePath|escape}{$issue->getFileName($locale)|escape}"{if $coverPageAltText != ''} alt="{$coverPageAltText|escape}"{else} alt="{translate key="issue.coverPage.altText"}"{/if}{if $width} width="{$width|escape}"{/if}{if $height} height="{$height|escape}"{/if}/></a></div>{/if}
		<div id="issueCoverDescription">{$issue->getLocalizedCoverPageDescription()|strip_unsafe_html|nl2br}</div>

	{* Display an issue with a Table of Contents *}
	{elseif $issue}

		<div class="description">
			{$issue->getLocalizedDescription()|strip_unsafe_html|nl2br}
		</div>

		{* Display galleys for the entire issue *}
		{if $issueGalleys}

			<h3 class="heading_full_issue">{translate key="issue.fullIssue"}</h3>
			<ul class="ojs_galleys">

				{if $hasAccess || $showGalleyLinks}
					{foreach from=$issueGalleys item=issueGalley}

						{* Determine galley type and URL op *}
						{if $issueGalley->isPdfGalley()}
							{assign var=type value="pdf"}
							{assign var=op value="viewIssue"}
						{else}
							{assign var=type value="file"}
							{assign var=op value="viewDownloadInterstitial"}
						{/if}

						{* Get user access flag *}
						{assign var=restricted value=0}
						{if !$hasAccess && $showGalleyLinks}
							{if $restrictOnlyPdf && type=='pdf'}
								{assign var=restricted value="1"}
							{elseif !$restrictOnlyPdf}
								{assign var=restricted value="1"}
							{/if}
						{/if}

						<li class="galley {$type}{if $restricted} restricted{/if}">

							{* Add some screen reader text to indicate if a galley is restricted *}
							{if $restricted}
								<span class="pkp_screen_reader">
									{if $purchaseArticleEnabled}
										{translate key="reader.subscriptionOrFeeAccess"}
									{else}
										{translate key="reader.subscriptionAccess"}
									{/if}
								</span>
							{/if}

							<a href="{url page="issue" op=$op path=$issue->getBestIssueId()|to_array:$issueGalley->getBestGalleyId($currentJournal)}">
								{$issueGalley->getGalleyLabel()|escape}
							</a>
						</li>
					{/foreach}
				{/if}
			</ul>
		{/if}

		{include file="issue/issue.tpl"}

		{* Display a legend describing the open/restricted access icons *}
		{if $showGalleyLinks && $showToc}
			<ul class="access_legend">
				<li class="restricted">
					{if $purchaseArticleEnabled}
						{translate key="reader.subscriptionOrFeeAccess"}
					{else}
						{translate key="reader.subscriptionAccess"}
					{/if}
				</li>
			</ul>
		{/if}

	{* Display a message if no current issue exists *}
	{else}
		{translate key="current.noCurrentIssueDesc"}
	{/if}
</div><!-- .ojs_issue -->
