<?php

    // Load the PHP library
    include_once('../../../../packager_atom_multipart.php');

    // Store the values
    session_start();
    $_SESSION['durl'] = $_POST['durl'];

    // Set the location of this site on file
    include('../../config.php');

    // Construct the package
	$test_rootin = $_SESSION['location'];
    $test_dirin = 'files';
    $test_rootout = $_SESSION['location'] . '/files';
    $test_fileout = mt_rand() . '.multipart';

    // Create the test package
	$test_packager = new PackagerAtomMultipart($test_rootin, $test_dirin, $test_rootout, $test_fileout);
	$test_packager->setTitle($_POST['title']);
    $test_packager->setIdentifier($_POST['identifier']);
    $test_packager->addEntryAuthor($_POST['author']);
    $test_packager->setSummary($_POST['abstract']);
    $test_packager->addMetadata("abstract", $_POST['abstract']);
    $test_packager->addMetadata("available", $_POST['date']);
    $test_packager->addMetadata("creator", $_POST['author']);
    $test_packager->addMetadata("identifier", $_POST['identifier']);
    $test_packager->addMetadata("title", $_POST['title']);

    $filename = $test_rootin . $test_dirin . '/' . basename($_FILES['file']['name']);
    move_uploaded_file($_FILES['file']['tmp_name'], $filename);
    $test_packager->addFile(basename($_FILES['file']['name']));

    $test_packager->create();

    $_SESSION['filename'] = $test_rootout . '/' . $test_fileout;


    // Is it in progress?
    if(isset($_POST['inprogress'])) {
        $_SESSION['inprogress'] = "true";
    } else {
        $_SESSION['inprogress'] = "false";
    }

    header('Location: ../../post/multipart/');

?>