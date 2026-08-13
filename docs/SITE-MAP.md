# Site Map

Only `/` is implemented in Phase 1. Other routes document the future architecture and must not be treated as finished pages.

```text
HOME
└── /

COMPANY
├── /about
└── /success-story

TRAINING
├── /training
└── /trainers

COURSES
├── /courses
└── /courses/:slug

CONSULTING
├── /consulting
├── /consulting/government
└── /consulting/private-sector

SOCIAL PROOF
├── /testimonials
└── /events

SUPPORT
├── /faq
└── /contact

CAREERS
└── /careers

LEGAL
├── /terms
└── /privacy

COMMERCE
├── /checkout
├── /payment
└── /order-confirmation
```

## Legacy mapping

The initial mapping is stored in `src/core/constants/legacy-routes.ts`. It is migration documentation only; redirects are intentionally not enabled in Phase 1.
