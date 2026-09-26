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
 * Extracts plain text (for the AI) and formatted HTML (for the teacher)
 * from uploaded Word documents.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader;


/**
 * Parser for Word documents (.doc / .docx).
 */
class document_parser {
    /** @var string WordprocessingML main namespace. */
    public const NS_W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /**
     * Extract text from a file based on its extension.
     *
     * @param string $filepath Full path to the file on disk
     * @param string $filename Original filename (for extension detection)
     * @return string Extracted text, one line per paragraph
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
     * Converts a document to HTML that keeps the student's formatting
     * (bold, italic, underline, alignment, indents, line spacing), for the
     * teacher's grading view. Only .docx is supported; returns null for
     * anything else or if the file can't be read.
     *
     * @param string $filepath Full path to the file on disk
     * @param string $filename Original filename (for extension detection)
     * @return string|null
     */
    public static function extract_html($filepath, $filename) {
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'docx') {
            return null;
        }

        try {
            $parts = self::load_docx($filepath);
        } catch (\Throwable $e) {
            return null;
        }

        $converter = new docx_html_converter($parts['document'], $parts['styles']);
        $html = $converter->convert();

        return trim(strip_tags($html)) === '' ? null : $html;
    }

    /**
     * Extract text from a .docx file (a ZIP archive of XML parts), keeping
     * each paragraph on its own line so paragraphs can be counted.
     *
     * @param string $filepath
     * @return string
     */
    private static function extract_from_docx($filepath) {
        $parts = self::load_docx($filepath);

        $xpath = new \DOMXPath($parts['document']);
        $xpath->registerNamespace('w', self::NS_W);

        $lines = [];
        foreach ($xpath->query('//w:body//w:p') as $paragraph) {
            $line = '';
            foreach ($xpath->query('.//w:t | .//w:tab | .//w:br | .//w:cr', $paragraph) as $node) {
                // Skip text inside nested paragraphs (e.g. text boxes); they are
                // visited as paragraphs in their own right.
                if (self::closest_paragraph($node) !== $paragraph) {
                    continue;
                }
                if ($node->localName === 't') {
                    $line .= $node->textContent;
                } else if ($node->localName === 'tab') {
                    $line .= "\t";
                } else {
                    $line .= ' ';
                }
            }
            $lines[] = rtrim($line);
        }

        $text = trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));

        if ($text === '') {
            throw new \moodle_exception('notextextracted', 'mod_aiproofreader', '', 'DOCX');
        }

        return $text;
    }

    /**
     * Finds the nearest enclosing w:p element of a node.
     *
     * @param \DOMNode $node
     * @return \DOMNode|null
     */
    private static function closest_paragraph(\DOMNode $node) {
        $parent = $node->parentNode;
        while ($parent && !($parent->namespaceURI === self::NS_W && $parent->localName === 'p')) {
            $parent = $parent->parentNode;
        }
        return $parent;
    }

    /**
     * Opens a .docx and loads its document and styles parts.
     *
     * @param string $filepath
     * @return array With keys document (\DOMDocument) and styles (\DOMDocument|null)
     * @throws \moodle_exception
     */
    private static function load_docx($filepath) {
        $zip = new \ZipArchive();

        if ($zip->open($filepath) !== true) {
            throw new \moodle_exception('cannotopendocx', 'mod_aiproofreader');
        }

        $content = $zip->getFromName('word/document.xml');
        $stylescontent = $zip->getFromName('word/styles.xml');
        $zip->close();

        if ($content === false) {
            throw new \moodle_exception('notextextracted', 'mod_aiproofreader', '', 'DOCX');
        }

        $document = new \DOMDocument();
        if (!@$document->loadXML($content, LIBXML_NONET)) {
            throw new \moodle_exception('invaliddocx', 'mod_aiproofreader');
        }

        $styles = null;
        if ($stylescontent !== false) {
            $styles = new \DOMDocument();
            if (!@$styles->loadXML($stylescontent, LIBXML_NONET)) {
                $styles = null;
            }
        }

        return ['document' => $document, 'styles' => $styles];
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
