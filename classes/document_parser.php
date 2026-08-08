<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Extracts plain text from uploaded Word documents so it can be sent to the AI.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader;


/**
 * Parser for extracting text from Word documents (.doc / .docx).
 */
class document_parser {
    /**
     * Extract text from a file based on its extension.
     *
     * @param string $filepath Full path to the file on disk
     * @param string $filename Original filename (for extension detection)
     * @return string Extracted text
     * @throws \moodle_exception
     */
    public static function extract_text($filepath, $filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'docx':
                return self::extract_from_docx($filepath);
            case 'doc':
                return self::extract_from_doc($filepath);
            default:
                throw new \moodle_exception('unsupportedfiletype', 'mod_aiproofreader', '', $extension);
        }
    }

    /**
     * Extract text from a .docx file (a ZIP archive of XML parts).
     *
     * @param string $filepath
     * @return string
     */
    private static function extract_from_docx($filepath) {
        $zip = new \ZipArchive();

        if ($zip->open($filepath) !== true) {
            throw new \moodle_exception('cannotopendocx', 'mod_aiproofreader');
        }

        $content = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($content === false) {
            throw new \moodle_exception('notextextracted', 'mod_aiproofreader', '', 'DOCX');
        }

        $xml = simplexml_load_string($content);
        if ($xml === false) {
            throw new \moodle_exception('invaliddocx', 'mod_aiproofreader');
        }

        $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $textnodes = $xml->xpath('//w:t');

        $text = '';
        foreach ($textnodes as $textnode) {
            $text .= (string)$textnode;
        }

        $text = trim($text);

        if (empty($text)) {
            throw new \moodle_exception('notextextracted', 'mod_aiproofreader', '', 'DOCX');
        }

        return $text;
    }

    /**
     * Best-effort text extraction from the legacy binary .doc format.
     *
     * @param string $filepath
     * @return string
     */
    private static function extract_from_doc($filepath) {
        $content = file_get_contents($filepath);

        if ($content === false) {
            throw new \moodle_exception('cannotreadfile', 'mod_aiproofreader');
        }

        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = preg_replace('/[^\x20-\x7E\n]/', '', $content);
        $content = preg_replace('/[ \t]+/', ' ', $content);
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        $content = trim($content);

        if (empty($content)) {
            throw new \moodle_exception('notextextracted', 'mod_aiproofreader', '', 'DOC');
        }

        return $content;
    }
}
