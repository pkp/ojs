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

<ul class="plain">
	<li>&#187; <a href="{url page="about" op="submissions" anchor="onlineSubmissions"}">{translate key="about.onlineSubmissions"}</a></li>
	{if !empty($journalSettings.authorGuidelines)}<li>&#187; <a href="{url page="about" op="submissions" anchor="authorGuidelines"}">{translate key="about.authorGuidelines"}</a></li>{/if}
	{if !empty($journalSettings.copyrightNotice)}<li>&#187; <a href="{url page="about" op="submissions" anchor="copyrightNotice"}">{translate key="about.copyrightNotice"}</a></li>{/if}
	{if !empty($journalSettings.privacyStatement)}<li>&#187; <a href="{url page="about" op="submissions" anchor="privacyStatement"}">{translate key="about.privacyStatement"}</a></li>{/if}
</ul>

<a name="onlineSubmissions"></a><h3>{translate key="about.onlineSubmissions"}</h3>
<p>
	{translate key="about.onlineSubmissions.haveAccount" journalTitle=$siteTitle|escape}<br />
	<a href="{url page="login"}" class="action">{translate key="about.onlineSubmissions.login"}</a>
</p>
<p>
	{translate key="about.onlineSubmissions.needAccount"}<br />
	<a href="{url page="user" op="register"}" class="action">{translate key="about.onlineSubmissions.registration"}</a>
</p>
<p>{translate key="about.onlineSubmissions.registrationRequired"}</p>

<div class="separator">&nbsp;</div>

{if !empty($journalSettings.authorGuidelines)}
<a name="authorGuidelines"></a><h3>{translate key="about.authorGuidelines"}</h3>
<p>{$journalSettings.authorGuidelines|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

<a name="submissionPreparationChecklist"></a><h3>{translate key="about.submissionPreparationChecklist"}</h3>
<ol>
	{foreach from=$journalSettings.submissionChecklist item=checklistItem}
		<li>{$checklistItem.content|nl2br}</li>	
	{/foreach}
</ol>

<div class="separator">&nbsp;</div>

{if !empty($journalSettings.copyrightNotice)}
<a name="copyrightNotice"></a><h3>{translate key="about.copyrightNotice"}</h3>
<p>{$journalSettings.copyrightNotice|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if !empty($journalSettings.privacyStatement)}<a name="privacyStatement"></a><h3>{translate key="about.privacyStatement"}</h3>
<p>{$journalSettings.privacyStatement|nl2br}</p>
{/if}

{include file="common/footer.tpl"}
