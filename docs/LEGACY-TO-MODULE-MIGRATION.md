# Legacy-to-module migration register

## Scope and non-negotiable rules

- This register applies only to the local Laravel V2 project at
  `C:\xampp\htdocs\ainchors-website-v2`.
- It must never modify, publish to, or depend on the live `ainchors.com`
  domain. Local development uses `http://127.0.0.1:8000`.
- `resources/legacy/` is a temporary, read-only content reference. It is not
  part of the final production module structure.
- The existing shared `x-site-header` and `x-site-footer` components are
  locked. A page migration may consume them through `layouts.app`, but must
  not change their CSS, layout, wording, links, or content.
- A final route, module directory, Blade view, controller class, and asset
  directory must use a clean semantic slug. Random numeric or generated ID
  suffixes are forbidden. Semantic numbers that belong to factual course
  titles, such as `ai-prompt-engineering-101`, are allowed.

## Final module roots

```text
app/Http/Controllers/Modules/
├── Account/
├── Company/
├── Training/
├── Consulting/
├── Support/
├── Commerce/
└── Legal/

resources/views/modules/
├── account/
├── home/
├── company/about/
├── training/courses/
├── training/trainers/
├── training/testimonials/
├── training/success-stories/
├── consulting/main/
├── consulting/government/
├── consulting/private-sector/
├── support/faqs/
├── support/contact/
├── support/careers/
├── commerce/events/
├── commerce/checkout/
└── legal/
```

Empty directories are intentionally not committed. Each directory is created
with its first real Blade view, rather than adding placeholder pages.

## Public-page migration register

| Module | Final clean route | Legacy content source | Target controller | Target Blade view | Current state |
| --- | --- | --- | --- | --- | --- |
| Company / About | `/about-us` | `legacy/about-us/` | `Modules\Company\AboutController` | `modules.company.about.index` | iframe bridge |
| Training / Trainers | `/trainers-profile` | `legacy/trainers-profile/` | `Modules\Training\TrainersController` | `modules.training.trainers.index` | iframe bridge |
| Training / Testimonials | `/testimonials` | `legacy/testimonials/` | `Modules\Training\TestimonialsController` | `modules.training.testimonials.index` | iframe bridge |
| Training / Success stories | `/success-story-of-angie` | `legacy/success-story-of-angie/` | `Modules\Training\SuccessStoriesController` | `modules.training.success-stories.angie` | iframe bridge |
| Consulting / Introduction | `/consulting-main` | `legacy/consulting-main/` | `Modules\Consulting\IntroductionController` | `modules.consulting.main.index` | iframe bridge |
| Consulting / Government | `/consulting-gov` | `legacy/consulting-gov/` | `Modules\Consulting\GovernmentController` | `modules.consulting.government.index` | iframe bridge |
| Consulting / Government booking | `/consulting-gov/booking` | `legacy/boooking-page/` | `Modules\Consulting\GovernmentBookingController` | `modules.consulting.government.booking` | native CRM booking form |
| Consulting / Private | `/consulting-private` | `legacy/consulting-private/` | `Modules\Consulting\PrivateSectorController` | `modules.consulting.private-sector.index` | iframe bridge |
| Support / FAQs | `/faqs` | `legacy/faqs/` | `Modules\Support\FaqController` | `modules.support.faqs.index` | iframe bridge |
| Support / Contact | `/contact-us` | `legacy/contact-us/` | `Modules\Support\ContactController` | `modules.support.contact.index` | iframe bridge |
| Support / Careers | `/join-us` | `legacy/join-us/` | `Modules\Support\CareersController` | `modules.support.careers.index` | iframe bridge |
| Commerce / Events | `/events` | `legacy/events/` | `Modules\Commerce\EventsController` | `modules.commerce.events.index` | iframe bridge |
| Legal / Terms | `/terms--conditions` | `legacy/terms--conditions/` | `Modules\Legal\TermsController` | `modules.legal.terms` | iframe bridge |
| Legal / Privacy | `/privacy--policy` | `legacy/privacy--policy/` | `Modules\Legal\PrivacyController` | `modules.legal.privacy` | iframe bridge |

## Existing native domains

These already have Laravel controllers and should be moved into the indicated
module root as a later refactor, without changing their public routes or user
experience:

| Existing feature | Final module root | Clean routes |
| --- | --- | --- |
| Course catalogue, course detail and learning | `training/courses/` | `/courses`, `/courses/{slug}`, `/learn/{slug}` |
| Checkout, orders and payment success | `commerce/checkout/` | `/checkouts/{product}`, `/orders/{order}/success` |
| Account profile, purchases and enrolments | `account/` | `/profile`, `/purchase-history`, `/my-courses` |

## Legacy-only names and deletion gate

The following are not valid final names: generated checkout routes such as
`checkout-page123`, `checkout-page6767`, `checkout-page-684967`, package
sources such as `package-page-4066`, and the typo source `boooking-page`.
They remain only as factual migration references until the equivalent native
feature is verified.

No legacy source may be deleted until all of these are true:

1. The replacement has a clean named route and a real module view.
2. Original text, images, logos, CTA wording, links and factual company data
   have been checked against the legacy source.
3. The page uses the locked shared Header/Footer through `layouts.app` and
   contains no embedded legacy Header/Footer/chat surface.
4. Desktop and mobile visual checks pass at 375, 390, 768, 1024, 1280 and
   1440 pixels.
5. Any form, booking, payment or course interaction stores/retrieves data
   through the approved Laravel service and database schema.
6. A clean commit preserves the old source in Git history before removal.

Historical compatibility redirects, if retained later, may redirect an old
address to a clean final route; they must never create a new numeric page,
module, Blade filename, or canonical URL.
