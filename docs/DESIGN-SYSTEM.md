# AINCHORS Design System

The V2 system keeps the existing green AINCHORS identity, dark corporate typography and blue action colour while replacing one-off page styling with reusable tokens.

## Colour tokens

| Token | Value | Use |
| --- | --- | --- |
| Primary | `#2bae83` | Brand highlights and key surfaces |
| Primary dark | `#178063` | Accessible brand-colour text and hover states |
| Secondary | `#1b2735` | Dark sections and footer |
| Accent | `#2f73e8` | Primary calls to action |
| Background | `#ffffff` | Page background |
| Surface | `#f4faf8` | Alternate section background |
| Text | `#17212b` | Primary copy |
| Muted | `#5f6c76` | Supporting copy |
| Border | `#dbe6e2` | Cards and separators |
| Success | `#198754` | Positive states |
| Error | `#c83b3b` | Errors and destructive states |

## Typography

The site uses the system sans-serif stack, with Montserrat preferred for headings when available. Type scales responsively with `clamp()`.

| Role | Behaviour |
| --- | --- |
| Display / H1 | `clamp(2.5rem, 5.7vw, 4.75rem)` |
| H2 | `clamp(2rem, 4vw, 3.25rem)` |
| H3 | `clamp(1.25rem, 2vw, 1.625rem)` |
| H4 | `1.125rem` |
| Body large | `clamp(1.05rem, 1.7vw, 1.25rem)` |
| Body | `1rem` with `1.7` line height |
| Small / caption | `0.75rem–0.9rem` |

## Spacing

The scale is `4, 8, 12, 16, 24, 32, 48, 64, 80px`. Components use only these values unless an intrinsic ratio requires otherwise.

## Layout

- Standard container: `min(100% - 32px, 1200px)`.
- Sections use responsive vertical padding.
- Grid content collapses from 3–4 columns, to 2 columns, to 1 column.
- Images use intrinsic proportions, `max-width: 100%`, and `object-fit: cover` where cropped.
- Avoid absolute positioning except small decorative or anchored overlay elements.

## Breakpoints

- Mobile: below `640px` (layout also handles narrow 375/390 widths)
- Tablet: `640–1023px`
- Laptop: `1024–1279px`
- Desktop: `1280px+`

## Accessibility

All interactive controls need keyboard focus styles, useful accessible names, and adequate touch targets. Motion respects `prefers-reduced-motion`.
