{**
 * editorialPolicies.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal / Editorial Policies.
 * 
 * TODO: - Crosses and checkmarks for the section properties are currently just
 * 		text. Replace with images.
 *		 - Editor Bio link doesn't exist yet.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.editorialPolicies"}
{include file="common/header.tpl"}

<ul class="plain">
	<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#focusAndScope">{translate key="about.focusAndScope"}</a></li>
	<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#sectionPolicies">{translate key="about.sectionPolicies"}</a></li>
	<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#peerReviewProcess">{translate key="about.peerReviewProcess"}</a></li>	
	<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#publicationFrequency">{translate key="about.publicationFrequency"}</a></li>
	<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#openAccessPolicy">{translate key="about.openAccessPolicy"}</a></li>
	{foreach key=key from=$journalSettings.customAboutItems item=customAboutItem}
		{if !empty($customAboutItem.title)}
			<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#custom{$key}">{$customAboutItem.title}</a>
		{/if}
	{/foreach}
</ul>

<a name="focusAndScope"></a><h3>{translate key="about.focusAndScope"}</h3>
<p>{$journalSettings.focusScopeDesc|nl2br}</p>

<a name="sectionPolicies"></a><h3>{translate key="about.sectionPolicies"}</h3>
{foreach from=$sections item=section}
	<h4>{$section->getTitle()}</h4>
	{if strlen($section->getPolicy()) > 0}
		<p>{$section->getPolicy()|nl2br}</p>
	{/if}
	<form>
		{translate key="user.role.editors"}

		<ul class="plain">
		{foreach from=$sectionEditors item=sectionSectionEditors key=key}
			{if $key == $section->getSectionId()}
				{foreach from=$sectionSectionEditors item=sectionEditor}
					<li>{$sectionEditor->getFirstName()} {$sectionEditor->getLastName()}{if strlen($sectionEditor->getAffiliation()) > 0}, {$sectionEditor->getAffiliation()}{/if}</li>
				{/foreach}
			{/if}
		{/foreach}
		</ul>

		<input type="checkbox" disabled="disabled"{if $section->getMetaIndexed()} checked="checked"{/if}/>
		{translate key="manager.sections.openSubmissions"}
		<br/>
	</form>
{/foreach}

<a name="peerReviewProcess"></a><h3>{translate key="about.peerReviewProcess"}</h3>
<p>{$journalSettings.reviewPolicy|nl2br}</p>

<a name="publicationFrequency"></a><h3>{translate key="about.publicationFrequency"}</h3>
<p>{$journalSettings.pubFreqPolicy|nl2br}</p>

<a name="openAccessPolicy"></a><h3>{translate key="about.openAccessPolicy"}</h3>
<p>{$journalSettings.openAccessPolicy}</p>

{foreach key=key from=$journalSettings.customAboutItems item=customAboutItem}
	{if !empty($customAboutItem.title)}
		<a name="custom{$key}"></a><h3>{$customAboutItem.title}</h3>
		<p>{$customAboutItem.content|nl2br}</p>
	{/if}
{/foreach}

{include file="common/footer.tpl"}
