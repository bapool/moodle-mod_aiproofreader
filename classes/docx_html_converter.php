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
 * Converts a Word (.docx) document body to simple HTML.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader;

/**
 * Converts WordprocessingML to HTML, keeping the formatting a teacher
 * needs to judge an essay's layout (e.g. MLA format): bold, italic,
 * underline, strikethrough, superscript/subscript, paragraph alignment,
 * indents, hanging indents, line spacing and paragraph spacing. Paragraph
 * and run styles (including the document defaults) are resolved, since
 * templates usually set double spacing and first-line indents on the
 * Normal style rather than on each paragraph.
 *
 * Images, headers/footers, footnotes and comments are not included.
 */
class docx_html_converter {
    /** @var \DOMXPath XPath over word/document.xml. */
    protected $xpath;

    /** @var \DOMDocument word/document.xml. */
    protected $document;

    /** @var array Style id => ['type', 'basedon', 'ppr' => array, 'rpr' => array]. */
    protected $styles = [];

    /** @var array Document-default paragraph properties. */
    protected $defaultppr = [];

    /** @var array Document-default run properties. */
    protected $defaultrpr = [];

    /** @var string|null Id of the default paragraph style. */
    protected $defaultpstyle = null;

    /**
     * Constructor.
     *
     * @param \DOMDocument $document word/document.xml
     * @param \DOMDocument|null $styles word/styles.xml
     */
    public function __construct(\DOMDocument $document, ?\DOMDocument $styles) {
        $this->document = $document;
        $this->xpath = new \DOMXPath($document);
        $this->xpath->registerNamespace('w', document_parser::NS_W);

        if ($styles) {
            $this->load_styles($styles);
        }
    }

    /**
     * Converts the document body to HTML.
     *
     * @return string
     */
    public function convert() {
        $body = $this->xpath->query('/w:document/w:body')->item(0);
        if (!$body) {
            return '';
        }
        return $this->convert_block_children($body);
    }

    /**
     * Reads styles.xml into lookup arrays.
     *
     * @param \DOMDocument $styles
     */
    protected function load_styles(\DOMDocument $styles) {
        $xpath = new \DOMXPath($styles);
        $xpath->registerNamespace('w', document_parser::NS_W);

        $ppr = $xpath->query('/w:styles/w:docDefaults/w:pPrDefault/w:pPr')->item(0);
        if ($ppr) {
            $this->defaultppr = $this->read_ppr($ppr);
        }
        $rpr = $xpath->query('/w:styles/w:docDefaults/w:rPrDefault/w:rPr')->item(0);
        if ($rpr) {
            $this->defaultrpr = $this->read_rpr($rpr);
        }

        foreach ($xpath->query('/w:styles/w:style') as $style) {
            $id = $this->attr($style, 'styleId');
            if ($id === null) {
                continue;
            }
            $type = $this->attr($style, 'type');
            $basedon = $this->child($style, 'basedOn');
            $stylep = $this->child($style, 'pPr');
            $styler = $this->child($style, 'rPr');

            $this->styles[$id] = [
                'type' => $type,
                'basedon' => $basedon ? $this->attr($basedon, 'val') : null,
                'ppr' => $stylep ? $this->read_ppr($stylep) : [],
                'rpr' => $styler ? $this->read_rpr($styler) : [],
            ];

            if ($type === 'paragraph' && in_array($this->attr($style, 'default'), ['1', 'true', 'on'])) {
                $this->defaultpstyle = $id;
            }
        }
    }

    /**
     * Resolves a style's properties, following its basedOn chain.
     *
     * @param string|null $styleid
     * @param string $kind ppr|rpr
     * @param int $depth Recursion guard
     * @return array
     */
    protected function style_props($styleid, $kind, $depth = 0) {
        if ($styleid === null || !isset($this->styles[$styleid]) || $depth > 10) {
            return [];
        }
        $style = $this->styles[$styleid];
        return array_merge($this->style_props($style['basedon'], $kind, $depth + 1), $style[$kind]);
    }

    /**
     * Converts the block-level children (paragraphs, tables) of a node.
     *
     * @param \DOMNode $node
     * @return string
     */
    protected function convert_block_children(\DOMNode $node) {
        $out = '';
        foreach ($node->childNodes as $child) {
            if ($child->namespaceURI !== document_parser::NS_W) {
                continue;
            }
            switch ($child->localName) {
                case 'p':
                    $out .= $this->convert_paragraph($child);
                    break;
                case 'tbl':
                    $out .= $this->convert_table($child);
                    break;
                case 'sdt':
                    $content = $this->child($child, 'sdtContent');
                    if ($content) {
                        $out .= $this->convert_block_children($content);
                    }
                    break;
            }
        }
        return $out;
    }

    /**
     * Converts a table to an HTML table.
     *
     * @param \DOMNode $table
     * @return string
     */
    protected function convert_table(\DOMNode $table) {
        $out = '<table class="table table-bordered">';
        foreach ($this->children($table, 'tr') as $row) {
            $out .= '<tr>';
            foreach ($this->children($row, 'tc') as $cell) {
                $out .= '<td>' . $this->convert_block_children($cell) . '</td>';
            }
            $out .= '</tr>';
        }
        return $out . '</table>';
    }

    /**
     * Converts one paragraph.
     *
     * @param \DOMNode $paragraph
     * @return string
     */
    protected function convert_paragraph(\DOMNode $paragraph) {
        $directppr = $this->child($paragraph, 'pPr');
        $direct = $directppr ? $this->read_ppr($directppr) : [];

        $styleid = $direct['style'] ?? $this->defaultpstyle;
        $props = array_merge($this->defaultppr, $this->style_props($styleid, 'ppr'), $direct);
        $baserpr = array_merge($this->defaultrpr, $this->style_props($styleid, 'rpr'));

        $inner = $this->convert_inline_children($paragraph, $baserpr);
        if (trim(strip_tags($inner, '<br>')) === '') {
            $inner = '&nbsp;';
        }

        $style = $this->paragraph_css($props);
        return '<p' . ($style !== '' ? ' style="' . $style . '"' : '') . '>' . $inner . '</p>';
    }

    /**
     * Converts the inline content (runs, hyperlinks, insertions) of a node.
     *
     * @param \DOMNode $node
     * @param array $baserpr Run properties inherited from the paragraph
     * @return string
     */
    protected function convert_inline_children(\DOMNode $node, array $baserpr) {
        $out = '';
        foreach ($node->childNodes as $child) {
            if ($child->namespaceURI !== document_parser::NS_W) {
                continue;
            }
            switch ($child->localName) {
                case 'r':
                    $out .= $this->convert_run($child, $baserpr);
                    break;
                case 'hyperlink':
                case 'ins':
                case 'smartTag':
                case 'fldSimple':
                    $out .= $this->convert_inline_children($child, $baserpr);
                    break;
                case 'sdt':
                    $content = $this->child($child, 'sdtContent');
                    if ($content) {
                        $out .= $this->convert_inline_children($content, $baserpr);
                    }
                    break;
            }
        }
        return $out;
    }

    /**
     * Converts one run of text with its character formatting.
     *
     * @param \DOMNode $run
     * @param array $baserpr
     * @return string
     */
    protected function convert_run(\DOMNode $run, array $baserpr) {
        $directrpr = $this->child($run, 'rPr');
        $direct = $directrpr ? $this->read_rpr($directrpr) : [];
        $props = array_merge($baserpr, $this->style_props($direct['style'] ?? null, 'rpr'), $direct);

        $text = '';
        foreach ($run->childNodes as $child) {
            if ($child->namespaceURI !== document_parser::NS_W) {
                continue;
            }
            switch ($child->localName) {
                case 't':
                    $text .= htmlspecialchars($child->textContent, ENT_QUOTES, 'UTF-8');
                    break;
                case 'tab':
                    $text .= '&emsp;&emsp;';
                    break;
                case 'br':
                case 'cr':
                    $text .= '<br>';
                    break;
                case 'noBreakHyphen':
                    $text .= '-';
                    break;
            }
        }

        if ($text === '') {
            return '';
        }

        if (!empty($props['sup'])) {
            $text = '<sup>' . $text . '</sup>';
        } else if (!empty($props['sub'])) {
            $text = '<sub>' . $text . '</sub>';
        }
        if (!empty($props['strike'])) {
            $text = '<s>' . $text . '</s>';
        }
        if (!empty($props['u'])) {
            $text = '<u>' . $text . '</u>';
        }
        if (!empty($props['i'])) {
            $text = '<em>' . $text . '</em>';
        }
        if (!empty($props['b'])) {
            $text = '<strong>' . $text . '</strong>';
        }

        return $text;
    }

    /**
     * Reads the paragraph properties this converter uses.
     *
     * @param \DOMNode $ppr A w:pPr element
     * @return array
     */
    protected function read_ppr(\DOMNode $ppr) {
        $props = [];

        if ($node = $this->child($ppr, 'pStyle')) {
            $props['style'] = $this->attr($node, 'val');
        }
        if ($node = $this->child($ppr, 'jc')) {
            $props['jc'] = $this->attr($node, 'val');
        }
        if ($node = $this->child($ppr, 'ind')) {
            foreach (['left', 'start', 'right', 'end', 'firstLine', 'hanging'] as $name) {
                $value = $this->attr($node, $name);
                if ($value !== null && is_numeric($value)) {
                    $key = ['start' => 'left', 'end' => 'right'][$name] ?? $name;
                    $props['ind' . $key] = (int) $value;
                }
            }
            // A hanging indent and a first-line indent cancel each other out.
            if (isset($props['indhanging'])) {
                $props['indfirstLine'] = 0;
            } else if (isset($props['indfirstLine'])) {
                $props['indhanging'] = 0;
            }
        }
        if ($node = $this->child($ppr, 'spacing')) {
            $line = $this->attr($node, 'line');
            $rule = $this->attr($node, 'lineRule');
            if ($line !== null && is_numeric($line) && ($rule === null || $rule === 'auto')) {
                $props['line'] = (int) $line;
            }
            foreach (['before', 'after'] as $name) {
                $value = $this->attr($node, $name);
                if ($value !== null && is_numeric($value)) {
                    $props[$name] = (int) $value;
                }
            }
        }

        return $props;
    }

    /**
     * Reads the run (character) properties this converter uses.
     *
     * @param \DOMNode $rpr A w:rPr element
     * @return array
     */
    protected function read_rpr(\DOMNode $rpr) {
        $props = [];

        if ($node = $this->child($rpr, 'rStyle')) {
            $props['style'] = $this->attr($node, 'val');
        }
        foreach (['b' => 'b', 'i' => 'i', 'strike' => 'strike', 'dstrike' => 'strike'] as $name => $key) {
            if ($node = $this->child($rpr, $name)) {
                $props[$key] = $this->toggle_on($node);
            }
        }
        if ($node = $this->child($rpr, 'u')) {
            $props['u'] = !in_array($this->attr($node, 'val'), ['none', '0', 'false']);
        }
        if ($node = $this->child($rpr, 'vertAlign')) {
            $value = $this->attr($node, 'val');
            $props['sup'] = ($value === 'superscript');
            $props['sub'] = ($value === 'subscript');
        }

        return $props;
    }

    /**
     * Builds the inline CSS for a paragraph.
     *
     * @param array $props
     * @return string
     */
    protected function paragraph_css(array $props) {
        $css = [];

        $align = ['center' => 'center', 'right' => 'right', 'end' => 'right', 'both' => 'justify', 'distribute' => 'justify'];
        if (!empty($props['jc']) && isset($align[$props['jc']])) {
            $css[] = 'text-align: ' . $align[$props['jc']];
        }
        if (!empty($props['indleft'])) {
            $css[] = 'margin-left: ' . $this->twips_to_pt($props['indleft']);
        }
        if (!empty($props['indright'])) {
            $css[] = 'margin-right: ' . $this->twips_to_pt($props['indright']);
        }
        if (!empty($props['indhanging'])) {
            $css[] = 'text-indent: -' . $this->twips_to_pt($props['indhanging']);
        } else if (!empty($props['indfirstLine'])) {
            $css[] = 'text-indent: ' . $this->twips_to_pt($props['indfirstLine']);
        }
        if (!empty($props['line'])) {
            $css[] = 'line-height: ' . round($props['line'] / 240, 2);
        }
        $css[] = 'margin-top: ' . $this->twips_to_pt($props['before'] ?? 0);
        $css[] = 'margin-bottom: ' . $this->twips_to_pt($props['after'] ?? 0);

        return implode('; ', $css);
    }

    /**
     * Converts twentieths of a point to a CSS point value.
     *
     * @param int $twips
     * @return string
     */
    protected function twips_to_pt($twips) {
        return round($twips / 20, 1) . 'pt';
    }

    /**
     * Whether an on/off property element (e.g. w:b) is switched on.
     *
     * @param \DOMElement $node
     * @return bool
     */
    protected function toggle_on(\DOMElement $node) {
        $value = $this->attr($node, 'val');
        return $value === null || !in_array(strtolower($value), ['0', 'false', 'off']);
    }

    /**
     * Reads a w:-namespaced attribute.
     *
     * @param \DOMElement $node
     * @param string $name
     * @return string|null
     */
    protected function attr(\DOMElement $node, $name) {
        return $node->hasAttributeNS(document_parser::NS_W, $name)
            ? $node->getAttributeNS(document_parser::NS_W, $name)
            : null;
    }

    /**
     * First direct w:-namespaced child element with the given name.
     *
     * @param \DOMNode $node
     * @param string $name
     * @return \DOMElement|null
     */
    protected function child(\DOMNode $node, $name) {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->namespaceURI === document_parser::NS_W && $child->localName === $name) {
                return $child;
            }
        }
        return null;
    }

    /**
     * All direct w:-namespaced child elements with the given name.
     *
     * @param \DOMNode $node
     * @param string $name
     * @return \DOMElement[]
     */
    protected function children(\DOMNode $node, $name) {
        $result = [];
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->namespaceURI === document_parser::NS_W && $child->localName === $name) {
                $result[] = $child;
            }
        }
        return $result;
    }
}
