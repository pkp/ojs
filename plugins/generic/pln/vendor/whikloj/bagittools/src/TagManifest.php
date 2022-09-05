<?php

namespace whikloj\BagItTools;

/**
 * Tag Manifest extension of AbstractManifest class.
 *
 * @package whikloj\BagItTools
 * @author whikloj
 * @since 1.0.0
 */
class TagManifest extends AbstractManifest
{

  /**
   * PayloadManifest constructor.
   *
   * @param \whikloj\BagItTools\Bag $bag
   *   The bag this manifest is part of.
   * @param string $algorithm
   *   The BagIt name of the hash algorithm.
   * @param boolean $load
   *   Whether we are loading an existing file
   */
    public function __construct(Bag $bag, $algorithm, $load = false)
    {
        parent::__construct($bag, $algorithm, "tagmanifest-{$algorithm}.txt", $load);
    }

  /**
   * {@inheritdoc}
   */
    public function update()
    {
        $this->hashes = [];
        $files = BagUtils::getAllFiles($this->bag->getBagRoot(), ["data"]);
        foreach ($files as $file) {
            if (!$this->isTagManifest($file)) {
                $this->hashes[$this->bag->makeRelative($file)] = "";
            }
        }
        parent::update();
    }

    /**
     * {@inheritdoc}
     */
    public function validate()
    {
        parent::validate();
        $onDisk = BagUtils::getAllFiles($this->bag->getBagRoot(), ["data"]);
        $onDisk = array_filter($onDisk, function ($o) {
            return !$this->isTagManifest($o);
        });
        $tagfiles = array_filter(array_keys($this->hashes), function ($o) {
            return $this->isTagManifest($o);
        });
        if (count($tagfiles) > 0) {
            $this->addError("MUST not list any tag files");
        }
        // 1.0 Spec says each manifest SHOULD list every file other than tagmanifests.
        array_walk($onDisk, function (&$item) {
            $item = $this->bag->makeRelative($item);
        });
        $onDisk = array_diff($onDisk, array_keys($this->hashes));
        if (count($onDisk) > 0) {
            $this->addWarning("There are files on disk not listed in this manifest file.");
        }
    }

    /**
     * Is the filename match a tag manifest file?
     *
     * @param string $filepath
     *   The file path.
     * @return bool
     *   True if it is a tagmanifest file.
     */
    private function isTagManifest($filepath) : bool
    {
        return (substr(basename($filepath), 0, 11) == "tagmanifest");
    }
}
