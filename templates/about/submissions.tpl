{**
 * submissions.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal / Submissions.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.submissions"}
{include file="common/header.tpl"}

<div class="block">
	<ul>
		<li><a href="{$pageUrl}/about/submissions#onlineSubmissions">{translate key="about.onlineSubmissions"}</a></li>
		<li><a href="{$pageUrl}/about/submissions#authorGuidelines">{translate key="about.authorGuidelines"}</a></li>
		<li><a href="{$pageUrl}/about/submissions#copyrightNotice">{translate key="about.copyrightNotice"}</a></li>
		<li><a href="{$pageUrl}/about/submissions#privacyStatement">{translate key="about.privacyStatement"}</a></li>
	</ul>
</div>

<a name="onlineSubmissions"></a><div class="subTitle">{translate key="about.onlineSubmissions"}</div>
<p>
	{translate key="about.onlineSubmissions.haveAccount" journalTitle=$siteTitle}<br />
	<a href="{$pageUrl}/login">{translate key="about.onlineSubmissions.login"}</a>
</p>
<p>
	{translate key="about.onlineSubmissions.needAccount"}<br />
	<a href="{$pageUrl}/user/register">{translate key="about.onlineSubmissions.registration"}</a>
</p>
<p>{translate key="about.onlineSubmissions.registrationRequired"}</p>

<a name="authorGuidelines"></a><div class="subTitle">{translate key="about.authorGuidelines"}</div>
<p>{$journalSettings.authorGuidelines}</p>

<a name="submissionPreparationChecklist"></a><div class="subTitle">{translate key="about.submissionPreparationChecklist"}</div>
<ol>
	{foreach from=$journalSettings.submissionChecklist item=checklistItem}
		<li>{$checklistItem.content}</li>	
	{/foreach}
</ol>

<a name="copyrightNotice"></a><div class="subTitle">{translate key="about.copyrightNotice"}</div>
<p>{$journalSettings.copyrightNotice}</p>

<a name="privacyStatement"></a><div class="subTitle">{translate key="about.privacyStatement"}</div>
<p>{$journalSettings.authorGuidelines}</p>

{include file="common/footer.tpl"}
