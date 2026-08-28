<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Keep only simple formatting tags so admin-written alert messages
 * cannot run scripts on the customer dashboard.
 */
class SafeHtml
{
    /** @var list<string> */
    protected static array $tags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike',
        'ul', 'ol', 'li', 'a', 'h2', 'h3', 'h4', 'h5', 'span',
    ];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $prev = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<div id="hpr-root">' . $html . '</div>';
        $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementById('hpr-root');
        if (! $root) {
            foreach ($dom->getElementsByTagName('div') as $div) {
                if ($div->getAttribute('id') === 'hpr-root') {
                    $root = $div;
                    break;
                }
            }
        }
        if (! $root) {
            return '';
        }

        self::scrub($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    /**
     * Render stored body: keep old plain-text alerts readable,
     * and print cleaned HTML for new formatted messages.
     */
    public static function display(?string $html): string
    {
        $raw = trim((string) $html);
        if ($raw === '') {
            return '';
        }

        if (! preg_match('/<[a-z][\s\S]*>/i', $raw)) {
            return nl2br(e($raw), false);
        }

        return self::clean($raw);
    }

    protected static function scrub(DOMNode $node): void
    {
        $remove = [];
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if ($tag === 'script' || $tag === 'style' || $tag === 'iframe' || $tag === 'object') {
                    $remove[] = $child;
                    continue;
                }
                if (! in_array($tag, self::$tags, true)) {
                    self::unwrap($child);
                    continue;
                }
                self::cleanAttrs($child, $tag);
                self::scrub($child);
            }
        }
        foreach ($remove as $el) {
            $el->parentNode?->removeChild($el);
        }
    }

    protected static function unwrap(DOMElement $el): void
    {
        self::scrub($el);
        $parent = $el->parentNode;
        if (! $parent) {
            return;
        }
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }

    protected static function cleanAttrs(DOMElement $el, string $tag): void
    {
        $keep = [];
        if ($tag === 'a') {
            $href = trim((string) $el->getAttribute('href'));
            if ($href !== '' && ! preg_match('#^(javascript|data|vbscript):#i', $href)) {
                if (preg_match('#^https?://#i', $href) || str_starts_with($href, '/')) {
                    $keep['href'] = $href;
                    $keep['rel'] = 'noopener noreferrer';
                    if (preg_match('#^https?://#i', $href)) {
                        $keep['target'] = '_blank';
                    }
                }
            }
        }

        $style = self::safeStyle((string) $el->getAttribute('style'));
        if ($style !== '') {
            $keep['style'] = $style;
        }

        while ($el->attributes->length) {
            $el->removeAttribute($el->attributes->item(0)->name);
        }
        foreach ($keep as $name => $value) {
            $el->setAttribute($name, $value);
        }
    }

    protected static function safeStyle(string $style): string
    {
        $ok = [];
        foreach (explode(';', $style) as $part) {
            if (! str_contains($part, ':')) {
                continue;
            }
            [$prop, $val] = array_map('trim', explode(':', $part, 2));
            $prop = strtolower($prop);
            $val = trim($val);
            if ($val === '' || preg_match('/expression|javascript|url\s*\(/i', $val)) {
                continue;
            }
            if (in_array($prop, ['color', 'background-color', 'text-align'], true)) {
                $ok[] = $prop . ': ' . $val;
            }
        }

        return implode('; ', $ok);
    }
}
