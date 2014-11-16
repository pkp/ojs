<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This is a PHP implementation of the {@link
 * https://wiki.ucop.edu/display/Curation/BagIt BagIt specification}. Really,
 * it is a port of {@link https://github.com/ahankinson/pybagit/ PyBagIt} for
 * PHP.
 * 
 * PHP version 5
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0 Unless
 * required by applicable law or agreed to in writing, software distributed
 * under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 * CONDITIONS OF ANY KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
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


require_once 'Archive/Tar.php';
require_once 'bagit_fetch.php';
require_once 'bagit_manifest.php';
require_once 'bagit_utils.php';


/**
 * This is a class for all bag exceptions.
 *
 * @category   FileUtils
 * @package    Bagit
 * @subpackage Exception
 * @author     Eric Rochester <erochest@gmail.com>
 * @author     Wayne Graham <wayne.graham@gmail.com>
 * @copyright  2011 The Board and Visitors of the University of Virginia
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version    Release: <package_version>
 * @link       https://github.com/erochest/BagItPHP
 */
class BagItException extends Exception
{

}


/**
 * This is the main class for interacting with a bag.
 *
 * @category  FileUtils
 * @package   Bagit
 * @author    Eric Rochester <erochest@gmail.com>
 * @author    Wayne Graham <wayne.graham@gmail.com>
 * @copyright 2011 The Board and Visitors of the University of Virginia
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version   Release: <package_version>
 * @link      https://github.com/erochest/BagItPHP
 */
class BagIt
{

    //{{{ properties

    /**
     * The bag as passed into the constructor. This could be a directory or a
     * file name, and it may not exist.
     *
     * @var string
     */
    var $bag;

    /**
     * Absolute path to the bag directory.
     *
     * @var string
     */
    var $bagDirectory;

    /**
     * True if the bag is extended.
     *
     * @var boolean
     */
    var $extended;

    /**
     * The version information declared in 'bagit.txt'.
     *
     * @var array
     */
    var $bagVersion;

    /**
     * The tag file encoding declared in 'bagit.txt'. Default is 'utf-8'.
     *
     * @var string
     */
    var $tagFileEncoding;

    /**
     * Absolute path to the bagit file.
     *
     * @var string
     */
    var $bagitFile;

    /**
     * Information about the 'manifest-(sha1|md5).txt'.
     *
     * @var BagItManifest
     */
    var $manifest;

    /**
     * Information about the 'tagmanifest-{sha1,md5}.txt' or null.
     *
     * @var BagItManifest
     */
    var $tagManifest;

    /**
     * Information about files that need to be downloaded, listed in fetch.txt.
     *
     * @var BagItFetch
     */
    var $fetch;

    /**
     * Absolute path to the 'bag-info.txt' file or null.
     *
     * @var string
     */
    var $bagInfoFile;

    /**
     * A dictionary array containing the 'bag-info.txt' file contents.
     *
     * @var array
     */
    var $bagInfoData;

    /**
     * If the bag came from a compressed file, this contains either 'tgz' or
     * 'zip' to indicate the file's compression format.
     *
     * @var string
     */
    var $bagCompression;

    /**
     * An array of all bag validation errors. Each entries is a two-element
     * array containing the path of the file and the error message.
     *
     * @var array
     */
    var $bagErrors;

    //}}}

    //{{{ Public Methods

    /**
     * Define a new BagIt instance.
     *
     * @param string  $bag         Either a non-existing folder name (will create
     * a new bag here); an existing folder name (this will treat it as a bag
     * and create any missing files or folders needed); or an existing
     * compressed file (this will un-compress it to a temporary directory and
     * treat it as a bag).
     * @param boolean $validate    This will validate all files in the bag,
     * including running checksums on all of them. Default is false.
     * @param boolean $extended    This will ensure that optional 'bag-info.txt',
     * 'fetch.txt', and 'tagmanifest-{sha1,md5}.txt' are created. Default is
     * true.
     * @param boolean $fetch       If true, it will download all files in
     * 'fetch.txt'. Default is false.
     * @param array   $bagInfoData If given, this sets the bagInfoData
     * property.
     */
    public function __construct(
        $bag, $validate=false, $extended=true, $fetch=false, $bagInfoData=null
    ) {
        $this->bag = $bag;
        $this->extended = $extended || (! is_null($bagInfoData));
        $this->bagVersion = array('major' => 0, 'minor' => 96);
        $this->tagFileEncoding = 'UTF-8';
        $this->bagDirectory = null;
        $this->bagitFile = null;
        $this->manifest = null;
        $this->tagManifest = null;
        $this->fetch = null;
        $this->bagInfoFile = null;
        $this->bagInfoData = $bagInfoData;
        $this->bagCompression = null;
        $this->bagErrors = array();

        if (
            file_exists($this->bag) &&
            ($this->_isCompressed() || file_exists("{$this->bag}/bagit.txt"))
        ) {
            $this->_openBag();
        } else {
            $this->_createBag();
        }

        if ($fetch) {
            $this->fetch->download();
        }

        if ($validate) {
            $this->validate();
        }
    }

    /**
     * Test if a Bag is valid
     *
     * @return boolean True if no validation errors occurred.
     */
    public function isValid()
    {
        return (count($this->bagErrors) == 0);
    }

    /**
     * Test if a bag has optional files
     *
     * @return boolean True if the bag contains the optional files
     * 'bag-info.txt', 'fetch.txt', or 'tagmanifest-{sha1,md5}.txt'.
     */
    function isExtended()
    {
        return $this->extended;
    }

    /**
     * Return the info keys
     *
     * @return array A dictionary array containing these keys: 'version',
     * 'encoding', 'hash'.
     */
    function getBagInfo()
    {
        $major = $this->bagVersion['major'];
        $minor = $this->bagVersion['minor'];

        $info = array(
            'version'  => "$major.$minor",
            'encoding' => $this->tagFileEncoding,
            'hash'     => $this->getHashEncoding()
        );
        return $info;
    }

    /**
     * Get the absolute path of the bag's data directory
     *
     * @return string The absolute path to the bag's data directory.
     */
    function getDataDirectory()
    {
        return "{$this->bagDirectory}/data";
    }

    /**
     * Determine hash encoding
     *
     * @return string The bag's checksum encoding scheme.
     */
    function getHashEncoding()
    {
        return $this->manifest->getHashEncoding();
    }

    /**
     * Sets the bag's checksum hash algorithm.
     *
     * @param string $hashAlgorithm The bag's checksum hash algorithm. Must be
     * either 'sha1' or 'md5'.
     *
     * @return void
     */
    function setHashEncoding($hashAlgorithm)
    {
        $hashAlgorithm = strtolower($hashAlgorithm);
        if ($hashAlgorithm != 'md5' && $hashAlgorithm != 'sha1') {
            throw new InvalidArgumentException("Invalid hash algorithim: '$hashAlgorithm'.");
        }

        $this->manifest->setHashEncoding($hashAlgorithm);
        if ($this->tagManifest !== null) {
            $this->tagManifest->setHashEncoding($hashAlgorithm);
        }
    }

    /**
     * Return an array of all files in the data directory
     *
     * @return array An array of absolute paths for all of the files in the
     * data directory.
     */
    function getBagContents()
    {
        return rls($this->getDataDirectory());
    }

    /**
     * Return errors for a bag
     *
     * @param boolean $validate If true, then it will run this->validate() to
     * verify the integrity first. Default is false.
     *
     * @return array An array of all bag errors.
     */
    function getBagErrors($validate=false)
    {
        if ($validate) {
            $this->validate();
        }
        return $this->bagErrors;
    }

    /**
     * Runs the bag validator on the contents of the bag. This verifies the
     * presence of required iles and folders and verifies the checksum for
     * each file.
     *
     * For the results of validation, check isValid() and getBagErrors().
     *
     * @return array The list of bag errors.
     */
    function validate()
    {
        $errors = array();

        BagIt_validateExists($this->bagitFile, $errors);
        BagIt_validateExists($this->getDataDirectory(), $errors);
        $this->manifest->validate($errors);

        $this->bagErrors = $errors;
        return $this->bagErrors;
    }

    /**
     * This method is used whenever something is added to or removed from the
     * bag. It performs these steps:
     *
     * <ul>
     * <li>Ensures that required files are present;</li>
     * <li>Sanitizes file names;</li>
     * <li>Makes sure that checksums are up-to-date;</li>
     * <li>Adds checksums and file entries for new files;</li>
     * <li>Removes checksums and file entries for missing files; and</li>
     * <li>If it's an extended bag, makes sure that those files are also
     * up-to-date.</li>
     * </ul>
     *
     * @return void
     */
    function update()
    {
        // Clear the manifests.
        $this->manifest->clear();
        if ($this->tagManifest !== null) {
            $this->tagManifest->clear();
        }

        // Clean up the file names in the data directory.
        $dataFiles = rls($this->getDataDirectory());
        foreach ($dataFiles as $dataFile) {
            $baseName = basename($dataFile);
            if ($baseName == '.' || $baseName == '..') {
                continue;
            }

            $cleanName = BagIt_sanitizeFileName($baseName);
            if ($baseName != $cleanName) {
                $dirName = dirname($dataFile);
                rename($dataFile, "$dirName/$cleanName");
            }
        }

        if ($this->extended || count($this->bagInfoData) > 0) {
            $this->_writeBagInfo();
        }

        // Update the manifests.
        $this->manifest->update(rls($this->getDataDirectory()));
        if ($this->tagManifest !== null) {
            $bagdir = $this->bagDirectory;
            $tagFiles = array(
                "$bagdir/bagit.txt",
                "$bagdir/bag-info.txt",
                $this->fetch->fileName,
                $this->manifest->getFileName()
            );
            $this->tagManifest->update($tagFiles);
        }
    }

    /**
     * This copies the file specified into the bag at the place given.
     *
     * $dest should begin with "data/", but if it doesn't that will be added.
     *
     * @param string $src  The file name for the source file.
     * @param string $dest The file name for the destination file. This should 
     * be relative to the bag directory.
     *
     * @return void
     */
    function addFile($src, $dest)
    {
        $dataPref = 'data' . DIRECTORY_SEPARATOR;
        $prefLen = strlen($dataPref);
        if ((strncasecmp($dest, $dataPref, $prefLen) != 0)
            && (strncasecmp($dest, $dataPref, $prefLen) != 0)
        ) {
            $dest = $dataPref . $dest;
        }

        $fulldest = "{$this->bagDirectory}/$dest";
        $dirname = dirname($fulldest);
        if (! is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }

        copy($src, $fulldest);
    }


    /**
     * Compresses the bag into a file.
     *
     * @param string $destination The file to put the bag into.
     * @param string $method      Either 'tgz' or 'zip'. Default is 'tgz'.
     *
     * @return void
     */
    function package($destination, $method='tgz')
    {
        $method = strtolower($method);
        if ($method != 'zip' && $method != 'tgz') {
            throw new BagItException("Invalid compression method: '$method'.");
        }

        if (substr_compare($destination, ".$method", -4, 4, true) != 0) {
            $destination = "$destination.$method";
        }

        BagIt_compressBag(
            $this->bagDirectory,
            $destination,
            $method
        );
    }

    /**
     * This tests whether bagInfoData has a key.
     *
     * @param string $key The key to test for existence of.
     *
     * @return bool
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    public function hasBagInfoData($key)
    {
        $this->_ensureBagInfoData();
        return array_key_exists($key, $this->bagInfoData);
    }

    /**
     * This inserts a value into bagInfoData.
     *
     * @param string $key   This is the key to insert into the data.
     * @param string $value This is the value to associate with the key.
     *
     * @return void
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    public function setBagInfoData($key, $value)
    {
        $this->_ensureBagInfoData();
        $this->bagInfoData[$key] = BagIt_getAccumulatedValue(
            $this->bagInfoData, $key, $value
        );
    }

    /**
     * This removes all the values for a key in the `bag-info.txt` file.
     *
     * @param string $key The key to clear.
     *
     * @return void
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    public function clearBagInfoData($key)
    {
        if (array_key_exists($key, $this->bagInfoData)) {
            unset($this->bagInfoData[$key]);
        }
    }

    /**
     * This returns the value for a key from bagInfoData.
     * 
     * @param string $key This is the key to get the value associated with.
     *
     * @return string|null
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    public function getBagInfoData($key)
    {
        $this->_ensureBagInfoData();
        return array_key_exists($key, $this->bagInfoData) ? $this->bagInfoData[$key] : null;
    }

    //}}}

    //{{{ Private Methods

    /**
     * Open an existing bag. This expects $bag to be set.
     *
     * @return void
     */
    private function _openBag()
    {
        if ($this->_isCompressed()) {
            $this->bagDirectory = BagIt_uncompressBag($this->bag);
        } else {
            $this->bagDirectory = realpath($this->bag);
        }

        $this->bagitFile = "{$this->bagDirectory}/bagit.txt";
        list($version, $fileEncoding, $errors) = BagIt_readBagItFile(
            $this->bagitFile
        );
        $this->bagVersion = $version;
        $this->tagFileEncoding = $fileEncoding;
        $this->bagErrors = array_merge($this->bagErrors, $errors);

        $files = scandir($this->bagDirectory);
        if (count($files) > 0) {
            $bagdir = $this->bagDirectory;
            $manifestFile = findFirstExisting(
                array("$bagdir/manifest-sha1.txt", "$bagdir/manifest-md5.txt"),
                "$bagdir/manifest-sha1.txt"
            );
            try {
                $this->manifest = new BagItManifest(
                    $manifestFile,
                    $this->bagDirectory . '/',
                    $this->tagFileEncoding
                );
            } catch (Exception $exc) {
                array_push(
                    $this->bagErrors,
                    array('manifest', "Error reading $manifestFile.")
                );
            }

            if ($this->isExtended()) {
                $manifestFile = findFirstExisting(
                    array("$bagdir/tagmanifest-sha1.txt",
                    "$bagdir/tagmanifest-md5.txt"),
                    "$bagdir/tagmanifest-sha1.txt"
                );
                $this->tagManifest = new BagItManifest(
                    $manifestFile,
                    $this->bagDirectory . '/',
                    $this->tagFileEncoding
                );

                try {
                    $this->fetch = new BagItFetch(
                        "{$this->bagDirectory}/fetch.txt",
                        $this->tagFileEncoding
                    );
                } catch (Exception $exc) {
                    array_push(
                        $this->bagErrors,
                        array('fetch', 'Error reading fetch file.')
                    );
                }

                $this->bagInfoFile = "{$this->bagDirectory}/bag-info.txt";
                $this->_readBagInfo();
            }
        }
    }

    /**
     * Create a new bag. This expects $bag to be set.
     *
     * @return void
     */
    private function _createBag()
    {
        if (!is_dir($this->bag)) {
            mkdir($this->bag);
        }
        $this->bagDirectory = realpath($this->bag);

        $dataDir = $this->getDataDirectory();
        if (!is_dir($dataDir)) {
            mkdir($dataDir);
        }

        $this->bagitFile = $this->bagDirectory . '/bagit.txt';
        $this->manifest = new BagItManifest(
            "{$this->bagDirectory}/manifest-sha1.txt",
            $this->bagDirectory . '/',
            $this->tagFileEncoding
        );

        $major = $this->bagVersion['major'];
        $minor = $this->bagVersion['minor'];
        $bagItData
            = "BagIt-Version: $major.$minor\n" .
              "Tag-File-Character-Encoding: {$this->tagFileEncoding}\n";
        writeFileText($this->bagitFile, $this->tagFileEncoding, $bagItData);

        $this->_createExtendedBag();
    }

    /**
     * This creates the files for an extended bag.
     *
     * @return void
     */
    private function _createExtendedBag()
    {
        if ($this->extended) {
            $hashEncoding = $this->getHashEncoding();
            $this->tagManifest = new BagItManifest(
                "{$this->bagDirectory}/tagmanifest-$hashEncoding.txt",
                $this->bagDirectory . '/',
                $this->tagFileEncoding
            );

            $fetchFile = $this->bagDirectory . '/fetch.txt';
            $this->fetch = new BagItFetch($fetchFile, $this->tagFileEncoding);

            $this->bagInfoFile = $this->bagDirectory . '/bag-info.txt';
            touch($this->bagInfoFile);
            if (is_null($this->bagInfoData)) {
                $this->bagInfoData = array();
            }
        }
    }

    /**
     * This reads the bag-info.txt file into an array dictionary.
     *
     * @return void
     */
    private function _readBagInfo()
    {
        try {
            $lines = readLines($this->bagInfoFile, $this->tagFileEncoding);
            $this->bagInfoData = BagIt_parseBagInfo($lines);
        } catch (Exception $exc) {
            array_push(
                $this->bagErrors,
                array('baginfo', 'Error reading bag info file.')
            );
        }
    }

    /**
     * This writes the bag-info.txt file with the contents of bagInfoData.
     *
     * @return void
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    private function _writeBagInfo()
    {
        $lines = array();

        if (count($this->bagInfoData)) {
            foreach ($this->bagInfoData as $label => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $lines[] = "$label: $v\n";
                    }
                } else {
                    $lines[] = "$label: $value\n";
                }
            }
        }

        writeFileText($this->bagInfoFile, $this->tagFileEncoding, join('', $lines));
    }

    /**
     * Tests if a bag is compressed
     *
     * @return True if this is a compressed bag.
     */
    private function _isCompressed()
    {
        if (is_dir($this->bag)) {
            return false;
        } else {
            $bag = strtolower($this->bag);
            if (endsWith($bag, '.zip')) {
                $this->bagCompression = 'zip';
                return true;
            } else if (endsWith($bag, '.tar.gz') || endsWith($bag, '.tgz')) {
                $this->bagCompression = 'tgz';
                return true;
            }
        }
        return false;
    }

    /**
     * This makes sure that bagInfoData is not null.
     *
     * @return array
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    private function _ensureBagInfoData()
    {
        if (is_null($this->bagInfoData)) {
            $this->bagInfoData = array();
        }
        return $this->bagInfoData;
    }

    //}}}

}

/* Functional wrappers/facades. */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


?>
