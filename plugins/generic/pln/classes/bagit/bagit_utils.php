<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This is a PHP implementation of the {@link 
 * https://wiki.ucop.edu/display/Curation/BagIt BagIt specification}. Really, 
 * it is a port of {@link https://github.com/ahankinson/pybagit/ PyBagIt} for 
 * PHP. This contains some useful functions.
 * 
 * PHP version 5 
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at http://www.apache.org/licenses/LICENSE-2.0 Unless required by
 * applicable law or agreed to in writing, software distributed under the
 * License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS
 * OF ANY KIND, either express or implied. See the License for the specific
 * language governing permissions and limitations under the License.
 *
 * @category  FileUtils
 * @package   Bagit
 * @author    Eric Rochester <erochest@gmail.com>
 * @author    Wayne Graham <wayne.graham@gmail.com>
 * @copyright 2011 The Board and Visitors of the University of Virginia
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version   0.2.1
 * @link      https://github.com/erochest/BagItPHP
 *
 */



/**
 * This filters an array by items that match a regex.
 *
 * @param string $regex The regex to filter by.
 * @param array  $list  The list of items to filter.
 * 
 * @return The match objects for items from $list that match $regex.
 */
function filterArrayMatches($regex, $list) 
{
    $ret = array();

    foreach ($list as $item) {
        $matches = array();
        if (preg_match($regex, $item, $matches)) {
            array_push($ret, $matches);
        }
    }

    return $ret;
}

/**
 * This tests whether a string ends with another string.
 *
 * @param string $main   The primary string to test.
 * @param string $suffix The string to test against the end of the other.
 * 
 * @return True if $suffix occurs at the end of $main.
 */
function endsWith($main, $suffix) 
{
    $len = strlen($suffix);
    return substr_compare($main, $suffix, -$len, $len) === 0;
}

/**
 * This recursively lists the contents of a directory. This doesn't return 
 * hidden files.
 * 
 * @param string $dir The name of the directory to list.
 * 
 * @return array A list of files in the directory.
 */
function rls($dir) 
{
    $files = array();
    $queue = array($dir);

    while (count($queue) > 0) {
        $current = array_shift($queue);

        foreach (scandir($current) as $item) {
            if ($item[0] != '.') {
                $filename = "$current/$item";

                switch (filetype($filename))
                {
                case 'file':
                    array_push($files, $filename);
                    break;
                case 'dir':
                    array_push($queue, $filename);
                    break;
                }
            }
        }
    }

    return $files;
}

/**
 * Recursively delete a directory.
 *
 * @param string $dir The directory to delete.
 *
 * @return void
 */
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

/**
 * Get a temporary name and create a directory there.
 *
 * The caller is responsible for deleting this directory and its contents.
 *
 * @param string $prefix The prefix for the temporary directory.
 *
 * @return string The name of the temporary directory.
 */
function tmpdir($prefix='bag')
{
    $dir = tempnam(sys_get_temp_dir(), $prefix);
    unlink($dir);
    return $dir;
}

/**
 * This tests whether the item is in a list of lists at the given key.
 *
 * @param array          $array The array of arrays to search.
 * @param integer/string $key   The key to search under.
 * @param anything       $item  The item to search for.
 *
 * @return True if $item is in a subarray under $key.
 */
function seenAtKey($array, $key, $item)
{
    $keys = array_keys($array);
    for ($x=0, $len=count($keys); $x<$len; $x++) {
        $sub = $array[$keys[$x]];
        if (array_key_exists($key, $sub) && $sub[$key] == $item) {
            return true;
        }
    }
    return false;
}

/**
 * This copies a URL to a file.
 *
 * @param string $url      The URL to pull.
 * @param string $filename The file name to write to.
 *
 * @return void
 */
function saveUrl($url, $filename)
{
    $curl = curl_init($url);
    $file = fopen($filename, 'w');

    curl_setopt($curl, CURLOPT_FILE, $file);
    curl_setopt($curl, CURLOPT_HEADER, 0);

    curl_exec($curl);
    curl_close($curl);

    fclose($file);
}

/**
 * This returns the first file name that exists, or the default if none do.
 *
 * @param array  $fileNames A list of file names to test for.
 * @param string $default   The default value to return. Defaults to null.
 *
 * @return string The name of the first existing file.
 */
function findFirstExisting($fileNames, $default=null)
{
    foreach ($fileNames as $fileName) {
        if (file_exists($fileName)) {
            return $fileName;
        }
    }
    return $default;
}

/**
 * This reads the data in $fileName and converts it from $fileEncoding to 
 * UTF-8.
 *
 * @param string $fileName     The file name to read.
 * @param string $fileEncoding The encoding that the text in the file is stored 
 * in.
 *
 * @return string The data in $fileName in UTF-8.
 */
function readFileText($fileName, $fileEncoding)
{
    $data = iconv($fileEncoding, 'UTF-8', file_get_contents($fileName));
    return $data;
}

/**
 * This reads the data in $fileName, converts it from $fileEncoding to UTF-8, 
 * and splits it into lines.
 *
 * @param string $fileName     The file name to read.
 * @param string $fileEncoding The encoding to that the text in file is stored 
 * in.
 *
 * @return array The lines of data in the file.
 */
function readLines($fileName, $fileEncoding)
{
    $data = readFileText($fileName, $fileEncoding);
    $lines = preg_split('/[\n\r]+/', $data, null, PREG_SPLIT_NO_EMPTY);
    return $lines;
}

/**
 * Write the data in the file, converting it from UTF-8 to tagFileEncoding.
 *
 * @param string $fileName     The name of the file to write to.
 * @param string $fileEncoding The encoding that the text in the file is stored 
 * in.
 * @param string $data         The data to write.
 *
 * @return void
 */
function writeFileText($fileName, $fileEncoding, $data)
{
    file_put_contents($fileName, iconv('UTF-8', $fileEncoding, $data));
}

/**
 * This cleans up the file name.
 *
 * @param string $filename The file name to clean up.
 *
 * @return string The cleaned up file name.
 */
function BagIt_sanitizeFileName($filename)
{
    // White space => underscores.
    $filename = preg_replace('/\s+/', '_', $filename);

    // Remove some characters.
    $filename = preg_replace(
        '/\.{2}|[~\^@!#%&\*\/:\'?\"<>\|]/',
        '',
        $filename
    );

    $forbidden = '/^(CON|PRN|AUX|NUL|COM1|COM2|COM3|COM4|COM5| ' .
        'COM6|COM7|COM8|COM9|LPT1|LPT2|LPT3|LPT4|LPT5|LPT6|' .
        'LPT7|LPT8|LPT9)$/';

    if (preg_match($forbidden, $filename)) {
        $filename = strtolower($filename);
        $suffix = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 12);
        $filename = "{$filename}_{$suffix}";
    }

    return $filename;
}
/**
 * This reads the information from the bag it file.
 *
 * @param string $filename The bagit.txt file to read.
 *
 * @return array An array triple of the version, the file encoding, and any 
 * errors encountered.
 */
function BagIt_readBagItFile($filename)
{
    $errors = array();

    if (file_exists($filename)) {
        $data = readFileText($filename, 'UTF-8');

        $versions = BagIt_parseVersionString($data);
        if ($versions === null) {
            array_push(
                $errors,
                array('bagit',
                'Error reading version information from bagit.txt file.')
            );
        }

        $fileEncoding = BagIt_parseEncodingString($data);

    } else {
        $versions = array('major' => 0, 'minor' => 96);
        $fileEncoding = 'UTF-8';
    }

    return array($versions, $fileEncoding, $errors);
}

/**
 * This parses the version string from the bagit.txt file.
 *
 * @param string $bagitFileData The contents of the bagit file.
 *
 * @return array A two-item array containing the version string as
 * integers. The keys for this array are 'major' and 'minor'.
 */
function BagIt_parseVersionString($bagitFileData)
{
    $matches = array();
    $success = preg_match(
        "/BagIt-Version: (\d+)\.(\d+)/",
        $bagitFileData,
        $matches
    );

    if ($success) {
        $major = (int)$matches[1];
        $minor = (int)$matches[2];
        if ($major === null || $minor === null) {
            throw new Exception("Invalid bagit version: '{$matches[0]}'.");
        }
        return array('major' => $major, 'minor' => $minor);
    }

    return null;
}

/**
 * This parses the encoding string from the bagit.txt file.
 *
 * @param string $bagitFileData The contents of the bagit file.
 *
 * @return string The encoding.
 */
function BagIt_parseEncodingString($bagitFileData)
{
    $matches = array();
    $success = preg_match(
        '/Tag-File-Character-Encoding: (.*)/',
        $bagitFileData,
        $matches
    );

    if ($success) {
        return $matches[1];
    }

    return null;
}

/**
 * This uncompresses a bag.
 *
 * @param string $compressedFile The file name of the compressed file.
 *
 * @return The bagDirectory.
 */
function BagIt_uncompressBag($compressedFile)
{
    // Create an output directory.
    $dir = tempnam(sys_get_temp_dir(), 'bagit_');
    unlink($dir);
    mkdir($dir, 0700);

    // Pull apart the compressed file name.
    $matches = array();
    $success = preg_match(
        '/^(.*)\.(zip|tar\.gz|tgz)$/',
        basename($compressedFile),
        $matches
    );
    if (!$success) {
        throw new ErrorException("File not compressed: $compressedFile.");
    }

    $bagBase = $matches[1];
    $ext = $matches[2];

    if ($ext == 'zip') {

        $zip = new ZipArchive();
        $zip->open($compressedFile);
        $zip->extractTo($dir);

        $datadir = $dir . '/' . $bagBase . '/data';

        if (!file_exists($datadir)) {
            mkdir($datadir, 0700);
        }

    } else if ($ext == 'tgz' || $ext == 'tar.gz') {

        $tar = new Archive_Tar($compressedFile, 'gz');
        $tar->extract($dir);

    }

    return "$dir/$bagBase";
}

/**
 * This compresses the bag into a new file.
 *
 * @param string $dirname The directory to compress.
 * @param string $output  The output file.
 * @param string $method  Either 'tgz' or 'zip'. Default is 'tgz'.
 *
 * @return string The file name for the file.
 */
function BagIt_compressBag($dirname, $output, $method='tgz')
{
    $base = basename($dirname);
    $stripLen = strlen($dirname) - strlen($base);

    if ($method == 'zip') {
        $zip = new ZipArchive();
        $zip->open($output, ZIPARCHIVE::CREATE);

        foreach (rls($dirname) as $file) {
            $zip->addFile($file, substr($file, $stripLen));
        }

        $zip->close();

    } else if ($method == 'tgz') {
        $tar = new Archive_Tar($output, 'gz');
        $tar->createModify($dirname, $base, $dirname);

    }
}

/**
 * This validates that a file or directory exists.
 *
 * @param string $filename The file name to check for.
 * @param array  &$errors  The list of errors to add the message to, if the
 * file doesn't exist.
 *
 * @return boolean True if the file does exist; false otherwise.
 */
function BagIt_validateExists($filename, &$errors)
{
    if (! file_exists($filename)) {
        $basename = basename($filename);
        array_push(
            $errors,
            array($basename, "$basename does not exist.")
        );
        return false;
    }
    return true;
}

/**
 * Parse bag info file.
 *
 * @param array $lines An array of lines from the file.
 *
 * @return array The parsed bag-info data.
 */
function BagIt_parseBagInfo($lines)
{
    $bagInfo = array();

    $prevKey = null;
    foreach ($lines as $line) {
        if (strlen($line) <= 1) {
            // Skip.
        } else if ($line[0] == ' ' || $line[0] == "\t") {
            // Continued line.
            $val = $bagInfo[$prevKey];
            if (is_array($val)) {
                $val[count($val) - 1] .= ' '. trim($line);
            } else {
                $val .= ' ' . trim($line);
            }
            $bagInfo[$prevKey] = $val;
        } else {
            list($key, $val)   = preg_split('/:\s*/', $line, 2);
            $val               = trim($val);
            $prevKey           = $key;
            $bagInfo[$prevKey] = BagIt_getAccumulatedValue(
                $bagInfo, $prevKey, $val
            );
        }
    }

    return $bagInfo;
}

/**
 * This accumulates values into an array.
 *
 * If $key exists in the array, the new value is appended to the array 
 * currently in the associative array. If the current value isn't an array, 
 * then it's wrapped in one.
 *
 * @param $map array  The associative array containing the current value.
 * @param $key string The key storing the current value.
 * @param $val mixed  The new value to add to the array under the given key.
 *
 * @return mixed $val The value either plan or appended to the end of an array 
 * containing the current values in the parent array.
 * @author Eric Rochester <erochest@virginia.edu>
 **/
function BagIt_getAccumulatedValue($map, $key, $val)
{
    if (array_key_exists($key, $map)) {
        $pval = $map[$key];
        if (is_array($pval)) {
            $pval[] = $val;
        } else {
            $pval = array( $pval, $val );
        }
        $val = $pval;
    }
    return $val;
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
