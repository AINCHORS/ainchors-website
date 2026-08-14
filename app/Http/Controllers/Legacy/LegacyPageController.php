<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LegacyPageController extends Controller
{
    public function home(): Response
    {
        return $this('home');
    }

    public function __invoke(string $path): Response
    {
        abort_unless((bool) preg_match('/\A[A-Za-z0-9\/-]+\z/', $path) && ! str_contains($path, '..'), 404);

        $legacyRoot = realpath(resource_path('legacy'));
        $source = realpath(resource_path('legacy/'.trim($path, '/').'/index.html'));

        if (! $legacyRoot || ! $source || ! str_starts_with($source, $legacyRoot.DIRECTORY_SEPARATOR) || ! is_file($source)) {
            throw new NotFoundHttpException();
        }

        $html = file_get_contents($source);
        $base = rtrim(url('/'), '/');

        $html = str_replace([
            'https://www.ainchors.com',
            'https://ainchors.com',
        ], $base, $html);

        $responsiveStyles = '<link rel="stylesheet" href="'.asset('legacy-responsive.css').'">';
        $html = str_replace('</head>', $responsiveStyles.'</head>', $html);

        $html = preg_replace_callback(
            '~https?://(?:images\.leadconnectorhq\.com/image/[^\"\']*?_https://)?(?:assets\.cdn\.filesafe\.space|storage\.googleapis\.com/msgsndr)/[^\"\']*/media/([^?\"\']+)~i',
            function (array $match): string {
                $filename = basename(urldecode($match[1]));
                $local = public_path('assets/site/'.$filename);

                return is_file($local) ? asset('assets/site/'.$filename) : $match[0];
            },
            $html,
        );

        // Keep navigation in the current tab. WhatsApp is the only approved
        // exception because it intentionally hands the visitor to WhatsApp.
        $html = preg_replace_callback(
            '~<a\b[^>]*>~i',
            function (array $match): string {
                $tag = $match[0];

                if (! preg_match('~\bhref\s*=\s*([\"\'])(.*?)\1~i', $tag, $href)) {
                    return $tag;
                }

                if (preg_match('~(?:wa\.me|(?:api\.)?whatsapp\.com)~i', $href[2])) {
                    return $tag;
                }

                return preg_replace('~\s+target\s*=\s*([\"\']).*?\1~i', '', $tag) ?? $tag;
            },
            $html,
        );

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
