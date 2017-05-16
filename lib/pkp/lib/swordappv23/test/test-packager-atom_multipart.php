<?php
    require('../packager_atom_multipart.php');
	
	// The location of the files (without final directory)
    $test_rootin = 'test-files';

	// The location of the files
    $test_dirin = 'atom_multipart';

    // The location to write the package out to
    $test_rootout = 'test-files';

    // The filename to save the package as
    $test_fileout = 'atom_multipart_package';

    // Create the test package
	$test_packager = new PackagerAtomMultipart($test_rootin, $test_dirin, $test_rootout, $test_fileout);
	$test_packager->setTitle("If SWORD is the answer, what is the question? Use of the Simple Web service Offering Repository Deposit protocol");
    $test_packager->setIdentifier("10.1108/00330330910998057");
    $test_packager->addEntryAuthor("Stuart Lewis, Leonie Hayes, Vanessa Newton-Wade, Antony Corfield, Richard Davis, Tim Donohue, Scott Wilson");
    $abstract = "Purpose - To describe the repository deposit protocol, Simple Web-service Offering Repository Deposit (SWORD), its development iteration, and some of its potential use cases. In addition, seven case studies of institutional use of SWORD are provided. Approach - The paper describes the recent development cycle of the SWORD standard, with issues being identified and overcome with a subsequent version. Use cases and case studies of the new standard in action are included to demonstrate the wide range of practical uses of the SWORD standard. Implications - SWORD has many potential use cases and has quickly become the de facto standard for depositing items into repositories. By making use of a widely-supported interoperable standard, tools can be created that start to overcome some of the problems of gathering content for deposit into institutional repositories. They can do this by changing the submission process from a 'one-size-fits-all' solution, as provided by the repository's own user interface, to customised solutions for different users. Originality - Many of the case studies described in this paper are new and unpublished, and describe methods of creating novel interoperable tools for depositing items into repositories. The description of SWORD version 1.3 and its development give an insight into the processes involved with the development of a new standard.";
    $test_packager->setSummary($abstract);
    $test_packager->addMetadata("abstract", $abstract);
    $test_packager->addMetadata("available", "2009");
    $test_packager->addMetadata("bibliographicCitation", "Lewis, S., Hayes, L., Newton-Wade, V., Corfield, A., Davis, R., Donohue, T., Wilson, S., If SWORD is the answer, what is the question? Use of the Simple Web-service Offering Repository Deposit protocol, Program: electronic library and information systems, 2009, Vol 43, Issue 4, pp: 407 - 418, 10.1108/00330330910998057, Emerald Group Publishing Limited");
    $test_packager->addMetadata("creator", "Lewis, Stuart");
    $test_packager->addMetadata("creator", "Hayes, Leonie");
    $test_packager->addMetadata("creator", "Newton-Wade, Vanessa");
    $test_packager->addMetadata("creator", "Corfield, Antony");
    $test_packager->addMetadata("creator", "Davis, Richard");
    $test_packager->addMetadata("creator", "Donohue, Tim");
    $test_packager->addMetadata("creator", "Wilson, Scott");
    $test_packager->addMetadata("identifier", "10.1108/00330330910998057");
    $test_packager->addMetadata("publisher", "Emerald");
    $test_packager->addMetadata("title", "If SWORD is the answer, what is the question? Use of the Simple Web service Offering Repository Deposit protocol");
    $test_packager->addFile('if-sword-is-the-answer.pdf');
	$test_packager->create();

    // The filename to save the second package as
    $test_fileout = 'atom_multipart_package2';

    // Create the test package
	$test_packager = new PackagerAtomMultipart($test_rootin, $test_dirin, $test_rootout, $test_fileout);
	$test_packager->setTitle("A photo of Stuart Lewis");
    $test_packager->setIdentifier("facebook.com/stuartlewis");
    $test_packager->addEntryAuthor("Stuart Lewis");
    $abstract = "Stuart's profile photo from Facebook";
    $test_packager->setSummary($abstract);
    $test_packager->addMetadata("abstract", $abstract);
    $test_packager->addMetadata("available", "2009");
    $test_packager->addMetadata("creator", "Lewis, Stuart");
    $test_packager->addMetadata("identifier", "facebook.com/stuartlewis");
    $test_packager->addMetadata("title", "Stuart Lewis");
    $test_packager->addFile('stuartlewis.jpg');
	$test_packager->create();

?>
