<?php

/**
 * @file tests/jobs/doi/DepositIssueTest.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Tests for deposit issue job.
 */

namespace APP\tests\jobs\doi;

use APP\doi\Repository as DoiRepository;
use APP\issue\Repository as IssueRepository;
use APP\jobs\doi\DepositIssue;
use Mockery;
use PKP\tests\PKPTestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @see https://docs.phpunit.de/en/9.6/annotations.html#runtestsinseparateprocesses
 */
class DepositIssueTest extends PKPTestCase
{
    /**
     * base64_encoded serializion from OJS 3.4.0
     */
    protected string $serializedJobData = <<<END
    O:25:"APP\\jobs\\doi\\DepositIssue":5:{s:10:"\0*\0issueId";i:1;s:10:"\0*\0context";O:19:"APP\\journal\\Journal":6:{s:5:"_data";a:73:{s:2:"id";i:1;s:7:"urlPath";s:15:"publicknowledge";s:7:"enabled";b:1;s:3:"seq";i:1;s:13:"primaryLocale";s:2:"en";s:14:"currentIssueId";i:1;s:19:"automaticDoiDeposit";b:0;s:12:"contactEmail";s:20:"rvaca@mailinator.com";s:11:"contactName";s:11:"Ramiro Vaca";s:18:"copyrightYearBasis";s:5:"issue";s:31:"copySubmissionAckPrimaryContact";b:1;s:7:"country";s:2:"IS";s:8:"currency";s:3:"CAD";s:17:"defaultReviewMode";i:2;s:18:"disableSubmissions";b:0;s:15:"doiCreationTime";s:20:"copyEditCreationTime";s:9:"doiPrefix";s:7:"10.1234";s:13:"doiSuffixType";s:7:"default";s:13:"doiVersioning";b:0;s:19:"editorialStatsEmail";b:1;s:14:"emailSignature";s:141:"<br><br>—<br><p>This is an automated message from <a href="http://localhost/index.php/publicknowledge">Journal of Public Knowledge</a>.</p>";s:19:"enableAnnouncements";b:1;s:15:"enabledDoiTypes";a:2:{i:0;s:11:"publication";i:1;s:5:"issue";}s:10:"enableDois";b:1;s:19:"enableGeoUsageStats";s:8:"disabled";s:27:"enableInstitutionUsageStats";b:0;s:9:"enableOai";b:1;s:16:"isSushiApiPublic";b:1;s:12:"itemsPerPage";i:25;s:8:"keywords";s:7:"request";s:14:"mailingAddress";s:49:"123 456th Street Burnaby, British Columbia Canada";s:13:"membershipFee";d:0;s:16:"notifyAllAuthors";b:1;s:12:"numPageLinks";i:10;s:19:"numWeeksPerResponse";i:4;s:17:"numWeeksPerReview";i:4;s:10:"onlineIssn";s:9:"0378-5955";s:17:"paymentPluginName";s:13:"PaypalPayment";s:15:"paymentsEnabled";b:1;s:9:"printIssn";s:9:"0378-5955";s:14:"publicationFee";d:0;s:20:"publisherInstitution";s:24:"Public Knowledge Project";s:18:"purchaseArticleFee";d:0;s:18:"registrationAgency";s:14:"dataciteplugin";s:25:"submissionAcknowledgement";s:10:"allAuthors";s:20:"submitWithCategories";b:0;s:20:"supportedFormLocales";a:2:{i:0;s:2:"en";i:1;s:5:"fr_CA";}s:16:"supportedLocales";a:2:{i:0;s:2:"en";i:1;s:5:"fr_CA";}s:26:"supportedSubmissionLocales";a:2:{i:0;s:2:"en";i:1;s:5:"fr_CA";}s:12:"supportEmail";s:20:"rvaca@mailinator.com";s:11:"supportName";s:11:"Ramiro Vaca";s:15:"themePluginPath";s:7:"default";s:12:"abbreviation";a:1:{s:2:"en";s:25:"publicknowledgeJ Pub Know";}s:7:"acronym";a:1:{s:2:"en";s:6:"JPKJPK";}s:16:"authorGuidelines";a:2:{s:2:"en";s:1209:"<p>Authors are invited to make a submission to this journal. All submissions will be assessed by an editor to determine whether they meet the aims and scope of this journal. Those considered to be a good fit will be sent for peer review before determining whether they will be accepted or rejected.</p><p>Before making a submission, authors are responsible for obtaining permission to publish any material included with the submission, such as photos, documents and datasets. All authors identified on the submission must consent to be identified as an author. Where appropriate, research should be approved by an appropriate ethics committee in accordance with the legal requirements of the study's country.</p><p>An editor may desk reject a submission if it does not meet minimum standards of quality. Before submitting, please ensure that the study design and research argument are structured and articulated properly. The title should be concise and the abstract should be able to stand on its own. This will increase the likelihood of reviewers agreeing to review the paper. When you're satisfied that your submission meets this standard, please follow the checklist below to prepare your submission.</p>";s:5:"fr_CA";s:44:"##default.contextSettings.authorGuidelines##";}s:17:"authorInformation";a:2:{s:2:"en";s:586:"Interested in submitting to this journal? We recommend that you review the <a href="http://localhost/index.php/publicknowledge/about">About the Journal</a> page for the journal's section policies, as well as the <a href="http://localhost/index.php/publicknowledge/about/submissions#authorGuidelines">Author Guidelines</a>. Authors need to <a href="http://localhost/index.php/publicknowledge/user/register">register</a> with the journal prior to submitting or, if already registered, can simply <a href="http://localhost/index.php/index/login">log in</a> and begin the five-step process.";s:5:"fr_CA";s:715:"Intéressé-e à soumettre à cette revue ? Nous vous recommandons de consulter les politiques de rubrique de la revue à la page <a href="http://localhost/index.php/publicknowledge/about">À propos de la revue</a> ainsi que les <a href="http://localhost/index.php/publicknowledge/about/submissions#authorGuidelines">Directives aux auteurs</a>. Les auteurs-es doivent <a href="http://localhost/index.php/publicknowledge/user/register">s'inscrire</a> auprès de la revue avant de présenter une soumission, ou s'ils et elles sont déjà inscrits-es, simplement <a href="http://localhost/index.php/publicknowledge/login">ouvrir une session</a> et accéder au tableau de bord pour commencer les 5 étapes du processus.";}s:19:"beginSubmissionHelp";a:2:{s:2:"en";s:611:"<p>Thank you for submitting to the Journal of Public Knowledge. You will be asked to upload files, identify co-authors, and provide information such as the title and abstract.<p><p>Please read our <a href="http://localhost/index.php/publicknowledge/about/submissions" target="_blank">Submission Guidelines</a> if you have not done so already. When filling out the forms, provide as many details as possible in order to help our editors evaluate your work.</p><p>Once you begin, you can save your submission and come back to it later. You will be able to review and correct any information before you submit.</p>";s:5:"fr_CA";s:42:"##default.submission.step.beforeYouBegin##";}s:14:"clockssLicense";a:2:{s:2:"en";s:271:"This journal utilizes the CLOCKSS system to create a distributed archiving system among participating libraries and permits those libraries to create permanent archives of the journal for purposes of preservation and restoration. <a href="https://clockss.org">More...</a>";s:5:"fr_CA";s:315:"Cette revue utilise le système CLOCKSS pour créer un système d'archivage distribué parmi les bibliothèques participantes et permet à ces bibliothèques de créer des archives permanentes de la revue à des fins de conservation et de reconstitution. <a href="https://clockss.org">En apprendre davantage... </a>";}s:16:"contributorsHelp";a:2:{s:2:"en";s:504:"<p>Add details for all of the contributors to this submission. Contributors added here will be sent an email confirmation of the submission, as well as a copy of all editorial decisions recorded against this submission.</p><p>If a contributor can not be contacted by email, because they must remain anonymous or do not have an email account, please do not enter a fake email address. You can add information about this contributor in a message to the editor at a later step in the submission process.</p>";s:5:"fr_CA";s:40:"##default.submission.step.contributors##";}s:13:"customHeaders";a:1:{s:2:"en";s:41:"<meta name="pkp" content="Test metatag.">";}s:11:"description";a:2:{s:2:"en";s:123:"<p>The Journal of Public Knowledge is a peer-reviewed quarterly publication on the subject of public access to science.</p>";s:5:"fr_CA";s:146:"<p>Le Journal de Public Knowledge est une publication trimestrielle évaluée par les pairs sur le thème de l'accès du public à la science.</p>";}s:11:"detailsHelp";a:2:{s:2:"en";s:92:"<p>Please provide the following details to help us manage your submission in our system.</p>";s:5:"fr_CA";s:35:"##default.submission.step.details##";}s:17:"forTheEditorsHelp";a:2:{s:2:"en";s:278:"<p>Please provide the following details in order to help our editorial team manage your submission.</p><p>When entering metadata, provide entries that you think would be most helpful to the person managing your submission. This information can be changed before publication.</p>";s:5:"fr_CA";s:41:"##default.submission.step.forTheEditors##";}s:20:"librarianInformation";a:2:{s:2:"en";s:361:"We encourage research librarians to list this journal among their library's electronic journal holdings. As well, it may be worth noting that this journal's open source publishing system is suitable for libraries to host for their faculty members to use with journals they are involved in editing (see <a href="https://pkp.sfu.ca/ojs">Open Journal Systems</a>).";s:5:"fr_CA";s:434:"Nous incitons les bibliothécaires à lister cette revue dans leur fonds de revues numériques. Aussi, il peut être pertinent de mentionner que ce système de publication en libre accès est conçu pour être hébergé par les bibliothèques de recherche pour que les membres de leurs facultés l'utilisent avec les revues dans lesquelles elles ou ils sont impliqués (voir <a href="https://pkp.sfu.ca/ojs">Open Journal Systems</a>).";}s:13:"lockssLicense";a:2:{s:2:"en";s:273:"This journal utilizes the LOCKSS system to create a distributed archiving system among participating libraries and permits those libraries to create permanent archives of the journal for purposes of preservation and restoration. <a href="https://www.lockss.org">More...</a>";s:5:"fr_CA";s:314:"Cette revue utilise le système LOCKSS pour créer un système de distribution des archives parmi les bibliothèques participantes et afin de permettre à ces bibliothèques de créer des archives permanentes pour fins de préservation et de restauration. <a href="https://lockss.org">En apprendre davantage...</a>";}s:4:"name";a:2:{s:2:"en";s:27:"Journal of Public Knowledge";s:5:"fr_CA";s:36:"Journal de la connaissance du public";}s:16:"openAccessPolicy";a:2:{s:2:"en";s:176:"This journal provides immediate open access to its content on the principle that making research freely available to the public supports a greater global exchange of knowledge.";s:5:"fr_CA";s:217:"Cette revue fournit le libre accès immédiat à son contenu se basant sur le principe que rendre la recherche disponible au public gratuitement facilite un plus grand échange du savoir, à l'échelle de la planète.";}s:16:"privacyStatement";a:2:{s:2:"en";s:206:"<p>The names and email addresses entered in this journal site will be used exclusively for the stated purposes of this journal and will not be made available for any other purpose or to any other party.</p>";s:5:"fr_CA";s:193:"<p>Les noms et courriels saisis dans le site de cette revue seront utilisés exclusivement aux fins indiquées par cette revue et ne serviront à aucune autre fin, ni à toute autre partie.</p>";}s:17:"readerInformation";a:2:{s:2:"en";s:654:"We encourage readers to sign up for the publishing notification service for this journal. Use the <a href="http://localhost/index.php/publicknowledge/user/register">Register</a> link at the top of the home page for the journal. This registration will result in the reader receiving the Table of Contents by email for each new issue of the journal. This list also allows the journal to claim a certain level of support or readership. See the journal's <a href="http://localhost/index.php/publicknowledge/about/submissions#privacyStatement">Privacy Statement</a>, which assures readers that their name and email address will not be used for other purposes.";s:5:"fr_CA";s:716:"Nous invitons les lecteurs-trices à s'inscrire pour recevoir les avis de publication de cette revue. Utiliser le lien <a href="http://localhost/index.php/publicknowledge/user/register">S'inscrire</a> en haut de la page d'accueil de la revue. Cette inscription permettra au,à la lecteur-trice de recevoir par courriel le sommaire de chaque nouveau numéro de la revue. Cette liste permet aussi à la revue de revendiquer un certain niveau de soutien ou de lectorat. Voir la <a href="http://localhost/index.php/publicknowledge/about/submissions#privacyStatement">Déclaration de confidentialité</a> de la revue qui certifie aux lecteurs-trices que leur nom et leur courriel ne seront pas utilisés à d'autres fins.";}s:10:"reviewHelp";a:2:{s:2:"en";s:368:"<p>Review the information you have entered before you complete your submission. You can change any of the details displayed here by clicking the edit button at the top of each section.</p><p>Once you complete your submission, a member of our editorial team will be assigned to review it. Please ensure the details you have entered here are as accurate as possible.</p>";s:5:"fr_CA";s:34:"##default.submission.step.review##";}s:17:"searchDescription";a:1:{s:2:"en";s:116:"The Journal of Public Knowledge is a peer-reviewed quarterly publication on the subject of public access to science.";}s:19:"submissionChecklist";a:2:{s:2:"en";s:591:"<p>All submissions must meet the following requirements.</p><ul><li>This submission meets the requirements outlined in the <a href="http://localhost/index.php/publicknowledge/about/submissions">Author Guidelines</a>.</li><li>This submission has not been previously published, nor is it before another journal for consideration.</li><li>All references have been checked for accuracy and completeness.</li><li>All tables and figures have been numbered and labeled.</li><li>Permission has been obtained to publish all photos, datasets and other material provided with this submission.</li></ul>";s:5:"fr_CA";s:37:"##default.contextSettings.checklist##";}s:15:"uploadFilesHelp";a:2:{s:2:"en";s:249:"<p>Provide any files our editorial team may need to evaluate your submission. In addition to the main work, you may wish to submit data sets, conflict of interest statements, or other supplementary files if these will be helpful for our editors.</p>";s:5:"fr_CA";s:39:"##default.submission.step.uploadFiles##";}}s:20:"_hasLoadableAdapters";b:0;s:27:"_metadataExtractionAdapters";a:0:{}s:25:"_extractionAdaptersLoaded";b:0;s:26:"_metadataInjectionAdapters";a:0:{}s:24:"_injectionAdaptersLoaded";b:0;}s:9:"\0*\0agency";O:43:"APP\\plugins\\generic\\datacite\\DatacitePlugin":4:{s:10:"pluginPath";s:24:"plugins/generic/datacite";s:14:"pluginCategory";s:7:"generic";s:7:"request";N;s:58:"\0APP\\plugins\\generic\\datacite\\DatacitePlugin\0_exportPlugin";N;}s:10:"connection";s:8:"database";s:5:"queue";s:5:"queue";}
    END;

    /**
     * Test job is a proper instance
     */
    public function testUnserializationGetProperDepositIssueJobInstance(): void
    {
        $this->assertInstanceOf(
            DepositIssue::class,
            unserialize($this->serializedJobData)
        );
    }

    /**
     * Ensure that a serialized job can be unserialized and executed
     */
    public function testRunSerializedJob(): void
    {
        // need to mock request so that a valid context information is set and can be retrived
        $this->mockRequest();

        $this->mockGuzzleClient();

        /** @var DepositIssue $depositIssueJob */
        $depositIssueJob = unserialize($this->serializedJobData);

        $issueMock = Mockery::mock(\APP\issue\Issue::class)
            ->makePartial()
            ->shouldReceive([
                'getDatePublished' => \Carbon\Carbon::today()
                    ->startOfYear()
                    ->format('Y-m-d H:i:s'),
                'getStoredPubId' => '10.1234/mq45t6723',
            ])
            ->shouldReceive('getData')
            ->with('doiObject')
            ->andReturn(new \PKP\doi\Doi())
            ->getMock();

        $issueDaoMock = Mockery::mock(\APP\issue\DAO::class, [
            new \PKP\services\PKPSchemaService()
        ])
            ->makePartial()
            ->shouldReceive([
                'fromRow' => $issueMock
            ])
            ->withAnyArgs()
            ->getMock();

        $issueRepoMock = Mockery::mock(app(IssueRepository::class))
            ->makePartial()
            ->shouldReceive([
                'get' => $issueMock,
            ])
            ->withAnyArgs()
            ->set('dao', $issueDaoMock)
            ->getMock();

        app()->instance(IssueRepository::class, $issueRepoMock);

        $doiRepoMock = Mockery::mock(app(DoiRepository::class))
            ->makePartial()
            ->shouldReceive([
                'edit'
            ])
            ->withAnyArgs()
            ->getMock();

        app()->instance(DoiRepository::class, $doiRepoMock);

        $this->assertNull($depositIssueJob->handle());
    }
}
