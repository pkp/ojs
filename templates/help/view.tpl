{**
 * view.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a help topic.
 *
 * $Id$
 *}

{assign var="pageTitle" value="help.help"}
{include file="help/header.tpl"}

<div id="topicFrame">
{include file="help/topic.tpl"}
</div>

<div id="tocSidebar">
{include file="help/toc.tpl"}
</div>

{include file="help/footer.tpl"}
