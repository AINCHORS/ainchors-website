# Legacy catalogue reconciliation

`ainchors:populate-legacy-course-catalogue` reconciles the verified legacy
records with the current customer-facing canonical names. The legacy website
and `C:\xampp\htdocs\ainchors-website` remain read-only source references; the
approved 2026-08-18 naming instruction governs the Laravel V2 catalogue.

## Canonical active catalogue

All ten courses are active, one-time products. Their current database price is
USD 19.00 and `metadata.pricing.original_amount` retains USD 50.00.

| Order | SKU | Canonical name | Slug |
| --- | --- | --- | --- |
| 1 | `SL-AI-001` | AI Prompt Engineering 101 | `ai-prompt-engineering-101` |
| 2 | `SL-DMAI-002` | Digital Marketing using AI | `digital-marketing-using-ai` |
| 3 | `SL-DA-003` | Data Analytics | `data-analytics` |
| 4 | `SL-SQL-004` | SQL for Data Analytics | `sql-for-data-analytics` |
| 5 | `SL-FLM-005` | Financial Literacy Mastery | `financial-literacy-mastery` |
| 6 | `SL-EP-006` | E-Payment Fundamentals | `e-payment-fundamentals` |
| 7 | `SL-FF-007` | Fintech Fundamentals | `fintech-fundamentals` |
| 8 | `SL-CBDC-008` | Central Bank Digital Currency (CBDC) | `central-bank-digital-currency-cbdc` |
| 9 | `SL-BSA-009` | Becoming Your Supervisor's Advisor | `becoming-your-supervisors-advisor` |
| 10 | `SL-IDK-010` | Influencing with Data & KPIs | `influencing-with-data-and-kpis` |

The AI and E-Payment rows are updates to their stable SKUs, not duplicate
products. Obsolete names remain only inside legacy-source metadata where needed
for traceability; they are not active customer-facing names.

## Package relationship

`SL-PACKAGE-ALL-10` is the active `Learning Course Package Deal`, slug
`learning-course-package-deal`, current price USD 150.00, original price USD
190.00. It has exactly ten unique `bundle_item` relations, one to every course
above. Checkout derives package enrollment targets from these relations.

## Learning content and repeatability

The separate idempotent command
`ainchors:populate-course-learning-content` maintains one `course_contents`
record per course, normalized private MP4/PPTX paths, and unique PPTX-grounded
01/02/03 lesson data. See `docs/COURSE-ASSETS.md` for the source manifest.

Run both commands from the project root:

```powershell
& 'C:\Users\Acer\.config\herd\bin\php84\php.exe' artisan ainchors:populate-legacy-course-catalogue
& 'C:\Users\Acer\.config\herd\bin\php84\php.exe' artisan ainchors:populate-course-learning-content
```

Both commands upsert by stable SKU/product relation and do not delete or alter
the three unrelated service products.
