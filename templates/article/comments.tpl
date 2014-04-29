{**
 * templates/article/comments.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View -- Comments component.
 *
 *}
{if $comments}
<div class="separator"></div>
<div id="commentsOnArticle">
<h4>{translate key="comments.commentsOnArticle"}</h4>

<ul>
{foreach from=$comments item=comment}
{assign var=poster value=$comment->getUser()}
	<li>
		<a href="{url page="comment" op="view" path=$article->getId()|to_array:$galleyId:$comment->getId()}" target="_parent">{$comment->getTitle()|escape|default:"&nbsp;"}</a>
		{if $comment->getChildCommentCount()==1}
			{translate key="comments.oneReply"}
		{elseif $comment->getChildCommentCount()>0}
			{translate key="comments.nReplies" num=$comment->getChildCommentCount()}
		{/if}

		<br/>

		{if $poster}
			{url|assign:"publicProfileUrl" page="user" op="viewPublicProfile" path=$poster->getId()}
			{translate key="comments.authenticated" userName=$poster->getFullName()|escape publicProfileUrl=$publicProfileUrl}
		{elseif $comment->getPosterName()}
			{translate key="comments.anonymousNamed" userName=$comment->getPosterName()|escape}
		{else}
			{translate key="comments.anonymous"}
		{/if}
		({$comment->getDatePosted()|date_format:$dateFormatShort})
	</li>
{/foreach}
</ul>

<a href="{url page="comment" op="view" path=$article->getId()|to_array:$galleyId}" class="action" target="_parent">{translate key="comments.viewAllComments"}</a>

{assign var=needsSeparator value=1}
</div>
{/if}{* $comments *}

{if $postingAllowed}
	{if $needsSeparator}
		&nbsp;|&nbsp;
	{else}
		<br/><br/>
	{/if}
	<a class="action" href="{url page="comment" op="add" path=$article->getId()|to_array:$galleyId}" target="_parent">{translate key="rt.addComment"}</a>
{/if}

