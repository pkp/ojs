<?php

    function sac_clean($string) {
        // Tidy a string
        $string = str_replace("\n", "", $string);
        $string = str_replace("\r", "", $string);
        $string = str_replace("\t", "", $string);

        $string = preg_replace('/\t/', '', $string);
        $string = preg_replace('/\s\s+/', ' ', $string);
        $string = trim($string);
        return $string;
    }

    function base64chunk($in, $out) {
        // Base64 encode, then chunk, a file
        // By 'MitMacher' from http://www.php.net/manual/en/function.base64-encode.php#92762
        
        $fh_in = fopen($in, 'rb');
        $fh_out = fopen($out, 'ab');

        $cache = '';
        $eof = false;

        while (true) {
            if (!$eof) {
                if (!feof($fh_in)) {
                    $row = fgets($fh_in, 4096);
                } else {
                    $row = '';
                    $eof = true;
                }
            }

            if ($cache !== '') {
                $row = $cache . $row;
            }
            elseif ($eof) {
                break;
            }

            $b64 = base64_encode($row);
            $put = '';

            if (strlen($b64) < 76) {
                if ($eof) {
                    $put = $b64 . "\r\n";
                    $cache = '';
                } else {
                    $cache = $row;
                }
            } elseif (strlen($b64) > 76) {
                do {
                    $put .= substr($b64, 0, 76) . "\r\n";
                    $b64 = substr($b64, 76);
                } while (strlen($b64) > 76);

                $cache = base64_decode($b64);
            } else {
                if (!$eof && $b64{75} == '=') {
                   $cache = $row;
                } else {
                    $put = $b64."\r\n";
                    $cache = '';
                }
            }

            if ($put !== '') {
                fputs($fh_out, $put);
            }
        }

        fclose($fh_in);
    }

?>
