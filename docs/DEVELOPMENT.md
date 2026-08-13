# Development

## Stack

V2 uses React 19, TypeScript and the Vinext/Vite build already provided by the local Sites starter. This gives the small company site component boundaries, metadata support and a production build without introducing backend or payment complexity during the homepage phase.

## Local workflow

```bash
npm install
npm run dev
npm run lint
npm test
```

The development server prints the local preview URL. Do not assume a port.

## Source layout

- `app/`: route entry points and global metadata.
- `src/core/`: central configuration, route constants, SEO and legacy mapping.
- `src/shared/`: reusable components, layouts and global styles.
- `src/modules/`: business-owned page composition.
- `public/assets/`: descriptively named, optimised assets grouped by domain.
- `docs/`: design, site map and module contracts.
- `tests/`: rendering and architecture checks.

## Rules

- Build responsive layouts from their smallest useful width.
- Add reusable tokens before one-off CSS values.
- Keep Courses editorial concerns separate from Commerce transactions.
- Do not implement redirects until migration planning is approved.
- Do not copy generated legacy HTML or its CSS into V2.
- Do not commit secrets. Environment examples must contain placeholders only.
- Run lint, build and the responsive browser checks before merging homepage changes.

## Current phase

Phase 1 implements the shared foundation and homepage only. Future route links intentionally establish information architecture but their pages must be built and approved in the documented order.
