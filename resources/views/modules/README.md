# Blade module roots

Each customer-facing area has one stable module root. When a legacy page is
migrated, place its Blade view under the matching directory and keep the URL
listed below unchanged.

| Root | Routes |
| --- | --- |
| `home/` | `/`, `/home` |
| `company/about/` | `/about-us-814253` |
| `training/courses/` | `/courses` and course catalogue pages |
| `training/trainers/` | `/trainers-profile` |
| `training/testimonials/` | `/testimonials` |
| `training/success-stories/` | `/success-story-of-angie` |
| `consulting/main/` | `/consulting-main` |
| `consulting/government/` | `/consulting-gov` |
| `consulting/private-sector/` | `/consulting-private` |
| `support/faqs/` | `/faqs` |
| `support/contact/` | `/contact-us` |
| `support/careers/` | `/hiring-page` |
| `commerce/events/` | `/events` |
| `commerce/checkout/` | checkout routes |
| `legal/` | terms and privacy routes |

The unmodified source pages remain in `resources/legacy/` until their matching
Blade module is implemented.
