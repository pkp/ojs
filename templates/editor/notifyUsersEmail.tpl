{translate key="email.multipart"}

--{$mimeBoundary}
Content-Type: text/plain; charset=us-ascii
Content-Transfer-Encoding: 7bit

{$body}

{translate key="issue.toc"}

{foreach name=sections from=$publishedArticles item=section key=sectionTitle}
{$sectionTitle}
--------
{foreach from=$section item=article}
{$article->getArticleTitle()} ({$article->getPages()})
{foreach from=$article->getAuthors() item=author name=authorList}
	{$author->getFullName()}{if !$smarty.foreach.authorList.last},{/if}{/foreach}

{/foreach}


{/foreach}

--{$mimeBoundary}
Content-Type: text/html; charset=us-ascii
Content-Transfer-Encoding: 7bit

<html>
	<head>
		<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
		{foreach from=$stylesheets item=cssFile}
			<link rel="stylesheet" href="{$baseUrl}/styles/{$cssFile}" type="text/css" />
		{/foreach}
	</head>
	<body>
<pre>{$body}</pre>

		<h3>{translate key="issue.toc"}</h3>
		{foreach name=sections from=$publishedArticles item=section key=sectionTitle}
			<h4>{$sectionTitle}</h4>

			{foreach from=$section item=article}
				<table width="100%">
					<tr>
						<td>{$article->getArticleTitle()}</td>
						<td align="right">
							<a href="{$pageUrl}/article/view/{$article->getArticleId()}" class="file">{translate key="issue.abstract"}</a>
							{if (!$subscriptionRequired || $article->getAccessStatus() || $subscribedUser)}
								{foreach from=$article->getGalleys() item=galley name=galleyList}
									&nbsp;
									<a href="{$pageUrl}/article/{if not $galley->isHtmlGalley()}download/{$article->getArticleId()}/{$galley->getFileId()}{else}view/{$article->getArticleId()}/{$galley->getGalleyId()}{/if}" class="file">{$galley->getLabel()}</a>
								{/foreach}
							{/if}
						</td>
					</tr>
					<tr>
						<td style="padding-left: 30px;font-style: italic;">
							{foreach from=$article->getAuthors() item=author name=authorList}
								{$author->getFullName()}{if !$smarty.foreach.authorList.last},{/if}
							{/foreach}
						</td>
						<td align="right">{$article->getPages()}</td>
						</tr>
					</table>
				{/foreach}
			{if !$smarty.foreach.sections.last}
				<div class="separator"></div>
			{/if}
		{/foreach}
	</body>
</html>

--{$mimeBoundary}--
