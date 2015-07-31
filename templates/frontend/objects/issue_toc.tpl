{**
 * templates/frontend/objects/issue_toc.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View of an Issue which displays a full table of contents list.
 *
 * Expects:
 *  $issue Issue The issue
 *  $galleys IssueGalleys Galleys for the entire issue
 *  $hasAccess bool Passed to objects/galleys.tpl
 *  $

 *  $restrictOnlyPdf
 *
 *}
<div class="obj_issue_toc">

    <div class=""

    <div class="description">
        {$issue->getLocalizedDescription()|strip_unsafe_html|nl2br}
    </div>


</div>
