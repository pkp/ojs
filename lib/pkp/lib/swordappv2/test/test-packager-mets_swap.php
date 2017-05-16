<?php
    require('../packager_mets_swap.php');
	
	// The location of the files (without final directory)
    $test_rootin = 'test-files';

	// The location of the files
    $test_dirin = 'mets_swap';

    // The location to write the package out to
    $test_rootout = 'test-files';

    // The filename to save the package as
    $test_fileout = 'mets_swap_package.zip';

    // The type (e.g. ScholarlyWork)
    $test_type = 'http://purl.org/eprint/entityType/ScholarlyWork';

    // The title of the item
    $test_title = 'SWORD: Simple Web-service Offering Repository Deposit';

    // The abstract of the item
    $test_abstract = 'This article offers a twofold introduction to the JISC-funded SWORD Project which ran for eight months in mid-2007. Firstly it presents an overview of the methods and madness that led us to where we currently are, including a timeline of how this work moved through an informal working group to a lightweight, distributed project. Secondly, it offers an explanation of the outputs produced for the SWORD Project and their potential benefits for the repositories community. SWORD, which stands for Simple Web service Offering Repository Deposit, came into being in March 2007 but was preceded by a series of discussions and activities which have contributed much to the project, known as the \'Deposit API\'. The project itself was funded under the JISC Repositories and Preservation Programme, Tools and Innovation strand, with the over-arching aim of scoping, defining, developing and testing a standard mechanism for depositing into repositories and other systems. The motivation was that there was no standard way of doing this currently and increasingly scenarios were arising that might usefully leverage such a standard.';

    // Creators
    $test_creators = array('Allinson, Julie', 'Francois, Sebastien', 'Lewis, Stuart');

    // Citation
	$test_citation = 'Allinson, J., Francois, S., Lewis, S. SWORD: Simple Web-service Offering Repository Deposit, Ariadne, Issue 54, January 2008. Online at http://www.ariadne.ac.uk/issue54/';
	
	// Identifier
    $test_identifier = 'http://www.ariadne.ac.uk/issue54/allinson-et-al/';

    // Date made available
    $test_dateavailable = '2008-01';

    // Copyright holder
    $test_copyrightholder = 'Julie Allinson, Sebastien Francois, Stuart Lewis';

    // Custodian
    $test_custodian = 'Julie Allinson, Sebastien Francois, Stuart Lewis';

	// Status statement
	$test_statusstatement = 'http://purl.org/eprint/status/PeerReviewed';

	// File name
	$test_file = 'SWORD Ariadne Jan 2008.pdf';

	// MIME type of file
	$test_mimetype = 'application/pdf';
	
	$test_packager = new PackagerMetsSwap($test_rootin, $test_dirin, $test_rootout, $test_fileout);
	$test_packager->setCustodian($test_custodian);
	$test_packager->setType($test_type);
	$test_packager->setTitle($test_title);
	$test_packager->setAbstract($test_abstract);
	foreach ($test_creators as $test_creator) {
		$test_packager->addCreator($test_creator);
	}
	$test_packager->setIdentifier($test_identifier);
	$test_packager->setDateAvailable($test_dateavailable);
	$test_packager->setStatusStatement($test_statusstatement);
	$test_packager->setCopyrightHolder($test_copyrightholder);
	$test_packager->setCitation($test_citation);
	$test_packager->addFile($test_file, $test_mimetype);

	$test_packager->create();
?>
