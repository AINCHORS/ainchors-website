# Course commerce and protected learning

AINCHORS V2 uses Laravel session authentication, Blade, Tailwind CSS, Alpine.js
for the submit interaction, Eloquent/MySQL, and domain services. It is not a
SPA and has no React, Funnel Freedom, GoKollab, or external course checkout in
the new course flow.

## Customer flow

- A guest selecting a course or package checkout is sent to login with
  Laravel's intended URL preserved. Login or registration returns the customer
  to that exact checkout.
- Checkout identity comes only from the authenticated account and is readonly.
- The server loads the product, current price, currency, package members, and
  enrollment targets from the database. It never trusts these values from the
  browser.
- Individual courses display list price USD 50 and charge the demo amount USD
  19. The package displays USD 190 and charges USD 150.
- The documented test values are `4242 4242 4242 4242`, `12/30`, and `123`.
  They are validated and discarded. Card number, expiry, and CVV are never
  persisted, logged, attached to exceptions, or sent over the network.
- A successful individual purchase produces one order, one demo payment, and
  one course enrollment. A package produces one order, one demo payment, and
  only the missing enrollments among its ten `bundle_item` relations.
- Existing ownership is enforced on both the page CTA and server. A fully owned
  package cannot be bought again.

## Service and transaction boundaries

`CheckoutService` owns the database transaction and locks the user while it
orchestrates `OrderService`, `PaymentService`, and `EnrollmentService`.
`CourseAccessService` is the shared authorization decision for learning pages
and protected media.

Each checkout form receives a server-issued idempotency key. The unique nullable
`orders.idempotency_key` index, the transaction, user lock, and unique
`(user_id, product_id)` enrollment constraint protect against double clicks,
retries, and repeat submissions. Payment records contain only the safe demo
provider, reference, amount, currency, status, order relationship, and
timestamps.

The package membership is always derived from `product_relations`; controllers
and views do not contain numeric course ID lists.

## Main routes

| Method | Route | Purpose |
| --- | --- | --- |
| GET/POST | `/register`, `/login` | Session authentication |
| POST | `/logout` | End the session |
| GET | `/courses` | Canonical catalogue |
| GET | `/courses/{course:slug}` | Individual detail and ownership-aware CTA |
| GET | `/course-packages/{package:slug}` | Package detail and ownership-aware CTA |
| GET/POST | `/checkouts/{product:slug}` | Authenticated reusable checkout and demo payment |
| GET | `/orders/{order:order_number}/success` | Authenticated purchase result |
| GET | `/my-courses` | Enrolled course products only |
| GET | `/learn/{course:slug}` | Authenticated, enrollment-protected learning page |
| GET | `/course-media/{course:slug}/video` | Protected MP4 response with byte-range support |
| GET | `/course-media/{course:slug}/slides` | Protected PPTX download |

Known old local course and checkout URLs redirect to their canonical local V2
destinations. The protected media controller maps the enrolled course to fixed
private relative paths; request parameters cannot select arbitrary files.

## Course content

`course_contents.lesson_content` stores the reusable ordered 01 Start Here, 02
Full Course, and 03 Course Recap & Next Steps data. Each of the ten records has
course-specific copy derived from its verified PPTX. The Blade layout is shared,
but the content is not duplicated between courses.

Reconcile catalogue and learning records safely with:

```powershell
& 'C:\Users\Acer\.config\herd\bin\php84\php.exe' artisan ainchors:populate-legacy-course-catalogue
& 'C:\Users\Acer\.config\herd\bin\php84\php.exe' artisan ainchors:populate-course-learning-content
```

Both commands are idempotent and do not delete unrelated products.

## Local setup and verification

```powershell
composer install
npm install
& 'C:\Users\Acer\.config\herd\bin\php84\php.exe' artisan migrate
& 'C:\Users\Acer\.config\herd\bin\php84\php.exe' artisan ainchors:populate-legacy-course-catalogue
& 'C:\Users\Acer\.config\herd\bin\php84\php.exe' artisan ainchors:populate-course-learning-content
npm run build
& 'C:\Users\Acer\.config\herd\bin\php84\php.exe' artisan test
& 'C:\Users\Acer\.config\herd\bin\php84\php.exe' artisan serve
```

The twenty binaries described in `docs/COURSE-ASSETS.md` must be restored to
private storage on a new machine because Git intentionally excludes them.

## Replacing the demo gateway later

A real provider should replace the implementation behind `PaymentService` and
introduce provider confirmation/webhook handling. Product pricing, orders,
package relations, enrollment rules, authentication, course authorization, and
the learning UI remain unchanged. Enrollment must only be granted after the
provider has authoritatively confirmed a paid transaction. Never pass raw card
data through this application.
