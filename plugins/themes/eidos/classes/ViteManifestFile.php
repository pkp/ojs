<?php

namespace APP\plugins\themes\eidos\classes;

/**
 * This class represents a file described by Vite's `manifest.json`
 *
 * The manifest.json file contains records of all the files compiled
 * by Vite, their dependencies, and other metadata.
 *
 * @see https://github.com/vitejs/vite/blob/e7adcf0878bd7f3c0b7bb5c9a1d7e6f0d55d9650/packages/vite/src/node/plugins/manifest.ts#L18-L28
 */
class ViteManifestFile
{
    public function __construct(
        /**
         * Path to the source file, relative to Vite's `root`.
         */
        public readonly ?string $src,

        /**
         * Logical chunk name, as defined by Rollup.
         *
         * Only defined for chunks that are entry points.
         *
         * Vite's `build.rollupOptions.input` setting affects this value - you
         * can define a custom chunk name for each entry point by using an
         * object instead of an array.
         *
         * @link https://rollupjs.org/configuration-options/#input
         */
        public readonly ?string $name,

        /**
         * Indicates whether this chunk is an entry point.
         */
        public readonly bool $isEntry,

        /**
         * Indicates whether this chunk is a dynamic entry point.
         */
        public readonly bool $isDynamicEntry,

        /**
         * Path to the published file, relative to Vite's `build.outDir`.
         */
        public readonly string $file,

        /**
         * Paths to published CSS files imported by this chunk,
         * relative to Vite's `build.outDir`.
         *
         * @var string[]
         */
        public readonly array $css,

        /**
         * Paths to published assets imported by this chunk,
         * relative to Vite's `build.outDir`.
         *
         * @var string[]
         */
        public readonly array $assets,

        /**
         * List of chunk names of other chunks (statically) imported by this chunk.
         *
         * @var string[]
         */
        public readonly array $imports,

        /**
         * Links of chunk names of other chunks (dynamically) imported by this chunk.
         *
         * @var string[]
         */
        public readonly array $dynamicImports,
    ) {
    }

    public static function create(array $chunk): self
    {
        return new self(
            src: $chunk['src'] ?? null,
            name: $chunk['name'] ?? null,
            isEntry: $chunk['isEntry'] ?? false,
            isDynamicEntry: $chunk['isDynamicEntry'] ?? false,
            file: $chunk['file'],
            css: $chunk['css'] ?? [],
            assets: $chunk['assets'] ?? [],
            imports: $chunk['imports'] ?? [],
            dynamicImports: $chunk['dynamicImports'] ?? [],
        );
    }
}
