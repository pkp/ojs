{**
 * templates/submission/form/section.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Include section placement for submissions.
 *}
{url|assign:"aboutUrl" router=$smarty.const.ROUTE_PAGE page="about" op="submissions"}
{if $readOnly}{* Bug #8379 *}
	{assign var=sectionDescription value=""}
{else}
	{translate|assign:sectionDescription key="author.submit.journalSectionDescription" aboutUrl=$aboutUrl}
{/if}
{fbvFormSection label="section.section" description=$sectionDescription translate=false}
	{fbvElement type="select" id="sectionId" from=$sectionOptions selected=$sectionId translate=false disabled=$readOnly size=$fbvStyles.size.MEDIUM}
{/fbvFormSection}
