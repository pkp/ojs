{**
 * templates/frontend/components/accessLegend.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief View of the legend describing access control icons (subscription, fee, etc)
 *
 * @uses $purchaseArticleEnabled bool Can they buy access to single articles?
 *}
<ul class="cmp_access_legend">
	{if $article->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN || ($article->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_ISSUE_DEFAULT && $issue->getAccessStatus() == $smarty.const.ISSUE_ACCESS_OPEN)}
		<li class="open_access">
			{translate key="reader.openAccess"}
		</li>
	{else}
		<li class="restricted">
			{if $purchaseArticleEnabled}
				{translate key="reader.subscriptionOrFeeAccess"}
			{else}
				{translate key="reader.subscriptionAccess"}
			{/if}
		</li>
	{/if}
</ul>
