<?php
namespace APP\plugins\generic\googleFonts\classes;

/**
 * Class representing a font handled by the Google Fonts plugin
 */
class GoogleFont
{
    public function __construct(
        /**
         * Alphanumeric name of the font
         *
         * Used as the directory name for files and as an
         * id in settings.
         */
        public string $id,

        /**
         * Family name
         *
         * Used in font-family CSS property and the UI.
         */
        public string $family,

        /**
         * Type category such as serif or sans-serif
         *
         * Other categories include display, handwriting, etc.
         */
        public string $category,

        /**
         * Supported character sets
         *
         * eg - latin, latin-ext, cyrillic, etc.
         *
         * @param string[] $subsets
         */
        public array $subsets,

        /**
         * Supported font weight and style variants
         *
         * eg - regular, italic, 700, 700italic
         *
         * @param string[] $variants
         */
        public array $variants,

        /**
         * Last modified date
         *
         * Format: YYYY-MM-DD
         */
        public string $lastModified,

        /**
         * Version of the files
         *
         * Usually in the format v12
         */
        public string $version
    ) {
        //
    }
}