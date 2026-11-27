<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Formatted text written in the editor (meeting minutes), kept safe to show and print.
 *
 * Only document formatting survives: headings, paragraphs, emphasis, lists, quotes, tables,
 * links (http, https, mailto) and a short list of inline styles such as colour, alignment
 * and font size. Scripts, event handlers, images, embeds and anything else are removed.
 */
class RichText
{
    /**
     * @var list<string>
     */
    private const ELEMENTS = [
        'p', 'br', 'div', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'mark', 'small',
        'blockquote', 'pre', 'code', 'hr',
        'ul', 'ol', 'li',
        'table', 'caption', 'colgroup', 'col', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'a',
    ];

    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks(false)
            ->withMaxInputLength(200000)
            ->withAttributeSanitizer(new class implements AttributeSanitizerInterface
            {
                /**
                 * CSS properties kept from the editor's inline styles.
                 */
                private const PROPERTIES = [
                    'color', 'background-color', 'text-align', 'text-decoration', 'font-weight', 'font-style',
                    'font-size', 'font-family', 'line-height', 'vertical-align', 'list-style-type',
                    'padding-left', 'margin-left', 'width', 'height', 'border', 'border-collapse', 'border-color',
                    'border-width', 'border-style',
                ];

                public function getSupportedElements(): ?array
                {
                    return null;
                }

                public function getSupportedAttributes(): ?array
                {
                    return ['style'];
                }

                public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
                {
                    $kept = [];

                    foreach (explode(';', $value) as $declaration) {
                        [$property, $propertyValue] = array_pad(array_map('trim', explode(':', $declaration, 2)), 2, '');
                        $property = strtolower($property);

                        if ($property === '' || $propertyValue === '' || ! in_array($property, self::PROPERTIES, true)) {
                            continue;
                        }

                        // No external resources, expressions or escapes inside a style.
                        if (preg_match('/url\s*\(|expression|javascript:|[\\\\<>@]/i', $propertyValue) === 1) {
                            continue;
                        }

                        $kept[] = $property.': '.$propertyValue;
                    }

                    return $kept === [] ? null : implode('; ', $kept);
                }
            });

        foreach (self::ELEMENTS as $element) {
            $config = $config->allowElement($element, match ($element) {
                'a' => ['href', 'title', 'target'],
                'td', 'th' => ['colspan', 'rowspan', 'style'],
                'table', 'col', 'colgroup' => ['style', 'border', 'cellpadding', 'cellspacing', 'width'],
                default => ['style'],
            });
        }

        $this->sanitizer = new HtmlSanitizer($config->forceAttribute('a', 'rel', 'noopener noreferrer'));
    }

    /**
     * Clean HTML from the editor. Text written before the editor existed (no tags) is turned
     * into paragraphs so it still reads the same.
     */
    public function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        if (! $this->looksLikeHtml($html)) {
            $html = collect(preg_split('/\R{2,}/', $html))
                ->map(fn (string $paragraph) => '<p>'.nl2br(e(trim($paragraph)), false).'</p>')
                ->implode('');
        }

        return $this->sanitizer->sanitize($html);
    }

    /**
     * The text without formatting, used to tell an empty editor ("<p>&nbsp;</p>") apart.
     */
    public function plainText(?string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ' ', (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $text));
    }

    private function looksLikeHtml(string $text): bool
    {
        return preg_match('/<\/?(p|br|div|span|h[1-6]|strong|b|em|i|u|s|ul|ol|li|table|tr|td|th|blockquote|a)[\s>\/]/i', $text) === 1;
    }
}
