{translate key="email.multipart"}

--{$mimeBoundary}
Content-Type: text/plain; charset={$defaultCharset|escape}
Content-Transfer-Encoding: quoted-printable

{literal}{$templateHeader}{/literal}
{$body}

{$issue->getIssueIdentification()}
{translate key="issue.toc"}

{foreach name=sections from=$publishedSubmissions item=section key=sectionId}
{if $section.title}{$section.title}{/if}
--------
{foreach from=$section.articles item=article}
{$article->getLocalizedTitle()|strip_tags}{if $article->getPages()} ({$article->getPages()}){/if}

{foreach from=$article->getAuthors() item=author name=authorList}
	{$author->getFullName()}{if !$smarty.foreach.authorList.last},{/if}{/foreach}

{/foreach}


{/foreach}
{literal}{$templateSignature}{/literal}

--{$mimeBoundary}
Content-Type: text/html; charset={$defaultCharset|escape}
Content-Transfer-Encoding: quoted-printable

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
		{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
		{/foreach}
		</head>
	<body>

	<pre>{literal}{$templateHeader}{/literal}</pre>

	<p>{$body|escape|nl2br}</p>

		<h3>{$issue->getIssueIdentification()}<br />{translate key="issue.toc"}</h3>
		{foreach name=sections from=$publishedSubmissions item=section key=sectionId}
			{if $section.title}<h4>{$section.title|escape}</h4>{/if}

			{foreach from=$section.articles item=article}
				<table>
					<tr>
						<td>{$article->getLocalizedTitle()|strip_unsafe_html}</td>
						<td align="right">
							<a href="{url page="article" op="view" path=$article->getBestArticleId()}" class="file">{if $article->getLocalizedAbstract()}{translate key="article.abstract"}{else}{translate key="article.details"}{/if}</a>
							{if (!$subscriptionRequired || $article->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN || $subscribedUser)}
								{foreach from=$article->getGalleys() item=galley name=galleyList}
									&nbsp;
									<a href="{url page="article" op="view" path=$article->getBestArticleId()|to_array:$galley->getBestGalleyId()}" class="file">{$galley->getGalleyLabel()|escape}</a>
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
