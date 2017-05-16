<?php

    // Load the PHP library
    include_once('../../../../swordappclient.php');
    include_once('../../utils.php');

    // Store the values
    session_start();

    // Try and deposit the multipart package
    $client = new SWORDAPPClient();
    try {
        $response = $client->depositMultipart($_SESSION['durl'], $_SESSION['u'], $_SESSION['p'],
                                              $_SESSION['obo'], $_SESSION['filename'],
                                              'http://purl.org/net/sword/package/SimpleZip',
                                              'application/zip', $_SESSION['inprogress']);
        if ($response->sac_status != 201) {
            $error = 'Unable to deposit package. HTTP response code: ' .
                     $response->sac_status . ' - ' . $response->sac_statusmessage;
            $_SESSION['error'] = $error;
        } else {
            $_SESSION['error'] = '';
        }
    } catch (Exception $e) {
        $error = 'Unable to deposit package: ' . $e->getMessage();
        $_SESSION['error'] = $error;
        header('Location: ../../get/sd/');
    }

    // Show the response
    include('../../common/depositresponse.php');
?>
