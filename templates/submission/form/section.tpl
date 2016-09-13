{**
 * templates/submission/form/section.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Include section placement for submissions.
 *}
{assign var=sectionDescription value=""}
{if !$readOnly}
	{assign var=sectionDescription value="author.submit.journalSectionDescription"}
{/if}
{fbvFormSection title="section.section" required=true}
	{fbvElement type="select" id="sectionId" label=$sectionDescription from=$sectionOptions selected=$sectionId translate=false disabled=$readOnly size=$fbvStyles.size.MEDIUM}
{/fbvFormSection}
