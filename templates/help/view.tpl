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

{assign var="pageTitle" value=$toc->getTitle()}
{include file="help/header.tpl"}


<div>
{include file="help/topic.tpl"}
</div>

{include file="help/footer.tpl"}
