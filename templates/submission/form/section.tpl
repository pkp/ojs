{**
 * templates/submission/form/section.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Include section placement for submissions.
 *}
{url|assign:"aboutUrl" page="about" op="submissions"}
{translate|assign:sectionTitle key="author.submit.journalSectionDescription" aboutUrl=$aboutUrl}
{fbvFormSection label="section.section" description=$sectionTitle translate=false}
	{fbvElement type="select" id="sectionId" from=$sectionOptions selected=$sectionId translate=false disabled=$readOnly size=$fbvStyles.size.MEDIUM}
{/fbvFormSection}
