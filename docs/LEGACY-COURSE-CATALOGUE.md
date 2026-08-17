# Legacy course catalogue import (Phase 1C)

This records the source mapping used by `ainchors:populate-legacy-course-catalogue`.
The legacy website and `C:\xampp\htdocs\ainchors-website` remain the content source of truth.

## Canonical course products

The public legacy `/courses` titles are the canonical `products.name` values.
All ten products use `type=course` and `billing_type=one_time`.

| Order | SKU | Canonical name | Legacy individual page | Checkout / price source | Status |
| --- | --- | --- | --- | --- | --- |
| 1 | `SL-AI-001` | Artificial Intelligence (AI) | `/individual-aiprompt` | `/check-out-pagecourse-individualaiprompt`; USD 50 struck through, USD 19 sale | active |
| 2 | `SL-DMAI-002` | Digital Marketing using AI | `/digitalmarketing` | `/check-out-pagecourse-individualdigital`; USD 50 struck through, USD 19 sale | active |
| 3 | `SL-DA-003` | Data Analytics | `/package-page-dataanalytics` | `/check-out-pagecourse-individualdataanalytics`; USD 50 struck through, USD 19 sale | active |
| 4 | `SL-SQL-004` | SQL for Data Analytics | `/package-page-page-303665` | `/check-out-pagecourse-individual`; USD 50 struck through, USD 19 sale | active |
| 5 | `SL-FLM-005` | Financial Literacy Mastery | `/financialliteracymastery` | `/check-out-pagefinancial`; USD 50 struck through, USD 19 sale | active |
| 6 | `SL-EP-006` | E-Payment Systems | `/individualepayment` | `/check-out-pageepayment`; USD 50 struck through, USD 19 sale | active |
| 7 | `SL-FF-007` | Fintech Fundamentals | `/individualfintech` | `/checkoutfintech`; USD 50 struck through, USD 19 sale | active |
| 8 | `SL-CBDC-008` | Central Bank Digital Currency (CBDC) | `/individualcbdc` | `/cbdccheckoutpage`; USD 19 from landing-page display | active |
| 9 | `SL-BSA-009` | Becoming Your Supervisor's Advisor | no individual course page found | `/product-details/product/6a55cb02824b1a2d6648bbf1`; `$19.00`, ISO currency not explicit | draft |
| 10 | `SL-IDK-010` | Influencing with Data & KPIs | no individual course page found | `/product-details/product/6a55cb4d03821e4f56e9e11f`; `$19.00`, ISO currency not explicit | draft |

The first eight courses are active at `USD 19.00`; their original `USD 50.00` is retained in `metadata.pricing`. The two career courses retain the legacy store amount as `USD 19.00` provisionally, in draft state, with the currency and checkout uncertainty recorded in metadata.

## Legacy variants and assets

Each course record retains the legacy catalogue title, individual/package-page title variant, checkout URL, catalogue image path, trainer (`Angie.F`), and legacy name variants in `products.metadata`. The import intentionally does not rewrite legacy copy. It also records the original statement that videos can be accessed anytime with unlimited replays.

No protected course video URL or PPT/slide file was located during the legacy HTML extraction. Therefore this import creates **no** `course_contents` rows; those fields remain pending real learning files.

## Package and product relations

One `course_package` product is imported:

- SKU: `SL-PACKAGE-ALL-10`
- Name: `Learning Course Package Deal`
- Price: `USD 150.00`, active, one-time
- Original price: `USD 190.00` in metadata
- Legacy package URLs: `/package-page-4066`, `/package-page-6341`, `/package-page-12`, `/package-page`, `/package-pagefi`, `/package-page-6219`, `/package-page-9865`, `/package-page-4157`

Exactly ten `bundle_item` relations are created in legacy package order:

1. SQL for Data Analytics
2. Data Analytics
3. Artificial Intelligence (AI)
4. Digital Marketing using AI
5. Financial Literacy Mastery
6. E-Payment Systems
7. Fintech Fundamentals
8. Central Bank Digital Currency (CBDC)
9. Becoming Your Supervisor's Advisor
10. Influencing with Data & KPIs

`/package-page-9865` visibly presents the package at USD 150, but its GET NOW link leads to `/check-out-pagecoursefintech` (a USD 19 Fintech checkout). This is preserved as `metadata.conflicts` / `metadata.fintech_checkout_conflict`; it is not used to create an incorrect product relation.

## Safe repeatability

Run the catalogue import from the Laravel project root:

```powershell
& 'C:\Users\Acer\.config\herd\bin\php84\php.exe' artisan ainchors:populate-legacy-course-catalogue
```

The command finds each product by stable SKU and each relation by its parent, child, and `bundle_item` type. Re-running it updates only changed source-mapped values and does not duplicate records or relations. It does not delete or alter the existing service products.
