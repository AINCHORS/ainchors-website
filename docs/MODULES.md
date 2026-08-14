# Modules

AINCHORS V2 is organised by customer-facing module roots. This structure keeps
each future Blade migration independent while the legacy bridge continues to
serve the original content and URLs.

## Application code

- `app/Http/Controllers/Legacy/` — read-only legacy-page bridge.
- `app/Http/Controllers/Modules/` — Laravel controllers for migrated modules.
- `app/Services/Content/` — approved content providers for migrated modules.
- `resources/views/layouts/` — shared page shell.
- `resources/views/components/site/` — header and footer.
- `resources/views/components/cards/` — reusable card UI.

## Blade module roots

- `modules/home/`
- `modules/company/about/`
- `modules/training/courses/`, `trainers/`, `testimonials/`, `success-stories/`
- `modules/consulting/main/`, `government/`, `private-sector/`
- `modules/support/faqs/`, `contact/`, `careers/`
- `modules/commerce/events/`, `checkout/`
- `modules/legal/`

The route-to-module mapping is kept in
`resources/views/modules/README.md`. Each legacy route can be migrated into
its matching controller, content service and Blade module without introducing
a SPA or changing its URL.
