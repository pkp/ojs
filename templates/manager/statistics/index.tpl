{**
 * index.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the statistics & reporting page.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.statistics"}
{include file="common/header.tpl"}
<br/>

{include file="manager/statistics/statistics.tpl"}

<div class="separator">&nbsp;</div>

<br/>

{include file="manager/statistics/reportGenerator.tpl"}

{include file="common/footer.tpl"}
