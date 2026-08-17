<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductRelation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateLegacyCourseCatalogue extends Command
{
    protected $signature = 'ainchors:populate-legacy-course-catalogue';

    protected $description = 'Idempotently imports the verified legacy self-learning catalogue.';

    public function handle(): int
    {
        $courses = $this->courses();
        $summary = ['created' => [], 'updated' => [], 'unchanged' => []];

        DB::transaction(function () use ($courses, &$summary): void {
            $courseIds = [];

            foreach ($courses as $course) {
                [$product, $action] = $this->persistProduct($course);
                $courseIds[$course['sku']] = $product->id;
                $summary[$action][] = $product->sku;
            }

            [$package, $packageAction] = $this->persistProduct($this->package());
            $summary[$packageAction][] = $package->sku;

            foreach ($this->bundleCourseSkus() as $index => $courseSku) {
                ProductRelation::query()->updateOrCreate(
                    [
                        'parent_product_id' => $package->id,
                        'child_product_id' => $courseIds[$courseSku],
                        'relation_type' => 'bundle_item',
                    ],
                    ['sort_order' => $index + 1],
                );
            }
        });

        $this->info('Legacy catalogue population complete.');
        $this->line('Created: '.implode(', ', $summary['created']) ?: 'Created: none');
        $this->line('Updated: '.implode(', ', $summary['updated']) ?: 'Updated: none');
        $this->line('Unchanged: '.implode(', ', $summary['unchanged']) ?: 'Unchanged: none');
        $this->line('Courses: '.Product::query()->where('type', 'course')->count());
        $this->line('Package: '.Product::query()->where('sku', 'SL-PACKAGE-ALL-10')->count());
        $this->line('Bundle relations: '.ProductRelation::query()
            ->where('parent_product_id', Product::query()->where('sku', 'SL-PACKAGE-ALL-10')->value('id'))
            ->where('relation_type', 'bundle_item')
            ->count());

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $attributes @return array{Product, string} */
    private function persistProduct(array $attributes): array
    {
        $product = Product::query()->firstOrNew(['sku' => $attributes['sku']]);
        $created = ! $product->exists;
        $product->fill($attributes);
        $changed = $created || $product->isDirty();
        $product->save();

        return [$product, $created ? 'created' : ($changed ? 'updated' : 'unchanged')];
    }

    /** @return list<array<string, mixed>> */
    private function courses(): array
    {
        return [
            [
                'sku' => 'SL-AI-001', 'type' => 'course', 'name' => 'Artificial Intelligence (AI)',
                'slug' => 'artificial-intelligence-ai',
                'short_description' => 'Master machine learning, natural language processing, and generative AI to build intelligent systems',
                'description' => 'Master machine learning, natural language processing, and generative AI to build intelligent systems',
                'image' => 'assets/site/6971824d15885e0e516659c2.webp', 'price' => 19, 'currency' => 'USD',
                'billing_type' => 'one_time', 'status' => 'active',
                'metadata' => $this->legacyMetadata(
                    catalogueTitle: 'Artificial Intelligence (AI)',
                    individualTitle: 'AI Prompt Engineering 101',
                    packageTitle: 'Artificial Intelligence (AI) Prompt Engineering 101',
                    packageUrl: '/package-page-4066', individualUrl: '/individual-aiprompt',
                    checkoutUrl: '/check-out-pagecourse-individualaiprompt',
                    variants: ['AI Prompt Engineering 101', 'Artificial Intelligence (AI) Prompt Engineering 101', 'AI Prompt Enginnering 101 pre record @ 19'],
                ),
            ],
            [
                'sku' => 'SL-DMAI-002', 'type' => 'course', 'name' => 'Digital Marketing using AI',
                'slug' => 'digital-marketing-using-ai',
                'short_description' => 'Leverage AI-powered tools for personalized campaigns, automation, and customer engagement optimization',
                'description' => 'Leverage AI-powered tools for personalized campaigns, automation, and customer engagement optimization',
                'image' => 'assets/site/699d61a92837e8fa8c17b91f.jpg', 'price' => 19, 'currency' => 'USD',
                'billing_type' => 'one_time', 'status' => 'active',
                'metadata' => $this->legacyMetadata(
                    catalogueTitle: 'Digital Marketing using AI', individualTitle: 'The overview of Digital Marketing using AI',
                    packageTitle: 'The Overview of Digital Marketing Using AI', packageUrl: '/package-page-6341',
                    individualUrl: '/digitalmarketing', checkoutUrl: '/check-out-pagecourse-individualdigital',
                    variants: ['The overview of Digital Marketing using AI', 'The Overview of Digital Marketing Using AI', 'Digital Marketing using AI @ 19'],
                ),
            ],
            [
                'sku' => 'SL-DA-003', 'type' => 'course', 'name' => 'Data Analytics', 'slug' => 'data-analytics',
                'short_description' => 'Transform raw data into strategic insights through visualization, statistical analysis, and business intelligence',
                'description' => 'Transform raw data into strategic insights through visualization, statistical analysis, and business intelligence',
                'image' => 'assets/site/699c338c590acb9104afa2f5.png', 'price' => 19, 'currency' => 'USD',
                'billing_type' => 'one_time', 'status' => 'active',
                'metadata' => $this->legacyMetadata(
                    catalogueTitle: 'Data Analytics', individualTitle: 'The overview of data analytics',
                    packageTitle: 'The Overview of Data Analytics', packageUrl: '/package-page-12',
                    individualUrl: '/package-page-dataanalytics', checkoutUrl: '/check-out-pagecourse-individualdataanalytics',
                    variants: ['The overview of data analytics', 'The Overview of Data Analytics', 'data analytics @ 19'],
                ),
            ],
            [
                'sku' => 'SL-SQL-004', 'type' => 'course', 'name' => 'SQL for Data Analytics', 'slug' => 'sql-for-data-analytics',
                'short_description' => 'Learn to query databases, extract insights, and analyze data using structured query language',
                'description' => 'Learn to query databases, extract insights, and analyze data using structured query language',
                'image' => 'assets/site/6971830d7079aada0632836d.webp', 'price' => 19, 'currency' => 'USD',
                'billing_type' => 'one_time', 'status' => 'active',
                'metadata' => $this->legacyMetadata(
                    catalogueTitle: 'SQL for Data Analytics', individualTitle: 'The overview of SQL for data analytics',
                    packageTitle: 'The Overview of SQL for data analytics', packageUrl: '/package-page',
                    individualUrl: '/package-page-page-303665', checkoutUrl: '/check-out-pagecourse-individual',
                    variants: ['The overview of SQL for data analytics', 'The Overview of SQL for data analytics', 'SQL for Data Analytics @ 19'],
                ),
            ],
            [
                'sku' => 'SL-FLM-005', 'type' => 'course', 'name' => 'Financial Literacy Mastery', 'slug' => 'financial-literacy-mastery',
                'short_description' => 'Build wealth through budgeting, investing, credit management, and smart financial decision-making',
                'description' => 'Build wealth through budgeting, investing, credit management, and smart financial decision-making',
                'image' => 'assets/site/700f1cbb-ae75-42c0-bbb7-d4c22a98074d.png', 'price' => 19, 'currency' => 'USD',
                'billing_type' => 'one_time', 'status' => 'active',
                'metadata' => $this->legacyMetadata(
                    catalogueTitle: 'Financial Literacy Mastery', individualTitle: 'Financial Literacy Mastery',
                    packageTitle: 'Financial Literacy Mastery', packageUrl: '/package-pagefi',
                    individualUrl: '/financialliteracymastery', checkoutUrl: '/check-out-pagefinancial',
                    variants: ['Financial Literacy Mastery @ 19'],
                ),
            ],
            [
                'sku' => 'SL-EP-006', 'type' => 'course', 'name' => 'E-Payment Systems', 'slug' => 'e-payment-systems',
                'short_description' => 'Understand digital wallets, payment gateways, transaction security, and the cashless economy',
                'description' => 'Understand digital wallets, payment gateways, transaction security, and the cashless economy',
                'image' => 'assets/site/699c536a1001a5ff39d32f70.jpg', 'price' => 19, 'currency' => 'USD',
                'billing_type' => 'one_time', 'status' => 'active',
                'metadata' => $this->legacyMetadata(
                    catalogueTitle: 'E-Payment Systems', individualTitle: 'E-Payment Systems Mastery',
                    packageTitle: 'E-Payment Systems Mastery', packageUrl: '/package-page-6219',
                    individualUrl: '/individualepayment', checkoutUrl: '/check-out-pageepayment',
                    variants: ['E-Payment Systems Mastery', 'E-Payment Systems Mastery @ 19'],
                ),
            ],
            [
                'sku' => 'SL-FF-007', 'type' => 'course', 'name' => 'Fintech Fundamentals', 'slug' => 'fintech-fundamentals',
                'short_description' => 'Explore digital banking, lending platforms, robo-advisors, and the future of financial services',
                'description' => 'Explore digital banking, lending platforms, robo-advisors, and the future of financial services',
                'image' => 'assets/site/d11891b9-544e-4896-85c5-e8140dd77653.png', 'price' => 19, 'currency' => 'USD',
                'billing_type' => 'one_time', 'status' => 'active',
                'metadata' => array_merge($this->legacyMetadata(
                    catalogueTitle: 'Fintech Fundamentals', individualTitle: 'Fintech Fundamentals Mastery',
                    packageTitle: 'Fintech Fundamentals Mastery', packageUrl: '/package-page-9865',
                    individualUrl: '/individualfintech', checkoutUrl: '/checkoutfintech',
                    variants: ['Fintech Fundamentals Mastery', 'Fintech Fundamentals Mastery @ 19'],
                ), ['conflicts' => [[
                    'package_page' => '/package-page-9865',
                    'displayed_package_price' => ['currency' => 'USD', 'list' => 190, 'sale' => 150],
                    'incorrect_get_now_url' => '/check-out-pagecoursefintech',
                    'incorrect_checkout_product' => 'Fintech Fundamentals Mastery @ 19',
                ]]]),
            ],
            [
                'sku' => 'SL-CBDC-008', 'type' => 'course', 'name' => 'Central Bank Digital Currency (CBDC)',
                'slug' => 'central-bank-digital-currency-cbdc',
                'short_description' => 'Discover how government-backed digital currencies are reshaping global monetary systems',
                'description' => 'Discover how government-backed digital currencies are reshaping global monetary systems',
                'image' => 'assets/site/2c06a3e2-fc2f-4811-a248-4bfe5a57eb3b.png', 'price' => 19, 'currency' => 'USD',
                'billing_type' => 'one_time', 'status' => 'active',
                'metadata' => array_merge($this->legacyMetadata(
                    catalogueTitle: 'Central Bank Digital Currency (CBDC)', individualTitle: 'Central Bank Digital Currency Mastery',
                    packageTitle: 'Central Bank Digital Currency (CBDC) Mastery', packageUrl: '/package-page-4157',
                    individualUrl: '/individualcbdc', checkoutUrl: '/cbdccheckoutpage',
                    variants: ['Central Bank Digital Currency Mastery', 'Central Bank Digital Currency (CBDC) Mastery'],
                ), ['checkout_verification' => 'No recoverable product amount metadata was found in /cbdccheckoutpage. USD 19 is sourced from the individual landing page display.']),
            ],
            [
                'sku' => 'SL-BSA-009', 'type' => 'course', 'name' => "Becoming Your Supervisor's Advisor",
                'slug' => 'becoming-your-supervisors-advisor',
                'short_description' => 'Position yourself as a trusted strategic partner and advance your influence within the organization',
                'description' => 'Position yourself as a trusted strategic partner and advance your influence within the organization',
                'image' => 'assets/site/eaab034e-af0a-4ed4-8d5b-e60b051acf9d.png', 'price' => 19, 'currency' => 'USD',
                'billing_type' => 'one_time', 'status' => 'draft',
                'metadata' => $this->careerMetadata("Becoming Your Supervisor's Advisor", 'Becoming Your Supervisor\'s Advisor course', '/product-details/product/6a55cb02824b1a2d6648bbf1'),
            ],
            [
                'sku' => 'SL-IDK-010', 'type' => 'course', 'name' => 'Influencing with Data & KPIs',
                'slug' => 'influencing-with-data-kpis',
                'short_description' => 'Master data storytelling and persuasive analytics to drive business decisions and gain stakeholder buy-in',
                'description' => 'Master data storytelling and persuasive analytics to drive business decisions and gain stakeholder buy-in',
                'image' => 'assets/site/22df6cf5-88df-410c-bbab-70e990c409d6.png', 'price' => 19, 'currency' => 'USD',
                'billing_type' => 'one_time', 'status' => 'draft',
                'metadata' => $this->careerMetadata('Influencing with Data & KPIs', 'Influencing with Data & KPIs course', '/product-details/product/6a55cb4d03821e4f56e9e11f'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function package(): array
    {
        return [
            'sku' => 'SL-PACKAGE-ALL-10', 'type' => 'course_package', 'name' => 'Learning Course Package Deal',
            'slug' => 'learning-course-package-deal',
            'short_description' => 'Access to all 10 video courses', 'description' => 'Can access the videos anytime, anywhere. Unlimited replays. Access to all 10 video courses.',
            'image' => null, 'price' => 150, 'currency' => 'USD', 'billing_type' => 'one_time', 'status' => 'active',
            'metadata' => [
                'source' => 'legacy_html',
                'original_price' => 190,
                'current_sale_price' => 150,
                'currency' => 'USD',
                'price_wording' => 'Discount only for limited time',
                'legacy_package_urls' => ['/package-page-4066', '/package-page-6341', '/package-page-12', '/package-page', '/package-pagefi', '/package-page-6219', '/package-page-9865', '/package-page-4157'],
                'verified_package_checkout_urls' => ['/check-out-page-page', '/check-out-pagecourse-00', '/check-out-pagecourse23', '/check-out-pagecoursedeale', '/check-out-page2'],
                'included_course_order' => ['SL-SQL-004', 'SL-DA-003', 'SL-AI-001', 'SL-DMAI-002', 'SL-FLM-005', 'SL-EP-006', 'SL-FF-007', 'SL-CBDC-008', 'SL-BSA-009', 'SL-IDK-010'],
                'legacy_name_variants' => ['Learning Course Package Deal', 'Deal package @ 150'],
                'fintech_checkout_conflict' => '/package-page-9865 displays this package but links to /check-out-pagecoursefintech, a USD 19 Fintech checkout.',
                'course_contents_status' => 'pending_real_learning_files',
            ],
        ];
    }

    /** @return list<string> */
    private function bundleCourseSkus(): array
    {
        return [
            'SL-SQL-004', 'SL-DA-003', 'SL-AI-001', 'SL-DMAI-002', 'SL-FLM-005',
            'SL-EP-006', 'SL-FF-007', 'SL-CBDC-008', 'SL-BSA-009', 'SL-IDK-010',
        ];
    }

    /** @return array<string, mixed> */
    private function legacyMetadata(string $catalogueTitle, string $individualTitle, string $packageTitle, string $packageUrl, string $individualUrl, string $checkoutUrl, array $variants): array
    {
        return [
            'source' => 'legacy_html', 'trainer' => 'Angie.F',
            'catalogue' => ['title' => $catalogueTitle, 'url' => '/courses'],
            'individual_page' => ['title' => $individualTitle, 'url' => $individualUrl],
            'package_page' => ['title' => $packageTitle, 'url' => $packageUrl],
            'checkout' => ['url' => $checkoutUrl, 'verified_current_price' => 19, 'currency' => 'USD'],
            'pricing' => ['original_price' => 50, 'current_sale_price' => 19, 'currency' => 'USD', 'wording' => 'Only for limited time'],
            'name_variants' => $variants,
            'video_statement' => 'Access to course video. Can access the videos anytime, anywhere. Unlimited replays.',
            'protected_video_url' => null, 'slide_url' => null,
            'course_contents_status' => 'pending_real_learning_files',
        ];
    }

    /** @return array<string, mixed> */
    private function careerMetadata(string $catalogueTitle, string $packageTitle, string $storeProductUrl): array
    {
        return [
            'source' => 'legacy_html', 'trainer' => 'Angie.F',
            'catalogue' => ['title' => $catalogueTitle, 'url' => '/courses'],
            'package_page' => ['title' => $packageTitle, 'url' => null],
            'store_listing' => ['url' => $storeProductUrl, 'displayed_price' => '$19.00'],
            'pricing' => ['provisional_price' => 19, 'provisional_currency' => 'USD'],
            'currency_note' => 'The legacy store listing shows $19.00 without an explicit ISO currency. USD is provisional because the surrounding self-learning catalogue is USD-based.',
            'checkout' => ['url' => null, 'status' => 'unconfirmed'],
            'name_variants' => [$packageTitle],
            'protected_video_url' => null, 'slide_url' => null,
            'course_contents_status' => 'pending_real_learning_files',
        ];
    }
}
