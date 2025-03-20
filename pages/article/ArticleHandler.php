<?php

/**
 * @file pages/article/ArticleHandler.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleHandler
 *
 * @ingroup pages_article
 *
 * @brief Handle requests for article functions.
 *
 */

namespace APP\pages\article;

use APP\facades\Repo;
use APP\handler\Handler;

class ArticleHandler extends Handler
{
    /**
     * Redirect calls to articlesHandler
     * https://github.com/pkp/pkp-lib/issues/5932
     *
     * @deprecated 3.6
     *
     */
    public function view($args, $request)
    {
        header('HTTP/1.1 301 Moved Permanently');
        $request->redirect(null, 'articles', 'view', $args);
    }

    /**
     * Download an article file
     * For deprecated OJS 2.x URLs; see https://github.com/pkp/pkp-lib/issues/1541
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function viewFile($args, $request)
    {
        $articleId = $args[0] ?? 0;
        $galleyId = $args[1] ?? 0;
        $submissionFileId = isset($args[2]) ? (int) $args[2] : 0;
        header('HTTP/1.1 301 Moved Permanently');
        $request->redirect(null, null, 'download', [$articleId, $galleyId, $submissionFileId]);
    }

    /**
     * Download a supplementary file.
     * For deprecated OJS 2.x URLs; see https://github.com/pkp/pkp-lib/issues/1541
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function downloadSuppFile($args, $request)
    {
        $articleId = $args[0] ?? 0;
        $article = Repo::submission()->get($articleId);
        if (!$article) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        $suppId = $args[1] ?? 0;

        $submissionFiles = Repo::submissionFile()
            ->getCollector()
            ->filterBySubmissionIds([$article->getId()])
            ->getMany();

        foreach ($submissionFiles as $submissionFile) {
            if ($submissionFile->getData('old-supp-id') == $suppId) {
                $articleGalleys = Repo::galley()->getCollector()
                    ->filterByPublicationIds([$article->getCurrentPublication()->getId()])
                    ->getMany();

                foreach ($articleGalleys as $articleGalley) {
                    $galleyFile = Repo::submissionFile()->get($articleGalley->getData('submissionFileId'));
                    if ($galleyFile && $galleyFile->getData('submissionFileId') == $submissionFile->getId()) {
                        header('HTTP/1.1 301 Moved Permanently');
                        $request->redirect(null, null, 'download', [$articleId, $articleGalley->getId(), $submissionFile->getId()]);
                    }
                }
            }
        }
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    /**
     * Redirect calls to articlesHandler
     * https://github.com/pkp/pkp-lib/issues/5932
     *
     * @deprecated 3.6
     *
     */
    public function download($args, $request)
    {
        header('HTTP/1.1 301 Moved Permanently');
        $request->redirect(null, 'articles', 'download', $args);
    }
}
