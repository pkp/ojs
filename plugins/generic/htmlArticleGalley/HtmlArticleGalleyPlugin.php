<?php

/**
 * @file plugins/generic/htmlArticleGalley/HtmlArticleGalleyPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class HtmlArticleGalleyPlugin
 *
 * @brief Class for HtmlArticleGalley plugin
 */

namespace APP\plugins\generic\htmlArticleGalley;

use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\file\PublicFileManager;
use APP\observers\events\UsageEvent;
use APP\publication\Publication;
use APP\template\TemplateManager;
use PKP\plugins\Hook;
use PKP\submissionFile\SubmissionFile;

class HtmlArticleGalleyPlugin extends \PKP\plugins\GenericPlugin
{
    /**
     * @see Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }
        if ($this->getEnabled($mainContextId)) {
            Hook::add('ArticleHandler::view::galley', [$this, 'articleViewCallback'], Hook::SEQUENCE_LATE);
            Hook::add('ArticleHandler::download', [$this, 'articleDownloadCallback'], Hook::SEQUENCE_LATE);
        }
        return true;
    }

    /**
     * Install default settings on journal creation.
     *
     * @return string
     */
    public function getContextSpecificPluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * Get the display name of this plugin.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.generic.htmlArticleGalley.displayName');
    }

    /**
     * Get a description of the plugin.
     */
    public function getDescription()
    {
        return __('plugins.generic.htmlArticleGalley.description');
    }

    /**
     * Present the article wrapper page.
     *
     * @param string $hookName
     * @param array $args
     */
    public function articleViewCallback($hookName, $args)
    {
        $request = & $args[0];
        $issue = & $args[1];
        /** @var \PKP\galley\Galley */
        $galley = & $args[2];
        $article = & $args[3];

        if ($galley && $galley->getFileType() === 'text/html') {
            /** @var ?Publication */
            $galleyPublication = null;
            foreach ($article->getData('publications') as $publication) {
                if ($publication->getId() === $galley->getData('publicationId')) {
                    $galleyPublication = $publication;
                    break;
                }
            }
            $templateMgr = TemplateManager::getManager($request);
            $templateMgr->assign([
                'issue' => $issue,
                'article' => $article,
                'galley' => $galley,
                'isLatestPublication' => $article->getData('currentPublicationId') === $galley->getData('publicationId'),
                'galleyPublication' => $galleyPublication,
                'submissionFile' => $galley->getFile(),
            ]);
            $templateMgr->display($this->getTemplateResource('display.tpl'));

            return true;
        }

        return false;
    }

    /**
     * Present rewritten article HTML.
     *
     * @param string $hookName
     * @param array $args
     */
    public function articleDownloadCallback($hookName, $args)
    {
        $article = & $args[0];
        $galley = & $args[1];
        $fileId = & $args[2];
        $request = Application::get()->getRequest();

        if (!$galley) {
            return false;
        }

        $submissionFile = $galley->getFile();
        if ($galley->getData('submissionFileId') == $fileId && $submissionFile->getData('mimetype') === 'text/html' && $galley->getData('submissionFileId') == $submissionFile->getId()) {
            if (!Hook::call('HtmlArticleGalleyPlugin::articleDownload', [$article,  &$galley, &$fileId])) {
                echo $this->_getHTMLContents($request, $galley);
                $returner = true;
                Hook::call('HtmlArticleGalleyPlugin::articleDownloadFinished', [&$returner]);
                $publication = Repo::publication()->get($galley->getData('publicationId'));
                // This part is the same as in ArticleHandler::initialize():
                if ($publication->getData('issueId')) {
                    // TODO: Previously fetched issue from cache. Reimplement when caching added.
                    $issue = Repo::issue()->get($publication->getData('issueId'));
                    $issue = $issue->getJournalId() == $article->getData('contextId') ? $issue : null;
                }
                event(new UsageEvent(Application::ASSOC_TYPE_SUBMISSION_FILE, $request->getContext(), $article, $galley, $submissionFile, $issue));
            }
            return true;
        }

        return false;
    }

    /**
     * Return string containing the contents of the HTML file.
     * This function performs any necessary filtering, like image URL replacement.
     *
     * @param \APP\core\Request $request
     * @param \PKP\galley\Galley $galley
     *
     * @return string
     */
    protected function _getHTMLContents($request, $galley)
    {
        $submissionFile = $galley->getFile();
        $submissionId = $submissionFile->getData('submissionId');
        $contents = Services::get('file')->fs->read($submissionFile->getData('path'));

        // Replace media file references
        $embeddableFiles = Repo::submissionFile()
            ->getCollector()
            ->filterByAssoc(
                Application::ASSOC_TYPE_SUBMISSION_FILE,
                [$submissionFile->getId()]
            )
            ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_DEPENDENT])
            ->includeDependentFiles()
            ->getMany();

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
            [$this, '_handleOjsUrl'],
            $contents
        );
        if ($contents === null) {
            error_log('PREG error in ' . __FILE__ . ' line ' . __LINE__ . ': ' . preg_last_error());
        }

        $templateMgr = TemplateManager::getManager($request);
        $contents = $templateMgr->loadHtmlGalleyStyles($contents, $embeddableFiles->toArray());

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

    public function _handleOjsUrl($matchArray)
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
            switch (strtolower_codesafe($urlParts[0])) {
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
                            $urlParts[1],
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
                            $urlParts[1],
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

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\htmlArticleGalley\HtmlArticleGalleyPlugin', '\HtmlArticleGalleyPlugin');
}
