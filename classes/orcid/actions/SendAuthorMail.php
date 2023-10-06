<?php

namespace APP\orcid\actions;

use APP\author\Author;
use APP\core\Application;
use APP\facades\Repo;
use APP\orcid\OrcidManager;
use Illuminate\Support\Facades\Mail;
use PKP\context\Context;
use PKP\mail\mailables\OrcidCollectAuthorId;
use PKP\mail\mailables\OrcidRequestAuthorAuthorization;

class SendAuthorMail
{
    public function __construct(
        private Author $author,
        private Context $context,
        /** @var bool $updateAuthor If true, update the author fields in the database. Use only if not called from a function, which will already update the author. */
        private bool $updateAuthor = false
    ) {
    }

    /**
     * Send mail with ORCID authorization link to the email address of the supplied Author
     */
    public function execute(): void
    {
        $context = Application::get()->getRequest()->getContext();
        if ($context === null) {
            throw new \Exception('Author ORCID emails should only be sent from a Context, never site-wide');
        }

        $contextId = $context->getId();
        $publicationId = $this->author->getData('publicationId');
        $publication = Repo::publication()->get($publicationId);
        $submission = Repo::submission()->get($publication->getData('submissionId'));

        $emailToken = md5(microtime() . $this->author->getEmail());
        $this->author->setData('orcidEmailToken', $emailToken);
        $oauthUrl = OrcidManager::buildOAuthUrl('verify', ['token' => $emailToken, 'state' => $publicationId]);

        if (OrcidManager::isMemberApiEnabled($context)) {
            $mailable = new OrcidRequestAuthorAuthorization($context, $submission, $oauthUrl);
        } else {
            $mailable = new OrcidCollectAuthorId($context, $submission, $oauthUrl);
        }

        // Set From to primary journal contact
        $mailable->from($context->getData('contactEmail'), $context->getData('contactName'));

        // Send to author
        $mailable->recipients([$this->author]);
        $emailTemplateKey = $mailable::getEmailTemplateKey();
        $emailTemplate = Repo::emailTemplate()->getByKey($contextId, $emailTemplateKey);
        $mailable->body($emailTemplate->getLocalizedData('body'))
            ->subject($emailTemplate->getLocalizedData('subject'));
        Mail::send($mailable);

        if ($this->updateAuthor) {
            Repo::author()->dao->update($this->author);
        }
    }
}
