# Module Boundaries

## Home

- Purpose: introduce AINCHORS and direct visitors to the appropriate service.
- Owns: hero, client logos, service preview, trust signals, testimonial preview and homepage CTA.
- Pages: `/`.
- Dependencies: shared layout, Section, Card, Button and SectionTitle.
- Does not own: course detail, payment, contact submissions or full testimonials.

## Company

- Purpose: explain the company, mission and stories.
- Owns: About and success stories.
- Pages: `/about`, `/success-story`.
- Does not own: training catalogue or recruitment.

## Training

- Purpose: corporate training and trainer information.
- Owns: training overview, trainer listing and profiles.
- Pages: `/training`, `/trainers`, `/trainers/:slug`.
- Does not own: course purchases.

## Courses

- Purpose: describe what AINCHORS sells.
- Owns: catalogue, course details, course cards and package information.
- Pages: `/courses`, `/courses/:slug`.
- Dependencies: shared UI; links to Commerce for purchase.
- Does not own: checkout, payment or orders.

## Commerce

- Purpose: manage how a customer buys.
- Owns: price resolution, checkout, payment adapter, orders and confirmation.
- Pages: `/checkout`, `/payment`, `/order-confirmation`.
- Does not own: course editorial content.

## Consulting

- Purpose: explain strategic consulting services.
- Owns: overview, government and private-sector consulting.
- Pages: `/consulting`, `/consulting/government`, `/consulting/private-sector`.

## Testimonials

- Purpose: manage customer feedback and social proof.
- Owns: testimonial data, cards and listing.
- Pages: `/testimonials`.

## Events

- Purpose: manage event listings and event details.
- Pages: `/events`, `/events/:slug`.

## Careers

- Purpose: recruitment and job information.
- Owns: Join Us, job list and job details.
- Pages: `/careers`, `/careers/:slug`.

## Contact

- Purpose: enquiries and contact methods.
- Owns: contact information, enquiry form and WhatsApp CTA.
- Pages: `/contact`.

## FAQ

- Purpose: common questions and answers.
- Owns: FAQ data and accessible accordion behaviour.
- Pages: `/faq`.

## Legal

- Purpose: policies and legal information.
- Owns: terms, privacy and future policies.
- Pages: `/terms`, `/privacy`.

## Shared

- Owns: Header, navigation, Footer, Button, Container, Section, Card, Modal, Breadcrumb, CTA, SectionTitle, WhatsAppButton, layouts, utilities and design tokens.
- Rule: business modules consume shared components; shared code must not import a business module.
