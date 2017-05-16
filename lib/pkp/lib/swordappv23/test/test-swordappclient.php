<?php
    // Test the V2 PHP client implementation using the Simple SWORD Server (SSS)

	// The URL of the service document
	$testurl = "http://localhost/sss/sd-uri";
	
	// The user (if required)
	$testuser = "sword";
	
	// The password of the user (if required)
	$testpw = "sword";
	
	// The on-behalf-of user (if required)
	//$testobo = "user@swordapp.com";

	// The URL of the example deposit collection
	$testdepositurl = "http://localhost/sss/col-uri/da9b9feb-4266-446a-8847-46f6c30b2ff0";

	// The test atom entry to deposit
	$testatomentry = "test-files/atom_multipart/atom";

	// The second test atom entry to deposit
	$testatomentry2 = "test-files/atom_multipart/atom2";

	// The test atom multipart file to deposit
	$testmultipart = "test-files/atom_multipart_package";

	// The second test file to deposit
	$testmultipart2 = "test-files/atom_multipart_package2";

	// The test content zip file to deposit
	$testzipcontentfile = "test-files/atom_multipart_package2.zip";

    // A plain content file
    $testextrafile = "test-files/swordlogo.jpg";

    // The file type of the extra file
    $testextrafiletype = "image/jpg";

	// The content type of the test file
	$testcontenttype = "application/zip";

	// The packaging format of the test file
	$testpackaging = "http://purl.org/net/sword/package/SimpleZip";
	
	require("../swordappclient.php");
    $testsac = new SWORDAPPClient();

	if (false) {
		print "About to request servicedocument from " . $testurl . "\n";
		if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
		$testsdr = $testsac->servicedocument($testurl, $testuser, $testpw, $testobo);
		print "Received HTTP status code: " . $testsdr->sac_status . " (" . $testsdr->sac_statusmessage . ")\n";

		if ($testsdr->sac_status == 200) {
            $testsdr->toString();
        }

        print "\n\n";
	}
	
	if (true) {
		print "About to deposit multipart file (" . $testmultipart . ") to " . $testdepositurl . "\n";
		if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
		$testdr = $testsac->depositMultipart($testdepositurl, $testuser, $testpw, $testobo, $testmultipart, $testpackaging, false);
		print "Received HTTP status code: " . $testdr->sac_status . " (" . $testdr->sac_statusmessage . ")\n";
		
		if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            $testdr->toString();
        }

        print "\n\n";

        $edit_iri = $testdr->sac_edit_iri;
        $cont_iri = $testdr->sac_content_src;
        $edit_media = $testdr->sac_edit_media_iri;
        $statement_atom = $testdr->sac_state_iri_atom;
        $statement_ore = $testdr->sac_state_iri_ore;
    }

    if (false) {
        print "About to request Atom serialisation of the deposit statement from " . $statement_atom . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testatomstatement = $testsac->retrieveAtomStatement($statement_atom, $testuser, $testpw, $testobo);

        if (($testatomstatement->sac_status >= 200) || ($testatomstatement->sac_status < 300)) {
            $testatomstatement->toString();
        }

        print "\n\n";
    }

    if (false) {
        print "About to request OAI-ORE serialisation of the deposit statement from " . $statement_ore . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testoaiore = $testsac->retrieveOAIOREStatement($statement_ore, $testuser, $testpw, $testobo);
        echo $testoaiore;

        print "\n\n";
    }

    if (false) {
        print "About to retrieve content from " . $edit_media . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testresp = $testsac->retrieveContent($edit_media, $testuser, $testpw, $testobo, "http://purl.org/net/sword/package/SimpleZip");
        // file_put_contents("temp-save.zip", $testresp);

        print "\n\n";
    }

    if (false) {
        print "About to replace content at " . $edit_media . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $status = $testsac->replaceFileContent($edit_media, $testuser, $testpw, $testobo, $testzipcontentfile, $testpackaging, $testcontenttype, false);
        print "Received HTTP status code: " . $status . "\n";
        if ($status == 204) {
            echo "Content replaced\n";
        }

        print "\n\n";
    }

    if (false) {
        print "About to replace atom entry (" . $testatomentry2 . ") to " . $edit_iri . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testdr = $testsac->replaceMetadata($edit_iri, $testuser, $testpw, $testobo, $testatomentry2, false);
        print "Received HTTP status code: " . $testdr->sac_status .
              " (" . $testdr->sac_statusmessage . ")\n";

        if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            $testdr->toString();
        }

        print "\n\n";
    }

    if (false) {
        print "About to replace multipart atom entry and file (" . $testmultipart2 . ") to " . $edit_iri . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testdr = $testsac->replaceMetadataAndFile($edit_iri, $testuser, $testpw, $testobo, $testmultipart2, $testpackaging, false);
        print "Received HTTP status code: " . $testdr->sac_status .
              " (" . $testdr->sac_statusmessage . ")\n";

        if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            $testdr->toString();
        }

        print "\n\n";
    }

    if (false) {
        print "About to add file (" . $testextrafile . ") to " . $edit_media . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testdr = $testsac->addExtraFileToMediaResource($edit_media, $testuser, $testpw, $testobo, $testextrafile, $testextrafiletype, false);
        print "Received HTTP status code: " . $testdr->sac_status .
              " (" . $testdr->sac_statusmessage . ")\n";

        if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            $testdr->toString();
        }

        print "\n\n";
    }

    if (false) {
        print "About to add package (" . $testzipcontentfile . ") to " . $edit_iri . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testdr = $testsac->addExtraPackage($edit_iri, $testuser, $testpw, $testobo, $testzipcontentfile, $testpackaging, $testcontenttype, false);
        print "Received HTTP status code: " . $testdr->sac_status .
              " (" . $testdr->sac_statusmessage . ")\n";

        if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            $testdr->toString();
        }

        print "\n\n";
    }

    if (false) {
        print "About to add atom entry (" . $testatomentry2 . ") to " . $edit_iri . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testdr = $testsac->addExtraAtomEntry($edit_iri, $testuser, $testpw, $testobo, $testatomentry2, false);
        print "Received HTTP status code: " . $testdr->sac_status .
              " (" . $testdr->sac_statusmessage . ")\n";

        if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            $testdr->toString();
        }

        print "\n\n";
    }

    if (false) {
        print "About to add multipart atom entry and file (" . $testmultipart2 . ") to " . $edit_iri . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testdr = $testsac->addExtraMultipartPackage($edit_iri, $testuser, $testpw, $testobo, $testmultipart2, $testpackaging, false);
        print "Received HTTP status code: " . $testdr->sac_status .
              " (" . $testdr->sac_statusmessage . ")\n";

        if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            $testdr->toString();
        }

        print "\n\n";
    }


    /**
    if (false) {
        print "About to complete the deposit at " . $complete_url . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testdr = $testsac->completeIncompleteDeposit($testdepositurl, $testuser, $testpw, $testobo);
        print "Received HTTP status code: " . $testdr->sac_status .
              " (" . $testdr->sac_statusmessage . ")\n";

        if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            $testdr->toString();
        }

       print "\n\n";
    }
    */

    if (false) {
        print "About to delete container at " . $edit_iri . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        try {
            $deleteresponse = $testsac->deleteContainer($edit_iri, $testuser, $testpw, $testobo);
            print " - Container successfully deleted, HTTP code 204\n";
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        print "\n\n";
    }

    if (false) {
        print "About to deposit atom entry (" . $testatomentry . ") to " . $testdepositurl . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testdr = $testsac->depositAtomEntry($testdepositurl, $testuser, $testpw, $testobo, $testatomentry, false);
        print "Received HTTP status code: " . $testdr->sac_status .
              " (" . $testdr->sac_statusmessage . ")\n";

        if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            $testdr->toString();
        }

        print "\n\n";

        $edit_iri = $testdr->sac_edit_iri;
        $cont_iri = $testdr->sac_content_src;
        $edit_media = $testdr->sac_edit_media_iri;
        $statement_atom = $testdr->sac_state_iri_atom;
        $statement_ore = $testdr->sac_state_iri_ore;
    }

    if (true) {
        print "About to retrieve deposit receipt from " . $edit_iri . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testdr = $testsac->retrieveDepositReceipt($edit_iri, $testuser, $testpw, $testobo, "http://purl.org/net/sword/package/SimpleZip");
        print "Received HTTP status code: " . $testsdr->sac_status . " (" . $testsdr->sac_statusmessage . ")\n";
        if ($testdr->sac_status == 200) {
            $testdr->toString();
        }

        print "\n\n";
    }

?>
