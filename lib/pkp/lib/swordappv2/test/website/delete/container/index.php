<?php

    // Load the PHP library
    include_once('../../../../swordappclient.php');
    include_once('../../utils.php');

    // Store the values
    session_start();

    // Try and delete the edit-iri package
    $client = new SWORDAPPClient();
    $response = $client->deleteContainer($_POST['editiri'], $_SESSION['u'], $_SESSION['p'], $_SESSION['obo']);

    if ($response->sac_status != 204) {
        $error = 'Unable to delete container. HTTP response code: ' .
                 $response->sac_status;
    } else {
        $_SESSION['error'] = '';
    }

?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>SWORD v2 exerciser - DELETE a container</title>
        <link rel='stylesheet' type='text/css' media='all' href='../../css/style.css' />
    </head>
    <body>

        <div id="header">
            <h1>SWORD v2 exerciser</h1>
        </div>

        <div class="section">
            <h2>Response:</h2>
            <pre>Status code: <?php echo $response->sac_status; ?></pre>
            <pre><?php
                if ($response->sac_status != 204) {
                    echo htmlentities($response->sac_xml);
                }?></pre>
        </div>

        <div id="footer">
                <a href='../../'>Home</a> | Based on the <a href="http://github.com/stuartlewis/swordappv2-php-library/">swordappv2-php-library</a>
        </div>
    </body>
</html>