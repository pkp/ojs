<?php declare(strict_types = 1);

/**
 * @defgroup pages_index Index Pages
 */

/**
 * @file pages/exercise/index.php
 *
 * Copyright (c) 2020 Simon Fraser University
 * Copyright (c) 2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_exercise
 * @brief Handle site index requests.
 *
 */

$defaultFunction = static function () {
    define('HANDLER_CLASS', 'ExerciseHandler');
    import('pages.exercise.ExerciseHandler');
};

$adminDefaultFunction = static function () {
    define('HANDLER_CLASS', 'AdminExerciseHandler');
    import('pages.exercise.AdminExerciseHandler');
};

$subPages = [
    'announcementsAdmin' => $adminDefaultFunction,
    'announcements' => $defaultFunction,
    'users' => $defaultFunction,
    'index' => $defaultFunction,
];

if (!isset($subPages[$op])) {
    Application::get()->getRequest()->getDispatcher()->handle404();
}

$subPages[$op]();

