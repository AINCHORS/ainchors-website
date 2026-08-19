<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LegacyPageController extends Controller
{
    public function home(): Response
    {
        return $this->respond('home');
    }

    public function __invoke(string $path): Response
    {
        return $this->respond($path);
    }

    public function embedded(string $path): Response
    {
        return $this->respond($path, true);
    }

    private function respond(string $path, bool $embedded = false): Response
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

        // Internal links from embedded legacy pages navigate the parent Laravel
        // page. Social and WhatsApp destinations retain their own behavior.
        $html = preg_replace_callback(
            '~<a\b[^>]*>~i',
            function (array $match) use ($embedded, $base): string {
                $tag = $match[0];

                if (! preg_match('~\bhref\s*=\s*([\"\'])(.*?)\1~i', $tag, $href)) {
                    return $tag;
                }

                if (preg_match('~(?:wa\.me|(?:api\.)?whatsapp\.com|facebook\.com|instagram\.com|linkedin\.com|tiktok\.com)~i', $href[2])) {
                    return $tag;
                }

                $tag = preg_replace('~\s+target\s*=\s*([\"\']).*?\1~i', '', $tag) ?? $tag;

                if ($embedded && (str_starts_with($href[2], $base) || str_starts_with($href[2], '/'))) {
                    return rtrim(substr($tag, 0, -1)).' target="_parent">';
                }

                return $tag;
            },
            $html,
        );

        if ($embedded) {
            $injection = '<style>#nav-menu-popup,.c-section:has(.c-nav-menu),.footersection{display:none!important}html,body{overflow-x:hidden!important}</style>';

            if ($path === 'contact-us') {
                $endpoint = json_encode(route('contact.submit'), JSON_THROW_ON_ERROR);
                $token = json_encode(csrf_token(), JSON_THROW_ON_ERROR);
                $injection .= '<script>document.addEventListener("DOMContentLoaded",function(){const endpoint='.$endpoint.',token='.$token.';document.querySelectorAll("form").forEach(function(form){form.addEventListener("submit",async function(event){event.preventDefault();event.stopImmediatePropagation();const field=function(selector){return form.querySelector(selector)?.value?.trim()||""};const payload={full_name:field("[name=full_name]")||field("[name=last_name]"),email:field("[name=email]"),phone:field("[name=phone]"),country:field("[name=country]"),message:field("[data-q=comment]"),source:"contact_page"};try{const response=await fetch(endpoint,{method:"POST",headers:{"Accept":"application/json","Content-Type":"application/json","X-CSRF-TOKEN":token},body:JSON.stringify(payload)});const result=await response.json();let notice=form.querySelector(".ainchors-contact-feedback");if(!notice){notice=document.createElement("p");notice.className="ainchors-contact-feedback";form.append(notice)}notice.textContent=response.ok?result.message:Object.values(result.errors||{}).flat().join(" ");notice.style.color=response.ok?"#37AD82":"#b42318"}catch(error){const notice=document.createElement("p");notice.className="ainchors-contact-feedback";notice.textContent="Unable to submit right now. Please try again.";notice.style.color="#b42318";form.append(notice)}} ,true)})});</script>';
            }

            $injection .= '<script>document.addEventListener("DOMContentLoaded",function(){const report=function(){parent.postMessage({source:"ainchors-legacy",type:"height",height:Math.max(document.body.scrollHeight,document.documentElement.scrollHeight)},location.origin)};new ResizeObserver(report).observe(document.body);window.addEventListener("load",report);report()})</script>';
            $html = str_replace('</head>', $injection.'</head>', $html);
        }

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
