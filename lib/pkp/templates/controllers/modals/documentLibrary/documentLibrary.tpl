{**
 * templates/controllers/modals/documentLibrary/documentLibrary.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Document library
 *}

{help file="editorial-workflow.md" section="submission-library" class="pkp_help_modal"}

{url|assign:submissionLibraryGridUrl submissionId=$submission->getId() router=$smarty.const.ROUTE_COMPONENT component="grid.files.submissionDocuments.SubmissionDocumentsFilesGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="submissionLibraryGridContainer" url=$submissionLibraryGridUrl}
