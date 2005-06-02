{translate key="email.multipart"}

--{$mimeBoundary}
Content-Type: text/plain; charset=us-ascii
Content-Transfer-Encoding: 7bit

{$body}

{$issue->getIssueIdentification()}
{translate key="issue.toc"}

{foreach name=sections from=$publishedArticles item=section key=sectionTitle}
{$sectionTitle}
--------
{foreach from=$section item=article}
{$article->getArticleTitle()}{if $article->getPages()} ({$article->getPages()}){/if}

{foreach from=$article->getAuthors() item=author name=authorList}
	{$author->getFullName()}{if !$smarty.foreach.authorList.last},{/if}{/foreach}

{/foreach}


{/foreach}
{literal}{$templateSignature}{/literal}

--{$mimeBoundary}
Content-Type: text/html; charset=us-ascii
Content-Transfer-Encoding: 7bit

<html>
	<head>
		<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
		{foreach from=$stylesheets item=cssFile}
		<link rel="stylesheet" href="{$baseUrl}/styles/{$cssFile}" type="text/css" />
		{/foreach}
		{if $pageStyleSheet}
		<link rel="stylesheet" href="{$publicFilesDir}/{$pageStyleSheet.uploadName}" type="text/css" />
		{/if}
		</head>
	<body>

	<p>{$body|escape|nl2br}</p>

		<h3>{$issue->getIssueIdentification()}<br />{translate key="issue.toc"}</h3>
		{foreach name=sections from=$publishedArticles item=section key=sectionTitle}
			<h4>{$sectionTitle}</h4>

			{foreach from=$section item=article}
				<table width="100%">
					<tr>
						<td>{$article->getArticleTitle()}</td>
						<td align="right">
							<a href="{$pageUrl}/article/view/{$article->getBestArticleId($currentJournal)}" class="file">{translate key="issue.abstract"}</a>
							{if (!$subscriptionRequired || $article->getAccessStatus() || $subscribedUser)}
								{foreach from=$article->getGalleys() item=galley name=galleyList}
									&nbsp;
									<a href="{$pageUrl}/article/view/{$article->getBestArticleId($currentJournal)}/{$galley->getGalleyId()}" class="file">{$galley->getLabel()}</a>
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
						<td align="right">{if $article->getPages()}{$article->getPages()}{else}&nbsp;{/if}</td>
						</tr>
					</table>
				{/foreach}
			{if !$smarty.foreach.sections.last}
				<div class="separator"></div>
			{/if}
		{/foreach}
		<pre>{literal}{$templateSignature}{/literal}</pre>
	</body>
</html>

--{$mimeBoundary}--
