<?php

namespace whikloj\BagItTools;

use whikloj\BagItTools\Exceptions\BagItException;
use whikloj\BagItTools\Exceptions\FilesystemException;

/**
 * Bag class as normal interface for all actions and holder of supporting constructs.
 *
 * @package \whikloj\BagItTools
 * @author  whikloj
 * @since   1.0.0
 */
class Bag
{

    /**
     * The default algorithm to use if one is not specified.
     */
    private const DEFAULT_HASH_ALGORITHM = 'sha512';

    /**
     * The default file encoding if one is not specified.
     */
    private const DEFAULT_FILE_ENCODING = 'UTF-8';

    /**
     * The default bagit version.
     */
    private const DEFAULT_BAGIT_VERSION = [
        'major' => 1,
        'minor' => 0,
    ];

    /**
     * Bag-info fields that MUST not be repeated.
     */
    private const BAG_INFO_MUST_NOT_REPEAT = [
        'payload-oxum',
    ];

    /**
     * Bag-info fields that SHOULD NOT be repeated.
     */
    private const BAG_INFO_SHOULD_NOT_REPEAT = [
        'bagging-date',
        'bag-size',
        'bag-group-identifer',
        'bag-count',
    ];

    /**
     * Reserved element names for Bag-info fields.
     */
    private const BAG_INFO_RESERVED_ELEMENTS = [
        'source-organization',
        'organization-address',
        'contact-name',
        'contact-phone',
        'contact-email',
        'external-description',
        'bagging-date',
        'external-identifier',
        'payload-oxum',
        'bag-size',
        'bag-group-identifier',
        'bag-count',
        'internal-sender-identifier',
        'internal-sender-description',
    ];

    /**
     * Fields you can't set because we generate them on $bag->update().
     */
    private const BAG_INFO_GENERATED_ELEMENTS = [
        'payload-oxum',
        'bag-size',
        'bagging-date',
    ];

    /**
     * Array of BagIt approved names of hash algorithms to the PHP names of
     * those hash algorithms for use with hash_file().
     *
     * @see https://tools.ietf.org/html/rfc8493#section-2.4
     *
     * @var array
     */
    private const HASH_ALGORITHMS = array(
        'md5' => 'md5',
        'sha1' => 'sha1',
        'sha224' => 'sha224',
        'sha256' => 'sha256',
        'sha384' => 'sha384',
        'sha512' => 'sha512',
        'sha3224' => 'sha3-224',
        'sha3256' => 'sha3-256',
        'sha3384' => 'sha3-384',
        'sha3512' => 'sha3-512',
    );

    /**
     * File names that are not allowed on windows, should be disallowed in bags for interoperability.
     */
    private const WINDOWS_RESERVED_NAMES = [
        'CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1',
        'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9',
    ];

    private const WINDOWS_PATH_CHARACTERS = [
        '<', '>', ':', '"', '/', '|', '?', '*',
    ];

    /**
     * Extensions which map to a tar file.
     */
    private const TAR_EXTENSIONS = [
        'tar',
        'tgz',
        'tar.gz',
        'tar.bz2',
    ];

    /**
     * Extensions which map to a zip file.
     */
    private const ZIP_EXTENSIONS = [
        'zip',
    ];

    /**
     * Length we start trying to wrap at.
     */
    const BAGINFO_AUTOWRAP_START = 77;

    /**
     * The length of a line over which we assume the line has been auto-wrapped.
     */
    const BAGINFO_AUTOWRAP_GUESS_LENGTH = 70;

    /**
     * All the extensions in one array.
     *
     * @var array
     */
    private $packageExtensions;

    /**
     * Array of current bag version with keys 'major' and 'minor'.
     *
     * @var array
     */
    private $currentVersion;

    /**
     * Current bag file encoding.
     *
     * @var string
     */
    private $currentFileEncoding;

    /**
     * Array of payload manifests.
     *
     * @var array
     */
    private $payloadManifests;

    /**
     * Array of tag manifests.
     *
     * @var array
     */
    private $tagManifests;

    /**
     * List of relative file paths for all files.
     *
     * @var array
     */
    private $payloadFiles;

    /**
     * Reference to a Fetch file or null if not used.
     *
     * @var \whikloj\BagItTools\Fetch
     */
    private $fetchFile = null;

    /**
     * The absolute path to the root of the bag, all other file paths are
     * relative to this. This path is stored with / as directory separator
     * regardless of the OS.
     *
     * @var string
     */
    private $bagRoot;

    /**
     * Is this an extended bag?
     *
     * @var boolean
     */
    private $isExtended = false;

    /**
     * The valid algorithms from the current version of PHP filtered to those
     * supported by the BagIt specification. Stored to avoid extraneous calls
     * to hash_algos().
     *
     * @var array
     */
    private $validHashAlgorithms;

    /**
     * Errors when validating a bag.
     *
     * @var array
     */
    private $bagErrors;

    /**
     * Warnings when validating a bag.
     *
     * @var array
     */
    private $bagWarnings;

    /**
     * Have we changed the bag and not written it to disk?
     *
     * @var boolean
     */
    private $changed = false;

    /**
     * Bag Info data.
     *
     * @var array
     */
    private $bagInfoData = [];

    /**
     * Unique array of all Bag info tags/values. Tags are stored once in lower case with an array of all instances
     * of values. This index does not save order.
     *
     * @var array
     */
    private $bagInfoTagIndex = [];

    /**
     * Did we load this from disk.
     *
     * @var boolean
     */
    private $loaded = false;

    /**
     * Bag constructor.
     *
     * @param string  $rootPath
     *   The path of the root of the new or existing bag.
     * @param boolean $new
     *   Are we making a new bag?
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems accessing a file.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   Bag directory exists for new bag or various issues for loading an existing bag.
     */
    private function __construct($rootPath, $new = true)
    {
        $this->packageExtensions = array_merge(self::TAR_EXTENSIONS, self::ZIP_EXTENSIONS);
        // Define valid hash algorithms our PHP supports.
        $this->validHashAlgorithms = array_filter(
            hash_algos(),
            array($this, 'filterPhpHashAlgorithms')
        );
        // Alter the algorithm name to the sanitize version.
        array_walk(
            $this->validHashAlgorithms,
            array($this, 'normalizeHashAlgorithmName')
        );
        $this->bagRoot = $this->internalPath($rootPath);
        $this->bagRoot = BagUtils::getAbsolute($this->bagRoot, true);
        $this->loaded = (!$new);
        if ($new) {
            $this->createNewBag();
        } else {
            $this->loadBag();
        }
    }

    /**
     * Static function to create a new Bag
     *
     * @param  string $rootPath
     *   Path to the new bag, must not exist
     * @return \whikloj\BagItTools\Bag
     *   The bag.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   If we can't create the directory.
     */
    public static function create($rootPath) : Bag
    {
        return new Bag($rootPath, true);
    }

    /**
     * Static constructor to load an existing bag.
     *
     * @param  string $rootPath
     *   Path to the existing bag.
     * @return \whikloj\BagItTools\Bag
     *   The bag object.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   If we can't read files in the bag.
     */
    public static function load($rootPath) : Bag
    {
        $rootPath = BagUtils::getAbsolute($rootPath, true);
        if (is_file($rootPath) && self::isCompressed($rootPath)) {
            $rootPath = self::uncompressBag($rootPath);
        }
        return new Bag($rootPath, false);
    }

    /**
     * Validate the bag as it appears on disk.
     *
     * @return boolean
     *   True if the bag is valid
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   Problems writing to disk.
     */
    public function validate() : bool
    {
        if (!$this->loaded || $this->changed) {
            // If we created this bag or have changed stuff we need to write it.
            $this->update();
        }
        // Reload the bag from disk.
        $this->loadBag();
        if (isset($this->fetchFile)) {
            $this->fetchFile->downloadAll();
            $this->mergeErrors($this->fetchFile->getErrors());
        }
        $manifests = array_values($this->payloadManifests);
        if ($this->isExtended) {
            // merge in the tag manifests so we can do them all at once.
            $manifests = array_merge($manifests, array_values($this->tagManifests));
        }
        foreach ($manifests as $manifest) {
            $manifest->validate();
            $this->mergeErrors($manifest->getErrors());
            $this->mergeWarnings($manifest->getWarnings());
        }
        return (count($this->bagErrors) == 0);
    }

    /**
     * Write the updated BagIt files to disk.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Errors with writing files to disk.
     */
    public function update()
    {
        if (!file_exists($this->makeAbsolute("data"))) {
            BagUtils::checkedMkdir($this->makeAbsolute("data"), 0777);
        }
        $this->updateBagIt();
        $this->updatePayloadManifests();
        $this->updateFetch();

        if ($this->isExtended) {
            $this->updateBagInfo();
            $this->updateTagManifests();
        } else {
            $this->removeBagInfo();
            $this->clearTagManifests();
        }
        $this->changed = false;
    }

    /**
     * This does cleanup functions related to packaging, for example deleting downloaded files referenced in fetch.txt
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues deleting files from filesystem.
     */
    public function finalize()
    {
        // Update files to ensure they are correct.
        $this->update();
        if (isset($this->fetchFile)) {
            // Clean up fetch files downloaded to generate checksums.
            $this->fetchFile->cleanup();
        }
    }

    /**
     * Package a bag up into an archive.
     *
     * @param  string $filepath
     *   The full path to create the archive at.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   Problems creating the archive.
     */
    public function package($filepath)
    {
        $extension = self::getExtensions($filepath);
        if (!in_array($extension, $this->packageExtensions)) {
            throw new BagItException(
                "Unknown archive type, the file extension must be one of (" .
                implode(", ", $this->packageExtensions) . ")"
            );
        }
        $this->finalize();
        $this->makePackage($filepath);
    }

    /**
     * Add a file to the bag.
     *
     * @param string $source
     *   Full path to the source file.
     * @param string $dest
     *   Relative path for the destination.
     *
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   Source file does not exist or the destination is outside the data directory.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues writing to the filesystem.
     */
    public function addFile($source, $dest)
    {
        if (file_exists($source)) {
            $dest = BagUtils::baseInData($dest);
            if (!$this->pathInBagData($dest)) {
                throw new BagItException("Path {$dest} resolves outside the bag.");
            } elseif ($this->reservedFilename($dest)) {
                throw new BagItException("The filename requested is reserved on Windows OSes.");
            } elseif (isset($this->fetchFile) && $this->fetchFile->reservedPath($dest)) {
                throw new BagItException("The path ({$dest}) is used in the fetch.txt file.");
            } else {
                $fullDest = $this->makeAbsolute($dest);
                $fullDest = \Normalizer::normalize($fullDest);
                if (file_exists($fullDest)) {
                    throw new BagItException("File {$dest} already exists in the bag.");
                }
                $dirname = dirname($fullDest);
                if (substr($this->makeRelative($dirname), 0, 5) == "data/") {
                    // Create any missing missing directories inside data.
                    if (!file_exists($dirname)) {
                        BagUtils::checkedMkdir($dirname, 0777, true);
                    }
                }
                BagUtils::checkedCopy($source, $fullDest);
                $this->changed = true;
            }
        } else {
            throw new BagItException("{$source} does not exist");
        }
    }

    /**
     * Remove a payload file.
     *
     * @param string $dest
     *   The relative path of the file.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues deleting the file.
     */
    public function removeFile($dest)
    {
        $dest = BagUtils::baseInData($dest);
        if ($this->pathInBagData($dest)) {
            $fullPath = $this->makeAbsolute($dest);
            if (file_exists($fullPath) && is_file($fullPath)) {
                BagUtils::checkedUnlink($fullPath);
                $this->checkForEmptyDir($fullPath);
                $this->changed = true;
            }
        }
    }

    /**
     * Add a string as a file to the bag.
     *
     * @param  string $string
     *   The contents of the file.
     * @param  string $dest
     *   The name of the file in the bag.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   Source file does not exist or the destination is outside the data directory.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues creating/deleting temporary file.
     */
    public function createFile($string, $dest)
    {
        $tempname = BagUtils::checkedTempnam("", "bagit_");
        BagUtils::checkedUnlink($tempname);
        BagUtils::checkedFilePut($tempname, $string);
        try {
            $this->addFile($tempname, $dest);
        } finally {
            BagUtils::checkedUnlink($tempname);
        }
    }

    /**
     * Add the bag root to the front of a relative bag path and return with
     * OS directory separator.
     *
     * @param  string $path
     *   The relative path.
     * @return string
     *   The absolute path.
     */
    public function makeAbsolute($path) : string
    {
        $length = strlen($this->bagRoot);
        $path = $this->internalPath($path);
        $path = BagUtils::getAbsolute($path);
        if (substr($path, 0, $length) == $this->bagRoot) {
            return $path;
        }
        $components = array_filter(explode("/", $path));
        $rootComponents = array_filter(explode("/", $this->bagRoot));
        $components = array_merge($rootComponents, $components);
        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $components);
    }

    /**
     * Remove all the extraneous path information and make relative to bag root.
     *
     * @param  string $path
     *   The absolute path to process
     * @return string
     *   The shortened path or blank if it is outside bag root.
     */
    public function makeRelative($path) : string
    {
        $path = $this->internalPath($path);
        $path = BagUtils::getAbsolute($path);
        $rootLength = strlen($this->bagRoot);
        if (substr($path, 0, $rootLength) !== $this->bagRoot) {
            // We are not in bag root so return nothing.
            return '';
        }
        return substr($path, strlen($this->bagRoot) + 1);
    }

    /**
     * Return raw bag info data.
     *
     * @return array
     *   Bag Info data.
     */
    public function getBagInfoData() : array
    {
        return $this->bagInfoData;
    }

    /**
     * Case-insensitive search of bag-info tags.
     *
     * @param  string $tag
     *   Bag info tag to locate
     * @return bool
     *   Does the tag exist.
     */
    public function hasBagInfoTag($tag) : bool
    {
        $tag = self::trimLower($tag);
        return $this->bagInfoTagExists($tag);
    }

    /**
     * Find all instances of tag and return an array of values.
     *
     * @param  string $tag
     *   Bag info tag to locate
     * @return array
     *   Array of values for the tag.
     */
    public function getBagInfoByTag($tag) : array
    {
        $tag = self::trimLower($tag);
        if ($this->bagInfoTagExists($tag)) {
            return $this->bagInfoTagIndex[$tag];
        }
        return [];
    }

    /**
     * Remove ALL instances of tag.
     *
     * @param string $tag
     *   The tag to remove.
     */
    public function removeBagInfoTag($tag)
    {
        $tag = self::trimLower($tag);
        if ($this->bagInfoTagExists($tag)) {
            $newInfo = [];
            foreach ($this->bagInfoData as $row) {
                $rowTag = self::trimLower($row['tag']);
                if ($rowTag !== $tag) {
                    $newInfo[] = $row;
                }
            }
            $this->bagInfoData = $newInfo;
            $this->updateBagInfoIndex();
            $this->changed = true;
        }
    }

    /**
     * Removes a specific entry for a tag by the array index. This can be determined using the index in the array
     * returned by getBagInfoByKey().
     *
     * @param string $tag
     *   The tag to remove.
     * @param int    $index
     *   The index of the value to remove.
     */
    public function removeBagInfoTagIndex($tag, $index)
    {
        if (is_int($index) && $index > -1) {
            $tag = self::trimLower($tag);
            if ($this->bagInfoTagExists($tag)) {
                $values = $this->getBagInfoByTag($tag);
                if ($index < count($values)) {
                    $newInfo = [];
                    $tagCount = 0;
                    foreach ($this->bagInfoData as $row) {
                        $rowTag = self::trimLower($row['tag']);
                        if ($rowTag !== $tag || $tagCount !== $index) {
                            $newInfo[] = $row;
                        }
                        if ($rowTag == $tag) {
                            $tagCount += 1;
                        }
                    }
                    $this->bagInfoData = $newInfo;
                    $this->updateBagInfoIndex();
                    $this->changed = true;
                }
            }
        }
    }

    /**
     * Add tag and value to bag-info.
     *
     * @param  string $tag
     *   The tag to add.
     * @param  string $value
     *   The value to add.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   When you try to set an auto-generated tag value.
     */
    public function addBagInfoTag($tag, $value)
    {
        if (!$this->isExtended) {
            throw new BagItException("This bag is not extended, you need '\$bag->setExtended(true);'");
        }
        $internal_tag = self::trimLower($tag);
        if (in_array($internal_tag, self::BAG_INFO_GENERATED_ELEMENTS)) {
            throw new BagItException("Field {$tag} is auto-generated and cannot be manually set.");
        }
        if (!$this->bagInfoTagExists($internal_tag)) {
            $this->bagInfoTagIndex[$internal_tag] = [];
        }
        $this->bagInfoTagIndex[$internal_tag][] = $value;
        $this->bagInfoData[] = [
            'tag' => trim($tag),
            'value' => trim($value),
        ];
        $this->changed = true;
    }

    /**
     * Set the file encoding.
     *
     * @param  string $encoding
     *   The MIME name of the character set to encode with.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   If we don't support the requested character set.
     */
    public function setFileEncoding($encoding)
    {
        $encoding = self::trimLower($encoding);
        $charset = BagUtils::getValidCharset($encoding);
        if (is_null($charset)) {
            throw new BagItException("Character set {$encoding} is not supported.");
        } else {
            if ($encoding == self::trimLower(self::DEFAULT_FILE_ENCODING)) {
                // go back to default.
                unset($this->currentFileEncoding);
            } else {
                $this->currentFileEncoding = $charset;
            }
            $this->changed = true;
        }
    }

    /**
     * Get current file encoding or default if not specified.
     *
     * @return string
     *   Current file encoding.
     */
    public function getFileEncoding() : string
    {
        if (isset($this->currentFileEncoding)) {
            return $this->currentFileEncoding;
        }
        return self::DEFAULT_FILE_ENCODING;
    }

    /**
     * Get the currently active payload (and tag) manifests.
     *
     * @return array
     *   Internal hash names for current manifests.
     */
    public function getAlgorithms() : array
    {
        return array_keys($this->payloadManifests);
    }

    /**
     * Do we have this hash algorithm already?
     *
     * @param string $hashAlgorithm
     *   The requested hash algorithms.
     *
     * @return boolean Do we already have this payload manifest.
     */
    public function hasAlgorithm($hashAlgorithm) : bool
    {
        $internal_name = $this->getHashName($hashAlgorithm);
        if ($this->hashIsSupported($internal_name)) {
            return $this->hasHash($internal_name);
        }
        return false;
    }

    /**
     * The algorithm is supported.
     *
     * @param  string $algorithm
     *   The requested hash algorithm
     * @return boolean
     *   Whether it is supported by our PHP.
     */
    public function algorithmIsSupported($algorithm) : bool
    {
        $internal_name = $this->getHashName($algorithm);
        return $this->hashIsSupported($internal_name);
    }

    /**
     * Add a hash algorithm to the bag.
     *
     * @param  string $algorithm
     *   Algorithm to add.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   Asking for an unsupported algorithm.
     */
    public function addAlgorithm($algorithm)
    {
        $internal_name = $this->getHashName($algorithm);
        if ($this->hashIsSupported($internal_name)) {
            if (!array_key_exists($internal_name, $this->payloadManifests)) {
                $this->payloadManifests[$internal_name] = new PayloadManifest($this, $internal_name);
            }
            if ($this->isExtended) {
                $this->ensureTagManifests();
                if (!array_key_exists($internal_name, $this->tagManifests)) {
                    $this->tagManifests[$internal_name] = new TagManifest($this, $internal_name);
                }
            }
            $this->changed = true;
        } else {
            throw new BagItException("Algorithm {$algorithm} is not supported.");
        }
    }

    /**
     * Remove a hash algorithm from the bag.
     *
     * @param  string $algorithm
     *   Algorithm to remove
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   Trying to remove the last algorithm or asking for an unsupported algorithm.
     */
    public function removeAlgorithm($algorithm)
    {
        $internal_name = $this->getHashName($algorithm);
        if ($this->hashIsSupported($internal_name)) {
            if (array_key_exists($internal_name, $this->payloadManifests)) {
                if (count($this->payloadManifests) == 1) {
                    throw new BagItException("Cannot remove last payload algorithm, add one before removing this one");
                }
                $this->removePayloadManifest($internal_name);
            }
            if ($this->isExtended && isset($this->tagManifests)
                && array_key_exists($internal_name, $this->tagManifests)
            ) {
                if (count($this->tagManifests) == 1) {
                    throw new BagItException("Cannot remove last tag algorithm, add one before removing this one");
                }
                $this->removeTagManifest($internal_name);
            }
            $this->changed = true;
        } else {
            throw new BagItException("Algorithm {$algorithm} is not supported.");
        }
    }

    /**
     * Replaces any existing hash algorithms with the one requested.
     *
     * @param  string $algorithm
     *   Algorithm to use.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   Asking for an unsupported algorithm.
     */
    public function setAlgorithm($algorithm)
    {
        $internal_name = $this->getHashName($algorithm);
        if ($this->hashIsSupported($internal_name)) {
            $this->removeAllPayloadManifests([$internal_name]);
            if (count($this->payloadManifests) == 0) {
                $this->payloadManifests[$internal_name] = new PayloadManifest($this, $internal_name);
            }
            $this->removeAllTagManifests([$internal_name]);
            if ($this->isExtended) {
                $this->ensureTagManifests();
                if (count($this->tagManifests) == 0) {
                    $this->tagManifests[$internal_name] = new TagManifest($this, $internal_name);
                }
            }
            $this->changed = true;
        } else {
            throw new BagItException("Algorithm {$algorithm} is not supported.");
        }
    }

    /**
     * Add a file to your fetch file.
     *
     * @param  string       $url
     *   The source URL.
     * @param  string       $destination
     *   The destination path in the bag.
     * @param  null|integer $size
     *   Size of the file to be stored in the fetch file, if desired.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   On errors adding the file.
     */
    public function addFetchFile($url, $destination, $size = null)
    {
        $fetchData = [
            'uri' => $url,
            'destination' => $destination,
        ];
        if (!is_null($size) && is_int($size)) {
            $fetchData['size'] = $size;
        }
        if (!isset($this->fetchFile)) {
            $this->fetchFile = new Fetch($this, false);
        }
        // Download the file now to help with manifest handling, deleted when you package() or finalize().
        $this->fetchFile->download($fetchData);
        $this->changed = true;
    }

    /**
     * Return the fetch file data, an array of arrays with keys 'url', 'destination' and (optionally) 'size'.
     *
     * @return array
     */
    public function listFetchFiles() : array
    {
        return (!isset($this->fetchFile) ? [] : $this->fetchFile->getData());
    }

    /**
     * Wipe the fetch file data
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues deleting files/directories from the filesystem.
     */
    public function clearFetch()
    {
        if (isset($this->fetchFile)) {
            $this->fetchFile->clearData();
            unset($this->fetchFile);
            $this->changed = true;
        }
    }

    /**
     * Delete a line from the fetch file.
     *
     * @param string $url
     *   The url to delete.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems deleting the file.
     */
    public function removeFetchFile($url)
    {
        if (isset($this->fetchFile)) {
            $this->fetchFile->removeFile($url);
            $this->changed = true;
        }
    }

    /**
     * Get the current version array or default if not specified.
     *
     * @return array
     *   Current version.
     */
    public function getVersion() : array
    {
        if (isset($this->currentVersion)) {
            return $this->currentVersion;
        }
        return self::DEFAULT_BAGIT_VERSION;
    }

    /**
     * Get current version as string.
     *
     * @return string
     *   Current version in M.N format.
     */
    public function getVersionString() : string
    {
        $version = $this->getVersion();
        return $version['major'] . "." . $version['minor'];
    }

    /**
     * Return path to the bag root.
     *
     * @return string
     *   The bag root path.
     */
    public function getBagRoot() : string
    {
        return $this->bagRoot;
    }

    /**
     * Return path to the data directory.
     *
     * @return string
     *   The bag data directory path.
     */
    public function getDataDirectory() : string
    {
        return $this->makeAbsolute("data");
    }

    /**
     * Check the bag's extended status.
     *
     * @return boolean
     *   Does the bag use extended features?
     */
    public function isExtended() : bool
    {
        return $this->isExtended;
    }

    /**
     * Turn extended bag features on or off.
     *
     * @param boolean $extBag
     *   Whether the bag should be extended or not.
     */
    public function setExtended(bool $extBag)
    {
        $extBag = (bool) $extBag;
        $this->isExtended = $extBag;
        $this->changed = true;
    }

    /**
     * Get errors on the bag.
     *
     * @return array
     *   The errors.
     */
    public function getErrors() : array
    {
        return $this->bagErrors;
    }

    /**
     * Get any warnings related to the bag.
     *
     * @return array
     *   The warnings.
     */
    public function getWarnings() : array
    {
        return $this->bagWarnings;
    }

    /**
     * Get the payload manifests as an associative array with hash algorithm as key.
     *
     * @return array
     *   hash algorithm => Payload manifests
     */
    public function getPayloadManifests() : array
    {
        return $this->payloadManifests;
    }

    /**
     * Get the tag manifests as an associative array with hash algorithm as key.
     *
     * @return array|null
     *   hash algorithm => Tag manifests or null if not an extended bag.
     */
    public function getTagManifests()
    {
        return (isset($this->tagManifests) ? $this->tagManifests : null);
    }

    /**
     * Utility function to convert text to UTF-8
     *
     * @param  string $text
     *   The source text.
     * @return string
     *   The converted text.
     */
    public function decodeText($text) : string
    {
        return mb_convert_encoding($text, self::DEFAULT_FILE_ENCODING, $this->getFileEncoding());
    }

    /**
     * Utility function to convert text back to the encoding for the file.
     *
     * @param  string $text
     *   The source text.
     * @return string
     *   The converted text.
     */
    public function encodeText($text) : string
    {
        return mb_convert_encoding($text, $this->getFileEncoding(), self::DEFAULT_FILE_ENCODING);
    }

    /**
     * Is the path inside the payload directory?
     *
     * @param  string $filepath
     *   The internal path.
     * @return boolean
     *   Path is inside the data/ directory.
     */
    public function pathInBagData($filepath) : bool
    {
        $external = $this->makeAbsolute($filepath);
        $external = trim($external);
        $external = BagUtils::getAbsolute($external);
        $relative = $this->makeRelative($external);
        return ($relative !== "" && substr($relative, 0, 5) === "data/");
    }


    /**
     * Check the directory we just deleted a file from, if empty we should remove
     * it too.
     *
     * @param string $path
     *   The file just deleted.
     */
    public function checkForEmptyDir($path)
    {
        $parentPath = dirname($path);
        if (substr($this->makeRelative($parentPath), 0, 5) == "data/") {
            $files = scandir($parentPath);
            $payload = array_filter(
                $files,
                function ($o) {
                    // Don't count directory specifiers.
                    return (!BagUtils::isDotDir($o));
                }
            );
            if (count($payload) == 0) {
                rmdir($parentPath);
            }
        }
    }

    /**
     * Upgrade an older bag to comply with the 1.0 specification.
     *
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   If the bag cannot be upgraded for some reason.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues writing files to the filesystem.
     */
    public function upgrade()
    {
        if (!$this->loaded) {
            throw new BagItException("You can only upgrade loaded bags.");
        } elseif ($this->getVersion() == self::DEFAULT_BAGIT_VERSION) {
            throw new BagItException("Bag is already at version {$this->getVersionString()}");
        } elseif (!$this->validate()) {
            throw new BagItException("This bag is not valid, we cannot automatically upgrade it.");
        } else {
            // We can upgrade.
            $hashes = array_keys($this->getPayloadManifests());
            if (count($hashes) == 1 && $hashes[0] == 'md5') {
                $this->setAlgorithm(self::DEFAULT_HASH_ALGORITHM);
            }
            unset($this->currentVersion);
            $this->update();
        }
    }

    /*
     *  XXX: Private functions
     */

    /**
     * Load a bag from disk.
     *
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   If a file cannot be read.
     */
    private function loadBag()
    {
        $root = $this->getBagRoot();
        if (!file_exists($root)) {
            throw new BagItException("Path {$root} does not exist, could not load Bag.");
        }
        $this->resetErrorsAndWarnings();
        // Reset these or we end up with double manifests in a validate() situation.
        $this->payloadManifests = [];
        unset($this->tagManifests);
        $this->bagInfoData = [];
        $this->loadBagIt();
        $this->loadPayloadManifests();
        $bagInfo = $this->loadBagInfo();
        $tagManifest = $this->loadTagManifests();
        $this->loadFetch();
        $this->isExtended = ($bagInfo || $tagManifest);
    }

    /**
     * Create a new bag and output the default parts.
     *
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   If the bag root directory already exists.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems writing to the filesystem.
     */
    private function createNewBag()
    {
        $this->resetErrorsAndWarnings();
        if (file_exists($this->bagRoot)) {
            throw new BagItException("New bag directory {$this->bagRoot} exists");
        }
        BagUtils::checkedMkdir($this->bagRoot . DIRECTORY_SEPARATOR . "data", 0777, true);
        $this->updateBagIt();
        $this->payloadManifests = [
            self::DEFAULT_HASH_ALGORITHM => new PayloadManifest($this, self::DEFAULT_HASH_ALGORITHM)
        ];
    }

    /**
     * On new bag or load bag or update we need to refresh these containers.
     */
    private function resetErrorsAndWarnings()
    {
        $this->bagErrors = [];
        $this->bagWarnings = [];
    }

    /**
     * Update a fetch.txt if it exists.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     */
    private function updateFetch()
    {
        if (isset($this->fetchFile)) {
            $this->fetchFile->update();
        }
    }

    /**
     * Read in the bag-info.txt file.
     *
     * To support newlines in bag-info.txt any line that is less than 70 characters will have the newline at the end
     * maintained. Otherwise, the newline is considered an auto-wrap and is removed to not interfere with the text.
     *
     * @return boolean
     *   Does bag-info.txt exists.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Unable to read bag-info.txt
     */
    private function loadBagInfo()
    {
        $info_file = 'bag-info.txt';
        $fullPath = $this->makeAbsolute($info_file);
        if (file_exists($fullPath)) {
            $fp = fopen($fullPath, 'rb');
            if ($fp === false) {
                throw new FilesystemException("Unable to access {$info_file}");
            }
            $bagData = [];
            $lineCount = 0;
            while (!feof($fp)) {
                $line = fgets($fp);
                $lineCount += 1;
                if (trim($line) == "") {
                    continue;
                }
                $line = $this->decodeText($line);
                if (!empty($line)) {
                    $lineLength = strlen($line);
                    if (substr($line, 0, 2) == "  " || $line[0] == "\t") {
                        // Continuation of a line
                        if (count($bagData) > 0) {
                            $previousValue = $bagData[count($bagData)-1]['value'];
                            // Add a space only if the previous character was not a line break.
                            $lastChar = substr($previousValue, -1);
                            $previousValue .= ($lastChar != "\r" && $lastChar != "\n" ? " " :"");
                            $previousValue .= Bag::trimSpacesOnly($line);
                            if ($lineLength >= Bag::BAGINFO_AUTOWRAP_GUESS_LENGTH) {
                                // Line is max length or longer, should be autowrapped
                                $previousValue = rtrim($previousValue, "\r\n");
                            }
                            $bagData[count($bagData)-1]['value'] = $previousValue;
                        }
                    } elseif (preg_match("~^(\s+)?([^:]+?)(\s+)?:(.*)([\r\n]+)~", $line, $matches)) {
                        // First line
                        $current_tag = $matches[2];
                        if ($this->mustNotRepeatBagInfoExists($current_tag)) {
                            $this->addBagError(
                                $info_file,
                                "Tag {$current_tag} MUST not be repeated. (Line {$lineCount})"
                            );
                        } elseif ($this->shouldNotRepeatBagInfoExists($current_tag)) {
                            $this->addBagWarning(
                                $info_file,
                                "Tag {$current_tag} SHOULD NOT be repeated. (Line {$lineCount})"
                            );
                        }
                        if (($this->compareVersion('1.0') <=0) && (!empty($matches[1]) || !empty($matches[3]))) {
                            $this->addBagError(
                                $info_file,
                                "Labels cannot begin or end with a whitespace. (Line {$lineCount})"
                            );
                        }
                        if ($lineLength >= Bag::BAGINFO_AUTOWRAP_GUESS_LENGTH) {
                            // Line is max length or longer, should be autowrapped
                            $value = $matches[4];
                        } else {
                            // Shorter line, save the newline.
                            $value = $matches[4] . $matches[5];
                        }
                        $bagData[] = [
                            'tag' => $current_tag,
                            // Newline is removed by preg_match, re-add it here.
                            'value' => Bag::trimSpacesOnly($value),
                        ];
                    } elseif (!empty($line)) {
                        $this->addBagError($info_file, "Invalid tag.");
                    }
                }
            }
            fclose($fp);
            // We left newlines on the end of each tag value, those can be stripped.
            array_walk($bagData, function (&$item) {
                    $item['value'] = rtrim($item['value']);
            });
            $this->bagInfoData = $bagData;
            $this->updateBagInfoIndex();
            return true;
        }
        return false;
    }

    /**
     * Return a trimmed and lowercase version of text.
     *
     * @param  string $text
     *   The original text.
     * @return string
     *   The lowercase trimmed text.
     */
    private static function trimLower($text) : string
    {
        $text = strtolower($text);
        return trim($text);
    }

    /**
     * Just trim spaces NOT newlines and carriage returns.
     * @param $text
     *   The original text
     * @return string
     *   The text with surrounding spaces trimmed away.
     */
    private static function trimSpacesOnly($text)
    {
        return trim($text, " \t\0\x0B");
    }

    /**
     * Generate a faster index of Bag-Info tags.
     */
    private function updateBagInfoIndex()
    {
        $tags = [];
        foreach ($this->bagInfoData as $row) {
            $tagName = self::trimLower($row['tag']);
            if (!array_key_exists($tagName, $tags)) {
                $tags[$tagName] = [];
            }
            $tags[$tagName][] = $row['value'];
        }
        $this->bagInfoTagIndex = $tags;
    }

    /**
     * Internal case insensitive search of bag info.
     *
     * @param  string $internal_tag
     *   Trimmed and lowercase tag.
     * @return bool
     *   Does it exist in the index.
     */
    private function bagInfoTagExists($internal_tag) : bool
    {
        return array_key_exists($internal_tag, $this->bagInfoTagIndex);
    }

    /**
     * Write the contents of the bag-info array to disk.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues writing the file to disk.
     */
    private function updateBagInfo()
    {
        $fullPath = $this->makeAbsolute("bag-info.txt");
        $fp = fopen($fullPath, 'wb');
        if ($fp === false) {
            throw new FilesystemException("Could not write bag-info.txt");
        }
        $this->updateCalculateBagInfoFields();
        $this->updateBagInfoIndex();
        foreach ($this->bagInfoData as $bag_info_datum) {
            $tag = $bag_info_datum['tag'];
            $value = $bag_info_datum['value'];
            // We don't guarantee newlines remain once you edit a bag.
            $value = str_replace("\r\n", " ", $value);
            $value = str_replace("\n", " ", $value);
            $data = self::wrapBagInfoText("{$tag}: {$value}");
            foreach ($data as $line) {
                $line = $this->encodeText($line);
                BagUtils::checkedFwrite($fp, $line . PHP_EOL);
            }
        }
        fclose($fp);
    }

    /**
     * Update the calculated bag-info fields
     */
    private function updateCalculateBagInfoFields()
    {
        $newInfo = [];
        foreach ($this->bagInfoData as $row) {
            if (in_array(self::trimLower($row['tag']), self::BAG_INFO_GENERATED_ELEMENTS)) {
                continue;
            }
            $newInfo[] = $row;
        }
        $calculated = $this->calculateTotalFileSizeAndAmountOfFiles();
        if (!empty($calculated)) {
            $newInfo[] = [
                'tag' => 'Payload-Oxum',
                'value' => $calculated['totalFileSize'] . '.' . $calculated['totalFiles'],
            ];
            $newInfo[] = [
                'tag' => 'Bag-Size',
                'value' => $this->convertToHumanReadable($calculated['totalFileSize']),
            ];
        }
        $newInfo[] = [
            'tag' => 'Bagging-Date',
            'value' => date('Y-m-d', time()),
        ];
        $this->bagInfoData = $newInfo;
    }

    /**
     * Convert given byte value to a human readable value.
     *
     * @param int $bytes
     * @return string
     */
    private function convertToHumanReadable($bytes) : string
    {
        $symbols = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        if ($bytes > 0) {
            $bytesInFloat = floatval($bytes);
            $exp = floor(log($bytesInFloat) / log(1024));
            $result =  sprintf('%.2f ' . $symbols[$exp], ($bytesInFloat / pow(1024, floor($exp))));
        } else {
            $result = '0 B';
        }
        return $result;
    }

    /**
     * Remove the bag-info.txt file and data.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Unable to delete the bag-info.txt file.
     */
    private function removeBagInfo()
    {
        $fullPath = $this->makeAbsolute('bag-info.txt');
        if (file_exists($fullPath)) {
            BagUtils::checkedUnlink($fullPath);
        }
        $this->bagInfoData = [];
    }

    /**
     * Calculate the total file size and amount of files of all payload files.
     *
     * @return array|null
     *   The total file size and amount of all files or
     *   null if we couldn't read all the file sizes.
     */
    private function calculateTotalFileSizeAndAmountOfFiles()
    {
        $total_size = 0;
        $total_files = 0;
        foreach ($this->payloadFiles as $file) {
            $fullPath = $this->makeAbsolute($file);
            if (file_exists($fullPath) && is_file($fullPath)) {
                $info = stat($fullPath);
                if (isset($info[7])) {
                    $total_size += (int) $info[7];
                } else {
                    return null;
                }
                $total_files += 1;
            }
        }
        return [
            'totalFileSize' => $total_size,
            'totalFiles' => $total_files
            ];
    }

    /**
     * Wrap bagInfo lines to 79 characters if possible
     *
     * @param  string $text
     *   The whole tag and value as one.
     * @return array
     *   The text as an array.
     */
    private static function wrapBagInfoText($text) : array
    {
        // Start short of 79 for some leeway.
        $length = Bag::BAGINFO_AUTOWRAP_START;
        do {
            $rows = self::wrapAtLength($text, $length);
            $too_long = array_filter(
                $rows,
                function ($o) {
                    return strlen($o) > Bag::BAGINFO_AUTOWRAP_START;
                }
            );
            $length -= 1;
        } while ($length > 0 && count($too_long) > 0);
        if (count($too_long) > 0) {
            // No matter the size we couldn't get it to fit in 79 characters. So we give up.
            $rows = self::wrapAtLength($text, Bag::BAGINFO_AUTOWRAP_START);
        }
        for ($foo = 1; $foo < count($rows); $foo += 1) {
            $rows[$foo] = "  " . $rows[$foo];
        }
        return $rows;
    }

    /**
     * Utility to remove newline characters, wrap the string and return an array of the rows.
     *
     * @param  string $text
     *   The text to wrap.
     * @param  int    $length
     *   The length to wrap at.
     * @return array
     *   Rows of text.
     */
    private static function wrapAtLength($text, $length) : array
    {
        $text = str_replace("\n", "", $text);
        $wrapped = wordwrap($text, $length, "\n");
        return explode("\n", $wrapped);
    }

    /**
     * Load all tag manifests (if any).
     *
     * @return boolean
     *   Are there any tag manifest files.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems with glob() pattern or loading manifest.
     */
    private function loadTagManifests() : bool
    {
        $tagManifests = [];
        $pattern = $this->getBagRoot() . DIRECTORY_SEPARATOR . "tagmanifest-*.txt";
        $files = BagUtils::findAllByPattern($pattern);
        if (count($files) > 0) {
            foreach ($files as $file) {
                $hash = self::determineHashFromFilename($file);
                if (isset($tagManifests[$hash])) {
                    $this->addBagError(
                        $this->makeRelative($file),
                        "More than one tag manifest for hash ({$hash}) found."
                    );
                } else {
                    $tagManifests[$hash] = new TagManifest($this, $hash, true);
                }
            }
            $this->tagManifests = $tagManifests;
            return true;
        }
        return false;
    }

    /**
     * Utility to setup tag manifests.
     */
    private function ensureTagManifests()
    {
        if (!isset($this->tagManifests)) {
            $this->tagManifests = [];
        }
    }

    /**
     * Run update against the tag manifests.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues deleting the tag manifest files.
     */
    private function updateTagManifests()
    {
        if ($this->isExtended) {
            $this->clearTagManifests();
            $this->ensureTagManifests();
            $hashes = (is_array($this->payloadManifests) ? $this->payloadManifests :
                [self::DEFAULT_HASH_ALGORITHM => ""]);
            $hashes = array_diff_key($hashes, $this->tagManifests);
            foreach (array_keys($hashes) as $hash) {
                $this->tagManifests[$hash] = new TagManifest($this, $hash);
            }
            foreach ($this->tagManifests as $manifest) {
                $manifest->update();
            }
        }
    }

    /**
     * Remove all tagmanifest files.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *    Errors with glob() pattern or deleting files from filesystem.
     */
    private function clearTagManifests()
    {
        $pattern = $this->getBagRoot() . DIRECTORY_SEPARATOR . "tagmanifest-*.txt";
        $this->clearFilesOfPattern($pattern);
        unset($this->tagManifests);
    }

    /**
     * Remove tag manifests.
     *
     * @param array $exclusions
     *   Hash algorithm names of manifests to preserve.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues deleting files from the filesystem.
     */
    private function removeAllTagManifests($exclusions = [])
    {
        if (isset($this->tagManifests)) {
            foreach ($this->tagManifests as $hash => $manifest) {
                if (in_array($hash, $exclusions)) {
                    continue;
                }
                $this->removeTagManifest($hash);
            }
        }
    }

    /**
     * Remove a single tag manifest.
     *
     * @param string $internal_name
     *   The hash name to remove.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems deleting the tag manifest file.
     */
    private function removeTagManifest($internal_name)
    {
        $manifest = $this->tagManifests[$internal_name];
        $filename = $manifest->getFilename();
        if (file_exists($filename)) {
            BagUtils::checkedUnlink($this->makeAbsolute($filename));
        }
        unset($this->tagManifests[$internal_name]);
    }

    /**
     * Load all payload manifests found on disk.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems with glob() pattern
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   Invalid algorithm detected.
     */
    private function loadPayloadManifests()
    {
        $pattern = $this->getBagRoot() . DIRECTORY_SEPARATOR . "manifest-*.txt";
        $manifests = BagUtils::findAllByPattern($pattern);
        if (count($manifests) == 0) {
            $this->addBagError('manifest-ALG.txt', 'No payload manifest files found.');
        } else {
            $files = [];
            foreach ($manifests as $manifest) {
                $hash = self::determineHashFromFilename($manifest);
                $relative_filename = $this->makeRelative($manifest);
                if (!is_null($hash) && !in_array($hash, array_keys(self::HASH_ALGORITHMS))) {
                    throw new BagItException("We do not support the algorithm {$hash}");
                } elseif (is_null($hash)) {
                    $this->addBagError(
                        $relative_filename,
                        "Payload manifest MUST have a name in the form of manifest-ALG.txt"
                    );
                } elseif (isset($this->payloadManifests[$hash])) {
                    $this->addBagError(
                        $relative_filename,
                        "More than one payload manifest for hash ({$hash}) found."
                    );
                } else {
                    $temp = new PayloadManifest($this, $hash, true);
                    $this->payloadManifests[$hash] = $temp;
                    $files = array_merge($files, array_keys($temp->getHashes()));
                }
            }
            $this->payloadFiles = array_unique($files);
        }
    }

    /**
     * Run update against the payload manifests.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues deleting payload manifest files.
     */
    private function updatePayloadManifests()
    {
        if (!isset($this->payloadManifests)) {
            $manifest = new PayloadManifest($this, self::DEFAULT_HASH_ALGORITHM);
            $this->payloadManifests = [self::DEFAULT_HASH_ALGORITHM => $manifest];
        }
        // Delete all manifest files, before we update the current manifests.
        $this->clearPayloadManifests();
        $files = [];
        foreach ($this->payloadManifests as $manifest) {
            $manifest->update();
            $files = array_merge($files, array_keys($manifest->getHashes()));
        }
        $this->payloadFiles = array_unique($files);
    }

    /**
     * Remove all manifest files.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *    Errors with glob() pattern.
     */
    private function clearPayloadManifests()
    {
        $pattern = $this->getBagRoot() . DIRECTORY_SEPARATOR . "manifest-*.txt";
        $this->clearFilesOfPattern($pattern);
    }

    /**
     * Remove payload manifests.
     *
     * @param array $exclusions
     *   Hash algorithm names of manifests to preserve.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues deleting a file.
     */
    private function removeAllPayloadManifests($exclusions = [])
    {
        foreach ($this->payloadManifests as $hash => $manifest) {
            if (in_array($hash, $exclusions)) {
                continue;
            }
            $this->removePayloadManifest($hash);
        }
    }

    /**
     * Remove a single payload manifest.
     *
     * @param string $internal_name
     *   The hash name to remove.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues deleting the payload manifest file.
     */
    private function removePayloadManifest($internal_name)
    {
        $manifest = $this->payloadManifests[$internal_name];
        $filename = $manifest->getFilename();
        if (file_exists($filename)) {
            BagUtils::checkedUnlink($this->makeAbsolute($filename));
        }
        unset($this->payloadManifests[$internal_name]);
    }

    /**
     * Load the bagit.txt on disk.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Can't read the file on disk.
     */
    private function loadBagIt()
    {
        $fullPath = $this->makeAbsolute("bagit.txt");
        if (!file_exists($fullPath)) {
            $this->addBagError(
                'bagit.txt',
                'Required file missing.'
            );
        } else {
            $contents = file_get_contents($fullPath);
            if ($contents === false) {
                throw new FilesystemException("Unable to read {$fullPath}");
            }
            $lines = explode(PHP_EOL, $contents);
            // remove blank lines.
            $lines = array_filter($lines);
            array_walk(
                $lines,
                function (&$item) {
                    $item = trim($item);
                }
            );
            if (count($lines) !== 2) {
                $this->addBagError(
                    'bagit.txt',
                    sprintf(
                        "File MUST contain exactly 2 lines, found %b",
                        count($lines)
                    )
                );
            } else {
                if (!preg_match(
                    "~^BagIt\-Version: (\d+)\.(\d+)$~",
                    $lines[0],
                    $match
                )
                ) {
                    $this->addBagError(
                        'bagit.txt',
                        'First line should have pattern BagIt-Version: M.N'
                    );
                } else {
                    $this->currentVersion = [
                        'major' => $match[1],
                        'minor' => $match[2],
                    ];
                }
                if (!preg_match(
                    "~^Tag\-File\-Character\-Encoding: (.*)$~",
                    $lines[1],
                    $match
                )
                ) {
                    $this->addBagError(
                        'bagit.txt',
                        'Second line should have pattern ' .
                            'Tag-File-Character-Encoding: ENCODING'
                    );
                } else {
                    $this->currentFileEncoding = $match[1];
                }
            }
        }
    }

    /**
     * Update the bagit.txt on disk.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues putting contents to the file.
     */
    private function updateBagIt()
    {
        $version = $this->getVersion();

        $output = sprintf(
            "BagIt-Version: %d.%d" . PHP_EOL .
            "Tag-File-Character-Encoding: %s" . PHP_EOL,
            $version['major'],
            $version['minor'],
            $this->getFileEncoding()
        );

        // We don't use encodeText because this must always be UTF-8.
        $output = mb_convert_encoding($output, self::DEFAULT_FILE_ENCODING);

        BagUtils::checkedFilePut(
            $this->makeAbsolute("bagit.txt"),
            $output
        );
    }

    /**
     * Load a fetch.txt if it exists.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Unable to read fetch.txt for existing bag.
     */
    private function loadFetch()
    {
        $fullPath = $this->makeAbsolute('fetch.txt');
        if (file_exists($fullPath)) {
            $this->fetchFile = new Fetch($this, true);
            $this->mergeErrors($this->fetchFile->getErrors());
        }
    }

    /**
     * Create an archive file of the current bag.
     *
     * @param  string $filename
     *   The archive filename.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems creating the archive.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   Unable to determine archive format.
     */
    private function makePackage($filename)
    {
        $extension = self::getExtensions($filename);
        if (in_array($extension, self::ZIP_EXTENSIONS)) {
            $this->makeZip($filename);
        } elseif (in_array($extension, self::TAR_EXTENSIONS)) {
            $this->makeTar($filename);
        } else {
            throw new BagItException("Unable to determine archive format.");
        }
    }

    /**
     * Create a Zip archive.
     *
     * @param  string $filename
     *   The archive filename.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems creating the archive.
     */
    private function makeZip($filename)
    {
        $zip = new \ZipArchive;
        $res = $zip->open($filename, \ZipArchive::CREATE);
        if ($res === true) {
            $files = BagUtils::getAllFiles($this->bagRoot);
            $parentPrefix = basename($this->bagRoot);
            foreach ($files as $file) {
                $relative = $this->makeRelative($file);
                $zip->addFile($file, "{$parentPrefix}/{$relative}");
            }
            $zip->close();
        } else {
            throw new FilesystemException("Unable to create zip file");
        }
    }

    /**
     * Create a Tar archive.
     *
     * @param  string $filename
     *   The archive filename.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems creating the archive.
     */
    private function makeTar($filename)
    {
        $extension = self::getExtensions($filename);
        $compression = self::extensionTarCompression($extension);
        $tar = new \Archive_Tar($filename, $compression);
        if ($tar === false) {
            throw new FilesystemException("Error creating Tar file.");
        }
        $parent = $this->getParentDir();
        $files = BagUtils::getAllFiles($this->bagRoot);
        if (!$tar->createModify($files, "", $parent)) {
            throw new FilesystemException("Error adding files to {$filename}.");
        }
    }

    /**
     * Get the parent directory of the current Bag.
     *
     * @return string
     *   The parent directory.
     */
    private function getParentDir() : string
    {
        $fullPath = $this->bagRoot;
        return dirname($fullPath);
    }

    /**
     * Uncompress a BagIt archive file.
     *
     * @param  string $filepath
     *   The full path to the archive file.
     * @return string
     *   The full path to extracted bag.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems accessing and/or uncompressing files on filesystem.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   Unable to determine correct archive format or file does not exist.
     */
    private static function uncompressBag($filepath) : string
    {
        if (file_exists($filepath)) {
            $extension = self::getExtensions($filepath);
            if (in_array($extension, self::ZIP_EXTENSIONS)) {
                $directory = self::unzipBag($filepath);
            } elseif (in_array($extension, self::TAR_EXTENSIONS)) {
                $directory = self::untarBag($filepath, $extension);
            } else {
                throw new BagItException("Unable to determine archive format.");
            }
            // $directory contains the directory with the bag, so find it.
            return self::getDirectory($directory);
        }
        throw new BagItException("File {$filepath} does not exist.");
    }

    /**
     * Unzip a zip file.
     *
     * @param  string $filename
     *   The full path to the zip file.
     * @return string
     *   The path the archive file was extracted to.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems extracting the zip file.
     */
    private static function unzipBag($filename) : string
    {
        $zip = new \ZipArchive;
        $res = $zip->open($filename);
        if ($res === false) {
            throw new FilesystemException("Unable to unzip {$filename}");
        }
        $directory = self::extractDir();
        $zip->extractTo($directory);
        $zip->close();
        return $directory;
    }

    /**
     * Untar a tar file.
     *
     * @param  string $filename
     *   The fullpath to the tar file.
     * @param  string $extension
     *   The extension pulled from the filename.
     * @return string
     *   The path the archive file was extracted to.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems extracting the zip file.
     */
    private static function untarBag($filename, $extension) : string
    {
        $compression = self::extensionTarCompression($extension);
        $directory = self::extractDir();
        $tar = new \Archive_Tar($filename, $compression);
        $res = $tar->extract($directory);
        if ($res === false) {
            throw new FilesystemException("Usable to untar {$filename}");
        }
        return $directory;
    }

    /**
     * Determine the correct compression (if any) from the extension.
     *
     * @param  string $extension
     *   The extension.
     * @return string|null
     *   The compression string or null for no compression.
     */
    private static function extensionTarCompression($extension)
    {
        return (substr($extension, -3) == 'bz2' ? 'bz2' : (substr($extension, -2) == 'gz' ? 'gz' :
            null));
    }

    /**
     * Generate a temporary directory name.
     *
     * @return string
     *   The path to a new temporary directory.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues creating/deleting files on filesystem.
     */
    private static function extractDir() : string
    {
        $temp = BagUtils::checkedTempnam();
        BagUtils::checkedUnlink($temp);
        BagUtils::checkedMkdir($temp);
        return $temp;
    }

    /**
     * Test a filepath to see if we think it is compressed.
     *
     * @param  string $filepath
     *   The full path
     * @return bool
     *   True if compressed file (we support).
     */
    private static function isCompressed($filepath) : bool
    {
        return (in_array(
            self::getExtensions($filepath),
            array_merge(
                self::ZIP_EXTENSIONS,
                self::TAR_EXTENSIONS
            )
        ));
    }

    /**
     * Get the file extension from a full path.
     *
     * @param  string $filepath
     *   The full file path.
     * @return string
     *   The extension or an empty string.
     */
    private static function getExtensions($filepath) : string
    {
        $filename = strtolower(basename($filepath));
        if (strpos($filename, '.') !== false) {
            return substr($filename, strpos($filename, '.') + 1);
        }
        return '';
    }

    /**
     * Locate the extracted bag directory from inside our temporary directory.
     *
     * @param  string $filepath
     *   The temporary directory.
     * @return string
     *   The bag directory.
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     *   Find more or less than one directory (not including . and ..)
     */
    private static function getDirectory($filepath) : string
    {
        $files = scandir($filepath);
        $dirs = [];
        if (count($files) > 0) {
            foreach ($files as $file) {
                if (BagUtils::isDotDir($file)) {
                    continue;
                }
                $fullpath = $filepath . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fullpath)) {
                    $dirs[] = $fullpath;
                }
            }
        }
        if (count($dirs) !== 1) {
            throw new BagItException("Found multiple root level directories inside archive file.");
        }
        return reset($dirs);
    }

    /**
     * Utility to remove files using a pattern.
     *
     * @param  string $filePattern
     *   The file pattern.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problems matching or deleting files.
     */
    private function clearFilesOfPattern($filePattern)
    {
        $files = BagUtils::findAllByPattern($filePattern);
        if (count($files) > 0) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    BagUtils::checkedUnlink($file);
                }
            }
        }
    }

    /**
     * Utility function to add bag error.
     *
     * @param string $filename
     *   The file the error was detected in.
     * @param string $message
     *   The message.
     */
    private function addBagError($filename, $message)
    {
        $this->bagErrors[] = [
            'file' => $filename,
            'message' => $message
        ];
    }

    /**
     * Utility function to add bag error.
     *
     * @param string $filename
     *   The file the error was detected in.
     * @param string $message
     *   The message.
     */
    private function addBagWarning($filename, $message)
    {
        $this->bagWarnings[] = [
            'file' => $filename,
            'message' => $message
        ];
    }

    /**
     * Convert paths from using the OS directory separator to using /.
     *
     * @param  string $path
     *   The external path.
     * @return string
     *   The modified path.
     */
    private function internalPath($path) : string
    {
        return str_replace(DIRECTORY_SEPARATOR, "/", $path);
    }

    /**
     * Normalize a PHP hash algorithm to a BagIt specification name. Used to alter the incoming $item.
     *
     * @param string $item
     *   The hash algorithm name.
     */
    private static function normalizeHashAlgorithmName(&$item)
    {
        $item = array_flip(self::HASH_ALGORITHMS)[$item];
    }

    /**
     * Check if the algorithm PHP has is allowed by the specification.
     *
     * @param string $item
     *   A hash algorithm name.
     *
     * @return boolean
     *   True if allowed by the specification.
     */
    private static function filterPhpHashAlgorithms($item) : bool
    {
        return in_array($item, array_values(self::HASH_ALGORITHMS));
    }

    /**
     * Return the BagIt sanitized algorithm name.
     *
     * @param  string $algorithm
     *   A algorithm name
     * @return string
     *   The sanitized version of algorithm or an empty string if invalid.
     */
    public static function getHashName($algorithm) : string
    {
        $algorithm = Bag::trimLower($algorithm);
        $algorithm = preg_replace("/[^a-z0-9]/", "", $algorithm);
        if (in_array($algorithm, array_keys(Bag::HASH_ALGORITHMS))) {
            return $algorithm;
        }
        return "";
    }

    /**
     * Do we have a payload manifest with this internal hash name. Internal use only to avoid getHashName()
     *
     * @param  string $internal_name
     *   Internal name from getHashName.
     * @return boolean
     *   Already have this algorithm.
     */
    private function hasHash($internal_name) : bool
    {
        return (in_array($internal_name, array_keys($this->payloadManifests)));
    }

    /**
     * Is the internal named hash supported by our PHP. Internal use only to avoid getHashName()
     *
     * @param  string $internal_name
     *   Output of getHashName
     * @return boolean
     *   Do we support the algorithm
     */
    private function hashIsSupported($internal_name) : bool
    {
        return ($internal_name != null && in_array($internal_name, $this->validHashAlgorithms));
    }

    /**
     * Case-insensitive version of array_key_exists
     *
     * @param  string     $search The key to look for.
     * @param  string|int $key    The associative or numeric key to look in.
     * @param  array      $map    The associative array to search.
     * @return boolean True if the key exists regardless of case.
     */
    private static function arrayKeyExistsNoCase($search, $key, array $map) : bool
    {
        $keys = array_column($map, $key);
        array_walk(
            $keys,
            function (&$item) {
                $item = strtolower($item);
            }
        );
        return in_array(strtolower($search), $keys);
    }

    /**
     * Check that the key is not non-repeatable and already in the bagInfo.
     *
     * @param string $key The key being added.
     *
     * @return boolean
     *   True if the key is non-repeatable and already in the
     */
    private function mustNotRepeatBagInfoExists($key) : bool
    {
        return (in_array(strtolower($key), self::BAG_INFO_MUST_NOT_REPEAT) &&
            self::arrayKeyExistsNoCase($key, 'tag', $this->bagInfoData));
    }

    /**
     * Check that the key is not non-repeatable and already in the bagInfo.
     *
     * @param string $key The key being added.
     *
     * @return boolean
     *   True if the key is non-repeatable and already in the
     */
    private function shouldNotRepeatBagInfoExists($key) : bool
    {
        return (in_array(strtolower($key), self::BAG_INFO_SHOULD_NOT_REPEAT) &&
            self::arrayKeyExistsNoCase($key, 'tag', $this->bagInfoData));
    }

    /**
     * Parse manifest/tagmanifest file names to determine hash algorithm.
     *
     * @param string $filepath the filename.
     *
     * @return string|null the hash or null.
     */
    private static function determineHashFromFilename($filepath)
    {
        $filename = basename($filepath);
        if (preg_match('~\-([a-z0-9]+)\.txt$~', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }


    /**
     * Is the requested destination filename reserved on Windows?
     *
     * @param  string $filepath
     *   The relative filepath.
     * @return boolean
     *   True if a reserved filename.
     */
    private function reservedFilename($filepath) : bool
    {
        $filename = substr($filepath, strrpos($filepath, '/') + 1);
        return (in_array(strtoupper($filename), self::WINDOWS_RESERVED_NAMES));
    }

    /**
     * Compare the provided version against the current one.
     *
     * @param  string $version
     *   The version to compare against.
     * @return int
     *   returns -1 $version < current, 0  $version == current, and 1 $version > current.
     */
    private function compareVersion($version) : int
    {
        return version_compare($version, $this->getVersionString());
    }

    /**
     * Utility to merge manifest and fetch errors into the bag errors.
     *
     * @param array $newErrors
     *   The new errors to be added.
     */
    private function mergeErrors(array $newErrors)
    {
        $this->bagErrors = array_merge($this->bagErrors, $newErrors);
    }

    /**
     * Utility to merge manifest and fetch warnings into the bag warnings.
     *
     * @param array $newWarnings
     *   The new warnings to be added.
     */
    private function mergeWarnings(array $newWarnings)
    {
        $this->bagWarnings = array_merge($this->bagWarnings, $newWarnings);
    }
}
