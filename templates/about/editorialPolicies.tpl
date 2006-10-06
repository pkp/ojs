{**
 * editorialPolicies.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal / Editorial Policies.
 * 
 * $Id$
 *}

{assign var="pageTitle" value="about.editorialPolicies"}
{include file="common/header.tpl"}

<ul class="plain">
	{if !empty($journalSettings.focusScopeDesc)}<li>&#187; <a href="{url op="editorialPolicies" anchor="focusAndScope"}">{translate key="about.focusAndScope"}</a></li>{/if}
	<li>&#187; <a href="{url op="editorialPolicies" anchor="sectionPolicies"}">{translate key="about.sectionPolicies"}</a></li>
	{if !empty($journalSettings.reviewPolicy)}<li>&#187; <a href="{url op="editorialPolicies" anchor="peerReviewProcess"}">{translate key="about.peerReviewProcess"}</a></li>{/if}
	{if !empty($journalSettings.pubFreqPolicy)}<li>&#187; <a href="{url op="editorialPolicies" anchor="publicationFrequency"}">{translate key="about.publicationFrequency"}</a></li>{/if}
	{if empty($journalSettings.enableSubscriptions) && !empty($journalSettings.openAccessPolicy)}<li>&#187; <a href="{url op="editorialPolicies" anchor="openAccessPolicy"}">{translate key="about.openAccessPolicy"}</a></li>{/if}
	{if !empty($journalSettings.enableSubscriptions) && !empty($journalSettings.enableAuthorSelfArchive)}<li>&#187; <a href="{url op="editorialPolicies" anchor="authorSelfArchivePolicy"}">{translate key="about.authorSelfArchive"}</a></li>{/if}
	{if !empty($journalSettings.enableSubscriptions) && !empty($journalSettings.enableDelayedOpenAccess)}<li>&#187; <a href="{url op="editorialPolicies" anchor="delayedOpenAccessPolicy"}">{translate key="about.delayedOpenAccess"}</a></li>{/if}
	{if $journalSettings.enableLockss && !empty($journalSettings.lockssLicense)}<li>&#187; <a href="{url op="editorialPolicies" anchor="archiving"}">{translate key="about.archiving"}</a></li>{/if}
	{foreach key=key from=$journalSettings.customAboutItems item=customAboutItem}
		{if !empty($customAboutItem.title)}
			<li>&#187; <a href="{url op="editorialPolicies" anchor=custom`$key`}">{$customAboutItem.title|escape}</a></li>
		{/if}
	{/foreach}
</ul>

{if !empty($journalSettings.focusScopeDesc)}
<a name="focusAndScope"></a><h3>{translate key="about.focusAndScope"}</h3>
<p>{$journalSettings.focusScopeDesc|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

<a name="sectionPolicies"></a><h3>{translate key="about.sectionPolicies"}</h3>
{foreach from=$sections item=section}{if !$section->getHideAbout()}
	<h4>{$section->getSectionTitle()}</h4>
	{if strlen($section->getPolicy()) > 0}
		<p>{$section->getPolicy()|nl2br}</p>
	{/if}

	{assign var="hasEditors" value=0}
	{foreach from=$sectionEditors item=sectionSectionEditors key=key}
		{if $key == $section->getSectionId()}
			{foreach from=$sectionSectionEditors item=sectionEditor}
				{if 0 == $hasEditors++}
				{translate key="user.role.editors"}
				<ul class="plain">
				{/if}
				<li>{$sectionEditor->getFirstName()|escape} {$sectionEditor->getLastName()|escape}{if strlen($sectionEditor->getAffiliation()) > 0}, {$sectionEditor->getAffiliation()|escape}{/if}</li>
			{/foreach}
		{/if}
	{/foreach}
	{if $hasEditors}</ul>{/if}

	<table class="plain" width="60%">
		<tr>
			<td width="33%">{if !$section->getEditorRestricted()}{icon name="checked"}{else}{icon name="unchecked"}{/if} {translate key="manager.sections.open"}</td>
			<td width="33%">{if $section->getMetaIndexed()}{icon name="checked"}{else}{icon name="unchecked"}{/if} {translate key="manager.sections.indexed"}</td>
			<td width="34%">{if $section->getMetaReviewed()}{icon name="checked"}{else}{icon name="unchecked"}{/if} {translate key="manager.sections.reviewed"}</td>
		</tr>
	</table>
{/if}{/foreach}

<div class="separator">&nbsp;</div>

{if !empty($journalSettings.reviewPolicy)}<a name="peerReviewProcess"></a><h3>{translate key="about.peerReviewProcess"}</h3>
<p>{$journalSettings.reviewPolicy|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if !empty($journalSettings.pubFreqPolicy)}
<a name="publicationFrequency"></a><h3>{translate key="about.publicationFrequency"}</h3>
<p>{$journalSettings.pubFreqPolicy|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if empty($journalSettings.enableSubscriptions) && !empty($journalSettings.openAccessPolicy)} 
<a name="openAccessPolicy"></a><h3>{translate key="about.openAccessPolicy"}</h3>
<p>{$journalSettings.openAccessPolicy|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if !empty($journalSettings.enableSubscriptions) && !empty($journalSettings.enableAuthorSelfArchive)} 
<a name="authorSelfArchivePolicy"></a><h3>{translate key="about.authorSelfArchive"}</h3> 
<p>{$journalSettings.authorSelfArchivePolicy|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if !empty($journalSettings.enableSubscriptions) && !empty($journalSettings.enableDelayedOpenAccess)}
<a name="delayedOpenAccessPolicy"></a><h3>{translate key="about.delayedOpenAccess"}</h3> 
<p>{translate key="about.delayedOpenAccessDescription1"} {$journalSettings.delayedOpenAccessDuration} {translate key="about.delayedOpenAccessDescription2"}</p>
{if !empty($journalSettings.delayedOpenAccessPolicy)}
	<p>{$journalSettings.delayedOpenAccessPolicy|nl2br}</p>
{/if}

<div class="separator">&nbsp;</div>
{/if}

{if $journalSettings.enableLockss && !empty($journalSettings.lockssLicense)}
<a name="archiving"></a><h3>{translate key="about.archiving"}</h3>
<p>{$journalSettings.lockssLicense|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{foreach key=key from=$journalSettings.customAboutItems item=customAboutItem name=customAboutItems}
	{if !empty($customAboutItem.title)}
		<a name="custom{$key}"></a><h3>{$customAboutItem.title|escape}</h3>
		<p>{$customAboutItem.content|nl2br}</p>
		{if !$smarty.foreach.customAboutItems.last}<div class="separator">&nbsp;</div>{/if}
	{/if}
{/foreach}

{include file="common/footer.tpl"}
