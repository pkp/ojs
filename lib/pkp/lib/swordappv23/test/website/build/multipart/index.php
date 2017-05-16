<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>SWORD v2 exerciser - build an atom multipart deposit</title>
        <link rel='stylesheet' type='text/css' media='all' href='../../css/style.css' />
    </head>
    <body>

        <div id="header">
            <h1>SWORD v2 exerciser</h1>
        </div>

        <p>
            Complete the following form to deposit to <?php echo $_POST['durl']; ?>:
        </p>

        <div class="section">

            <form action="./make.php" method="post" enctype="multipart/form-data">

                <div class="formtextnext">

                    <label for="title" class="fixedwidthlabel">Title:</label>
                    <input type="text" id="title" name="title" size="60" /><br />

                    <label for="author" class="fixedwidthlabel">Author:</label>
                    <input type="text" id="author" name="author" size="60" /><br />

                    <label for="abstract" class="fixedwidthlabel">Abstract:</label>
                    <input type="text" id="abstract" name="abstract" size="60" /><br />

                    <label for="identifier" class="fixedwidthlabel">Identifier:</label>
                    <input type="text" id="identifier" name="identifier" size="60" /><br />

                    <label for="date" class="fixedwidthlabel">Date:</label>
                    <input type="text" id="date" name="date" size="60" /><br />

                    <label for="inprogress" class="fixedwidthlabel">In progress:</label>
                    <input type="checkbox" id="inprogress" name="inprogress" /><br />

                    <label for="file" class="fixedwidthlabel">File:</label>
                    <input type="file" id="file" name="file" size="30" /><br />

                    <input type="Submit" name="submit" id="submit" value="Next &gt;" />

                    <input type="hidden" name="durl" value="<?php echo $_POST['durl']; ?>" />
                 </div>

            </form>

        </div>

        <div id="footer">
                Based on the <a href="http://github.com/stuartlewis/swordappv2-php-library/">swordappv2-php-library</a>
        </div>

    </body>
</html>