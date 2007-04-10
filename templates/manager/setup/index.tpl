{**
 * index.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal setup index/intro.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.journalSetup"}
{include file="common/header.tpl"}

<h3>{translate key="manager.setup.stepsToJournalSite"}</h3>

<ol>
	<li>
		<h4><a href="{url op="setup" path="1"}">{translate key="manager.setup.details"}</a></h4>
		{translate key="manager.setup.details.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="2"}">{translate key="manager.setup.policies"}</a></h4>
		{translate key="manager.setup.policies.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="3"}">{translate key="manager.setup.submissions"}</a></h4>
		{translate key="manager.setup.submissions.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="4"}">{translate key="manager.setup.management"}</a></h4>
		{translate key="manager.setup.management.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="5"}">{translate key="manager.setup.look"}</a></h4>
		{translate key="manager.setup.look.description"}<br/>
		&nbsp;
	</li>
</ol>

{include file="common/footer.tpl"}
