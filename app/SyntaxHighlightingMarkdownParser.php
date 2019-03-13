<?php

namespace App;

use Highlight\Highlighter;
use TightenCo\Jigsaw\Parsers\JigsawMarkdownParser;

class SyntaxHighlightingMarkdownParser extends JigsawMarkdownParser
{
    /**
     * @var  \Highlight\Highlighter
     */
    protected $highlighter;

    public function __construct()
    {
        $this->highlighter = new Highlighter();

        parent::__construct();
    }

    /**
     * Add syntax highlighting to a fenced code block.
     *
     * @see  https://jd-powered.net/notes/adding-syntax-highlighting-to-jigsaw-sites
     *
     * @param  array $block
     * @return  array
     */
    protected function blockFencedCodeComplete($block)
    {
        $class = $block['element']['element']['attributes']['class'] ?? '';

        if (!starts_with($class, 'language-')) {
            return parent::blockFencedCodeComplete($block);
        }

        $block['element']['element']['text'] = $this->highlighter->highlight(
            str_after($class, 'language-'),
            $block['element']['element']['text'] ?? ''
        )->value;
        $block['element']['element']['attributes']['class'] = 'hljs' . $class;

        return $block;
    }
}
