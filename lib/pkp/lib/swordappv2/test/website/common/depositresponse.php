<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>SWORD v2 exerciser - POST</title>
        <link rel='stylesheet' type='text/css' media='all' href='../../css/style.css' />
    </head>
    <body>

        <div id="header">
            <h1>SWORD v2 exerciser</h1>
        </div>

        <p>
            Options:
        </p>

        <div class="section">

            Deposited ID: <?php echo $response->sac_idl ?>;

            <ul>
                <li>
                    EDIT-IRI: <?php echo $response->sac_edit_iri; ?>
                    <form action="../../delete/container/" method="post">
                        <input type="hidden" name="editiri" value="<?php echo $response->sac_edit_iri; ?>" />
                        <input type="submit" value="DELETE CONTAINER" />
                    </form>
                </li>
                <li>
                    SE-IRI: <?php echo $response->sac_se_iri; ?>
                    <form action="../../post/complete/" method="post" target="_new">
                        <input type="hidden" name="seiri" value="<?php echo $response->sac_se_iri; ?>" />
                        <input type="submit" value="COMPLETE INCOMPLETE DEPOSIT" />
                    </form>
                </li>
                <li>
                    EDIT-MEDIA: <?php echo $response->sac_edit_media_iri; ?>
                    <form action="../../delete/media/" method="post">
                        <input type="hidden" name="editmediairi" value="<?php echo $response->sac_edit_media_iri; ?>" />
                        <input type="submit" value="DELETE MEDIA" />
                    </form>
                </li>
                <li>Statement (Atom): <?php echo $response->sac_state_iri_atom; ?>
                    <form action="../../get/uri/" method="post" target="_new">
                        <input type="hidden" name="uri" value="<?php echo $response->sac_state_iri_atom; ?>" />
                        <input type="submit" value="SHOW STATEMENT" />
                    </form>
                </li>
                <li>Statement (OAI-ORE): <?php echo $response->sac_state_iri_ore; ?>
                <form action="../../get/uri/" method="post" target="_new">
                        <input type="hidden" name="uri" value="<?php echo $response->sac_state_iri_ore; ?>" />
                        <input type="submit" value="SHOW STATEMENT" />
                    </form>
                </li>
            </ul>

        </div>

        <div class="section">
            <h2>Response:</h2>
            <pre>Status code: <?php echo $response->sac_status; ?></pre>
            <pre><?php echo xml_pretty_printer($response->sac_xml); ?></pre>
        </div>

        <div id="footer">
                <a href='../../'>Home</a> | Based on the <a href="http://github.com/stuartlewis/swordappv2-php-library/">swordappv2-php-library</a>
        </div>
    </body>
</html>