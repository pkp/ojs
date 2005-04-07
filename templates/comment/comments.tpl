{**
 * comments.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display comments on an article.
 *
 * $Id$
 *}

{assign var=pageTitle value="comments.readerComments"}

{include file="common/header.tpl"}

{if $comment}
	{assign var=user value=$comment->getUser()}
	<h3>{$comment->getTitle()|escape}</h3>
	<h4>{if $user}{$user->getFullName()}{else}{translate key="comments.anonymous"}{/if} ({$comment->getDatePosted()|date_format:$dateFormatShort})</h4>
	{if ($journalRt && $journalRt->getAddComment()) || $parent}
		<p>
		{if $parent}
			{assign var=parentId value=$parent->getCommentId()}
			<i>{translate key="comments.inResponseTo" url="$pageUrl/comment/view/$articleId/$parentId" title=$parent->getTitle()|escape}</i><br />
		{/if}
		{if $journalRt && $journalRt->getAddComment()}
			<a href="{$pageUrl}/comment/add/{$articleId}/{$comment->getCommentId()}" class="action">{translate key="comments.reply"}</a><br />
		{/if}
		</p>
	{/if}
	{$comment->getBody()|escape|nl2br}

<div class="separator"></div>

{if $comments}<h3>{translate key="comments.replies"}</h3>{/if}

{/if}

{foreach from=$comments item=child}

{assign var=user value=$child->getUser()}
<h4><a href="{$pageUrl}/comment/view/{$articleId}/{$child->getCommentId()}" target="_parent">{$child->getTitle()|escape}</a></h4>
<h5>{if $user}{$user->getFullName()}{else}{translate key="comments.anonymous"}{/if} ({$child->getDatePosted()|date_format:$dateFormatShort})</h5>

<p>{$child->getBody()|escape|nl2br|truncate:300:"..."}</p>

{assign var=grandChildren value=$child->getChildren()}
{if $grandChildren}<ul>{/if}
{foreach from=$child->getChildren() item=grandChild}
	<li>
		<a href="{$pageUrl}/comment/view/{$articleId}/{$grandChild->getCommentId()}" target="_parent">{$grandChild->getTitle()|escape}</a>
		{if $grandChild->getChildCommentCount()==1}{translate key="comments.oneReply"}{elseif $grandChild->getChildCommentCount()>0}{translate key="comments.nReplies" num=$grandChild->getChildCommentCount()}{/if}<br/>
		{if $poster}{$poster->getFullName()|escape}{else}{translate key="comments.anonymous"}{/if}&nbsp;({$grandChild->getDatePosted()|date_format:$dateFormatShort})
	</li>
{/foreach}
{if $grandChildren}
	</ul>
{/if}

{/foreach}

{include file="common/footer.tpl"}

