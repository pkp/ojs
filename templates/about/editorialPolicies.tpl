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
	{if !empty($journalSettings.focusScopeDesc)}<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#focusAndScope">{translate key="about.focusAndScope"}</a></li>{/if}
	<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#sectionPolicies">{translate key="about.sectionPolicies"}</a></li>
	{if !empty($journalSettings.reviewPolicy)}<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#peerReviewProcess">{translate key="about.peerReviewProcess"}</a></li>{/if}
	{if !empty($journalSettings.pubFreqPolicy)}<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#publicationFrequency">{translate key="about.publicationFrequency"}</a></li>{/if}
	{if !empty($journalSettings.openAccessPolicy)}<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#openAccessPolicy">{translate key="about.openAccessPolicy"}</a></li>{/if}
	{foreach key=key from=$journalSettings.customAboutItems item=customAboutItem}
		{if !empty($customAboutItem.title)}
			<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#custom{$key}">{$customAboutItem.title|escape}</a>
		{/if}
	{/foreach}
</ul>

{if !empty($journalSettings.focusScopeDesc)}
<a name="focusAndScope"></a><h3>{translate key="about.focusAndScope"}</h3>
<p>{$journalSettings.focusScopeDesc|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

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
					<li>{$sectionEditor->getFirstName()|escape} {$sectionEditor->getLastName()|escape}{if strlen($sectionEditor->getAffiliation()) > 0}, {$sectionEditor->getAffiliation()|escape}{/if}</li>
				{/foreach}
			{/if}
		{/foreach}
		</ul>

		<p><input type="checkbox" disabled="disabled"{if $section->getMetaIndexed()} checked="checked"{/if}/>
		{translate key="manager.sections.Indexed"}</p>
	</form>
{/foreach}

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

{if !empty($journalSettings.openAccessPolicy)}
<a name="openAccessPolicy"></a><h3>{translate key="about.openAccessPolicy"}</h3>
<p>{$journalSettings.openAccessPolicy|nl2br}</p>
{if !empty($journalSettings.customAboutItems)}<div class="separator">&nbsp;</div>{/if}
{/if}

{foreach key=key from=$journalSettings.customAboutItems item=customAboutItem name=customAboutItems}
	{if !empty($customAboutItem.title)}
		<a name="custom{$key}"></a><h3>{$customAboutItem.title|escape}</h3>
		<p>{$customAboutItem.content|nl2br}</p>
		{if !$smarty.foreach.customAboutItems.last}<div class="separator">&nbsp;</div>{/if}
	{/if}
{/foreach}

{include file="common/footer.tpl"}
