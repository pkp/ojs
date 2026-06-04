<?php

/**
 * @file plugins/generic/htmlArticleGalley/classes/HtmlGalleyHelper.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class HtmlGalleyHelper
 *
 * @brief Helper for HTML galleys. Reads an HTML galley file and returns its
 *   contents with media-file references rewritten to download URLs, ojs://
 *   URLs resolved, context styles injected, and {$issueTitle}/{$journalTitle}/
 *   {$siteTitle}/{$currentUrl} placeholders substituted. Shared between the
 *   htmlArticleGalley plugin (separate-page rendering) and themes that embed
 *   the galley body inline.
 */

namespace APP\plugins\generic\htmlArticleGalley\classes;

use APP\core\Application;
use APP\facades\Repo;
use APP\file\PublicFileManager;
use APP\template\TemplateManager;
use PKP\submissionFile\enums\MediaVariantType;
use PKP\submissionFile\SubmissionFile;

class HtmlGalleyHelper
{
    /**
     * Return the contents of the galley's HTML file with media-file URLs,
     * ojs:// URLs, context styles and template placeholders all resolved.
     *
     * @param \APP\core\Request $request
     * @param \PKP\galley\Galley $galley
     */
    public function getHTMLContents($request, $galley): string
    {
        $submissionFile = $galley->getFile();
        $submissionId = $submissionFile->getData('submissionId');
        $contents = app()->get('file')->fs->read($submissionFile->getData('path'));

        // Replace media file references
        $dependentFiles = Repo::submissionFile()
            ->getCollector()
            ->filterByAssoc(
                Application::ASSOC_TYPE_SUBMISSION_FILE,
                [$submissionFile->getId()]
            )
            ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_DEPENDENT])
            ->includeDependentFiles()
            ->getMany();

        // Publication-level media files can be referenced from any HTML galley of
        // the same publication. Embed only the web variant; high-resolution variants
        // are reserved for download/export use cases (e.g. PubMed Central).
        $mediaFiles = Repo::submissionFile()
            ->getCollector()
            ->filterByAssoc(
                Application::ASSOC_TYPE_PUBLICATION,
                [$galley->getData('publicationId')]
            )
            ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_MEDIA])
            ->getMany()
            ->filter(fn ($file) => $file->getData('variantType') !== MediaVariantType::HIGH_RESOLUTION->value);

        // Dedupe by filename (dependent, concatenated last, wins over a same-named media
        // file). collect() makes keyBy() dedupe eagerly; toArray() yields a plain array.
        $embeddableFiles = $mediaFiles->concat($dependentFiles)
            ->collect()
            ->keyBy(fn ($file) => $file->getLocalizedData('name'))
            ->toArray();

        $referredArticle = null;
        foreach ($embeddableFiles as $embeddableFile) {
            $params = [];

            if ($embeddableFile->getData('mimetype') == 'text/plain' || $embeddableFile->getData('mimetype') == 'text/css') {
                $params['inline'] = 'true';
            }

            // Ensure that the $referredArticle object refers to the article we want
            if (!$referredArticle || $referredArticle->getId() != $submissionId) {
                $referredArticle = Repo::submission()->get($submissionId);
            }
            $fileUrl = $request->url(null, 'article', 'download', [$referredArticle->getBestId(), 'version', $galley->getData('publicationId'), $galley->getBestGalleyId(), $embeddableFile->getId(), $embeddableFile->getLocalizedData('name')], $params);
            $pattern = preg_quote(rawurlencode($embeddableFile->getLocalizedData('name')), '/');

            $contents = preg_replace(
                '/([Ss][Rr][Cc]|[Hh][Rr][Ee][Ff]|[Dd][Aa][Tt][Aa])\s*=\s*"([^"]*' . $pattern . ')"/',
                '\1="' . $fileUrl . '"',
                $contents
            );
            if ($contents === null) {
                error_log('PREG error in ' . __FILE__ . ' line ' . __LINE__ . ': ' . preg_last_error());
            }

            // Replacement for Flowplayer or other Javascript
            $contents = preg_replace(
                '/[Uu][Rr][Ll]\s*\:\s*\'(' . $pattern . ')\'/',
                'url:\'' . $fileUrl . '\'',
                $contents
            );
            if ($contents === null) {
                error_log('PREG error in ' . __FILE__ . ' line ' . __LINE__ . ': ' . preg_last_error());
            }

            // Replacement for CSS url(...)
            $contents = preg_replace(
                '/[Uu][Rr][Ll]\(' . $pattern . '\)/',
                'url(' . $fileUrl . ')',
                $contents
            );
            if ($contents === null) {
                error_log('PREG error in ' . __FILE__ . ' line ' . __LINE__ . ': ' . preg_last_error());
            }

            // Replacement for other players (tested with odeo; yahoo and google player won't work w/ OJS URLs, might work for others)
            $contents = preg_replace(
                '/[Uu][Rr][Ll]=([^"]*' . $pattern . ')/',
                'url=' . $fileUrl,
                $contents
            );
            if ($contents === null) {
                error_log('PREG error in ' . __FILE__ . ' line ' . __LINE__ . ': ' . preg_last_error());
            }
        }

        // Perform replacement for ojs://... URLs
        $contents = preg_replace_callback(
            '/(<[^<>]*")[Oo][Jj][Ss]:\/\/([^"]+)("[^<>]*>)/',
            $this->handleOjsUrl(...),
            $contents
        );
        if ($contents === null) {
            error_log('PREG error in ' . __FILE__ . ' line ' . __LINE__ . ': ' . preg_last_error());
        }

        $templateMgr = TemplateManager::getManager($request);
        $contents = $templateMgr->loadHtmlGalleyStyles($contents, $embeddableFiles);

        // Perform variable replacement for journal, issue, site info
        $issue = Repo::issue()->getBySubmissionId($submissionId);

        $journal = $request->getContext();
        $site = $request->getSite();

        $paramArray = [
            'issueTitle' => $issue ? $issue->getIssueIdentification() : __('editor.article.scheduleForPublication.toBeAssigned'),
            'journalTitle' => $journal->getLocalizedName(),
            'siteTitle' => $site->getLocalizedTitle(),
            'currentUrl' => $request->getRequestUrl()
        ];

        foreach ($paramArray as $key => $value) {
            $contents = str_replace('{$' . $key . '}', $value, $contents);
        }

        return $contents;
    }

    protected function handleOjsUrl(array $matchArray): string
    {
        $request = Application::get()->getRequest();
        $url = $matchArray[2];
        $anchor = null;
        if (($i = strpos($url, '#')) !== false) {
            $anchor = substr($url, $i + 1);
            $url = substr($url, 0, $i);
        }
        $urlParts = explode('/', $url);
        if (isset($urlParts[0])) {
            switch (strtolower($urlParts[0])) {
                case 'journal':
                    $url = $request->url(
                        $urlParts[1] ?? $request->getRouter()->getRequestedContextPath($request),
                        null,
                        null,
                        null,
                        null,
                        $anchor
                    );
                    break;
                case 'article':
                    if (isset($urlParts[1])) {
                        $url = $request->url(
                            null,
                            'article',
                            'view',
                            [$urlParts[1]],
                            null,
                            $anchor
                        );
                    }
                    break;
                case 'issue':
                    if (isset($urlParts[1])) {
                        $url = $request->url(
                            null,
                            'issue',
                            'view',
                            [$urlParts[1]],
                            null,
                            $anchor
                        );
                    } else {
                        $url = $request->url(
                            null,
                            'issue',
                            'current',
                            null,
                            null,
                            $anchor
                        );
                    }
                    break;
                case 'sitepublic':
                    array_shift($urlParts);
                    $publicFileManager = new PublicFileManager();
                    $url = $request->getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath() . '/' . implode('/', $urlParts) . ($anchor ? '#' . $anchor : '');
                    break;
                case 'public':
                    array_shift($urlParts);
                    $journal = $request->getJournal();
                    $publicFileManager = new PublicFileManager();
                    $url = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($journal->getId()) . '/' . implode('/', $urlParts) . ($anchor ? '#' . $anchor : '');
                    break;
            }
        }
        return $matchArray[1] . $url . $matchArray[3];
    }
}
