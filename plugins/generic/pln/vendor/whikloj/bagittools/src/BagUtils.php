<?php

namespace whikloj\BagItTools;

use whikloj\BagItTools\Exceptions\FilesystemException;

/**
 * Utility class to hold static functions.
 *
 * @package whikloj\BagItTools
 * @author whikloj
 * @since 1.0.0
 */
class BagUtils
{

    /**
     * Valid character set MIME names from IANA.
     */
    private const CHARACTER_SETS = [
        "utf-8" => "UTF-8",
        "utf-16" => "UTF-16",
        "us-ascii" => "US-ASCII",
        "iso-8859-1" => "ISO-8859-1",
        "iso-8859-2" => "ISO-8859-2",
        "iso-8859-3" => "ISO-8859-3",
        "iso-8859-4" => "ISO-8859-4",
        "iso-8859-5" => "ISO-8859-5",
        "iso-8859-6" => "ISO-8859-6",
        "iso-8859-7" => "ISO-8859-7",
        "iso-8859-8" => "ISO-8859-8",
        "iso-8859-9" => "ISO-8859-9",
        "iso-8859-10" => "ISO-8859-10",
        "shift_jis" => "Shift_JIS",
        "euc-jp" => "EUC-JP",
        "iso-2022-kr" => "ISO-2022-KR",
        "euc-kr" => "EUC-KR",
        "iso-2022-jp" => "ISO-2022-JP",
        "iso-2022-jp-2" => "ISO-2022-JP-2",
        "iso-8859-6-e" => "ISO-8859-6-E",
        "iso-8859-6-i" => "ISO-8859-6-I",
        "iso-8859-8-e" => "ISO-8859-8-E",
        "iso-8859-8-i" => "ISO-8859-8-I",
        "gb2312" => "GB2312",
        "big5" => "Big5",
        "koi8-r" => "KOI8-R",
    ];

    /**
     * BagUtils constructor.
     */
    private function __construct()
    {
        // This constructor left intentionally blank.
    }

    /**
     * Utility to test a filename as . or ..
     *
     * @param string $filename
     *    Basename of a file or directory.
     * @return bool
     *    True if it is a dot directory name.
     */
    public static function isDotDir($filename) : bool
    {
        return ($filename == "." || $filename == "..");
    }

    /**
     * Rebase the path in the data directory as payloads only deal in there.
     *
     * @param string $path
     *   The provided path.
     * @return string
     *   The (possibly) rebased path.
     */
    public static function baseInData($path) : string
    {
        if (substr($path, 0, 5) !== 'data/') {
            $path = "data/" . ltrim($path, "/");
        }
        return $path;
    }

    /**
     * Return all files that match the pattern, or an empty array.
     *
     * @param string $pattern
     *   The pattern to search for.
     *
     * @return array
     *   Array of matches.
     *
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Error in matching pattern.
     */
    public static function findAllByPattern($pattern) : array
    {
        $matches=glob($pattern);
        if ($matches === false) {
            throw new FilesystemException("Error matching pattern {$pattern}");
        }
        return $matches;
    }

    /**
     * Check the provided lower case name of a character set against our list. If we have it, return the proper MIME
     * name.
     * @param string $charset
     *   The trimmed lowercase version of the character set MIME name.
     * @return string|null
     *   The proper name or null if we don't have it.
     */
    public static function getValidCharset($charset)
    {
        if (in_array($charset, array_keys(self::CHARACTER_SETS))) {
            return self::CHARACTER_SETS[$charset];
        }
        return null;
    }

    /**
     * There is a method that deal with Sven Arduwie proposal https://www.php.net/manual/en/function.realpath.php#84012
     * And runeimp at gmail dot com proposal https://www.php.net/manual/en/function.realpath.php#112367
     * @author  moreau.marc.web@gmail.com
     * @param string $path
     *   The path to decode.
     * @param bool $add_absolute
     *   Whether to prepend the current working directory if the path is relative.
     * @return string
     */
    public static function getAbsolute($path, bool $add_absolute = false) : string
    {
        // Cleaning path regarding OS
        $path = mb_ereg_replace('\\\\|/', DIRECTORY_SEPARATOR, $path, 'msr');
        // Check if path start with a separator (UNIX)
        $startWithSeparator = $path[0] === DIRECTORY_SEPARATOR;
        // Check if start with drive letter
        preg_match('/^[a-z]:/', $path, $matches);
        $startWithLetterDir = isset($matches[0]) ? $matches[0] : false;

        // whikloj - 2021-07-05 : Make sure we are using an absolute path.
        if (!($startWithLetterDir || $startWithSeparator) && $add_absolute) {
            // This was relative to start with, prepend the current working directory.
            $current_dir = getcwd();
            return BagUtils::getAbsolute(rtrim($current_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                ltrim($path, DIRECTORY_SEPARATOR));
        }

        // Get and filter empty sub paths
        $subPaths = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'mb_strlen');

        $absolutes = [];
        foreach ($subPaths as $subPath) {
            if ('.' === $subPath) {
                continue;
            }
            // if $startWithSeparator is false
            // and $startWithLetterDir
            // and (absolutes is empty or all previous values are ..)
            // save absolute cause that's a relative and we can't deal with that and just forget that we want go up
            if ('..' === $subPath
                && !$startWithSeparator
                && !$startWithLetterDir
                && empty(array_filter($absolutes, function ($value) {
                    return !('..' === $value);
                }))
            ) {
                $absolutes[] = $subPath;
                continue;
            }
            if ('..' === $subPath) {
                array_pop($absolutes);
                continue;
            }
            $absolutes[] = $subPath;
        }

        return
            (($startWithSeparator ? DIRECTORY_SEPARATOR : $startWithLetterDir) ?
                $startWithLetterDir . DIRECTORY_SEPARATOR : ''
            ) . implode(DIRECTORY_SEPARATOR, $absolutes);
    }

    /**
     * Paths for new and existing files should not have these conditions.
     *
     * @param string $path
     *   The relative path from an existing bag file or as a destination for a new file.
     * @return bool
     *   True if invalid characters/character sequences exist.
     */
    public static function invalidPathCharacters($path) : bool
    {
        $path = urldecode($path);
        return ($path[0] === DIRECTORY_SEPARATOR || strpos($path, "~") !== false ||
            substr($path, 0, 3) == "../");
    }

    /**
     * Recursively list all files in a directory, except dot files.
     *
     * @param string $directory
     *   The starting full path.
     * @param array $exclusions
     *   Array with directory names to skip.
     * @return array
     *   List of files with absolute path.
     */
    public static function getAllFiles($directory, $exclusions = []) : array
    {
        $paths = [$directory];
        $found_files = [];

        while (count($paths) > 0) {
            $currentPath = array_shift($paths);
            $files = scandir($currentPath);
            foreach ($files as $file) {
                if (self::isDotDir($file)) {
                    continue;
                }
                $fullPath = $currentPath . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fullPath) && !in_array($file, $exclusions)) {
                    $paths[] = $fullPath;
                } elseif (is_file($fullPath)) {
                    $found_files[] = $fullPath;
                }
            }
        }
        return $found_files;
    }

    /**
     * Copy a file and check that the copy succeeded.
     *
     * @param string $sourceFile
     *   The source path.
     * @param string $destFile
     *   The destination path.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   If the copy() call fails.
     * @see \copy()
     */
    public static function checkedCopy($sourceFile, $destFile)
    {
        if (!@copy($sourceFile, $destFile)) {
            throw new FilesystemException("Unable to copy file ({$sourceFile}) to ({$destFile})");
        }
    }

    /**
     * Make a directory (or directories) and check it succeeds.
     *
     * @param string $path
     *   The path to create.
     * @param int $mode
     *   The permissions on the new directories.
     * @param bool $recursive
     *   Whether to create intermediate directories automatically.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   If the mkdir() call fails.
     * @see \mkdir()
     */
    public static function checkedMkdir($path, $mode = 0777, $recursive = false)
    {
        if (!@mkdir($path, $mode, $recursive)) {
            throw new FilesystemException("Unable to create directory {$path}");
        }
    }

    /**
     * Put contents to a file and check it succeeded.
     *
     * @param string $path
     *   The path of the file.
     * @param mixed $contents
     *   The contents to put
     * @param int $flags
     *   Flags to pass on to file_put_contents.
     * @return int
     *   Number of bytes written to the file.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   On any error putting the contents to the file.
     * @see \file_put_contents()
     */
    public static function checkedFilePut($path, $contents, $flags = 0)
    {
        $res = @file_put_contents($path, $contents, $flags);
        if ($res === false) {
            throw new FilesystemException("Unable to put contents to file {$path}");
        }
        return $res;
    }

    /**
     * Delete a file/directory and check it succeeded.
     *
     * @param string $path
     *   The path to remove.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   If the call to unlink() fails.
     * @see \unlink()
     */
    public static function checkedUnlink($path)
    {
        if (!@unlink($path)) {
            throw new FilesystemException("Unable to delete path {$path}");
        }
    }

    /**
     * Create a temporary file and check it succeeded.
     *
     * @param string $directory
     *   The directory to create the file in.
     * @param string $prefix
     *   The prefix to the file.
     * @return string
     *   The path to the temporary filename.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Issues creating the file.
     * @see \tempnam()
     */
    public static function checkedTempnam($directory = "", $prefix = "") : string
    {
        $res = @tempnam($directory, $prefix);
        if ($res === false) {
            throw new FilesystemException("Unable to create a temporary file with directory ${directory}, prefix" .
            " {$prefix}");
        }
        return $res;
    }

    /**
     * Write to a file resource and check it succeeded.
     *
     * @param Resource $fp
     *   The file pointer.
     * @param string $content
     *   The content to write.
     * @throws \whikloj\BagItTools\Exceptions\FilesystemException
     *   Problem writing to file.
     */
    public static function checkedFwrite($fp, $content)
    {
        try {
            $res = @fwrite($fp, $content);
            if ($res === false) {
                throw new FilesystemException("Error writing to file");
            }
        } catch (\TypeError $e) {
            throw new FilesystemException("Error writing to file");
        }
    }
}
