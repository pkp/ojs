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
use APP\facades\Repo;
use APP\observers\events\UsageEvent;
use APP\plugins\generic\htmlArticleGalley\classes\HtmlGalleyHelper;
use APP\publication\Publication;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\plugins\Hook;
use PKP\plugins\interfaces\HasMetadataBlocks;
use PKP\view\MetadataBlock;
use PKP\view\MetadataBlocksRegistry;

class HtmlArticleGalleyPlugin extends \PKP\plugins\GenericPlugin implements HasMetadataBlocks
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
            Hook::add('ArticleHandler::view::galley', $this->articleViewCallback(...), Hook::SEQUENCE_LATE);
            Hook::add('ArticleHandler::download', $this->articleDownloadCallback(...), Hook::SEQUENCE_LATE);
        }
        return true;
    }

    public function registerMetadataBlocks(MetadataBlocksRegistry $blocks): void
    {
        $blocks->register(
            new MetadataBlock(
                component: 'htmlarticlegalleyplugin::metadata.galley',
                title: 'HTML Galley',
                loader: function (Publication $publication, Submission $submission) {
                    view()->share('htmlGalleyTest', '<p>This is an example of a <a href="https://example.org">custom article metadata</a> block in a plugin.</p>');
                }
            )
        );
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
        $request = &$args[0];
        $issue = &$args[1];
        /** @var \PKP\galley\Galley */
        $galley = &$args[2];
        $article = &$args[3];

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
     *
     * @hook HtmlArticleGalleyPlugin::articleDownload [[$article, &$galley, &$fileId]]
     * @hook HtmlArticleGalleyPlugin::articleDownloadFinished [[&$returner]]
     */
    public function articleDownloadCallback($hookName, $args)
    {
        $article = &$args[0];
        $galley = &$args[1];
        $fileId = &$args[2];
        $request = Application::get()->getRequest();

        if (!$galley) {
            return false;
        }

        $submissionFile = $galley->getFile();
        if ($galley->getData('submissionFileId') == $fileId && $submissionFile->getData('mimetype') === 'text/html' && $galley->getData('submissionFileId') == $submissionFile->getId()) {
            if (!Hook::call('HtmlArticleGalleyPlugin::articleDownload', [$article,  &$galley, &$fileId])) {
                echo (new HtmlGalleyHelper())->getHTMLContents($request, $galley);
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
}
