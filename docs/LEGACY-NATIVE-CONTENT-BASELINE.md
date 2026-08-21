# Legacy-to-native content baseline

## Migration contract

Each page below must be recreated as a native Laravel Blade module before its
legacy source is removed. The native module must preserve the legacy page's:

- complete wording, headings, testimonials, company and trainer information;
- images, logos, icons, CTA labels and CTA destinations;
- section order, visual design, colours, typography, spacing and responsive
  behaviour;
- public URL and same-tab navigation behaviour.

The shared current site header and footer are outside this migration scope and
must not be changed.

The SHA-256 value captures the exact current source used for migration. It is
an integrity baseline only: no legacy file is deleted in Phase 1.

| Module | Public route | Current source | Native Blade destination | Bytes | SHA-256 |
| --- | --- | --- | --- | ---: | --- |
| Company / About | `/about-us` | `resources/legacy/about-us/index.html` | `resources/views/modules/company/about/index.blade.php` | 508,581 | `dd66f0ca7fe2ae5b80da0c9357871a7c61d77ed71c119f06b1fc5bcf1d9c87e0` |
| Training / Trainers | `/trainers-profile` | `resources/legacy/trainers-profile/index.html` | `resources/views/modules/training/trainers/index.blade.php` | 389,801 | `24d4204fb210ad4cab8e070a7586409025593ffdacbb8de5cf580d3cc3af321c` |
| Training / Testimonials | `/testimonials` | `resources/legacy/testimonials/index.html` | `resources/views/modules/training/testimonials/index.blade.php` | 269,681 | `d15d94b256aa375208d46ac82b716a89dce42adca5930c9daa388924cc270ce4` |
| Training / Success Story | `/success-story-of-angie` | `resources/legacy/success-story-of-angie/index.html` | `resources/views/modules/training/success-stories/angie.blade.php` | 292,108 | `50047ebbb1f907121d9da4c3a5f64daf2fee46b1ebae7c2782e3b644b7734df8` |
| Consulting / Main | `/consulting-main` | `resources/legacy/consulting-main/index.html` | `resources/views/modules/consulting/main/index.blade.php` | 294,511 | `6e7405fba7227c564a162c5360addb55b181209a76795e58ef6b69d044d90535` |
| Consulting / Government | `/consulting-gov` | `resources/legacy/consulting-gov/index.html` | `resources/views/modules/consulting/government/index.blade.php` | 298,764 | `8cec245fa44b77eacedeaccddadbbdf0accabf6d1d0fc1386456f095d89da8f6` |
| Consulting / Private | `/consulting-private` | `resources/legacy/consulting-private/index.html` | `resources/views/modules/consulting/private-sector/index.blade.php` | 301,218 | `abaca035a183845b5f05a2a494952a2162d1365aadcca0dc9c26ec382866b90c` |
| Support / FAQ | `/faqs` | `resources/legacy/faqs/index.html` | `resources/views/modules/support/faqs/index.blade.php` | 293,834 | `52a3ae70091e3529f3f2d81abeb662067f1a5295853093a686a11eb61b1253aa` |
| Company / Careers | `/join-us` | `resources/legacy/join-us/index.html` | `resources/views/modules/company/careers/index.blade.php` | 298,561 | `49027a54538b896d020365bacd3ad5815840c86aec2930466e0f597086b5b63c` |
| Support / Contact | `/contact-us` | `resources/legacy/contact-us/index.html` | `resources/views/modules/support/contact/index.blade.php` | 365,744 | `274c2c56f2163822d086f1a9716af128b041a79936eb91152d01798f2dc3bebd` |
| Commerce / Events | `/events` | `resources/legacy/events/index.html` | `resources/views/modules/commerce/events/index.blade.php` | 458,090 | `6691e5a5b041b33db6ede5a69cc08c1e28d8465e581d36bb978e586e070e91f4` |
| Legal / Terms | `/terms--conditions` | `resources/legacy/terms--conditions/index.html` | `resources/views/modules/legal/terms/index.blade.php` | 303,802 | `8913cb0a6bea977b54ddd9153269302e51e8e6f569ba576c1e5fc08dddba4593` |
| Legal / Privacy | `/privacy--policy` | `resources/legacy/privacy--policy/index.html` | `resources/views/modules/legal/privacy/index.blade.php` | 305,929 | `32ca5ccb97144bda66b12715e3941bd16f90f0e35847472aad1c7d1fc3713d67` |

## Required evidence before a legacy source may be removed

1. Native Blade page exists at the listed destination and owns the unchanged
   public route.
2. Text, images, CTA targets and visible section order have been compared to
   the baseline source.
3. Desktop and mobile behaviour has been checked in the local browser.
4. Route, form and link tests pass.
5. The user approves removal of that source after visual review.

`resources/legacy/` and `LegacyPageController` remain in place until all
thirteen entries have passed these gates.
