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
 * This is a utility class for managing fetch files.
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
class BagItFetch
{

    //{{{ Properties

    /**
     * The file name containing the fetch information.
     *
     * @var string
     */
    var $fileName;

    /**
     * The data from the fetch file.
     *
     * This is an array-list containing array-mappings with the keys 'url', 
     * 'length', and 'filename'.
     *
     * @var array
     */
    var $data;

    /**
     * The character encoding for the data in the fetch file.
     *
     * @var string
     */
    var $fileEncoding;

    //}}}

    //{{{ Public methods

    /**
     * This initializes a new BagItFetch instance.
     *
     * @param string $fileName     This is the file name for the fetch file.
     * @param string $fileEncoding This is the encoding to use when reading or 
     * writing the fetch file. The default is 'UTF-8'.
     */
    public function __construct($fileName, $fileEncoding='UTF-8')
    {
        $this->fileName = $fileName;
        $this->fileEncoding = $fileEncoding;
        $this->data = array();

        if (file_exists($this->fileName)) {
            $this->read();
        } else {
            touch($this->fileName);
        }
    }

    /**
     * This returns the fetch data.
     *
     * @return array The fetch data.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * This reads the data from the fetch file and populates the data array.
     *
     * @return array The data from the file.
     */
    public function read()
    {
        $lines = readLines($this->fileName, $this->fileEncoding);
        $fetch = array();

        foreach ($lines as $line) {
            $fields = preg_split('/\s+/', $line);
            if (count($fields) == 3) {
                array_push(
                    $fetch,
                    array('url' => $fields[0],
                          'length' => $fields[1],
                          'filename' => $fields[2])
                );
            }
        }
        $this->data = $fetch;
    }

    /**
     * This writes the data to the fetch file.
     *
     * @return void
     */
    public function write()
    {
        $lines = array();

        foreach ($this->data as $fetch) {
            $data = array($fetch['url'], $fetch['length'], $fetch['filename']);
            array_push($lines, join(' ', $data) . "\n");
        }

        writeFileText($this->fileName, $this->fileEncoding, join('', $lines));
    }

    /**
     * This clears the fetch data and the file.
     *
     * @return void
     */
    public function clear()
    {
        $this->data = array();
        file_put_contents($this->fileName, '');
    }

    /**
     * This adds an entry to the fetch data.
     *
     * @param string $url      This is the URL to load the file from.
     * @param string $filename This is the file name, relative to the fetch 
     * file's directory, to save the data to.
     *
     * @return void
     */
    public function add($url, $filename)
    {
        array_push(
            $this->data,
            array('url' => $url, 'length' => '-', 'filename' => $filename)
        );
        $this->write();
    }

    /**
     * This downloads the files in the fetch information that aren't on the 
     * file system.
     *
     * @return void
     */
    public function download()
    {
        $basedir = dirname($this->fileName);
        foreach ($this->data as $fetch) {
            $filename = $basedir . '/' . $fetch['filename'];
            if (! file_exists($filename)) {
                $this->_downloadFile($fetch['url'], $filename);
            }
        }
    }

    //}}}

    //{{{ Private methods

    /**
     * This downloads a single file.
     *
     * @param string $url      The URL to fetch.
     * @param string $filename The absolute file name to save to.
     *
     * @return void
     */
    private function _downloadFile($url, $filename)
    {
        $dirname = dirname($filename);
        if (! is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
        saveUrl($url, $filename);
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
