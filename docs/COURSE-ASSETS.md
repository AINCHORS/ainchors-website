# Course asset manifest

The approved Google Drive folder is a read-only source. The application never
serves Drive URLs directly. Each verified PPTX and MP4 is normalized under
Laravel's private `local` disk (`storage/app/private`) and is intentionally
excluded from Git by `/storage/app/private/courses/**`.

Verified on 2026-08-18: all 10 PPTX files and all 10 MP4 files are available.
The Drive folder named `not relavant` was ignored.

| Course | Slug | Source PPTX (Drive file ID) | Source MP4 (Drive file ID) | Local private paths | Status |
| --- | --- | --- | --- | --- | --- |
| AI Prompt Engineering 101 | `ai-prompt-engineering-101` | `AI Prompt Engineering Course Slide.pptx.pptx` (`1mCEAEBGj3DHz5J3cjelTPkcMSJ0ZKZdK`) | `AI Prompt Engineering Course Slide.pptx.mp4` (`1k0lPez4BOfIvcAfrDdbM_RfnQ7EVEauL`) | `courses/ai-prompt-engineering-101/slides/course-slides.pptx`; `courses/ai-prompt-engineering-101/video/course.mp4` | Verified |
| Digital Marketing using AI | `digital-marketing-using-ai` | `Digital Marketing Using AI - Courses Slide.pptx.pptx` (`1JrNyLC0h5SrEG1mpmSEh7_r3uOUGDgEM`) | `Digital Marketing Using AI - Courses Slide.pptx.mp4` (`1SkdXreRcPXNFyQu3JaLCVprS3ba8yn8G`) | `courses/digital-marketing-using-ai/slides/course-slides.pptx`; `courses/digital-marketing-using-ai/video/course.mp4` | Verified |
| Data Analytics | `data-analytics` | `Data Analytics Course Slide.pptx.pptx` (`1Flv0JyhaTJZnX3n8p3KMgVZuegAmWt2g`) | `Data Analytics Course Slide.pptx.mp4` (`1wj92w-20hukJ4XAXwkDEVXUeX67DOeF3`) | `courses/data-analytics/slides/course-slides.pptx`; `courses/data-analytics/video/course.mp4` | Verified |
| SQL for Data Analytics | `sql-for-data-analytics` | `SQL For Data Analytics Course Slides.pptx.pptx` (`1jlTJCwied-XJbiBQxa1yBbO_zZVmRO6D`) | `SQL For Data Analytics Course Slides.pptx.mp4` (`1S_AE2jPxj74qaNN1kPII9Md_UDGxS1D2`) | `courses/sql-for-data-analytics/slides/course-slides.pptx`; `courses/sql-for-data-analytics/video/course.mp4` | Verified |
| Financial Literacy Mastery | `financial-literacy-mastery` | `Financial Literacy Mastery Course Slide.pptx.pptx` (`1uv0-HECdLBgK_8t0WdncW0EzW5ybSjuB`) | `Financial Literacy Mastery Course Slide.pptx.mp4` (`1F2aIqZ5ntcxGf2mD8l698kaJuM6mjf94`) | `courses/financial-literacy-mastery/slides/course-slides.pptx`; `courses/financial-literacy-mastery/video/course.mp4` | Verified |
| E-Payment Fundamentals | `e-payment-fundamentals` | `E-Payment Fundamentals Course Slides.pptx.pptx` (`1tQsegZm-9BcUiL1fTpiauPhEZOKNJIyh`) | `E-Payment Fundamentals Course Slides.pptx.mp4` (`1ucI2hicXkQ77gOAKBfB4_aMRaRxvRIiR`) | `courses/e-payment-fundamentals/slides/course-slides.pptx`; `courses/e-payment-fundamentals/video/course.mp4` | Verified |
| Fintech Fundamentals | `fintech-fundamentals` | `Fintech Fundamentals Course Slides.pptx.pptx` (`1KDerkoYJHfxcam38TH8ZWSR3nhtrSz8s`) | `Fintech Fundamentals Course Slides.pptx.mp4` (`1D32lWeD9Cu2IvLzp5fjvsLL10zhGkmQh`) | `courses/fintech-fundamentals/slides/course-slides.pptx`; `courses/fintech-fundamentals/video/course.mp4` | Verified |
| Central Bank Digital Currency (CBDC) | `central-bank-digital-currency-cbdc` | `Central Bank Digital Currency (CBDC) Course Slides.pptx.pptx` (`12wRcTgAxWIMBrvorSash2ZbERA_K8_qU`) | `Central Bank Digital Currency (CBDC) Course Slides.pptx.mp4` (`18Mw2otziq-DduCF2iGVbLlxXWLxe0Lt_`) | `courses/central-bank-digital-currency-cbdc/slides/course-slides.pptx`; `courses/central-bank-digital-currency-cbdc/video/course.mp4` | Verified |
| Becoming Your Supervisor's Advisor | `becoming-your-supervisors-advisor` | `Becoming Your Supervisor's Advisor Course Slides.pptx.pptx` (`174fse6SD_krX-oa1m_EeWTs12Sbn9n90`) | `Becoming Your Supervisor's Advisor Course Slides.pptx.mp4` (`195tOAmuJX-Kj39pt1sC5p6Wj2w7nXt6w`) | `courses/becoming-your-supervisors-advisor/slides/course-slides.pptx`; `courses/becoming-your-supervisors-advisor/video/course.mp4` | Verified |
| Influencing with Data & KPIs | `influencing-with-data-and-kpis` | `Influencing With Data And KPIs Course Slides.pptx.pptx` (`1OmpUtrJsAZPw9GuoTga1aUOW_p0OlipS`) | `Influencing With Data And KPIs Course Slides.pptx.mp4` (`1_YsxzA3Y2vM7f60L51PmKyMiqH_i4ocw`) | `courses/influencing-with-data-and-kpis/slides/course-slides.pptx`; `courses/influencing-with-data-and-kpis/video/course.mp4` | Verified |

## Refreshing an asset

1. Download only the approved replacement from the read-only Drive folder.
2. Verify its file type and course mapping.
3. Replace the normalized private file for that slug (`video/course.mp4` or
   `slides/course-slides.pptx`). Do not place it under `public/`.
4. Run `php artisan ainchors:populate-course-learning-content` to reconcile the
   private relative paths and content records.
5. Test the protected video and slide endpoints while signed in as an enrolled
   user. Also confirm an unenrolled user cannot access them.

No Drive credentials, sharing tokens, course binaries, or customer data belong
in Git.
