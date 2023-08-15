{**
 * templates/frontend/pages/announcements.tpl
 *
 * Copyright (c) 2013-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * About the Journal Subscriptions.
 *
 *}

{extends file="layouts/backend.tpl"}
{block name="page"}
    <pkp-header></pkp-header>

        <h1>
            {translate key="exercises.exercisesIndex"}
        </h1>

        <ul>
            <li><a href="http://localhost:8000/index.php/first-journal/exercise/announcements">Announcements</a> </li>
            <li><a href="http://localhost:8000/index.php/first-journal/exercise/users">Users</a> </li>
        </ul>
{/block}
