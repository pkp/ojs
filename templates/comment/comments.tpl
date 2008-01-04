{**
 * comments.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display comments on an article.
 *
 * $Id$
 *}
{if $comment}
{assign var=pageTitle value="comments.readerComments"}
{assign var=pageCrumbTitleTranslated value=$comment->getTitle()|escape|truncate:50:"..."|default:"&nbsp;"}
{else}
{assign var=pageTitle value="comments.readerComments"}
{/if}
{include file="common/header.tpl"}

{if $enableComments==COMMENTS_UNAUTHENTICATED || (($enableComments==COMMENTS_AUTHENTICATED || $enableComments==COMMENTS_ANONYMOUS) && $isUserLoggedIn)}
	{assign var=postingAllowed value=1}
{else}
	{assign var=postingAllowed value=0}
{/if}

{if $comment}
	{assign var=user value=$comment->getUser()}
	<h3>{$comment->getTitle()|escape|default:"&nbsp;"}</h3>
	<h4>{if $user}{translate key="comments.authenticated" userName=$comment->getPosterName()|escape}{elseif $comment->getPosterName()}{translate key="comments.anonymousNamed" userName=$comment->getPosterName()|escape}{else}{translate key="comments.anonymous"}{/if} ({$comment->getDatePosted()|date_format:$dateFormatShort})</h4>

	<p>

	{if $parent}
		{assign var=parentId value=$parent->getCommentId()}
		{url|assign:"url" page="comment" op="view" path=$articleId|to_array:$parentId}
		<i>{translate key="comments.inResponseTo" url=$url title=$parent->getTitle()|escape|default:"&nbsp;"}</i><br />
	{/if}

	{assign var="hasPriorAction" value=0}{* Track whether to add "|" between actions *}

	{if $comment->getPosterEmail()}
		{translate|assign:"emailReply" key="comments.emailReply"}
		{mailto text=$emailReply encode="javascript" address=$comment->getPosterEmail() subject=$comment->getTitle()|default:"&nbsp;" extra='class="action"'}
		{assign var="hasPriorAction" value=1}
	{/if}

	{if $postingAllowed}
		{if $hasPriorAction}&nbsp;|&nbsp;{/if}
		<a href="{url op="add" path=$articleId|to_array:$galleyId:$comment->getCommentId()}" class="action">{translate key="comments.postReply"}</a>
		{assign var="hasPriorAction" value=1}
	{/if}

	{if $isManager}
		{if $hasPriorAction}&nbsp;|&nbsp;{/if}
		<a href="{url op="delete" path=$articleId|to_array:$galleyId:$comment->getCommentId()}" {if $comment->getChildCommentCount()!=0}onclick="return confirm('{translate|escape:"jsparam" key="comments.confirmDeleteChildren"}')" {/if}class="action">{translate key="comments.delete"}</a>
		{assign var="hasPriorAction" value=1}
	{/if}

	<br />
	</p>

	{$comment->getBody()|strip_unsafe_html|nl2br}

<br /><br />

<div class="separator"></div>

{if $comments}<h3>{translate key="comments.replies"}</h3>{/if}

{/if}

{foreach from=$comments item=child}

{assign var=user value=$child->getUser()}
{assign var=childId value=$child->getCommentId()}
<h4><a href="{url op="view" path=$articleId|to_array:$galleyId:$childId}" target="_parent">{$child->getTitle()|escape|default:"&nbsp;"}</a></h4>
<h5>{if $user}{translate key="comments.authenticated" userName=$child->getPosterName()|escape}{elseif $child->getPosterName()}{translate key="comments.anonymousNamed" userName=$child->getPosterName()|escape}{else}{translate key="comments.anonymous"}{/if} ({$child->getDatePosted()|date_format:$dateFormatShort})</h5>

{assign var="hasPriorAction" value=0}

{if $child->getPosterEmail()}
	{translate|assign:"emailReply" key="comments.emailReply"}
	{mailto text=$emailReply encode="javascript" address=$child->getPosterEmail()|escape subject=$child->getTitle()|escape|default:"&nbsp;" extra='class="action"'}
	{assign var="hasPriorAction" value=1}
{/if}

{if $postingAllowed}
	{if $hasPriorAction}&nbsp;|&nbsp;{/if}
	<a href="{url op="add" path=$articleId|to_array:$galleyId:$childId}" class="action">{translate key="comments.postReply"}</a>
	{assign var="hasPriorAction" value=1}
{/if}
{if $isManager}
	{if $hasPriorAction}&nbsp;|&nbsp;{/if}
	<a href="{url op="delete" path=$articleId|to_array:$galleyId:$child->getCommentId()}" {if $child->getChildCommentCount()!=0}onclick="return confirm('{translate|escape:"jsparam" key="comments.confirmDeleteChildren"}')" {/if}class="action">{translate key="comments.delete"}</a>
	{assign var="hasPriorAction" value=1}
{/if}
<br />

{translate|assign:"readMore" key="comments.readMore"}
{url|assign:"moreUrl" op="view" path=$articleId|to_array:$galleyId:$childId}
{assign var=moreLink value="<a href=\"$moreUrl\">$readMore</a>"}
<p>{$child->getBody()|strip_unsafe_html|nl2br|truncate:300:"... $moreLink"}</p>

{assign var=grandChildren value=$child->getChildren()}
{if $grandChildren}<ul>{/if}
{foreach from=$child->getChildren() item=grandChild}
{assign var=user value=$grandChild->getUser()}
	<li>
		<a href="{url op="view" path=$articleId|to_array:$galleyId:$grandChild->getCommentId()}" target="_parent">{$grandChild->getTitle()|escape|default:"&nbsp;"}</a>
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

{if $postingAllowed}
	<p><a class="action" href="{url op="add" path=$articleId|to_array:$galleyId}" target="_parent">{translate key="rt.addComment"}</a></p>
{/if}

{include file="common/footer.tpl"}

