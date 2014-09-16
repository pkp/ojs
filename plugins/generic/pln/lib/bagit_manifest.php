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
 * of * the License at http://www.apache.org/licenses/LICENSE-2.0 Unless
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

/**
 * This is a utility class for managing manifest files.
 *
 * These files map file names to hashes.
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
class BagItManifest
{

    //{{{ properties

    /**
     * If given, this is the path prefix to strip off of files before using 
     * them as keys in the hash mapping (data property).
     *
     * @var string
     */
    var $pathPrefix;

    /**
     * The absolute file name for the manifest file.
     *
     * @var string
     */
    var $fileName;

    /**
     * The hash encoding to use. This must be one of 'sha1' or 'md5'.
     *
     * @var string
     */
    var $hashEncoding;

    /**
     * The file encoding to use when reading or writing the manifest file.
     *
     * @var string
     */
    var $fileEncoding;

    /**
     * A mapping of relative path name ($pathPrefix removed) to hash.
     *
     * @var array
     */
    var $data;

    //}}}

    //{{{ Public Methods

    /**
     * Define a new BagItManifest instance.
     *
     * @param string $fileName     This is the file name for the manifest file.
     * @param string $pathPrefix   This is the prefix to remove from the 
     * beginning of file names before they're used as keys in the hash mapping. 
     * The default is an empty string (i.e., nothing removed).
     * @param string $fileEncoding This is the encoding to use when reading or 
     * writing the manifest file. The default is 'UTF-8'.
     */
    public function __construct(
        $fileName, $pathPrefix='', $fileEncoding='UTF-8'
    ) {
        $this->fileName = $fileName;
        $this->pathPrefix = $pathPrefix;
        $this->fileEncoding = $fileEncoding;
        $this->data = array();

        $this->hashEncoding = $this->_parseHashEncoding($fileName);

        if (file_exists($fileName)) {
            $this->read();
        } else if (is_dir(dirname($this->fileName))) {
            touch($this->fileName);
        }
    }

    /**
     * This reads the data from the file name.
     *
     * @param string $fileName This is the file name to read. It defaults to 
     * the current value of the $fileName property. If given, it will set the 
     * value of the property also.
     *
     * @return array The data read.
     */
    public function read($fileName=null)
    {
        $this->_resetFileName($fileName);

        $manifest = array();
        $hashLen = ($this->hashEncoding == 'sha1') ? 40 : 32;
        $lines = readLines($this->fileName, $this->fileEncoding);

        foreach ($lines as $line) {
            $hash = trim(substr($line, 0, $hashLen));
            $payload = trim(substr($line, $hashLen));

            if (strlen($payload) > 0) {
                $manifest[$payload] = $hash;
            }
        }

        $this->data = $manifest;
        return $manifest;
    }

    /**
     * This clears the data in the manifest, both in memory and on disk.
     *
     * @return void
     */
    public function clear()
    {
        $this->data = array();
        file_put_contents($this->fileName, '');
    }

    /**
     * This updates the data in the manifest from the files passed in.
     *
     * @param array $fileList A list of files to include in the manifest.
     *
     * @return array The new hash mapping from those files.
     */
    public function update($fileList)
    {
        $csums = array();

        foreach ($fileList as $file) {
            if (file_exists($file)) {
                $hash = $this->calculateHash($file);
                $csums[$this->_makeRelative($file)] = $hash;
            }
        }

        $this->data = $csums;

        $this->write();

        return $csums;
    }

    /**
     * This calculates the hash for a file.
     *
     * @param string $fileName The path of the file to calculate the hash for.
     *
     * @return string The hash.
     */
    public function calculateHash($fileName)
    {
        return hash_file($this->hashEncoding, $fileName);
    }

    /**
     * This writes the data to the manifest file.
     *
     * @param string $fileName This is the file name to write to. It defaults 
     * to the current value of the $fileName property. If given, it will set 
     * the value of the property also.
     *
     * @return void
     */
    public function write($fileName=null)
    {
        $this->_resetFileName($fileName);

        ksort($this->data);
        $output = array();

        foreach ($this->data as $path => $hash) {
            array_push($output, "$hash $path\n");
        }

        writeFileText(
            $this->fileName,
            $this->fileEncoding,
            implode('', $output)
        );
    }

    /**
     * This returns the hash for a file.
     *
     * @param string $fileName This can be either the absolute file name or the 
     * file name without the path prefix.
     *
     * @return string The file's hash.
     */
    public function getHash($fileName)
    {
        if (array_key_exists($fileName, $this->data)) {
            return $this->data[$fileName];
        } else if (array_key_exists($this->_makeRelative($fileName), $this->data)) {
            return $this->data[$this->_makeRelative($fileName)];
        } else {
            return null;
        }
    }

    /**
     * This returns all of the hash data.
     *
     * @return array The hash data.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * This returns the manifest file's path.
     *
     * @return string The path to the manifest file.
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * This returns the file encoding.
     *
     * @return string The encoding to use when reading or writing the manifest 
     * file.
     */
    public function getFileEncoding()
    {
        return $this->fileEncoding;
    }

    /**
     * This sets the file encoding.
     *
     * The value for this should be either 'sha1' or 'md5', but this doesn't 
     * verify that.
     *
     * This also re-sets the manifest file name based upon this value.
     *
     * @param string $fileEncoding The new encoding to use when reading or 
     * writing the manifest file.
     *
     * @return void
     */
    public function setFileEncoding($fileEncoding)
    {
        $this->fileEncoding = $fileEncoding;
    }

    /**
     * This returns the hash encoding.
     *
     * @return string The encoding to use when creating the manifest hash data.
     */
    public function getHashEncoding()
    {
        return $this->hashEncoding;
    }

    /**
     * This sets the hash encoding.
     *
     * @param string $hashEncoding This sets the encoding to use when creating 
     * the manifest hash data. This must be either 'md5' or 'sha1'; however, it 
     * does not test for this.
     *
     *  @return void
     */
    public function setHashEncoding($hashEncoding)
    {
        $this->hashEncoding = $hashEncoding;

        $fileName = preg_replace(
            '/-\w+\.txt$/',
            "-$hashEncoding.txt",
            $this->fileName
        );

        if ($fileName != $this->fileName) {
            rename($this->fileName, $fileName);
            $this->fileName = $fileName;
        }
    }

    /**
     * This validates the data in the manifest.
     *
     * This tests three things:
     * <ol>
     * <li>That the manifest file does exist;</li>
     * <li>That the files in the hash mapping do exist; and</li>
     * <li>That the hashes in the mapping are correct.</li>
     * </ol>
     *
     * Problems will be added to the errors.
     *
     * @param array &$errors A list of error messages. Messages about any
     * errors in validation will be appended to this.
     *
     * @return boolean Does this validate or not?
     */
    public function validate(&$errors)
    {
        $errLen = count($errors);

        // That the manifest file does exist;
        if (! file_exists($this->fileName)) {
            $basename = basename($this->fileName);
            array_push(
                $errors,
                array($basename, "$basename does not exist.")
            );
            // There's no manifest file, so we might as well bail now.
            return false;
        }

        // That the files in the hash mapping do exist; and
        // That the hashes in the mapping are correct.
        foreach ($this->data as $fileName => $hash) {
            $fullPath = $this->pathPrefix . $fileName;
            if (! file_exists($fullPath)) {
                array_push(
                    $errors,
                    array($fileName, 'Missing data file.')
                );
            } else if ($this->calculateHash($fullPath) != $hash) {
                array_push(
                    $errors,
                    array($fileName, 'Checksum mismatch.')
                );
            }
        }

        return ($errLen == count($errors));
    }

    //}}}

    //{{{ Private Methods

    /**
     * This looks at a manifest file name and tries to pick out the hash
     * encoding.
     *
     * File names should be in the format '*-{sha1,md5}.txt'.
     *
     * @param string $filename The file name to parse.
     * 
     * @return string The hash encoding, if one is found.
     */
    private function _parseHashEncoding($filename)
    {
        $matches = array();
        if (preg_match('/-(sha1|md5)\.txt$/', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * This returns the file name to use.
     *
     * If a file name is passed into this function, that will be used. 
     * Otherwise, the current value of the $fileName property will be used.
     *
     * @param string $fileName If given, then this file name will be used;
     * otherwise, the $fileName property will be used.
     *
     * @return string The file name to use for the $fileName property.
     */
    private function _resetFileName($fileName=null)
    {
        if ($fileName === null) {
            return $this->fileName;
        } else {
            $this->hashEncoding = $this->_parseHashEncoding($fileName);
            $this->fileName = $fileName;
            return $fileName;
        }
    }
    
    /**
     * This takes a file name and strips the prefix path from it.
     *
     * This is unsafe, strictly speaking, because it doesn't check that the
     * file name passed in actually begins with the prefix.
     *
     * @param string $filename An absolute file name under the bag directory.
     *
     * @return string The file name with the $pathPrefix.
     */
    private function _makeRelative($filename)
    {
        $rel = substr($filename, strlen($this->pathPrefix));
        if (! $rel) {
            return '';
        } else {
            return $rel;
        }
    }
    //}}}

}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


?>
