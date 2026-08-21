<?php

namespace App\Services\Content;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;

/**
 * Produces a page module from the original local export without an iframe.
 *
 * The exported presentation is rendered in a Shadow DOM so its original CSS
 * cannot leak into the shared V2 header or footer.  Only the legacy page's
 * own navigation and footer are removed; the current shared components stay
 * outside the module and remain the single site-wide version.
 */
class ModuleMarkupRenderer
{
    /** @var array<string, string> */
    private const SOURCES = [
        'about' => 'about-us',
        'faqs' => 'faqs',
        'join-us' => 'join-us',
        'contact' => 'contact-us',
        'terms' => 'terms--conditions',
        'privacy' => 'privacy--policy',
    ];

    /**
     * @return array{key: string, styles: string, markup: string}
     */
    public function page(string $key): array
    {
        $sourceKey = self::SOURCES[$key] ?? null;

        if ($sourceKey === null) {
            throw new RuntimeException('Unknown content module.');
        }

        $source = resource_path('legacy/'.$sourceKey.'/index.html');
        $html = file_get_contents($source);

        if ($html === false) {
            throw new RuntimeException('The local module source could not be read.');
        }

        $html = $this->withoutExternalChatWidget($html);

        $baseUrl = rtrim(url('/'), '/');

        return [
            'key' => $key,
            'styles' => $this->rewriteUrls($this->extractStyles($html), $baseUrl),
            'markup' => $this->rewriteLinks(
                $this->rewriteUrls($this->extractPageMarkup($html), $baseUrl),
                $baseUrl,
                $key,
            ),
        ];
    }

    private function extractStyles(string $html): string
    {
        preg_match_all('~<style\b[^>]*>.*?</style>~is', $html, $styles);
        preg_match_all('~<link\b[^>]*>~is', $html, $links);

        $stylesheetLinks = array_filter(
            $links[0],
            static fn (string $link): bool => (bool) preg_match('~\brel\s*=\s*(["\'])[^"\']*stylesheet[^"\']*\1~i', $link),
        );

        // :root in the export provides its design variables.  In a Shadow DOM
        // it must be the host, otherwise those variables are not available.
        return str_ireplace(':root', ':host', implode("\n", [...$stylesheetLinks, ...$styles[0]]));
    }

    private function extractPageMarkup(string $html): string
    {
        if (! preg_match('~<body\b[^>]*>(.*)</body>~is', $html, $body)) {
            throw new RuntimeException('The local module source does not contain a page body.');
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<?xml encoding="UTF-8"><!doctype html><html><body>'.$body[1].'</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);
        $preview = $xpath->query('//*[@id="preview-container"]')->item(0);

        if (! $preview instanceof DOMElement) {
            throw new RuntimeException('The local module source does not contain the page preview.');
        }

        /** @var array<int, DOMNode> $remove */
        $remove = [];

        foreach ($xpath->query('//*[@id="nav-menu-popup"]') as $node) {
            $remove[spl_object_id($node)] = $node;
        }

        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " footersection ")]') as $node) {
            $remove[spl_object_id($node)] = $node;
        }

        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " c-nav-menu ")]') as $node) {
            $section = $node;

            while ($section instanceof DOMElement && ! str_contains(' '.$section->getAttribute('class').' ', ' c-section ')) {
                $section = $section->parentNode;
            }

            $remove[spl_object_id($section instanceof DOMNode ? $section : $node)] = $section instanceof DOMNode ? $section : $node;
        }

        foreach ($remove as $node) {
            if ($node->parentNode !== null) {
                $node->parentNode->removeChild($node);
            }
        }

        // The original page background is a sibling of the preview container.
        // It is retained here, inside the module boundary.
        return '<div class="bgCover bg-fixed" aria-hidden="true"></div>'.$document->saveHTML($preview);
    }

    private function rewriteUrls(string $html, string $baseUrl): string
    {
        $html = str_replace([
            'https://www.ainchors.com',
            'https://ainchors.com',
        ], $baseUrl, $html);

        return preg_replace_callback(
            '~https?://(?:images\.leadconnectorhq\.com/image/[^"\']*?_https://)?(?:assets\.cdn\.filesafe\.space|storage\.googleapis\.com/msgsndr)/[^"\']*/media/([^?"\']+)~i',
            static function (array $match) use ($baseUrl): string {
                $filename = basename(urldecode($match[1]));
                $local = public_path('assets/site/'.$filename);

                return is_file($local) ? $baseUrl.'/assets/site/'.$filename : $match[0];
            },
            $html,
        ) ?? $html;
    }

    private function rewriteLinks(string $html, string $baseUrl, string $key): string
    {
        $html = preg_replace(
            '~\bhref\s*=\s*(["\'])(?:'.$this->quoted($baseUrl).')?/hiring-page\1~i',
            'href="'.$baseUrl.'/join-us"',
            $html,
        ) ?? $html;

        if ($key === 'join-us') {
            $applyUrl = htmlspecialchars(route('job-applications.create'), ENT_QUOTES, 'UTF-8');
            $html = preg_replace_callback(
                '~<a\b[^>]*>\s*(?:<[^>]+>\s*)*Apply\s+Now!?\s*(?:</[^>]+>\s*)*</a>~i',
                static function (array $match) use ($applyUrl): string {
                    return preg_replace('~\bhref\s*=\s*(["\']).*?\1~i', 'href="'.$applyUrl.'"', $match[0]) ?? $match[0];
                },
                $html,
            ) ?? $html;
        }

        $html = preg_replace_callback(
            '~<a\b[^>]*>~i',
            static function (array $match) use ($baseUrl): string {
                $tag = $match[0];

                if (! preg_match('~\bhref\s*=\s*(["\'])(.*?)\1~i', $tag, $href)) {
                    return $tag;
                }

                if (preg_match('~(?:wa\.me|(?:api\.)?whatsapp\.com|facebook\.com|instagram\.com|linkedin\.com|tiktok\.com)~i', $href[2])) {
                    return $tag;
                }

                $tag = preg_replace('~\s+target\s*=\s*(["\']).*?\1~i', '', $tag) ?? $tag;

                if (str_starts_with($href[2], $baseUrl) || str_starts_with($href[2], '/')) {
                    return $tag;
                }

                return $tag;
            },
            $html,
        ) ?? $html;

        if ($key === 'contact') {
            $emailUrl = 'https://mail.google.com/mail/?view=cm&fs=1&to=info@ainchors.com';

            $html = preg_replace(
                '~<a\b[^>]*(?:__cf_email__|/cdn-cgi/l/email-protection)[^>]*>.*?</a>~is',
                '<a href="'.$emailUrl.'" target="_blank" rel="noopener noreferrer">info@ainchors.com</a>',
                $html,
            ) ?? $html;
        }

        return $html;
    }

    private function quoted(string $value): string
    {
        return preg_quote($value, '~');
    }

    private function withoutExternalChatWidget(string $html): string
    {
        $html = preg_replace(
            '~<div\b[^>]*\bclass\s*=\s*(["\'])[^"\']*\bcustom-code-container\b[^"\']*\1[^>]*>\s*<script\b[^>]*(?:tidio|lyro)[^>]*>.*?</script>\s*</div>~is',
            '',
            $html,
        ) ?? $html;

        return preg_replace('~<script\b[^>]*(?:tidio|lyro)[^>]*>.*?</script>~is', '', $html) ?? $html;
    }
}
