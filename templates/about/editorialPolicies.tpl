{**
 * editorialPolicies.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
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

<div class="block">
	<ul>
		<li><a href="{$pageUrl}/about/editorialPolicies#focusAndScope">{translate key="about.focusAndScope"}</a></li>
		<li><a href="{$pageUrl}/about/editorialPolicies#sectionPolicies">{translate key="about.sectionPolicies"}</a></li>
		<li><a href="{$pageUrl}/about/editorialPolicies#peerReviewProcess">{translate key="about.peerReviewProcess"}</a></li>	
		<li><a href="{$pageUrl}/about/editorialPolicies#publicationFrequency">{translate key="about.publicationFrequency"}</a></li>
		<li><a href="{$pageUrl}/about/editorialPolicies#openAccessPolicy">{translate key="about.openAccessPolicy"}</a></li>
		{foreach key=key from=$journalSettings.customAboutItems item=customAboutItem}
			{if !empty($customAboutItem.title)}
				<li><a href="{$pageUrl}/about/editorialPolicies#custom{$key}">{$customAboutItem.title}</a>
			{/if}
		{/foreach}
	</ul>
</div>

<a name="focusAndScope"></a><div class="subTitle">{translate key="about.focusAndScope"}</div>
<p>{$journalSettings.focusScopeDesc}</p>

<a name="sectionPolicies"></a><div class="subTitle">{translate key="about.sectionPolicies"}</div>
{foreach from=$sections item=section}
<div class="indented">
	<div class="sectionTitle">{$section->getTitle()}</div>
	<div class="indented">
		{if strlen($section->getPolicy()) > 0}
			<p>{$section->getPolicy()|nl2br}</p>
		{/if}
		<table class="plain">
			<tr>
				<td>{if $section->getMetaIndexed()}X{else}O{/if} {translate key="manager.sections.openSubmissions"}</td>
				<td>{if $section->getAuthorIndexed()}X{else}O{/if} {translate key="manager.sections.Indexed"}</td>
			</tr>
			<tr>
				<td>{if $section->getPeerReviewed()}X{else}O{/if} {translate key="manager.sections.peerReviewed"}</td>
				<td>{if $section->getRST()}X{else}O{/if} {translate key="manager.sections.researchSupportTool"}</td>
			</tr>
		</table>
		<p>
		{translate key="user.role.editors"}:<br />
		{foreach from=$sectionEditors item=sectionSectionEditors key=key}
			{if $key == $section->getSectionId()}
				{foreach from=$sectionSectionEditors item=sectionEditor}
					{$sectionEditor->getFirstName()} {$sectionEditor->getLastName()}
					{if strlen($sectionEditor->getAffiliation()) > 0}
						, {$sectionEditor->getAffiliation()}
					{/if}
					<br />
				{/foreach}
			{/if}
		{/foreach}
		</p>
	</div>
</div>
{/foreach}

<a name="peerReviewProcess"></a><div class="subTitle">{translate key="about.peerReviewProcess"}</div>
<p>{$journalSettings.reviewPolicy}</p>

<a name="publicationFrequency"></a><div class="subTitle">{translate key="about.publicationFrequency"}</div>
<p>{$journalSettings.pubFreqPolicy}</p>

<a name="openAccessPolicy"></a><div class="subTitle">{translate key="about.openAccessPolicy"}</div>
<p>{$journalSettings.openAccessPolicy}</p>

{foreach key=key from=$journalSettings.customAboutItems item=customAboutItem}
	{if !empty($customAboutItem.title)}
		<a name="custom{$key}"></a><div class="subTitle">{$customAboutItem.title}</div>
		<p>{$customAboutItem.content}</p>
	{/if}
{/foreach}

{include file="common/footer.tpl"}
