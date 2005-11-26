{translate key="email.multipart"}

--{$mimeBoundary}
Content-Type: text/plain; charset={$defaultCharset}
Content-Transfer-Encoding: quoted-printable

{$body}

{$issue->getIssueIdentification()}
{translate key="issue.toc"}

{foreach name=sections from=$publishedArticles item=section key=sectionId}
{if $section.title}{$section.title}{/if}
--------
{foreach from=$section.articles item=article}
{$article->getArticleTitle()|strip_tags}{if $article->getPages()} ({$article->getPages()}){/if}

{foreach from=$article->getAuthors() item=author name=authorList}
	{$author->getFullName()}{if !$smarty.foreach.authorList.last},{/if}{/foreach}

{/foreach}


{/foreach}
{literal}{$templateSignature}{/literal}

--{$mimeBoundary}
Content-Type: text/html; charset={$defaultCharset}
Content-Transfer-Encoding: quoted-printable

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
		{foreach name=sections from=$publishedArticles item=section key=sectionId}
			{if $section.title}<h4>{$section.title|escape}</h4>{/if}

			{foreach from=$section.articles item=article}
				<table width="100%">
					<tr>
						<td>{$article->getArticleTitle()|strip_unsafe_html}</td>
						<td align="right">
							<a href="{url page="author" op="view" path=$article->getBestArticleId($currentJournal)}" class="file">{if $section.abstractsDisabled}{translate key="article.details"}{else}{translate key="article.abstract"}{/if}</a>
							{if (!$subscriptionRequired || $article->getAccessStatus() || $subscribedUser)}
								{foreach from=$article->getGalleys() item=galley name=galleyList}
									&nbsp;
									<a href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getGalleyId()}" class="file">{$galley->getLabel()|escape}</a>
								{/foreach}
							{/if}
						</td>
					</tr>
					<tr>
						<td style="padding-left: 30px;font-style: italic;">
							{foreach from=$article->getAuthors() item=author name=authorList}
								{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
							{/foreach}
						</td>
						<td align="right">{if $article->getPages()}{$article->getPages()|escape}{else}&nbsp;{/if}</td>
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
