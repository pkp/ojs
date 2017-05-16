<?php

    // Is there are an error?
    session_start();
    if (!empty($_SESSION['error'])) {
        $errormsg = $_SESSION['error'];
        $_SESSION['error'] = '';
    }

    // Load values
    if (!empty($_SESSION['sdurl'])) { $sdurl = $_SESSION['sdurl']; } else { $sdlurl = "http://"; }
    if (!empty($_SESSION['u'])) { $u = $_SESSION['u']; } else { $u = ""; }
    if (!empty($_SESSION['p'])) { $p = $_SESSION['p']; } else { $p = ""; }
    if (!empty($_SESSION['obo'])) { $obo = $_SESSION['obo']; } else { $obo = ""; }

?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>SWORD v2 exerciser</title>
        <link rel='stylesheet' type='text/css' media='all' href='./css/style.css' />
    </head>
    <body>

        <div id="header">
            <h1>SWORD v2 exerciser</h1>
        </div>

        <?php if (!empty($errormsg)) { ?><div class="error"><?php echo $errormsg; ?></div><?php } ?>

        <p>
            Complete the following form to use the
            <a href="http://swordapp.org/sword-v2/sword-v2-specifications/">SWORD v2</a> exerciser:
        </p>

        <div class="section">

            <form action="get/sd/" method="post">

                <div class="formtextnext">

                    <label for="sdurl" class="fixedwidthlabel">Service Document IRI:</label>
                    <input type="text" id="sdurl" name="sdurl" size="60" value="<?php echo $sdurl; ?>" /><br />

                    <label for="u" class="fixedwidthlabel">Username:</label>
                    <input type="text" id="u" name="u" size="30" value="<?php echo $u; ?>" /><br />

                    <label for="p" class="fixedwidthlabel">Password:</label>
                    <input type="password" id="p" name="p" size="30" value="<?php echo $p; ?>" /><br />

                    <label for="obo" class="fixedwidthlabel">On Behalf Of:</label>
                    <input type="text" id="obo" name="obo" size="30" value="<?php echo $obo; ?>" /><br />

                    <input type="Submit" name="submit" id="submit" value="Next &gt;" />

                 </div>

            </form>

        </div>

        <div id="footer">
                Based on the <a href="http://github.com/stuartlewis/swordappv2-php-library/">swordappv2-php-library</a>
        </div>

    </body>
</html>