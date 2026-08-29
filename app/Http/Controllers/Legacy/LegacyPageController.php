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
        $html = $this->withoutExternalChatWidget($html);
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

        if ($embedded && in_array($path, ['consulting-main', 'consulting-gov', 'consulting-private'], true)) {
            $destinationUrl = $path === 'consulting-main'
                ? 'https://wa.me/61418802086'
                : route($path === 'consulting-gov' ? 'consulting.government' : 'consulting.private');
            $destinationUrl = htmlspecialchars($destinationUrl, ENT_QUOTES, 'UTF-8');

            $html = preg_replace_callback(
                '~<a\b[^>]*\baria-label\s*=\s*(["\'])Book\s+Now\s*\1[^>]*>~i',
                static function (array $match) use ($destinationUrl, $path): string {
                    $tag = preg_replace(
                        '~\bhref\s*=\s*(["\']).*?\1~i',
                        'href="'.$destinationUrl.'"',
                        $match[0],
                    ) ?? $match[0];

                    if ($path !== 'consulting-main') {
                        return $tag;
                    }

                    $tag = preg_replace('~\s+target\s*=\s*(["\']).*?\1~i', '', $tag) ?? $tag;
                    $tag = preg_replace('~\s+rel\s*=\s*(["\']).*?\1~i', '', $tag) ?? $tag;

                    return rtrim(substr($tag, 0, -1)).' target="_blank" rel="noopener noreferrer">';
                },
                $html,
            ) ?? $html;

            if (in_array($path, ['consulting-gov', 'consulting-private'], true)) {
                $bookingUrl = htmlspecialchars(route('consulting.booking'), ENT_QUOTES, 'UTF-8');
                $html = preg_replace_callback(
                    '~<a\b(?=[^>]*\bhref\s*=\s*(["\'])[^"\']*(?:boooking-page|booking-page|consulting-booking)[^"\']*\1)[^>]*>~i',
                    static function (array $match) use ($bookingUrl): string {
                        $tag = preg_replace('~\bhref\s*=\s*(["\']).*?\1~i', 'href="'.$bookingUrl.'"', $match[0]) ?? $match[0];
                        $tag = preg_replace('~\s+target\s*=\s*(["\']).*?\1~i', '', $tag) ?? $tag;
                        $tag = preg_replace('~\s+rel\s*=\s*(["\']).*?\1~i', '', $tag) ?? $tag;

                        return rtrim(substr($tag, 0, -1)).' target="_parent">';
                    },
                    $html,
                ) ?? $html;
            }
        }

        if ($embedded && $path === 'trainers-profile') {
            $html = str_replace(
                'https://angiefoong.com/founders',
                route('angie-foong'),
                $html,
            );

            $fondyUrl = htmlspecialchars(route('fondy-foong'), ENT_QUOTES, 'UTF-8');
            $html = preg_replace_callback(
                '~<a\b(?=[^>]*\bid="button-1YxNJRczNr_btn")[^>]*>~i',
                static function (array $match) use ($fondyUrl): string {
                    $tag = preg_replace(
                        '~\bhref\s*=\s*(["\']).*?\1~i',
                        'href="'.$fondyUrl.'"',
                        $match[0],
                    ) ?? $match[0];
                    $tag = preg_replace('~\s+target\s*=\s*(["\']).*?\1~i', '', $tag) ?? $tag;
                    $tag = preg_replace('~\s+rel\s*=\s*(["\']).*?\1~i', '', $tag) ?? $tag;

                    return preg_replace(
                        '~\baria-label\s*=\s*(["\']).*?\1~i',
                        'aria-label="View Story"',
                        $tag,
                    ) ?? $tag;
                },
                $html,
            ) ?? $html;
            $html = str_replace(
                '<span class="main-heading-button">View More</span>',
                '<span class="main-heading-button">View Story</span>',
                $html,
            );
        }

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
            $injection = '<style>#nav-menu-popup,#section-BK1OobeAEu,.c-section:has(.c-nav-menu),.footersection{display:none!important}html,body{overflow-x:hidden!important;overflow-y:hidden!important}</style><script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".c-nav-menu").forEach(function(nav){const legacyHeader=nav.closest(".c-section");if(legacyHeader){legacyHeader.remove()}});document.querySelectorAll("#nav-menu-popup,.footersection").forEach(function(element){element.remove()})});</script>';

            if ($path === 'contact-us') {
                $endpoint = json_encode(route('contact.submit'), JSON_THROW_ON_ERROR);
                $token = json_encode(csrf_token(), JSON_THROW_ON_ERROR);
                $injection .= '<script>document.addEventListener("DOMContentLoaded",function(){const endpoint='.$endpoint.',token='.$token.';document.querySelectorAll("form").forEach(function(form){form.addEventListener("submit",async function(event){event.preventDefault();event.stopImmediatePropagation();const field=function(selector){return form.querySelector(selector)?.value?.trim()||""};const payload={full_name:field("[name=full_name]")||field("[name=last_name]"),email:field("[name=email]"),phone:field("[name=phone]"),country:field("[name=country]"),message:field("[data-q=comment]"),source:"contact_page"};try{const response=await fetch(endpoint,{method:"POST",headers:{"Accept":"application/json","Content-Type":"application/json","X-CSRF-TOKEN":token},body:JSON.stringify(payload)});const result=await response.json();let notice=form.querySelector(".ainchors-contact-feedback");if(!notice){notice=document.createElement("p");notice.className="ainchors-contact-feedback";form.append(notice)}notice.textContent=response.ok?result.message:Object.values(result.errors||{}).flat().join(" ");notice.style.color=response.ok?"#37AD82":"#b42318"}catch(error){const notice=document.createElement("p");notice.className="ainchors-contact-feedback";notice.textContent="Unable to submit right now. Please try again.";notice.style.color="#b42318";form.append(notice)}} ,true)})});</script>';
            }

            if ($path === 'join-us') {
                $applicationUrl = json_encode(route('job-applications.create'), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $injection .= '<script>document.addEventListener("DOMContentLoaded",function(){const applicationUrl='.$applicationUrl.';document.querySelectorAll("a").forEach(function(link){const label=(link.textContent||"").toLowerCase().replace(/[^a-z]/g,"");if(label==="applynow"){link.href=applicationUrl;link.target="_parent";link.removeAttribute("rel")}})});</script>';
            }

            if ($path === 'consulting-main') {
                $whatsappUrl = json_encode('https://wa.me/61418802086', JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $injection .= '<script>(function(){const whatsappUrl='.$whatsappUrl.';const isBookNow=function(element){if(!element){return false}const label=((element.getAttribute("aria-label")||element.textContent||"").toLowerCase().replace(/[^a-z]/g,""));return label==="booknow"};const updateLinks=function(){document.querySelectorAll("a").forEach(function(link){if(!isBookNow(link)){return}if(link.getAttribute("href")!==whatsappUrl){link.setAttribute("href",whatsappUrl)}if(link.getAttribute("target")!=="_blank"){link.setAttribute("target","_blank")}if(link.getAttribute("rel")!=="noopener noreferrer"){link.setAttribute("rel","noopener noreferrer")}})};document.addEventListener("click",function(event){const element=event.target instanceof Element?event.target.closest("a,button,[role=button]"):null;if(isBookNow(element)){event.preventDefault();event.stopImmediatePropagation();window.open(whatsappUrl,"_blank","noopener,noreferrer")}},true);window.addEventListener("load",updateLinks);new MutationObserver(updateLinks).observe(document.documentElement,{childList:true,subtree:true,attributes:true,attributeFilter:["href","target","rel","aria-label"]})})();</script>';
            }

            if (in_array($path, ['consulting-gov', 'consulting-private'], true)) {
                $selectionUrl = json_encode(route('consulting.booking.select'), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $consultingType = json_encode($path === 'consulting-gov' ? 'government' : 'private', JSON_THROW_ON_ERROR);
                $buttonId = json_encode($path === 'consulting-gov' ? 'button-wnCqCxJipE_btn' : 'button-q_7paJEOWF_btn', JSON_THROW_ON_ERROR);
                $csrfToken = json_encode(csrf_token(), JSON_THROW_ON_ERROR);
                $bookingUrl = json_encode(route('consulting.booking'), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $injection .= '<script>(function(){const selectionUrl='.$selectionUrl.',bookingUrl='.$bookingUrl.',consultingType='.$consultingType.',buttonId='.$buttonId.',csrfToken='.$csrfToken.';let submitting=false;const isBookingCta=function(element){if(!element){return false}const href=element.getAttribute("href")||"";return element.id===buttonId||/(?:boooking-page|booking-page|consulting-booking)/i.test(href)};const submitSelection=function(){if(submitting){return}submitting=true;const form=document.createElement("form");form.method="POST";form.action=selectionUrl;form.target="_parent";[["_token",csrfToken],["consulting_type",consultingType]].forEach(function(entry){const input=document.createElement("input");input.type="hidden";input.name=entry[0];input.value=entry[1];form.append(input)});document.body.append(form);form.submit()};const updateLinks=function(){const link=document.getElementById(buttonId);if(!link){return}if(link.getAttribute("href")!==bookingUrl){link.setAttribute("href",bookingUrl)}if(link.getAttribute("target")!=="_parent"){link.setAttribute("target","_parent")}if(link.hasAttribute("rel")){link.removeAttribute("rel")}};document.addEventListener("click",function(event){const element=event.target instanceof Element?event.target.closest("a,button,[role=button]"):null;if(isBookingCta(element)){event.preventDefault();event.stopImmediatePropagation();submitSelection()}},true);document.addEventListener("DOMContentLoaded",updateLinks);window.addEventListener("load",updateLinks);new MutationObserver(updateLinks).observe(document.documentElement,{childList:true,subtree:true,attributes:true,attributeFilter:["href","target","rel","aria-label"]})})();</script>';
            }

            if ($path === 'trainers-profile') {
                $fondyUrl = json_encode(route('fondy-foong'), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $injection .= '<style>#button-x0KJmD9ZzT_btn,#button-1YxNJRczNr_btn,#button-IIjIZEpKRo_btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-width:0!important;min-height:0!important;padding:12px 24px!important;border:1px solid #37ad82!important;border-radius:8px!important;background:#37ad82!important;color:#fff!important;font-weight:600!important;transition:background-color .2s ease,color .2s ease,border-color .2s ease,box-shadow .2s ease!important}#button-x0KJmD9ZzT_btn:hover,#button-1YxNJRczNr_btn:hover,#button-IIjIZEpKRo_btn:hover,#button-x0KJmD9ZzT_btn:focus-visible,#button-1YxNJRczNr_btn:focus-visible,#button-IIjIZEpKRo_btn:focus-visible{background:#e8fff7!important;color:#37ad82!important;border-color:#37ad82!important;box-shadow:0 4px 10px rgba(55,173,130,.18)!important}#button-x0KJmD9ZzT_btn:focus-visible,#button-1YxNJRczNr_btn:focus-visible,#button-IIjIZEpKRo_btn:focus-visible{outline:3px solid rgba(55,173,130,.35);outline-offset:3px}</style><script>(function(){const fondyUrl='.$fondyUrl.';const updateViewStory=function(){const button=document.getElementById("button-1YxNJRczNr_btn");if(!button){return}if(button.getAttribute("href")!==fondyUrl){button.setAttribute("href",fondyUrl)}if(button.getAttribute("target")!=="_parent"){button.setAttribute("target","_parent")}if(button.getAttribute("aria-label")!=="View Story"){button.setAttribute("aria-label","View Story")}button.removeAttribute("rel");const text=button.querySelector(".main-heading-button");if(text&&text.textContent.trim()!=="View Story"){text.textContent="View Story"}};document.addEventListener("DOMContentLoaded",updateViewStory);window.addEventListener("load",updateViewStory);document.addEventListener("click",function(event){const button=event.target instanceof Element?event.target.closest("#button-1YxNJRczNr_btn"):null;if(button){event.preventDefault();event.stopImmediatePropagation();window.parent.location.assign(fondyUrl)}},true);new MutationObserver(updateViewStory).observe(document.documentElement,{childList:true,subtree:true,attributes:true,attributeFilter:["href","target","aria-label"]})})();</script>';
                $injection .= '<script>(function(){document.addEventListener("click",function(event){const button=event.target instanceof Element?event.target.closest("#button-IIjIZEpKRo_btn"):null;if(!button){return}event.preventDefault();event.stopImmediatePropagation();window.parent.scrollTo({top:0,behavior:"smooth"})},true)})();</script>';
            }

            if ($path === 'fondy-foong') {
                $homeUrl = json_encode(route('home'), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $testimonialsUrl = json_encode(route('testimonials'), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $injection .= '<style>#row-aCeKCn-MVZ>.inner{align-items:stretch!important}#row-aCeKCn-MVZ .c-column>.vertical.inner{display:flex!important;height:100%!important;flex-direction:column!important;align-items:center!important}#image-L881OFNMF2Z,#image-Ug6kHdpasQc,#image-URiRds6Zm3V{display:flex!important;width:100%!important;justify-content:center!important;align-items:flex-start!important}#image-L881OFNMF2Z .image-container,#image-Ug6kHdpasQc .image-container,#image-URiRds6Zm3V .image-container,#image-L881OFNMF2Z picture,#image-Ug6kHdpasQc picture,#image-URiRds6Zm3V picture{display:flex!important;width:auto!important;justify-content:center!important;background:transparent!important}#image-L881OFNMF2Z img,#image-Ug6kHdpasQc img,#image-URiRds6Zm3V img{display:block!important;width:auto!important;height:auto!important;max-width:100%!important;max-height:440px!important;object-fit:contain!important;background:transparent!important}#button-sMpCEfOO52,#button-JvjztUayrI,#button-xKpI38KBQ7{margin-top:auto!important;padding-top:16px!important}#row-ka0umtOoc0{display:none!important}@media(max-width:767px){#row-aCeKCn-MVZ .c-column>.vertical.inner{height:auto!important}#image-L881OFNMF2Z img,#image-Ug6kHdpasQc img,#image-URiRds6Zm3V img{max-height:none!important}#button-sMpCEfOO52,#button-JvjztUayrI,#button-xKpI38KBQ7{margin-top:0!important;margin-bottom:28px!important}}</style><script>document.addEventListener("DOMContentLoaded",function(){const centreColumn=document.querySelector("#col-Zo8pjvrIsX>.vertical.inner"),backButton=document.getElementById("button-xKpI38KBQ7");if(centreColumn&&backButton){centreColumn.append(backButton)}});</script>';
                $injection .= '<style>#image-L881OFNMF2Z,#image-Ug6kHdpasQc,#image-URiRds6Zm3V{height:clamp(300px,31vw,440px)!important;min-height:clamp(300px,31vw,440px)!important;max-height:clamp(300px,31vw,440px)!important;overflow:hidden!important;align-items:center!important}#image-L881OFNMF2Z .image-container,#image-Ug6kHdpasQc .image-container,#image-URiRds6Zm3V .image-container,#image-L881OFNMF2Z .image-container>div,#image-Ug6kHdpasQc .image-container>div,#image-URiRds6Zm3V .image-container>div,#image-L881OFNMF2Z .image-container>div>div,#image-Ug6kHdpasQc .image-container>div>div,#image-URiRds6Zm3V .image-container>div>div,#image-L881OFNMF2Z picture,#image-Ug6kHdpasQc picture,#image-URiRds6Zm3V picture{width:100%!important;height:100%!important;min-height:0!important;display:flex!important;align-items:center!important;justify-content:center!important}#image-L881OFNMF2Z img,#image-Ug6kHdpasQc img,#image-URiRds6Zm3V img{display:block!important;width:100%!important;height:100%!important;max-width:100%!important;max-height:100%!important;object-fit:contain!important;object-position:center center!important}#image-Ug6kHdpasQc img{transform:scale(.88)!important;transform-origin:center center!important}#button-xKpI38KBQ7{display:flex!important;visibility:visible!important;opacity:1!important;width:100%!important;align-items:center!important;justify-content:center!important}#button-xKpI38KBQ7_btn{display:inline-flex!important;visibility:visible!important;opacity:1!important}</style><script>(function(){let placementObserver=null;const placeBackButton=function(){const centreColumn=document.querySelector("#col-Zo8pjvrIsX>.vertical.inner"),backButton=document.getElementById("button-xKpI38KBQ7");if(!centreColumn||!backButton){return}if(backButton.parentElement!==centreColumn){centreColumn.append(backButton)}backButton.style.setProperty("display","flex","important");backButton.style.setProperty("visibility","visible","important");backButton.style.setProperty("opacity","1","important")};const watchPlacement=function(){placeBackButton();if(placementObserver||!document.body){return}placementObserver=new MutationObserver(placeBackButton);placementObserver.observe(document.body,{childList:true,subtree:true})};document.addEventListener("DOMContentLoaded",watchPlacement);window.addEventListener("load",watchPlacement);setTimeout(watchPlacement,0)})();</script>';
                $injection .= '<style>#button-sMpCEfOO52_btn,#button-JvjztUayrI_btn,#button-xKpI38KBQ7_btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-width:0!important;min-height:0!important;padding:12px 24px!important;border:1px solid #37ad82!important;border-radius:8px!important;background:#37ad82!important;color:#fff!important;font-weight:600!important;transition:background-color .2s ease,color .2s ease,border-color .2s ease,box-shadow .2s ease!important}#button-sMpCEfOO52_btn:hover,#button-JvjztUayrI_btn:hover,#button-xKpI38KBQ7_btn:hover,#button-sMpCEfOO52_btn:focus-visible,#button-JvjztUayrI_btn:focus-visible,#button-xKpI38KBQ7_btn:focus-visible{background:#e8fff7!important;color:#37ad82!important;border-color:#37ad82!important;box-shadow:0 4px 10px rgba(55,173,130,.18)!important}#button-sMpCEfOO52_btn:focus-visible,#button-JvjztUayrI_btn:focus-visible,#button-xKpI38KBQ7_btn:focus-visible{outline:3px solid rgba(55,173,130,.35);outline-offset:3px}</style><script>(function(){const destinations={"button-sMpCEfOO52_btn":'.$homeUrl.',"button-JvjztUayrI_btn":'.$testimonialsUrl.'};const updateLinks=function(){Object.entries(destinations).forEach(function(entry){const button=document.getElementById(entry[0]),url=entry[1];if(!button){return}if(button.getAttribute("href")!==url){button.setAttribute("href",url)}if(button.getAttribute("target")!=="_parent"){button.setAttribute("target","_parent")}button.removeAttribute("rel")})};document.addEventListener("DOMContentLoaded",function(){updateLinks();const backToTop=document.getElementById("button-xKpI38KBQ7_btn");if(backToTop){backToTop.addEventListener("click",function(event){event.preventDefault();window.parent.scrollTo({top:0,behavior:"smooth"})})}});window.addEventListener("load",updateLinks);document.addEventListener("click",function(event){const button=event.target instanceof Element?event.target.closest("#button-sMpCEfOO52_btn,#button-JvjztUayrI_btn"):null;if(button&&destinations[button.id]){event.preventDefault();event.stopImmediatePropagation();window.parent.location.assign(destinations[button.id])}},true);new MutationObserver(updateLinks).observe(document.documentElement,{childList:true,subtree:true,attributes:true,attributeFilter:["href","target"]})})();</script>';
                $injection .= '<script>(function(){document.addEventListener("click",function(event){const button=event.target instanceof Element?event.target.closest("#button-xKpI38KBQ7_btn"):null;if(!button){return}event.preventDefault();event.stopImmediatePropagation();window.parent.scrollTo({top:0,behavior:"smooth"})},true)})();</script>';
            }

            $injection .= '<script>document.addEventListener("DOMContentLoaded",function(){let pending=false,lastHeight=0;const report=function(){if(pending){return}pending=true;requestAnimationFrame(function(){pending=false;const height=Math.ceil(Math.max(document.body.scrollHeight,document.documentElement.scrollHeight));if(Math.abs(height-lastHeight)<2){return}lastHeight=height;parent.postMessage({source:"ainchors-legacy",type:"height",height:height},location.origin)})};const resizeObserver=new ResizeObserver(report);resizeObserver.observe(document.documentElement);resizeObserver.observe(document.body);const mutationObserver=new MutationObserver(report);mutationObserver.observe(document.body,{childList:true,subtree:true});window.addEventListener("load",report);if(document.fonts&&document.fonts.ready){document.fonts.ready.then(report)}[0,250,1000].forEach(function(delay){setTimeout(report,delay)});report()})</script>';
            $html = str_replace('</head>', $injection.'</head>', $html);
        }

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache');
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
