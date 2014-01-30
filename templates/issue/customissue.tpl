<div class="recentlyPublished">
	<div id="whatsnew">

{assign var=displayedNewArticles value=0}
{foreach from=$recentpublishedArticles item=article}
	{assign var=displayedNewArticles value=$displayedNewArticles+1}
	{assign var=issue2 value=$issueDao->getIssueById($article->getIssueId())}
	{assign var=articleId value=$article->getArticleId()}
	{assign var=fpage value=$article->getPages()|escape|explode:"-"}
	{assign var=articleId value=$article->getArticleId()}
	{assign var=articlePath value=$article->getBestArticleId($currentJournal)}

	{if $displayedNewArticles == 1}
		<div id="whatsnew-1">
			<div id="whatsnew-1-thumb">
				<img src="{$coverPagePath|escape}{$article->getFileName($locale)}" alt="Cover image" />
				<br />
				{$article->getCoverPageAltText($locale)}
			</div><!--whatsnew-1-thumb-->
			<div class="toc-title">{$article->getArticleTitle()|strip_unsafe_html}</div>
			<div class="toc-date">{$article->getDatePublished()|date_format:"%B %e, %Y"}. Vol. {$issue2->getVolume()}({$issue2->getNumber()}){if $article->getPages()|escape}, pp.{$article->getPages()|escape}{/if}</div>

			<div class="toc-byline">
				{if (!$section.hideAuthor && $article->getHideAuthor() == 0) || $article->getHideAuthor() == 2}
					{foreach from=$article->getAuthors() item=author name=authorList}
						{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
					{/foreach}
				{else}
					&nbsp;
				{/if}
			</div><!--toc-byline-->

			<div class="toc-links">
				{foreach from=$article->getLocalizedGalleys() item=galley name=galleyList}
					<a href="{url page="article" op="view" path=$articlePath|to_array:$galley->getBestGalleyId($currentJournal)}" class="file">{$galley->getGalleyLabel()|escape}</a> |
					{/foreach}

				{if $CommentDAO->attributedCommentsExistForArticle($article->getArticleId())}
					{if $CommentDAO->attributedCommentsExistForArticle($article->getArticleId()) == 1}
						<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Comment ({$CommentDAO->attributedCommentsExistForArticle($article->getArticleId())})</a>
					{else}
						<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Comments ({$CommentDAO->attributedCommentsExistForArticle($article->getArticleId())})</a>
					{/if}
				{else}
					<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Add a comment</a>
				{/if}
			</div><!--toc-links-->

			<div style="clear: both;"></div>
		</div><!--whatsnew-1-->
		<div class="whatsnew-2-meta">
	{else}
		<div class="whatsnew-2">
			<div class="whatsnew-2-thumb">
				<img src="{$baseurl}/images/{$issue2->getVolume()}/e{$fpage[0]}/{$article->getArticleId()}-thumb.png" alt="Cover image" />
				<br />
				{$article->getCoverPageAltText($locale)}
			</div>
			<div class="toc-thumb-title">{$article->getArticleTitle()|strip_unsafe_html}</div>
			<div class="toc-thumb-date">{$article->getDatePublished()|date_format:"%B %e, %Y"}. Vol. {$issue2->getVolume()}({$issue2->getNumber()}){if $article->getPages()|escape}, pp.{$article->getPages()|escape}{/if}</div>

			<div class="toc-thumb-byline">
				{if (!$section.hideAuthor && $article->getHideAuthor() == 0) || $article->getHideAuthor() == 2}
					{foreach from=$article->getAuthors() item=author name=authorList}
						{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
					{/foreach}
				{else}
					&nbsp;
				{/if}
			</div>

			<div class="toc-links">
				{foreach from=$article->getLocalizedGalleys() item=galley name=galleyList}
					<a href="{url page="article" op="view" path=$articlePath|to_array:$galley->getBestGalleyId($currentJournal)}" class="file">{$galley->getGalleyLabel()|escape}</a> |
					{/foreach}

				{if $CommentDAO->attributedCommentsExistForArticle($article->getArticleId())}
					{if $CommentDAO->attributedCommentsExistForArticle($article->getArticleId()) == 1}
						<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Comment ({$CommentDAO->attributedCommentsExistForArticle($article->getArticleId())})</a>
					{else}
						<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Comments ({$CommentDAO->attributedCommentsExistForArticle($article->getArticleId())})</a>
					{/if}
				{else}
					<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Add a comment</a>
				{/if}
			</div>
		</div>

	{/if}
	{if !$smarty.foreach.sections.last}
	{/if}
{/foreach}

	<div style="clear: both;"></div>
	</div> <!--Close meta-->
	</div> <!-- Close Whatsnew -->
</div><!--Close recentlyPublished-->


{foreach name=sections from=$allpublishedArticles item=section key=sectionId}
{assign var=moregiven value=0}
{assign var=displayedarticles value=0}
	{if $section.title}<div class="toc-Title2">{$section.title|escape}</div>{/if}

	{foreach from=$section.articles item=article}
		{assign var=issue2 value=$issueDao->getIssueById($article->getIssueId())}
		{if $displayedarticles >= 3}
			{if $moregiven == 0}
				<div><a href="{url page="issue" op="section" path=$sectionId}">&raquo;See more articles in the <em>{$section.title|escape}</em> section</a></div>
				{assign var=moregiven value=1}
			{/if}
		{else}
			{assign var=displayedarticles value=$displayedarticles+1}
			{assign var=articlePath value=$article->getBestArticleId($currentJournal)}


			{if $article->getArticleAbstract() == ""}
				{assign var=hasAbstract value=0}
			{else}
				{assign var=hasAbstract value=1}
			{/if}

			{assign var=articleId value=$article->getArticleId()}
			{if (!$subscriptionRequired || $article->getAccessStatus() || $subscribedUser || $subscribedDomain || ($subscriptionExpiryPartial && $articleExpiryPartial.$articleId))}
				{assign var=hasAccess value=1}
			{else}
				{assign var=hasAccess value=0}
			{/if}

			 <div class="toc-title">{$article->getArticleTitle()|strip_unsafe_html}</div>

			<div class="toc-date">{$article->getDatePublished()|date_format:"%B %e, %Y"}. Vol. {$issue2->getVolume()}({$issue2->getNumber()}){if $article->getPages()|escape}, pp.{$article->getPages()|escape}{/if}</div>

			<div class="toc-byline">
				{if (!$section.hideAuthor && $article->getHideAuthor() == 0) || $article->getHideAuthor() == 2}
					{foreach from=$article->getAuthors() item=author name=authorList}
						{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
					{/foreach}
				{else}
					&nbsp;
				{/if}
			</div>
			<div class="toc-links">
				{if $hasAccess || ($subscriptionRequired && $showGalleyLinks)}
					{foreach from=$article->getLocalizedGalleys() item=galley name=galleyList}
						<a href="{url page="article" op="view" path=$articlePath|to_array:$galley->getBestGalleyId($currentJournal)}" class="file">{$galley->getGalleyLabel()|escape}</a> |
						{if $subscriptionRequired && $showGalleyLinks && $restrictOnlyPdf}
							{if $article->getAccessStatus() || !$galley->isPdfGalley()}	
								<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
							{else}
								<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
							{/if}
						{/if}
					{/foreach}
				{/if}
				{if $subscriptionRequired && $showGalleyLinks && !$restrictOnlyPdf}
					{if $article->getAccessStatus()}
						<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
					{else}
						<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
					{/if}
				{/if}
				{if $CommentDAO->attributedCommentsExistForArticle($article->getArticleId())}
					{if $CommentDAO->attributedCommentsExistForArticle($article->getArticleId()) == 1}
						<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Comment ({$CommentDAO->attributedCommentsExistForArticle($article->getArticleId())})</a>
					{else}
						<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Comments ({$CommentDAO->attributedCommentsExistForArticle($article->getArticleId())})</a>
					{/if}
				{else}
					<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Add a comment</a>
				{/if}
			</div>
			{/if}
	{/foreach}

	{if !$smarty.foreach.sections.last}
	{/if}
{/foreach}

<!--<div class="block" id="sidebarExternalFeed">
{$tadditionalHomeContent-off}
</div>
-->
