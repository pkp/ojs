{**
 * plugins/generic/thesis/metadata.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Metadata elements for thesis abstracts.
 *
 *}
	<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/" />
	<link rel="schema.ETDMS" href="http://www.ndltd.org/standards/metadata/etdms/1.0/etdms.xsd" />
	<meta name="DC.Title" content="{$thesis->getTitle()|escape}"/>
	<meta name="DC.Creator" content="{$thesis->getStudentFullName()|escape}"/>
{if $thesis->getStudentEmailPublish()}
	<meta name="DC.Creator" content="{$thesis->getStudentEmail()|escape}"/>
{/if}
	<meta name="DC.Contributor" content="{$thesis->getSupervisorFullName()|escape}"/>
	<meta name="DC.Contributor.Role" content="Advisor"/>
{foreach from=$thesis->getSubject()|explode:"; " item=dc_subject}
	<meta name="DC.Subject" content="{$dc_subject|escape}"/>
{/foreach}
{if $thesis->getCoverageGeo()}
	<meta name="DC.Coverage" content="{$thesis->getCoverageGeo()|escape}"/>
{/if}
{if $thesis->getCoverageChron()}
	<meta name="DC.Coverage" content="{$thesis->getCoverageChron()|escape}"/>
{/if}
{if $thesis->getCoverageSample()}
	<meta name="DC.Coverage" content="{$thesis->getCoverageSample()|escape}"/>
{/if}
	<meta name="DC.Description" content="Abstract Only"/>
	<meta name="DC.Description.Abstract" content="{$thesis->getAbstract()|strip_tags|escape}"/>
	<meta name="DC.Publisher" content="{$journal->getLocalizedTitle()|escape}"/>
	<meta name="DC.Date" scheme="ISO8601" content="{$thesis->getDateApproved()|date_format:"%Y-%m-%d"}"/>
	<meta name="DC.Type" content="Electronic Thesis or Dissertation"/>
	<meta name="DC.Format" scheme="IMT" content="text/html"/>
	<meta name="DC.Identifier" content="{url op="view" path=$thesis->getId()}"/>
{if $thesis->getUrl()}
	<meta name="DC.Source" content="{$thesis->getUrl()|escape}"/>
{/if}
	<meta name="DC.Language" scheme="ISO639-1" content="{$thesis->getLanguage()|escape}"/>
	<meta name="DC.Rights" content="Copyright {$thesis->getStudentFullName()|escape}"/>
{if $thesis->getDegreeName()}
	<meta name="ETDMS.Thesis.Degree.Name" content="{$thesis->getDegreeName()|escape}"/>
{/if}
	<meta name="ETDMS.Thesis.Degree.Level" content="{$thesis->getDegreeLevel()|escape}"/>
{if $thesis->getDiscipline()}
	<meta name="ETDMS.Thesis.Degree.Discipline" content="{$thesis->getDiscipline()|escape}"/>
{/if}
	<meta name="ETDMS.Thesis.Degree.Grantor" content="{$thesis->getUniversity()|escape}"/>

