# AINCHORS V2 — Site Integration Audit

Audit date: 2026-08-19  
Scope: `C:\xampp\htdocs\ainchors-website-v2` only. The legacy mirror and production site were read-only references.

## Safety checkpoint

The working tree was clean on `laravel-v2`. It was already protected by the local design-system checkpoint `cb1532a` (`react-v2-backup` and `main` remain untouched). The branch was two commits ahead of `ainchors-org/main`; no remote operation was performed.

## Application and data foundation

Laravel 13.25.0 is running locally with Herd PHP 8.4.16, MySQL, Blade, Tailwind/Vite, and Alpine. The current migrations are applied. The SQL schema export describes 18 application/domain tables: users, products, product_relations, course_contents, orders, order_items, payments, enrollments, visitors, visitor_sessions, activity_events, privacy_consents, leads, consultation_requests, service_engagements, workflow_audits, workflow_audit_answers and workflow_audit_results (plus Laravel infrastructure tables).

Important reconciliation finding: the course-commerce migrations create the commerce tables, while several Phase-1 domain tables appear in `database/schema/ainchors.sql` but do not have corresponding repository migrations. New work must therefore use additive, guarded migrations and must not assume a fresh database already has the schema-export-only tables.

Existing working domains:

- Course catalogue, package, checkout, demo payment, orders, enrollments, and protected media are native Laravel features.
- The existing `User` model stores `full_name`, `email`, password, role, status, optional phone/country and login timestamp.
- Visitor, visitor-session and activity-event models already exist but only have lookup/read helpers; no request tracking is active.
- Leads and consultation-request models exist but public contact forms do not submit to either model.
- There is no admin authorization layer, administration route group, admin layout, role middleware or admin audit log.
- There is no setting model, no reusable public-page controller, no password-reset route/controller, no profile or purchase-history route/controller, and no SEO feature.

## Route and page matrix — before integration work

| Route | Name | Controller / view | Auth | Admin | Rendering | Header / footer | Status and problem |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `/`, `/home` | `home` only for `/home` | `LegacyPageController@home`, raw `resources/legacy/home/index.html` | No | No | Legacy | Legacy embedded in raw HTML | Works but bypasses the new layout/design system; duplicate home URLs. |
| `/about-us` | `legacy.page` | Legacy raw HTML | No | No | Legacy | Legacy | Works only through catch-all; no named canonical route. |
| `/trainers-profile` | `legacy.page` | Legacy raw HTML | No | No | Legacy | Legacy | Catch-all only; no named route. |
| `/testimonials` | `legacy.page` | Legacy raw HTML | No | No | Legacy | Legacy | Catch-all only; no named route. |
| `/courses` | `courses.index` | `CourseCatalogController@index`, `courses/index` | No | No | Native | New public shell | Native, but typography/layout coexist with old CSS classes. |
| `/courses/{slug}` | `courses.show` | Native product detail | No | No | Native | New public shell | Works. |
| `/course-packages/{slug}` | `packages.show` | Native package detail | No | No | Native | New public shell | Works. |
| `/consulting-main` | `legacy.page` | Legacy raw HTML | No | No | Legacy | Legacy | Exists through catch-all only. |
| `/consulting-gov` | `legacy.page` | Legacy raw HTML | No | No | Legacy | Legacy | Exists through catch-all only. |
| `/consulting-private` | `legacy.page` | Legacy raw HTML | No | No | Legacy | Legacy | Exists through catch-all only. |
| `/faqs`, `/join-us`, `/contact-us` | `legacy.page` | Legacy raw HTML | No | No | Legacy | Legacy | Contact form is not an application submission. |
| `/success-story-of-angie`, terms, privacy, events | `legacy.page` | Legacy raw HTML | No | No | Legacy | Legacy | Useful legacy compatibility pages without named canonical routes. |
| `/login`, `/register` | `login`, `register` | Native auth views | Guest | No | Native | Full public shell | No password visibility controls, consent checkbox, reset flow, rate limit, or auth-focused shell. |
| `/my-courses` | `my-courses` | Native enrollment view | Yes | No | Native | Full public shell | Works and remains protected. |
| `/learn/{slug}` and course media | `learn.show`, media names | Native protected course views/controllers | Yes | No | Native | Full public shell | Works; protected streaming/download authorization is already safely implemented. |
| `/checkouts/{slug}`, `/orders/{number}/success` | checkout names | Native checkout/payment success | Yes | No | Native | Full public shell | Works, but no account menu or purchase-history destination. |
| Legacy course/checkout aliases | redirects | `Route::redirect` | Mixed | No | Compatibility | N/A | Useful compatibility routes; some target protected checkout destinations. |
| `/{path}` | `legacy.page` | `LegacyPageController` | No | No | Legacy | Legacy | Absorbs all unmatched letters/slashes, concealing missing intended routes and preventing explicit route policies. |

## Navigation and shell findings

- The new header uses `route()` for home/courses/auth but `url('/…')` for most public destinations. It omits the required Consulting Introduction child, does not expose the exact desktop navigation requested, and flattens Training/Consulting on mobile instead of retaining nested disclosure controls.
- Guest navigation shows Login plus Contact instead of Login plus Register. Authenticated users only see My Courses and Logout; there is no Account dropdown, profile, purchase history or admin entry.
- The footer links use `url('/…')`; external WhatsApp/social links appropriately use a new tab, but its contact form only toggles a client-side “Thank you” message and discards data.
- The native public layout has the shared Header/Footer and a skip link. Legacy pages inject responsive CSS and remove non-WhatsApp `target` attributes, but do not share the native header/footer or accessibility/shell behavior.
- `LegacyPageController` transforms production-domain links to the current local host. This is intentional compatibility behavior, but is not a substitute for named V2 routes.

## URL and duplication findings

- No normal native application navigation hardcodes localhost, 127.0.0.1, `ainchors.com`, or `/ainchors-website/`; the only application-domain strings occur in the legacy compatibility transformer. Standard Laravel config defaults and `mailto:` addresses are not navigation leaks.
- There are duplicate implementations for Home: a prepared native `modules/home/index.blade.php` and the active legacy home. The route currently selects legacy.
- The named routes currently cover only auth, course commerce and learning. Required public informational pages have no named routes, profile/purchase history/reset/admin/analytics/SEO are absent.

## Test and quality findings

- Current tests concentrate on home, catalogue, checkout, enrollment and protected media. No tests cover explicit informational routes, contact submissions, reset/profile/purchase isolation, roles/admin, tracking, SEO, welcome modal or branded errors.
- `npm run build` and the existing feature suite previously passed; the build retains harmless Vite warnings for two runtime public background assets.
- The application is in local debug mode. Phase 6 must add branded error views and production-safe error behavior without changing local debugging settings.

## Integration strategy

1. Preserve legacy HTML as the factual content source and make its existing public paths explicit named routes through a narrow public-page controller; retain the catch-all only as a backward-compatible final fallback.
2. Promote the existing native home module to the canonical home route and apply the shared shell to native public/auth/admin views, using exact legacy factual content where content is migrated.
3. Add small, additive schema changes for settings, contact submissions, admin audit entries, and page views. Reuse the existing users role column, orders/payments/enrollments, visitor/session/event models and commerce services.
4. Build public, auth, admin, analytics and SEO features in the prescribed phase order, preserving current course purchase and protected-learning behavior.

## Phase 1 implementation outcome

- `/` is now the single native, named canonical home route; `/home` remains a 301 compatibility redirect.
- The required public paths are explicit named routes. Informational legacy content is deliberately rendered inside a same-origin, auto-sizing frame beneath the native header/footer. The legacy navigation and footer are removed only inside that frame, while raw factual content and original imagery remain intact. Internal links navigate the parent Laravel page.
- The desktop and mobile header now use the requested Training and Consulting disclosure hierarchies. Consulting contains exactly Consulting Introduction, Public / Government Sector and Private Sector. Guests receive Login and Register; authenticated users receive Account navigation, with future profile/history/admin destinations guarded until their Phase 2/3 routes are introduced.
- The footer form and legacy contact-page form now share ContactSubmissionService and store validated enquiries as CRM leads. No email destination or credential was invented.
- A configurable welcome_modal_frequency setting has a safe default of every_page; the accessible guest welcome modal excludes authentication and checkout flows.
- Browser QA at 375, 390, 768, 1024, 1280 and 1440 found no horizontal overflow. Desktop/mobile hierarchy, iframe shell isolation and responsive header behavior were verified.
