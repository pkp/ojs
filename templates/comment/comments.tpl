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

{if $comment}
	{assign var=pageTitle value="comments.readerComments"}
	{assign var=pageCrumbTitleTranslated value=$comment->getTitle()|escape|truncate:50:"..."}
{else}
	{assign var=pageTitle value="comments.readerComments"}
{/if}

{include file="common/header.tpl"}

{if $comment}
	{assign var=user value=$comment->getUser()}
	<h3>{$comment->getTitle()|escape}</h3>
	<h4>{if $user}{translate key="comments.authenticated" userName=$comment->getPosterName()|escape}{elseif $comment->getPosterName()}{translate key="comments.anonymousNamed" userName=$comment->getPosterName()|escape}{else}{translate key="comments.anonymous"}{/if} ({$comment->getDatePosted()|date_format:$dateFormatShort})</h4>

	<p>

	{if $parent}
		{assign var=parentId value=$parent->getCommentId()}
		<i>{translate key="comments.inResponseTo" url="$pageUrl/comment/view/$articleId/$parentId" title=$parent->getTitle()|escape}</i><br />
	{/if}

	{if $comment->getPosterEmail()}
		{assign_translate var=emailReply key="comments.emailReply"}
		{mailto text=$emailReply encode="javascript" address=$comment->getPosterEmail() subject=$comment->getTitle() extra='class="action"'}&nbsp;&nbsp;
	{/if}

	{if $enableComments=='unauthenticated' || (($enableComments=='authenticated' || $enableComments=='anonymous') && $isUserLoggedIn)}
		<a href="{$pageUrl}/comment/add/{$articleId}/{$galleyId}/{$comment->getCommentId()}" class="action">{translate key="comments.postReply"}</a>&nbsp;&nbsp;
	{/if}

	{if $isManager}
		<a href="{$pageUrl}/comment/delete/{$articleId}/{$galleyId}/{$comment->getCommentId()}" {if $comment->getChildCommentCount()!=0}onClick="return confirm('{translate|escape:"javascript" key="comments.confirmDeleteChildren"}')" {/if}class="action">{translate key="comments.delete"}</a>
	{/if}

	<br />
	</p>

	{$comment->getBody()|escape|nl2br}

<br /><br />

<div class="separator"></div>

<br />

{if $comments}<h3>{translate key="comments.replies"}</h3>{/if}

{/if}

{foreach from=$comments item=child}

{assign var=user value=$child->getUser()}
{assign var=childId value=$child->getCommentId()}
<h4><a href="{$pageUrl}/comment/view/{$articleId}/{$galleyId}/{$childId}" target="_parent">{$child->getTitle()|escape}</a></h4>
<h5>{if $user}{translate key="comments.authenticated" userName=$child->getPosterName()|escape}{elseif $child->getPosterName()}{translate key="comments.anonymousNamed" userName=$child->getPosterName()|escape}{else}{translate key="comments.anonymous"}{/if} ({$child->getDatePosted()|date_format:$dateFormatShort})</h5>
{if $child->getPosterEmail()}
	{assign_translate var=emailReply key="comments.emailReply"}
	{mailto text=$emailReply encode="javascript" address=$child->getPosterEmail() subject=$child->getTitle() extra='class="action"'}&nbsp;&nbsp;
{/if}

{if $enableComments=='unauthenticated' || (($enableComments=='authenticated' || $enableComments=='anonymous') && $isUserLoggedIn)}
	<a href="{$pageUrl}/comment/add/{$articleId}/{$galleyId}/{$childId}" class="action">{translate key="comments.postReply"}</a>&nbsp;&nbsp;
{/if}
{if $isManager}
	<a href="{$pageUrl}/comment/delete/{$articleId}/{$galleyId}/{$child->getCommentId()}" {if $child->getChildCommentCount()!=0}onClick="return confirm('{translate|escape:"javascript" key="comments.confirmDeleteChildren"}')" {/if}class="action">{translate key="comments.delete"}</a>
{/if}
<br />

{assign_translate var=readMore key="comments.readMore"}
{assign var=moreLink value="<a href=\"$pageUrl/comment/view/$articleId/$galleyId/$childId\">$readMore</a>"}
<p>{$child->getBody()|escape|nl2br|truncate:300:"... $moreLink"}</p>

{assign var=grandChildren value=$child->getChildren()}
{if $grandChildren}<ul>{/if}
{foreach from=$child->getChildren() item=grandChild}
{assign var=user value=$grandChild->getUser()}
	<li>
		<a href="{$pageUrl}/comment/view/{$articleId}/{$galleyId}/{$grandChild->getCommentId()}" target="_parent">{$grandChild->getTitle()|escape}</a>
		{if $grandChild->getChildCommentCount()==1}{translate key="comments.oneReply"}{elseif $grandChild->getChildCommentCount()>0}{translate key="comments.nReplies" num=$grandChild->getChildCommentCount()}{/if}<br/>
		{if $user}{translate key="comments.authenticated" userName=$grandChild->getPosterName()|escape}{elseif $grandChild->getPosterName()}{translate key="comments.anonymousNamed" userName=$grandChild->getPosterName()|escape}{else}{translate key="comments.anonymous"}{/if} ({$grandChild->getDatePosted()|date_format:$dateFormatShort})
	</li>
{/foreach}
{if $grandChildren}
	</ul>
{/if}

{foreachelse}
	{if !$comment}
		{translate key="comments.noComments"}
	{/if}
{/foreach}

{include file="common/footer.tpl"}

