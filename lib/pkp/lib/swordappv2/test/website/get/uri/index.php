<?php

    // Load the PHP library
    include_once('../../../../swordappclient.php');
    include_once('../../utils.php');

    // Try and load the file
    session_start();
    $client = new SWORDAPPClient();
    $response = $client->get($_POST['uri'], $_SESSION['u'], $_SESSION['p'], $_SESSION['obo']);

?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>SWORD v2 exerciser - GET a URI</title>
        <link rel='stylesheet' type='text/css' media='all' href='../../css/style.css' />
    </head>
    <body>

        <div id="header">
            <h1>SWORD v2 exerciser</h1>
        </div>

        <?php if (!empty($errormsg)) { ?><div class="error"><?php echo $errormsg; ?></div><?php } ?>

        <div class="section">
            <h2>Response:</h2>
            <pre><?php echo xml_pretty_printer($response); ?></pre>
        </div>

        <div id="footer">
                <a href='../../'>Home</a> | Based on the <a href="http://github.com/stuartlewis/swordappv2-php-library/">swordappv2-php-library</a>
        </div>
    </body>
</html>