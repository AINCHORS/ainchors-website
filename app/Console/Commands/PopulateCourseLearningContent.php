<?php

namespace App\Console\Commands;

use App\Models\CourseContent;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PopulateCourseLearningContent extends Command
{
    protected $signature = 'ainchors:populate-course-learning-content';

    protected $description = 'Idempotently maps private assets and PPTX-grounded learning copy to the ten canonical courses.';

    public function handle(): int
    {
        $created = 0;
        $updated = 0;
        $unchanged = 0;

        DB::transaction(function () use (&$created, &$updated, &$unchanged): void {
            foreach ($this->courses() as $sku => $definition) {
                $product = Product::query()->where('sku', $sku)->where('type', 'course')->first();
                if (! $product) {
                    throw new RuntimeException("Canonical course {$sku} is missing. Run ainchors:populate-legacy-course-catalogue first.");
                }

                $content = CourseContent::query()->firstOrNew(['product_id' => $product->id]);
                $wasCreated = ! $content->exists;
                $content->fill([
                    'video_title' => $product->name.' — Full Course',
                    'video_provider' => 'private',
                    'video_url' => 'courses/'.$product->slug.'/video/course.mp4',
                    'slide_name' => $product->name.' Course Slides',
                    'slide_url' => 'courses/'.$product->slug.'/slides/course-slides.pptx',
                    'lesson_content' => $definition,
                ]);

                $changed = $wasCreated || $content->isDirty();
                $content->save();
                $wasCreated ? $created++ : ($changed ? $updated++ : $unchanged++);
            }
        });

        $this->info("Course learning content complete: {$created} created, {$updated} updated, {$unchanged} unchanged.");

        return self::SUCCESS;
    }

    /** @return array<string, array<string, mixed>> */
    private function courses(): array
    {
        return [
            'SL-AI-001' => $this->lesson(
                purpose: 'Learn how clear instructions improve AI outputs and use the Role, Task, Context, Format (RTCF) framework to create reusable prompts for practical work.',
                objectives: ['Explain what a prompt is and why prompt clarity matters.', 'Build prompts with Role, Task, Context, and Format.', 'Improve vague prompts using audience, goals, constraints, and output structure.', 'Test, refine, and save effective prompts as reusable workflows.'],
                topics: ['Prompt basics and why prompt engineering matters', 'The RTCF framework and each of its four parts', 'Weak versus strong prompts', 'Marketing, training, and operations use cases', 'Prompt quality checklist, common mistakes, and improvement workflow'],
                recap: ['A useful prompt clearly defines the role, task, context, and expected format.', 'Prompting is iterative: draft, test, refine, and reuse.', 'Real work tasks make the best practice material.'],
                next: ['Choose one vague prompt from your work and rebuild it with RTCF.', 'Compare the old and new outputs against your intended goal.', 'Save the refined version as a reusable template.'],
            ),
            'SL-DMAI-002' => $this->lesson(
                purpose: 'Explore how AI supports digital marketing across content, personalization, SEO, advertising, analytics, and campaign planning while keeping ethics and data responsibilities visible.',
                objectives: ['Recognize machine learning, natural language processing, and generative AI marketing uses.', 'Connect AI tools to text, image, video, and campaign workflows.', 'Use segmentation and personalization concepts to improve relevance.', 'Plan a small AI marketing experiment and measure its result.'],
                topics: ['AI basics for marketers', 'AI-assisted text and visual content creation', 'RFM segmentation, hyper-personalization, and journey mapping', 'SEO, bidding, predictive analytics, and marketing tool categories', 'Ethics, bias, privacy, skills balance, and an action plan'],
                recap: ['AI can accelerate content and analysis, but marketing judgment remains important.', 'Personalization should be grounded in customer behavior and responsible data use.', 'A useful implementation starts with an audit, a controlled test, and measurement.'],
                next: ['Audit one current campaign for a suitable AI-assisted task.', 'Test one content variation and define the engagement metric before publishing.', 'Review the outcome and adjust the next campaign based on evidence.'],
            ),
            'SL-DA-003' => $this->lesson(
                purpose: 'Build a practical overview of the data analytics lifecycle, from responsible collection and cleaning to tools, models, use cases, evaluation, and communication.',
                objectives: ['Identify collection, privacy, completeness, bias, and duplication issues.', 'Apply common approaches to missing values, outliers, and normalization.', 'Recognize the roles of Python, SQL, visualization, machine learning, and AI tools.', 'Relate analytics practices to retail, finance, healthcare, and ethical governance.'],
                topics: ['Data collection, privacy compliance, metadata, and quality', 'Missing data, outliers, and normalization techniques', 'AutoML, NLP, computer vision, Python libraries, and visualization', 'SQL retrieval, joins, grouping, and industry use cases', 'Data ethics, bias, compliance, model metrics, and portfolio development'],
                recap: ['Reliable insights depend on reliable and responsibly collected data.', 'Analytics combines preparation, tools, models, evaluation, and communication.', 'Continuous practice and ethical governance are part of the analytics workflow.'],
                next: ['Select a small open dataset and document its quality issues.', 'Clean and visualize one meaningful pattern before attempting prediction.', 'Record the method, limitations, and result as a portfolio case.'],
            ),
            'SL-SQL-004' => $this->lesson(
                purpose: 'Learn how SQL supports data analytics through focused retrieval, filtering, aggregation, relational analysis, and efficient query practices.',
                objectives: ['Use SELECT, FROM, and WHERE to retrieve targeted data.', 'Summarize data with GROUP BY and aggregate functions.', 'Sort results and apply subqueries or CASE logic for analysis.', 'Recognize when joins, window functions, indexes, and documentation improve analytical work.'],
                topics: ['SQL role in querying and manipulating structured data', 'SELECT, FROM, WHERE, ORDER BY, and combined filters', 'GROUP BY with SUM, COUNT, and AVG', 'Subqueries, CASE statements, joins, and window functions', 'Query performance, naming conventions, and documentation'],
                recap: ['Start with a clear analytical question and retrieve only the data needed.', 'Aggregation, conditions, and relationships turn records into useful summaries.', 'Readable and efficient queries are easier to validate and reuse.'],
                next: ['Write a SELECT query for one real reporting question.', 'Add filtering, sorting, and an aggregate summary.', 'Document the query purpose and review whether an index or simpler structure would help.'],
            ),
            'SL-FLM-005' => $this->lesson(
                purpose: 'Strengthen personal financial decision-making through budgeting, emergency planning, credit and debt management, investing, protection, and practical action steps.',
                objectives: ['Track income and expenses and apply a structured budget approach.', 'Compare emergency fund, credit, and debt repayment strategies.', 'Explain compound interest and common investment and retirement concepts.', 'Recognize insurance, tax planning, behavioral biases, fraud risks, and digital tools.'],
                topics: ['Budgeting, income tracking, the 50/30/20 rule, and emergency funds', 'Credit reports, snowball and avalanche debt approaches, and good versus bad debt', 'Compound interest, stocks, bonds, mutual funds, and retirement stages', 'Insurance, tax-advantaged planning, behavioral finance, and fraud prevention', 'Financial tools, implementation checklist, takeaways, and reviews'],
                recap: ['Regular tracking makes financial decisions visible and manageable.', 'Debt, investment, protection, and retirement choices work best as a connected plan.', 'Behavior and fraud awareness matter alongside financial calculations.'],
                next: ['Assess current income, spending, debt, savings, and protection.', 'Create a realistic budget and an emergency-fund action.', 'Schedule regular financial reviews and seek professional advice where appropriate.'],
            ),
            'SL-EP-006' => $this->lesson(
                purpose: 'Understand the e-payment ecosystem, its main payment types, security controls, implementation considerations, and emerging payment trends.',
                objectives: ['Distinguish cards, wallets, QR payments, transfers, BNPL, and cryptocurrency use.', 'Explain convenience, cost, and analytics benefits for users and businesses.', 'Recognize encryption, tokenization, MFA, and compliance considerations.', 'Evaluate payment gateways, mobile security, cross-border payments, and future developments.'],
                topics: ['E-payment fundamentals and business benefits', 'Cards, digital wallets, QR payments, FPX, DuitNow, BNPL, and cryptocurrency', 'Encryption, tokenization, multi-factor authentication, and PCI DSS', 'Gateway selection, mobile security, cross-border and recurring payments', 'Embedded finance, biometrics, CBDCs, open banking, IoT, and implementation readiness'],
                recap: ['Payment choices should balance convenience, cost, interoperability, and security.', 'Security and compliance are core design requirements rather than optional features.', 'Businesses should choose solutions that fit their systems and customer experience.'],
                next: ['Map the payment methods used by one business or customer journey.', 'Review security, fees, reliability, integration, and cross-border needs.', 'Identify one secure, user-friendly improvement supported by the course framework.'],
            ),
            'SL-FF-007' => $this->lesson(
                purpose: 'Navigate the fintech ecosystem, technologies, applications, regulatory landscape, cybersecurity needs, and trends shaping digital financial services.',
                objectives: ['Describe the roles of startups, banks, regulators, technology providers, and consumers.', 'Connect mobile, cloud, APIs, AI, and blockchain to fintech services.', 'Recognize applications in payments, digital banking, lending, WealthTech, InsurTech, and cross-border finance.', 'Consider RegTech, cybersecurity, CBDCs, embedded finance, challenges, and opportunities.'],
                topics: ['Fintech definition, ecosystem, and evolution from branches to embedded finance', 'Mobile, cloud, APIs, AI, and blockchain technologies', 'Digital payments, banks, lending, WealthTech, InsurTech, and cross-border services', 'RegTech, Bank Negara oversight, compliance, and cybersecurity', 'Agentic AI, stablecoins, CBDCs, embedded finance, challenges, and opportunities'],
                recap: ['Fintech combines technology, financial services, regulation, and customer needs.', 'Core technologies enable new services but also create security and compliance responsibilities.', 'Sustainable innovation depends on collaboration and customer trust.'],
                next: ['Choose one fintech service and map its ecosystem participants.', 'Identify the technology, compliance, and cybersecurity dependencies.', 'Evaluate how the service improves access, experience, or efficiency.'],
            ),
            'SL-CBDC-008' => $this->lesson(
                purpose: 'Understand CBDCs as central-bank-issued digital money, including their types, designs, benefits, risks, technologies, and global pilots.',
                objectives: ['Distinguish CBDCs from cash, bank deposits, and cryptocurrencies.', 'Compare retail and wholesale CBDCs and account, token, or hybrid designs.', 'Evaluate inclusion, efficiency, programmability, privacy, security, and financial-stability considerations.', 'Review technology choices and lessons from global projects and pilots.'],
                topics: ['Money today, CBDC definition, and comparison with cryptocurrencies', 'Retail and wholesale CBDCs and design choices', 'Financial inclusion, efficiency, innovation, and payment use cases', 'Bank disintermediation, privacy, cybersecurity, surveillance, and policy challenges', 'Global launches and pilots, DLT versus centralized systems, offline use, and scalability'],
                recap: ['A CBDC is government-backed digital fiat currency, not a decentralized cryptocurrency.', 'Design choices shape access, privacy, security, and financial-system effects.', 'Global pilots provide practical lessons while policy and technology continue to evolve.'],
                next: ['Compare one retail and one wholesale CBDC use case.', 'List the inclusion, privacy, stability, and infrastructure trade-offs.', 'Track verified central-bank pilot updates and evaluate them against the course framework.'],
            ),
            'SL-BSA-009' => $this->lesson(
                purpose: 'Develop the mindset and practical skills to become a trusted strategic advisor to a supervisor through insight, trust, upward communication, and solution-focused action.',
                objectives: ['Shift from task execution toward strategic partnership.', 'Understand a supervisor’s goals, stakeholders, pressures, and communication preferences.', 'Build trust and communicate insights with data, storytelling, timing, and soft-entry language.', 'Offer structured recommendations, handle pushback, and measure advisory impact.'],
                topics: ['Traditional versus advisor mindset and mutual benefits', 'Understanding a supervisor’s world and building trust', 'Upward communication, data storytelling, timing, and concise follow-up', 'Problem + root cause + two options + recommendation framework', 'Reverse mentoring, pitfalls, strategic listening, advisor moments, and a 30-day plan'],
                recap: ['Advisory influence is built through reliability, relevance, and respect rather than title.', 'Strong advisors bring solutions and frame recommendations around supervisor priorities.', 'Small, measurable advisor moments build credibility over time.'],
                next: ['Map your supervisor’s goals, pressures, stakeholders, and preferred communication style.', 'Prepare one relevant weekly insight with evidence and a clear recommendation.', 'Ask for feedback, track what was adopted, and refine your approach over 30 days.'],
            ),
            'SL-IDK-010' => $this->lesson(
                purpose: 'Use KPIs, measurement frameworks, data storytelling, and stakeholder-focused persuasion to connect training evidence with business decisions.',
                objectives: ['Select SMART KPIs aligned with goals and business outcomes.', 'Apply the four Kirkpatrick levels to reaction, learning, behavior, and results.', 'Turn data into a clear narrative with context, evidence, insight, and action.', 'Tailor metrics and influencing strategies to stakeholder priorities.'],
                topics: ['KPI fundamentals, types, selection criteria, and measurement methods', 'Kirkpatrick Levels 1–4 and links to business outcomes', 'Training ROI framework, baselines, targets, and meaningful metrics', 'Persuasive visualization, data storytelling, and influencing strategies', 'Stakeholder tailoring, pitfalls, implementation roadmap, and quick wins'],
                recap: ['Useful KPIs are connected to decisions and business outcomes, not vanity metrics.', 'Measurement should progress from learner reaction to workplace behavior and results.', 'Clear stories, appropriate context, and actionable recommendations strengthen influence.'],
                next: ['Audit current reports and choose three to five decision-relevant metrics.', 'Define baselines, targets, and the stakeholder priority for each KPI.', 'Build a simple review rhythm and refine the measures based on feedback and outcomes.'],
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function lesson(string $purpose, array $objectives, array $topics, array $recap, array $next): array
    {
        return [
            'start' => [
                'title' => '01 Start Here',
                'body' => $purpose.' Begin with the objectives below, then use the full-course video and slides together.',
                'objectives' => $objectives,
            ],
            'full' => [
                'title' => '02 Full Course',
                'body' => 'Watch the protected course video and use the accompanying slides to follow the course roadmap.',
                'topics' => $topics,
            ],
            'recap' => [
                'title' => '03 Course Recap & Next Steps',
                'body' => 'Review the deck-supported takeaways and apply the next steps to a relevant real-world situation.',
                'takeaways' => $recap,
                'next_steps' => $next,
            ],
        ];
    }
}
