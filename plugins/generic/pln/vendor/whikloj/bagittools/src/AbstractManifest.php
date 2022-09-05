<?php

namespace whikloj\BagItTools;

use whikloj\BagItTools\Exceptions\BagItException;
use whikloj\BagItTools\Exceptions\FilesystemException;

/**
 * Abstract manifest class to hold common elements between Payload and Tag manifests.
 *
 * @package whikloj\BagItTools
 * @author whikloj
 * @since 1.0.0
 */
abstract class AbstractManifest
{

    /**
     * The bag this manifest is part of.
     *
     * @var \whikloj\BagItTools\Bag
     */
    protected $bag;

    /**
     * The hash algorithm for this manifest.
     *
     * @var string
     */
    protected $algorithm;

    /**
     * Associative array where paths are keys and hashes are values.
     *
     * @var array
     */
    protected $hashes = [];

    /**
     * Array of the same paths as in $hashes but normalized for case and characters to check for duplication.
     *
     * @var array
     */
    protected $normalizedPaths = [];

    /**
     * The filename for this manifest.
     *
     * @var string
     */
    protected $filename;

    /**
     * Array of files on disk to validate against.
     *
     * @var array
     */
    protected $filesOnDisk = [];

    /**
     * Errors while validating this manifest.
     *
     * @var array
     */
    protected $manifestErrors = [];

    /**
     * Warnings generated while validating this manifest.
     *
     * @var array
     */
    protected $manifestWarnings = [];

    /**
     * Errors/Warnings generated while loading.
     * Because of the path key in the hash array if there are multiple entries for a path we need to track it during
     * load but present it at validate().
     *
     * @var array
     *   Array of arrays with keys 'error' and 'warning'
     * @see \whikloj\BagItTools\AbstractManifest::resetLoadIssues()
     */
    protected $loadIssues;

    /**
     * Manifest constructor.
     *
     * @param \whikloj\BagItTools\Bag $bag
     *   The bag this manifest is part of.
     * @param string $algorithm
     *   The BagIt name of the hash algorithm.
     * @param string $filename
     *   The manifest filename.
     * @param boolean $load
     *   Whether we are loading an existing file
     */
    protected function __construct(Bag $bag, $algorithm, $filename, $load = false)
    {
        $this->bag = $bag;
        $this->algorithm = $algorithm;
        $this->filename = $filename;
        $this->resetLoadIssues();

        if ($load) {
            $this->loadFile();
        }
    }

    /**
     * Return the algorithm for this manifest.
     *
     * @return string
     */
    public function getAlgorithm() : string
    {
        return $this->algorithm;
    }

    /**
     * Return the filename of this manifest.
     *
     * @return string
     */
    public function getFilename() : string
    {
        return $this->filename;
    }

    /**
     * Return the array of errors.
     *
     * @return array
     */
    public function getErrors() : array
    {
        return $this->manifestErrors;
    }

    /**
     * Return the array of warnings.
     *
     * @return array
     */
    public function getWarnings() : array
    {
        return $this->manifestWarnings;
    }

    /**
     * Update the hashes for each path.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Error writing the manifest file to disk.
     */
    public function update()
    {
        $newHashes = [];
        foreach ($this->hashes as $path => $hash) {
            $newHashes[$path] = $this->calculateHash($this->bag->makeAbsolute($path));
        }
        $this->hashes = $newHashes;
        $this->writeToDisk();
    }

    /**
     * Compare file hashes against what is on disk.
     */
    public function validate()
    {
        $this->manifestWarnings = [] + $this->loadIssues['warning'];
        $this->manifestErrors = [] + $this->loadIssues['error'];
        if ($this->algorithm == 'md5') {
            $this->addWarning("This manifest is MD5, you should use setAlgorithm('sha512') to upgrade.");
        }
        foreach ($this->hashes as $path => $hash) {
            $fullPath = $this->bag->makeAbsolute($path);
            $fullPath = $this->cleanUpAbsPath($fullPath);
            $this->validatePath($path, $fullPath);
            if (file_exists($fullPath)) {
                $calculatedHash = strtolower($this->calculateHash($fullPath));
                $hash = strtolower($hash);
                if ($hash !== $calculatedHash) {
                    $this->addError("{$path} calculated hash ({$calculatedHash}) does not match manifest " .
                        "({$hash})");
                }
            }
        }
    }

    /**
     * Return the payload and hashes as an associative array.
     *
     * @return array
     *   Array of paths => hashes
     */
    public function getHashes() : array
    {
        return $this->hashes;
    }

    /*
     * Protected functions.
     */

    /**
     * Common checks for paths being added to a manifest.
     *
     * @param string $path
     *   The path relative to the bag root.
     * @param string $filepath
     *   The absolute filepath.
     */
    protected function validatePath($path, $filepath)
    {
        if (!file_exists($filepath)) {
            $this->addError("{$path} does not exist.");
        } elseif ($this->bag->makeRelative($filepath) === "") {
            $this->addError("{$path} resolves to a path outside of the data/ directory.");
        }
    }

    /**
     * Load the paths and hashes from the file on disk, does not validate.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Unable to read manifest file.
     */
    protected function loadFile()
    {
        $this->hashes = [];
        $this->resetLoadIssues();
        $fullPath = $this->bag->makeAbsolute($this->filename);
        if (file_exists($fullPath)) {
            $fp = fopen($fullPath, "rb");
            if ($fp === false) {
                throw new FilesystemException("Unable to read file {$fullPath}");
            }
            $lineCount = 0;
            while (!feof($fp)) {
                $lineCount += 1;
                $line = fgets($fp);
                $line = $this->bag->decodeText($line);
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                if (preg_match("~^(\w+)\s+\*?(.*)$~", $line, $matches)) {
                    $hash = $matches[1];
                    $originalPath = $matches[2];
                    if (substr($originalPath, 0, 2) == "./") {
                        $this->addLoadWarning("Line {$lineCount} : Paths SHOULD not be relative");
                    }
                    $path = $this->cleanUpRelPath($originalPath);
                    // Normalized path in lowercase (for matching)
                    $lowerNormalized = $this->normalizePath($path);
                    if (array_key_exists($path, $this->hashes)) {
                        $this->addLoadError("Line {$lineCount} : Path {$originalPath} appears more than once in " .
                            "manifest.");
                    } elseif ($this->matchNormalizedList($lowerNormalized)) {
                        $this->addLoadWarning("Line {$lineCount} : Path {$originalPath} matches another file when " .
                            "normalized for case and characters.");
                    } else {
                        $this->hashes[$path] = $hash;
                        $this->addToNormalizedList($lowerNormalized);
                    }
                }
            }
            fclose($fp);
        }
    }

    /**
     * Utility to recreate the manifest file using the currently stored hashes.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   If we can't write the manifest files.
     */
    protected function writeToDisk()
    {
        $fullPath = $this->bag->makeAbsolute($this->filename);
        if (file_exists($fullPath)) {
            BagUtils::checkedUnlink($fullPath);
        }
        $fp = fopen(addslashes($fullPath), "w");
        if ($fp === false) {
            throw new FilesystemException("Unable to write {$fullPath}");
        }
        foreach ($this->hashes as $path => $hash) {
            $line = "{$hash} {$path}" . PHP_EOL;
            $line = $this->bag->encodeText($line);
            BagUtils::checkedFwrite($fp, $line);
        }
        fclose($fp);
    }

    /**
     * Calculate the hash of the file.
     *
     * @param string $file
     *   Absolute path to the file.
     *
     * @return string
     *   The hash.
     */
    protected function calculateHash($file) : string
    {
        return hash_file($this->getPhpHashName(), $file);
    }

    /**
     * Add an error using the current filename.
     *
     * @param string $message
     *   The error text.
     */
    protected function addError($message)
    {
        $this->manifestErrors[] = [
            'file' => $this->filename,
            'message' => $message,
        ];
    }

    /**
     * Add a warning using the current filename.
     *
     * @param string $message
     *   The error text.
     */
    protected function addWarning($message)
    {
        $this->manifestWarnings[] = [
            'file' => $this->filename,
            'message' => $message,
        ];
    }

    /**
     * Needed to account for differences in PHP hash to BagIt hash naming.
     *
     * i.e. BagIt sha3512 -> PHP sha3-512
     *
     * @return string the PHP hash name for the internal hash encoding.
     */
    protected function getPhpHashName() : string
    {
        return Bag::getHashName($this->algorithm);
    }

    /*
     * Private functions
     */

    /**
     * Add a path to the list of normalized paths.
     *
     * @param string $path
     *   The normalized path.
     */
    private function addToNormalizedList($path)
    {
        $this->normalizedPaths[] = $path;
    }

    /**
     * Compare a path against a list of normalized paths and look for matches.
     *
     * @param string $path
     *   The normalized path to look for.
     * @return bool
     *   True if there is a match.
     */
    private function matchNormalizedList($path) : bool
    {
        return (in_array($this->normalizePath($path), $this->normalizedPaths));
    }

    /**
     * Normalize a path for character representation and case.
     *
     * @param string $path
     *   The path.
     * @param bool $toLower
     *   Whether to also lowercase the string.
     * @return string
     *   The normalized path.
     */
    private function normalizePath($path, $toLower = true)
    {
        $path = urldecode($path);
        if ($toLower) {
            $path = strtolower($path);
        }
        if (!\Normalizer::isNormalized($path)) {
            $path = \Normalizer::normalize($path);
        }
        return $path;
    }

    /**
     * Clean up file paths to remove extraneous period, double period and slashes
     *
     * @param string $filepath
     *   The absolute file path
     * @return string
     *   The cleaned up absolute file path, not resolved on disk.
     */
    private function cleanUpAbsPath($filepath) : string
    {
        $filepath = trim($filepath);
        return BagUtils::getAbsolute($filepath);
    }

    /**
     * Clean up file paths to remove extraneous periods, double periods and slashes
     *
     * @param string $filepath
     *   The relative file path.
     * @return string
     *   The cleaned up relative file path or blank if not in the bag Root.
     */
    private function cleanUpRelPath($filepath) : string
    {
        $filepath = $this->bag->makeAbsolute($filepath);
        $filepath = $this->cleanUpAbsPath($filepath);
        return $this->bag->makeRelative($filepath);
    }

    /**
     * Add a load error using the current filename. This is only erased on a new load.
     *
     * @param string $message
     *   The error text.
     */
    private function addLoadError($message)
    {
        $this->loadIssues['error'][] = [
            'file' => $this->filename,
            'message' => $message,
        ];
    }

    /**
     * Add a load warning using the current filename. This is only erased on a new load.
     *
     * @param string $message
     *   The error text.
     */
    private function addLoadWarning($message)
    {
        $this->loadIssues['warning'][] = [
            'file' => $this->filename,
            'message' => $message,
        ];
    }

    /**
     * Utility to reset the load issues construct.
     */
    private function resetLoadIssues()
    {
        $this->loadIssues = [
            'error' => [],
            'warning' => [],
        ];
    }
}
