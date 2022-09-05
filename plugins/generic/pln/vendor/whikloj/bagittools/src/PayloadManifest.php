<?php

namespace whikloj\BagItTools;

/**
 * Payload Manifest extension of AbstractManifest.
 *
 * @package whikloj\BagItTools
 * @author whikloj
 * @since 1.0.0
 */
class PayloadManifest extends AbstractManifest
{

  /**
   * PayloadManifest constructor.
   *
   * @param \whikloj\BagItTools\Bag $bag
   *   The bag this manifest is part of.
   * @param string $algorithm
   *   The BagIt name of the hash algorithm.
   * @param bool $load
   *   Whether we are loading an existing file
   */
    public function __construct(Bag $bag, $algorithm, $load = false)
    {
        parent::__construct($bag, $algorithm, "manifest-{$algorithm}.txt", $load);
    }

  /**
   * {@inheritdoc}
   */
    public function update()
    {
        $this->hashes = [];
        $files = BagUtils::getAllFiles($this->bag->makeAbsolute("data"));
        foreach ($files as $file) {
            $this->hashes[$this->bag->makeRelative($file)] = "";
        }
        parent::update();
    }

    /**
     * {@inheritdoc}
     */
    public function validate()
    {
        parent::validate();
        $onDisk = BagUtils::getAllFiles($this->bag->makeAbsolute("data"));
        // 1.0 Spec says each manifest MUST list every file in the data/ directory.
        array_walk($onDisk, function (&$item) {
            $item = $this->bag->makeRelative($item);
        });
        $onDisk = array_diff($onDisk, array_keys($this->hashes));
        if (count($onDisk) > 0) {
            $this->addError("There are files on disk not listed in this manifest file.");
        }
    }
}
