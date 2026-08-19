-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: ainchors
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_events`
--

DROP TABLE IF EXISTS `activity_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visitor_id` bigint(20) unsigned DEFAULT NULL,
  `visitor_session_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` enum('page_view','click','engagement','auth','course','workflow_audit','consultation','checkout','payment','ai_assistant') NOT NULL,
  `event_name` varchar(150) NOT NULL,
  `page_url` varchar(1000) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` bigint(20) unsigned DEFAULT NULL,
  `active_seconds` int(10) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activity_events_user_time` (`user_id`,`created_at`),
  KEY `idx_activity_events_visitor_time` (`visitor_id`,`created_at`),
  KEY `idx_activity_events_session` (`visitor_session_id`),
  KEY `idx_activity_events_name` (`event_name`),
  KEY `idx_activity_events_type_time` (`event_type`,`created_at`),
  KEY `idx_activity_events_related` (`related_type`,`related_id`),
  CONSTRAINT `fk_activity_events_session` FOREIGN KEY (`visitor_session_id`) REFERENCES `visitor_sessions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_activity_events_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_activity_events_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_events`
--

LOCK TABLES `activity_events` WRITE;
/*!40000 ALTER TABLE `activity_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consultation_requests`
--

DROP TABLE IF EXISTS `consultation_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consultation_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `workflow_audit_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_admin_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('requested','booked','completed','cancelled','no_show') NOT NULL DEFAULT 'requested',
  `requested_at` datetime NOT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_consultation_requests_workflow_audit` (`workflow_audit_id`),
  KEY `fk_consultation_requests_user` (`user_id`),
  KEY `idx_consultation_requests_lead` (`lead_id`),
  KEY `idx_consultation_requests_status` (`status`),
  KEY `idx_consultation_requests_scheduled_at` (`scheduled_at`),
  KEY `idx_consultation_requests_admin` (`assigned_admin_id`),
  CONSTRAINT `fk_consultation_requests_admin` FOREIGN KEY (`assigned_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_requests_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_requests_workflow_audit` FOREIGN KEY (`workflow_audit_id`) REFERENCES `workflow_audits` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consultation_requests`
--

LOCK TABLES `consultation_requests` WRITE;
/*!40000 ALTER TABLE `consultation_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `consultation_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_contents`
--

DROP TABLE IF EXISTS `course_contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_contents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `video_title` varchar(255) DEFAULT NULL,
  `video_provider` varchar(100) DEFAULT NULL,
  `video_url` varchar(1000) NOT NULL,
  `video_duration_seconds` int(10) unsigned DEFAULT NULL,
  `slide_name` varchar(255) DEFAULT NULL,
  `slide_url` varchar(1000) DEFAULT NULL,
  `lesson_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`lesson_content`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_contents_product` (`product_id`),
  CONSTRAINT `fk_course_contents_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_contents`
--

LOCK TABLES `course_contents` WRITE;
/*!40000 ALTER TABLE `course_contents` DISABLE KEYS */;
INSERT INTO `course_contents` VALUES (1,4,'AI Prompt Engineering 101 — Full Course','private','courses/ai-prompt-engineering-101/video/course.mp4',NULL,'AI Prompt Engineering 101 Course Slides','courses/ai-prompt-engineering-101/slides/course-slides.pptx','{\"start\":{\"title\":\"01 Start Here\",\"body\":\"Learn how clear instructions improve AI outputs and use the Role, Task, Context, Format (RTCF) framework to create reusable prompts for practical work. Begin with the objectives below, then use the full-course video and slides together.\",\"objectives\":[\"Explain what a prompt is and why prompt clarity matters.\",\"Build prompts with Role, Task, Context, and Format.\",\"Improve vague prompts using audience, goals, constraints, and output structure.\",\"Test, refine, and save effective prompts as reusable workflows.\"]},\"full\":{\"title\":\"02 Full Course\",\"body\":\"Watch the protected course video and use the accompanying slides to follow the course roadmap.\",\"topics\":[\"Prompt basics and why prompt engineering matters\",\"The RTCF framework and each of its four parts\",\"Weak versus strong prompts\",\"Marketing, training, and operations use cases\",\"Prompt quality checklist, common mistakes, and improvement workflow\"]},\"recap\":{\"title\":\"03 Course Recap & Next Steps\",\"body\":\"Review the deck-supported takeaways and apply the next steps to a relevant real-world situation.\",\"takeaways\":[\"A useful prompt clearly defines the role, task, context, and expected format.\",\"Prompting is iterative: draft, test, refine, and reuse.\",\"Real work tasks make the best practice material.\"],\"next_steps\":[\"Choose one vague prompt from your work and rebuild it with RTCF.\",\"Compare the old and new outputs against your intended goal.\",\"Save the refined version as a reusable template.\"]}}','2026-08-17 21:00:32','2026-08-17 21:05:56'),(2,5,'Digital Marketing using AI — Full Course','private','courses/digital-marketing-using-ai/video/course.mp4',NULL,'Digital Marketing using AI Course Slides','courses/digital-marketing-using-ai/slides/course-slides.pptx','{\"start\":{\"title\":\"01 Start Here\",\"body\":\"Explore how AI supports digital marketing across content, personalization, SEO, advertising, analytics, and campaign planning while keeping ethics and data responsibilities visible. Begin with the objectives below, then use the full-course video and slides together.\",\"objectives\":[\"Recognize machine learning, natural language processing, and generative AI marketing uses.\",\"Connect AI tools to text, image, video, and campaign workflows.\",\"Use segmentation and personalization concepts to improve relevance.\",\"Plan a small AI marketing experiment and measure its result.\"]},\"full\":{\"title\":\"02 Full Course\",\"body\":\"Watch the protected course video and use the accompanying slides to follow the course roadmap.\",\"topics\":[\"AI basics for marketers\",\"AI-assisted text and visual content creation\",\"RFM segmentation, hyper-personalization, and journey mapping\",\"SEO, bidding, predictive analytics, and marketing tool categories\",\"Ethics, bias, privacy, skills balance, and an action plan\"]},\"recap\":{\"title\":\"03 Course Recap & Next Steps\",\"body\":\"Review the deck-supported takeaways and apply the next steps to a relevant real-world situation.\",\"takeaways\":[\"AI can accelerate content and analysis, but marketing judgment remains important.\",\"Personalization should be grounded in customer behavior and responsible data use.\",\"A useful implementation starts with an audit, a controlled test, and measurement.\"],\"next_steps\":[\"Audit one current campaign for a suitable AI-assisted task.\",\"Test one content variation and define the engagement metric before publishing.\",\"Review the outcome and adjust the next campaign based on evidence.\"]}}','2026-08-17 21:00:32','2026-08-17 21:00:32'),(3,6,'Data Analytics — Full Course','private','courses/data-analytics/video/course.mp4',NULL,'Data Analytics Course Slides','courses/data-analytics/slides/course-slides.pptx','{\"start\":{\"title\":\"01 Start Here\",\"body\":\"Build a practical overview of the data analytics lifecycle, from responsible collection and cleaning to tools, models, use cases, evaluation, and communication. Begin with the objectives below, then use the full-course video and slides together.\",\"objectives\":[\"Identify collection, privacy, completeness, bias, and duplication issues.\",\"Apply common approaches to missing values, outliers, and normalization.\",\"Recognize the roles of Python, SQL, visualization, machine learning, and AI tools.\",\"Relate analytics practices to retail, finance, healthcare, and ethical governance.\"]},\"full\":{\"title\":\"02 Full Course\",\"body\":\"Watch the protected course video and use the accompanying slides to follow the course roadmap.\",\"topics\":[\"Data collection, privacy compliance, metadata, and quality\",\"Missing data, outliers, and normalization techniques\",\"AutoML, NLP, computer vision, Python libraries, and visualization\",\"SQL retrieval, joins, grouping, and industry use cases\",\"Data ethics, bias, compliance, model metrics, and portfolio development\"]},\"recap\":{\"title\":\"03 Course Recap & Next Steps\",\"body\":\"Review the deck-supported takeaways and apply the next steps to a relevant real-world situation.\",\"takeaways\":[\"Reliable insights depend on reliable and responsibly collected data.\",\"Analytics combines preparation, tools, models, evaluation, and communication.\",\"Continuous practice and ethical governance are part of the analytics workflow.\"],\"next_steps\":[\"Select a small open dataset and document its quality issues.\",\"Clean and visualize one meaningful pattern before attempting prediction.\",\"Record the method, limitations, and result as a portfolio case.\"]}}','2026-08-17 21:00:32','2026-08-17 21:00:32'),(4,7,'SQL for Data Analytics — Full Course','private','courses/sql-for-data-analytics/video/course.mp4',NULL,'SQL for Data Analytics Course Slides','courses/sql-for-data-analytics/slides/course-slides.pptx','{\"start\":{\"title\":\"01 Start Here\",\"body\":\"Learn how SQL supports data analytics through focused retrieval, filtering, aggregation, relational analysis, and efficient query practices. Begin with the objectives below, then use the full-course video and slides together.\",\"objectives\":[\"Use SELECT, FROM, and WHERE to retrieve targeted data.\",\"Summarize data with GROUP BY and aggregate functions.\",\"Sort results and apply subqueries or CASE logic for analysis.\",\"Recognize when joins, window functions, indexes, and documentation improve analytical work.\"]},\"full\":{\"title\":\"02 Full Course\",\"body\":\"Watch the protected course video and use the accompanying slides to follow the course roadmap.\",\"topics\":[\"SQL role in querying and manipulating structured data\",\"SELECT, FROM, WHERE, ORDER BY, and combined filters\",\"GROUP BY with SUM, COUNT, and AVG\",\"Subqueries, CASE statements, joins, and window functions\",\"Query performance, naming conventions, and documentation\"]},\"recap\":{\"title\":\"03 Course Recap & Next Steps\",\"body\":\"Review the deck-supported takeaways and apply the next steps to a relevant real-world situation.\",\"takeaways\":[\"Start with a clear analytical question and retrieve only the data needed.\",\"Aggregation, conditions, and relationships turn records into useful summaries.\",\"Readable and efficient queries are easier to validate and reuse.\"],\"next_steps\":[\"Write a SELECT query for one real reporting question.\",\"Add filtering, sorting, and an aggregate summary.\",\"Document the query purpose and review whether an index or simpler structure would help.\"]}}','2026-08-17 21:00:32','2026-08-17 21:00:32'),(5,8,'Financial Literacy Mastery — Full Course','private','courses/financial-literacy-mastery/video/course.mp4',NULL,'Financial Literacy Mastery Course Slides','courses/financial-literacy-mastery/slides/course-slides.pptx','{\"start\":{\"title\":\"01 Start Here\",\"body\":\"Strengthen personal financial decision-making through budgeting, emergency planning, credit and debt management, investing, protection, and practical action steps. Begin with the objectives below, then use the full-course video and slides together.\",\"objectives\":[\"Track income and expenses and apply a structured budget approach.\",\"Compare emergency fund, credit, and debt repayment strategies.\",\"Explain compound interest and common investment and retirement concepts.\",\"Recognize insurance, tax planning, behavioral biases, fraud risks, and digital tools.\"]},\"full\":{\"title\":\"02 Full Course\",\"body\":\"Watch the protected course video and use the accompanying slides to follow the course roadmap.\",\"topics\":[\"Budgeting, income tracking, the 50\\/30\\/20 rule, and emergency funds\",\"Credit reports, snowball and avalanche debt approaches, and good versus bad debt\",\"Compound interest, stocks, bonds, mutual funds, and retirement stages\",\"Insurance, tax-advantaged planning, behavioral finance, and fraud prevention\",\"Financial tools, implementation checklist, takeaways, and reviews\"]},\"recap\":{\"title\":\"03 Course Recap & Next Steps\",\"body\":\"Review the deck-supported takeaways and apply the next steps to a relevant real-world situation.\",\"takeaways\":[\"Regular tracking makes financial decisions visible and manageable.\",\"Debt, investment, protection, and retirement choices work best as a connected plan.\",\"Behavior and fraud awareness matter alongside financial calculations.\"],\"next_steps\":[\"Assess current income, spending, debt, savings, and protection.\",\"Create a realistic budget and an emergency-fund action.\",\"Schedule regular financial reviews and seek professional advice where appropriate.\"]}}','2026-08-17 21:00:32','2026-08-17 21:00:32'),(6,9,'E-Payment Fundamentals — Full Course','private','courses/e-payment-fundamentals/video/course.mp4',NULL,'E-Payment Fundamentals Course Slides','courses/e-payment-fundamentals/slides/course-slides.pptx','{\"start\":{\"title\":\"01 Start Here\",\"body\":\"Understand the e-payment ecosystem, its main payment types, security controls, implementation considerations, and emerging payment trends. Begin with the objectives below, then use the full-course video and slides together.\",\"objectives\":[\"Distinguish cards, wallets, QR payments, transfers, BNPL, and cryptocurrency use.\",\"Explain convenience, cost, and analytics benefits for users and businesses.\",\"Recognize encryption, tokenization, MFA, and compliance considerations.\",\"Evaluate payment gateways, mobile security, cross-border payments, and future developments.\"]},\"full\":{\"title\":\"02 Full Course\",\"body\":\"Watch the protected course video and use the accompanying slides to follow the course roadmap.\",\"topics\":[\"E-payment fundamentals and business benefits\",\"Cards, digital wallets, QR payments, FPX, DuitNow, BNPL, and cryptocurrency\",\"Encryption, tokenization, multi-factor authentication, and PCI DSS\",\"Gateway selection, mobile security, cross-border and recurring payments\",\"Embedded finance, biometrics, CBDCs, open banking, IoT, and implementation readiness\"]},\"recap\":{\"title\":\"03 Course Recap & Next Steps\",\"body\":\"Review the deck-supported takeaways and apply the next steps to a relevant real-world situation.\",\"takeaways\":[\"Payment choices should balance convenience, cost, interoperability, and security.\",\"Security and compliance are core design requirements rather than optional features.\",\"Businesses should choose solutions that fit their systems and customer experience.\"],\"next_steps\":[\"Map the payment methods used by one business or customer journey.\",\"Review security, fees, reliability, integration, and cross-border needs.\",\"Identify one secure, user-friendly improvement supported by the course framework.\"]}}','2026-08-17 21:00:32','2026-08-17 21:05:56'),(7,10,'Fintech Fundamentals — Full Course','private','courses/fintech-fundamentals/video/course.mp4',NULL,'Fintech Fundamentals Course Slides','courses/fintech-fundamentals/slides/course-slides.pptx','{\"start\":{\"title\":\"01 Start Here\",\"body\":\"Navigate the fintech ecosystem, technologies, applications, regulatory landscape, cybersecurity needs, and trends shaping digital financial services. Begin with the objectives below, then use the full-course video and slides together.\",\"objectives\":[\"Describe the roles of startups, banks, regulators, technology providers, and consumers.\",\"Connect mobile, cloud, APIs, AI, and blockchain to fintech services.\",\"Recognize applications in payments, digital banking, lending, WealthTech, InsurTech, and cross-border finance.\",\"Consider RegTech, cybersecurity, CBDCs, embedded finance, challenges, and opportunities.\"]},\"full\":{\"title\":\"02 Full Course\",\"body\":\"Watch the protected course video and use the accompanying slides to follow the course roadmap.\",\"topics\":[\"Fintech definition, ecosystem, and evolution from branches to embedded finance\",\"Mobile, cloud, APIs, AI, and blockchain technologies\",\"Digital payments, banks, lending, WealthTech, InsurTech, and cross-border services\",\"RegTech, Bank Negara oversight, compliance, and cybersecurity\",\"Agentic AI, stablecoins, CBDCs, embedded finance, challenges, and opportunities\"]},\"recap\":{\"title\":\"03 Course Recap & Next Steps\",\"body\":\"Review the deck-supported takeaways and apply the next steps to a relevant real-world situation.\",\"takeaways\":[\"Fintech combines technology, financial services, regulation, and customer needs.\",\"Core technologies enable new services but also create security and compliance responsibilities.\",\"Sustainable innovation depends on collaboration and customer trust.\"],\"next_steps\":[\"Choose one fintech service and map its ecosystem participants.\",\"Identify the technology, compliance, and cybersecurity dependencies.\",\"Evaluate how the service improves access, experience, or efficiency.\"]}}','2026-08-17 21:00:32','2026-08-17 21:00:32'),(8,11,'Central Bank Digital Currency (CBDC) — Full Course','private','courses/central-bank-digital-currency-cbdc/video/course.mp4',NULL,'Central Bank Digital Currency (CBDC) Course Slides','courses/central-bank-digital-currency-cbdc/slides/course-slides.pptx','{\"start\":{\"title\":\"01 Start Here\",\"body\":\"Understand CBDCs as central-bank-issued digital money, including their types, designs, benefits, risks, technologies, and global pilots. Begin with the objectives below, then use the full-course video and slides together.\",\"objectives\":[\"Distinguish CBDCs from cash, bank deposits, and cryptocurrencies.\",\"Compare retail and wholesale CBDCs and account, token, or hybrid designs.\",\"Evaluate inclusion, efficiency, programmability, privacy, security, and financial-stability considerations.\",\"Review technology choices and lessons from global projects and pilots.\"]},\"full\":{\"title\":\"02 Full Course\",\"body\":\"Watch the protected course video and use the accompanying slides to follow the course roadmap.\",\"topics\":[\"Money today, CBDC definition, and comparison with cryptocurrencies\",\"Retail and wholesale CBDCs and design choices\",\"Financial inclusion, efficiency, innovation, and payment use cases\",\"Bank disintermediation, privacy, cybersecurity, surveillance, and policy challenges\",\"Global launches and pilots, DLT versus centralized systems, offline use, and scalability\"]},\"recap\":{\"title\":\"03 Course Recap & Next Steps\",\"body\":\"Review the deck-supported takeaways and apply the next steps to a relevant real-world situation.\",\"takeaways\":[\"A CBDC is government-backed digital fiat currency, not a decentralized cryptocurrency.\",\"Design choices shape access, privacy, security, and financial-system effects.\",\"Global pilots provide practical lessons while policy and technology continue to evolve.\"],\"next_steps\":[\"Compare one retail and one wholesale CBDC use case.\",\"List the inclusion, privacy, stability, and infrastructure trade-offs.\",\"Track verified central-bank pilot updates and evaluate them against the course framework.\"]}}','2026-08-17 21:00:32','2026-08-17 21:00:32'),(9,12,'Becoming Your Supervisor\'s Advisor — Full Course','private','courses/becoming-your-supervisors-advisor/video/course.mp4',NULL,'Becoming Your Supervisor\'s Advisor Course Slides','courses/becoming-your-supervisors-advisor/slides/course-slides.pptx','{\"start\":{\"title\":\"01 Start Here\",\"body\":\"Develop the mindset and practical skills to become a trusted strategic advisor to a supervisor through insight, trust, upward communication, and solution-focused action. Begin with the objectives below, then use the full-course video and slides together.\",\"objectives\":[\"Shift from task execution toward strategic partnership.\",\"Understand a supervisor\\u2019s goals, stakeholders, pressures, and communication preferences.\",\"Build trust and communicate insights with data, storytelling, timing, and soft-entry language.\",\"Offer structured recommendations, handle pushback, and measure advisory impact.\"]},\"full\":{\"title\":\"02 Full Course\",\"body\":\"Watch the protected course video and use the accompanying slides to follow the course roadmap.\",\"topics\":[\"Traditional versus advisor mindset and mutual benefits\",\"Understanding a supervisor\\u2019s world and building trust\",\"Upward communication, data storytelling, timing, and concise follow-up\",\"Problem + root cause + two options + recommendation framework\",\"Reverse mentoring, pitfalls, strategic listening, advisor moments, and a 30-day plan\"]},\"recap\":{\"title\":\"03 Course Recap & Next Steps\",\"body\":\"Review the deck-supported takeaways and apply the next steps to a relevant real-world situation.\",\"takeaways\":[\"Advisory influence is built through reliability, relevance, and respect rather than title.\",\"Strong advisors bring solutions and frame recommendations around supervisor priorities.\",\"Small, measurable advisor moments build credibility over time.\"],\"next_steps\":[\"Map your supervisor\\u2019s goals, pressures, stakeholders, and preferred communication style.\",\"Prepare one relevant weekly insight with evidence and a clear recommendation.\",\"Ask for feedback, track what was adopted, and refine your approach over 30 days.\"]}}','2026-08-17 21:00:32','2026-08-17 21:00:32'),(10,13,'Influencing with Data & KPIs — Full Course','private','courses/influencing-with-data-and-kpis/video/course.mp4',NULL,'Influencing with Data & KPIs Course Slides','courses/influencing-with-data-and-kpis/slides/course-slides.pptx','{\"start\":{\"title\":\"01 Start Here\",\"body\":\"Use KPIs, measurement frameworks, data storytelling, and stakeholder-focused persuasion to connect training evidence with business decisions. Begin with the objectives below, then use the full-course video and slides together.\",\"objectives\":[\"Select SMART KPIs aligned with goals and business outcomes.\",\"Apply the four Kirkpatrick levels to reaction, learning, behavior, and results.\",\"Turn data into a clear narrative with context, evidence, insight, and action.\",\"Tailor metrics and influencing strategies to stakeholder priorities.\"]},\"full\":{\"title\":\"02 Full Course\",\"body\":\"Watch the protected course video and use the accompanying slides to follow the course roadmap.\",\"topics\":[\"KPI fundamentals, types, selection criteria, and measurement methods\",\"Kirkpatrick Levels 1\\u20134 and links to business outcomes\",\"Training ROI framework, baselines, targets, and meaningful metrics\",\"Persuasive visualization, data storytelling, and influencing strategies\",\"Stakeholder tailoring, pitfalls, implementation roadmap, and quick wins\"]},\"recap\":{\"title\":\"03 Course Recap & Next Steps\",\"body\":\"Review the deck-supported takeaways and apply the next steps to a relevant real-world situation.\",\"takeaways\":[\"Useful KPIs are connected to decisions and business outcomes, not vanity metrics.\",\"Measurement should progress from learner reaction to workplace behavior and results.\",\"Clear stories, appropriate context, and actionable recommendations strengthen influence.\"],\"next_steps\":[\"Audit current reports and choose three to five decision-relevant metrics.\",\"Define baselines, targets, and the stakeholder priority for each KPI.\",\"Build a simple review rhythm and refine the measures based on feedback and outcomes.\"]}}','2026-08-17 21:00:32','2026-08-17 21:05:56');
/*!40000 ALTER TABLE `course_contents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `source_order_item_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','completed','expired','revoked') NOT NULL DEFAULT 'active',
  `progress_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `enrolled_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollments_user_product` (`user_id`,`product_id`),
  KEY `fk_enrollments_product` (`product_id`),
  KEY `fk_enrollments_order_item` (`source_order_item_id`),
  KEY `idx_enrollments_user` (`user_id`),
  KEY `idx_enrollments_status` (`status`),
  CONSTRAINT `fk_enrollments_order_item` FOREIGN KEY (`source_order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_enrollments_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_enrollments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `visitor_id` bigint(20) unsigned DEFAULT NULL,
  `workflow_audit_id` bigint(20) unsigned DEFAULT NULL,
  `source` enum('contact','workflow_audit','course','ai_assistant','other') NOT NULL DEFAULT 'other',
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `status` enum('new','contacted','qualified','consultation_requested','consultation_booked','proposal','won','lost') NOT NULL DEFAULT 'new',
  `assigned_admin_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_leads_user` (`user_id`),
  KEY `fk_leads_visitor` (`visitor_id`),
  KEY `idx_leads_source` (`source`),
  KEY `idx_leads_status` (`status`),
  KEY `idx_leads_email` (`email`),
  KEY `idx_leads_workflow_audit` (`workflow_audit_id`),
  KEY `idx_leads_assigned_admin` (`assigned_admin_id`),
  CONSTRAINT `fk_leads_assigned_admin` FOREIGN KEY (`assigned_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_leads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_leads_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_leads_workflow_audit` FOREIGN KEY (`workflow_audit_id`) REFERENCES `workflow_audits` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_08_18_000000_baseline_course_commerce_tables',1),(5,'2026_08_18_000001_extend_course_commerce_tables',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(100) NOT NULL,
  `idempotency_key` varchar(64) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `status` enum('pending','awaiting_payment','paid','completed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `currency` char(3) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `placed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_order_number` (`order_number`),
  UNIQUE KEY `orders_idempotency_key_unique` (`idempotency_key`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created_at` (`created_at`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `provider` varchar(100) NOT NULL,
  `provider_transaction_id` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` char(3) NOT NULL,
  `status` enum('pending','processing','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `provider_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provider_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_provider_transaction` (`provider`,`provider_transaction_id`),
  KEY `idx_payments_order` (`order_id`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_paid_at` (`paid_at`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `privacy_consents`
--

DROP TABLE IF EXISTS `privacy_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `privacy_consents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visitor_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `consent_type` enum('analytics','personalisation','marketing') NOT NULL,
  `consent_version` varchar(30) NOT NULL,
  `granted` tinyint(1) NOT NULL,
  `granted_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_privacy_consents_visitor` (`visitor_id`,`consent_type`,`created_at`),
  KEY `idx_privacy_consents_user` (`user_id`,`consent_type`,`created_at`),
  CONSTRAINT `fk_privacy_consents_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_privacy_consents_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privacy_consents`
--

LOCK TABLES `privacy_consents` WRITE;
/*!40000 ALTER TABLE `privacy_consents` DISABLE KEYS */;
/*!40000 ALTER TABLE `privacy_consents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_relations`
--

DROP TABLE IF EXISTS `product_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_relations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_product_id` bigint(20) unsigned NOT NULL,
  `child_product_id` bigint(20) unsigned NOT NULL,
  `relation_type` enum('bundle_item','related') NOT NULL DEFAULT 'bundle_item',
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_relation` (`parent_product_id`,`child_product_id`,`relation_type`),
  KEY `idx_product_relations_parent` (`parent_product_id`),
  KEY `idx_product_relations_child` (`child_product_id`),
  CONSTRAINT `fk_product_relations_child` FOREIGN KEY (`child_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_product_relations_parent` FOREIGN KEY (`parent_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_relations`
--

LOCK TABLES `product_relations` WRITE;
/*!40000 ALTER TABLE `product_relations` DISABLE KEYS */;
INSERT INTO `product_relations` VALUES (1,14,4,'bundle_item',3,'2026-08-14 00:19:04'),(2,14,5,'bundle_item',4,'2026-08-14 00:19:04'),(3,14,6,'bundle_item',2,'2026-08-14 00:19:04'),(4,14,7,'bundle_item',1,'2026-08-14 00:19:04'),(5,14,8,'bundle_item',5,'2026-08-14 00:19:04'),(6,14,9,'bundle_item',6,'2026-08-14 00:19:04'),(7,14,10,'bundle_item',7,'2026-08-14 00:19:04'),(8,14,11,'bundle_item',8,'2026-08-14 00:19:04'),(9,14,12,'bundle_item',9,'2026-08-14 00:19:04'),(10,14,13,'bundle_item',10,'2026-08-14 00:19:04');
/*!40000 ALTER TABLE `product_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('course','course_package','consulting','service') NOT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `short_description` text DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `billing_type` enum('one_time','monthly','custom') NOT NULL DEFAULT 'one_time',
  `status` enum('draft','active','inactive') NOT NULL DEFAULT 'active',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_sku` (`sku`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_type` (`type`),
  KEY `idx_products_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'service','AI-WORKFLOW-DIAGNOSTIC','AINCHORS AI Workflow Diagnostic','ai-workflow-diagnostic',NULL,NULL,NULL,4500.00,'AUD','one_time','active',NULL,'2026-08-14 04:22:59','2026-08-14 04:22:59'),(2,'service','AI-OPERATOR-IMPLEMENTATION','AINCHORS AI Operator Implementation','ai-operator-implementation',NULL,NULL,NULL,12000.00,'AUD','one_time','active',NULL,'2026-08-14 04:22:59','2026-08-14 04:22:59'),(3,'service','AI-OPERATOR-SERVICING','AINCHORS AI Operator Monthly Servicing','ai-operator-monthly-servicing',NULL,NULL,NULL,2500.00,'AUD','monthly','active',NULL,'2026-08-14 04:22:59','2026-08-14 04:22:59'),(4,'course','SL-AI-001','AI Prompt Engineering 101','ai-prompt-engineering-101','Master machine learning, natural language processing, and generative AI to build intelligent systems','Master machine learning, natural language processing, and generative AI to build intelligent systems','assets/site/6971824d15885e0e516659c2.webp',19.00,'USD','one_time','active','{\"source\":\"legacy_html\",\"trainer\":\"Angie.F\",\"catalogue\":{\"title\":\"AI Prompt Engineering 101\",\"url\":\"\\/courses\"},\"individual_page\":{\"title\":\"AI Prompt Engineering 101\",\"url\":\"\\/individual-aiprompt\"},\"package_page\":{\"title\":\"Artificial Intelligence (AI) Prompt Engineering 101\",\"url\":\"\\/package-page-4066\"},\"checkout\":{\"url\":\"\\/check-out-pagecourse-individualaiprompt\",\"verified_current_price\":19,\"currency\":\"USD\"},\"pricing\":{\"original_price\":50,\"current_sale_price\":19,\"currency\":\"USD\",\"wording\":\"Only for limited time\"},\"name_variants\":[\"AI Prompt Engineering 101\",\"Artificial Intelligence (AI) Prompt Engineering 101\",\"AI Prompt Enginnering 101 pre record @ 19\"],\"video_statement\":\"Access to course video. Can access the videos anytime, anywhere. Unlimited replays.\",\"protected_video_url\":null,\"slide_url\":null,\"course_contents_status\":\"pending_real_learning_files\"}','2026-08-14 00:19:04','2026-08-17 21:00:36'),(5,'course','SL-DMAI-002','Digital Marketing using AI','digital-marketing-using-ai','Leverage AI-powered tools for personalized campaigns, automation, and customer engagement optimization','Leverage AI-powered tools for personalized campaigns, automation, and customer engagement optimization','assets/site/699d61a92837e8fa8c17b91f.jpg',19.00,'USD','one_time','active','{\"source\":\"legacy_html\",\"trainer\":\"Angie.F\",\"catalogue\":{\"title\":\"Digital Marketing using AI\",\"url\":\"\\/courses\"},\"individual_page\":{\"title\":\"The overview of Digital Marketing using AI\",\"url\":\"\\/digitalmarketing\"},\"package_page\":{\"title\":\"The Overview of Digital Marketing Using AI\",\"url\":\"\\/package-page-6341\"},\"checkout\":{\"url\":\"\\/check-out-pagecourse-individualdigital\",\"verified_current_price\":19,\"currency\":\"USD\"},\"pricing\":{\"original_price\":50,\"current_sale_price\":19,\"currency\":\"USD\",\"wording\":\"Only for limited time\"},\"name_variants\":[\"The overview of Digital Marketing using AI\",\"The Overview of Digital Marketing Using AI\",\"Digital Marketing using AI @ 19\"],\"video_statement\":\"Access to course video. Can access the videos anytime, anywhere. Unlimited replays.\",\"protected_video_url\":null,\"slide_url\":null,\"course_contents_status\":\"pending_real_learning_files\"}','2026-08-14 00:19:04','2026-08-14 00:19:04'),(6,'course','SL-DA-003','Data Analytics','data-analytics','Transform raw data into strategic insights through visualization, statistical analysis, and business intelligence','Transform raw data into strategic insights through visualization, statistical analysis, and business intelligence','assets/site/699c338c590acb9104afa2f5.png',19.00,'USD','one_time','active','{\"source\":\"legacy_html\",\"trainer\":\"Angie.F\",\"catalogue\":{\"title\":\"Data Analytics\",\"url\":\"\\/courses\"},\"individual_page\":{\"title\":\"The overview of data analytics\",\"url\":\"\\/package-page-dataanalytics\"},\"package_page\":{\"title\":\"The Overview of Data Analytics\",\"url\":\"\\/package-page-12\"},\"checkout\":{\"url\":\"\\/check-out-pagecourse-individualdataanalytics\",\"verified_current_price\":19,\"currency\":\"USD\"},\"pricing\":{\"original_price\":50,\"current_sale_price\":19,\"currency\":\"USD\",\"wording\":\"Only for limited time\"},\"name_variants\":[\"The overview of data analytics\",\"The Overview of Data Analytics\",\"data analytics @ 19\"],\"video_statement\":\"Access to course video. Can access the videos anytime, anywhere. Unlimited replays.\",\"protected_video_url\":null,\"slide_url\":null,\"course_contents_status\":\"pending_real_learning_files\"}','2026-08-14 00:19:04','2026-08-14 00:19:04'),(7,'course','SL-SQL-004','SQL for Data Analytics','sql-for-data-analytics','Learn to query databases, extract insights, and analyze data using structured query language','Learn to query databases, extract insights, and analyze data using structured query language','assets/site/6971830d7079aada0632836d.webp',19.00,'USD','one_time','active','{\"source\":\"legacy_html\",\"trainer\":\"Angie.F\",\"catalogue\":{\"title\":\"SQL for Data Analytics\",\"url\":\"\\/courses\"},\"individual_page\":{\"title\":\"The overview of SQL for data analytics\",\"url\":\"\\/package-page-page-303665\"},\"package_page\":{\"title\":\"The Overview of SQL for data analytics\",\"url\":\"\\/package-page\"},\"checkout\":{\"url\":\"\\/check-out-pagecourse-individual\",\"verified_current_price\":19,\"currency\":\"USD\"},\"pricing\":{\"original_price\":50,\"current_sale_price\":19,\"currency\":\"USD\",\"wording\":\"Only for limited time\"},\"name_variants\":[\"The overview of SQL for data analytics\",\"The Overview of SQL for data analytics\",\"SQL for Data Analytics @ 19\"],\"video_statement\":\"Access to course video. Can access the videos anytime, anywhere. Unlimited replays.\",\"protected_video_url\":null,\"slide_url\":null,\"course_contents_status\":\"pending_real_learning_files\"}','2026-08-14 00:19:04','2026-08-14 00:19:04'),(8,'course','SL-FLM-005','Financial Literacy Mastery','financial-literacy-mastery','Build wealth through budgeting, investing, credit management, and smart financial decision-making','Build wealth through budgeting, investing, credit management, and smart financial decision-making','assets/site/700f1cbb-ae75-42c0-bbb7-d4c22a98074d.png',19.00,'USD','one_time','active','{\"source\":\"legacy_html\",\"trainer\":\"Angie.F\",\"catalogue\":{\"title\":\"Financial Literacy Mastery\",\"url\":\"\\/courses\"},\"individual_page\":{\"title\":\"Financial Literacy Mastery\",\"url\":\"\\/financialliteracymastery\"},\"package_page\":{\"title\":\"Financial Literacy Mastery\",\"url\":\"\\/package-pagefi\"},\"checkout\":{\"url\":\"\\/check-out-pagefinancial\",\"verified_current_price\":19,\"currency\":\"USD\"},\"pricing\":{\"original_price\":50,\"current_sale_price\":19,\"currency\":\"USD\",\"wording\":\"Only for limited time\"},\"name_variants\":[\"Financial Literacy Mastery @ 19\"],\"video_statement\":\"Access to course video. Can access the videos anytime, anywhere. Unlimited replays.\",\"protected_video_url\":null,\"slide_url\":null,\"course_contents_status\":\"pending_real_learning_files\"}','2026-08-14 00:19:04','2026-08-14 00:19:04'),(9,'course','SL-EP-006','E-Payment Fundamentals','e-payment-fundamentals','Understand digital wallets, payment gateways, transaction security, and the cashless economy','Understand digital wallets, payment gateways, transaction security, and the cashless economy','assets/site/699c536a1001a5ff39d32f70.jpg',19.00,'USD','one_time','active','{\"source\":\"legacy_html\",\"trainer\":\"Angie.F\",\"catalogue\":{\"title\":\"E-Payment Fundamentals\",\"url\":\"\\/courses\"},\"individual_page\":{\"title\":\"E-Payment Fundamentals\",\"url\":\"\\/individualepayment\"},\"package_page\":{\"title\":\"E-Payment Systems Mastery\",\"url\":\"\\/package-page-6219\"},\"checkout\":{\"url\":\"\\/check-out-pageepayment\",\"verified_current_price\":19,\"currency\":\"USD\"},\"pricing\":{\"original_price\":50,\"current_sale_price\":19,\"currency\":\"USD\",\"wording\":\"Only for limited time\"},\"name_variants\":[\"E-Payment Systems Mastery\",\"E-Payment Systems Mastery @ 19\"],\"video_statement\":\"Access to course video. Can access the videos anytime, anywhere. Unlimited replays.\",\"protected_video_url\":null,\"slide_url\":null,\"course_contents_status\":\"pending_real_learning_files\"}','2026-08-14 00:19:04','2026-08-17 21:00:36'),(10,'course','SL-FF-007','Fintech Fundamentals','fintech-fundamentals','Explore digital banking, lending platforms, robo-advisors, and the future of financial services','Explore digital banking, lending platforms, robo-advisors, and the future of financial services','assets/site/d11891b9-544e-4896-85c5-e8140dd77653.png',19.00,'USD','one_time','active','{\"source\":\"legacy_html\",\"trainer\":\"Angie.F\",\"catalogue\":{\"title\":\"Fintech Fundamentals\",\"url\":\"\\/courses\"},\"individual_page\":{\"title\":\"Fintech Fundamentals Mastery\",\"url\":\"\\/individualfintech\"},\"package_page\":{\"title\":\"Fintech Fundamentals Mastery\",\"url\":\"\\/package-page-9865\"},\"checkout\":{\"url\":\"\\/checkoutfintech\",\"verified_current_price\":19,\"currency\":\"USD\"},\"pricing\":{\"original_price\":50,\"current_sale_price\":19,\"currency\":\"USD\",\"wording\":\"Only for limited time\"},\"name_variants\":[\"Fintech Fundamentals Mastery\",\"Fintech Fundamentals Mastery @ 19\"],\"video_statement\":\"Access to course video. Can access the videos anytime, anywhere. Unlimited replays.\",\"protected_video_url\":null,\"slide_url\":null,\"course_contents_status\":\"pending_real_learning_files\",\"conflicts\":[{\"package_page\":\"\\/package-page-9865\",\"displayed_package_price\":{\"currency\":\"USD\",\"list\":190,\"sale\":150},\"incorrect_get_now_url\":\"\\/check-out-pagecoursefintech\",\"incorrect_checkout_product\":\"Fintech Fundamentals Mastery @ 19\"}]}','2026-08-14 00:19:04','2026-08-14 00:19:04'),(11,'course','SL-CBDC-008','Central Bank Digital Currency (CBDC)','central-bank-digital-currency-cbdc','Discover how government-backed digital currencies are reshaping global monetary systems','Discover how government-backed digital currencies are reshaping global monetary systems','assets/site/2c06a3e2-fc2f-4811-a248-4bfe5a57eb3b.png',19.00,'USD','one_time','active','{\"source\":\"legacy_html\",\"trainer\":\"Angie.F\",\"catalogue\":{\"title\":\"Central Bank Digital Currency (CBDC)\",\"url\":\"\\/courses\"},\"individual_page\":{\"title\":\"Central Bank Digital Currency Mastery\",\"url\":\"\\/individualcbdc\"},\"package_page\":{\"title\":\"Central Bank Digital Currency (CBDC) Mastery\",\"url\":\"\\/package-page-4157\"},\"checkout\":{\"url\":\"\\/cbdccheckoutpage\",\"verified_current_price\":19,\"currency\":\"USD\"},\"pricing\":{\"original_price\":50,\"current_sale_price\":19,\"currency\":\"USD\",\"wording\":\"Only for limited time\"},\"name_variants\":[\"Central Bank Digital Currency Mastery\",\"Central Bank Digital Currency (CBDC) Mastery\"],\"video_statement\":\"Access to course video. Can access the videos anytime, anywhere. Unlimited replays.\",\"protected_video_url\":null,\"slide_url\":null,\"course_contents_status\":\"pending_real_learning_files\",\"checkout_verification\":\"No recoverable product amount metadata was found in \\/cbdccheckoutpage. USD 19 is sourced from the individual landing page display.\"}','2026-08-14 00:19:04','2026-08-14 00:19:04'),(12,'course','SL-BSA-009','Becoming Your Supervisor\'s Advisor','becoming-your-supervisors-advisor','Position yourself as a trusted strategic partner and advance your influence within the organization','Position yourself as a trusted strategic partner and advance your influence within the organization','assets/site/eaab034e-af0a-4ed4-8d5b-e60b051acf9d.png',19.00,'USD','one_time','active','{\"source\":\"legacy_html\",\"trainer\":\"Angie.F\",\"catalogue\":{\"title\":\"Becoming Your Supervisor\'s Advisor\",\"url\":\"\\/courses\"},\"package_page\":{\"title\":\"Becoming Your Supervisor\'s Advisor course\",\"url\":null},\"store_listing\":{\"url\":\"\\/product-details\\/product\\/6a55cb02824b1a2d6648bbf1\",\"displayed_price\":\"$19.00\"},\"pricing\":{\"original_price\":50,\"current_sale_price\":19,\"currency\":\"USD\"},\"currency_note\":\"The canonical Laravel V2 course price is USD 19 with an original list price of USD 50.\",\"checkout\":{\"url\":null,\"status\":\"unconfirmed\"},\"name_variants\":[\"Becoming Your Supervisor\'s Advisor course\"],\"protected_video_url\":null,\"slide_url\":null,\"course_contents_status\":\"pending_real_learning_files\"}','2026-08-14 00:19:04','2026-08-17 21:00:36'),(13,'course','SL-IDK-010','Influencing with Data & KPIs','influencing-with-data-and-kpis','Master data storytelling and persuasive analytics to drive business decisions and gain stakeholder buy-in','Master data storytelling and persuasive analytics to drive business decisions and gain stakeholder buy-in','assets/site/22df6cf5-88df-410c-bbab-70e990c409d6.png',19.00,'USD','one_time','active','{\"source\":\"legacy_html\",\"trainer\":\"Angie.F\",\"catalogue\":{\"title\":\"Influencing with Data & KPIs\",\"url\":\"\\/courses\"},\"package_page\":{\"title\":\"Influencing with Data & KPIs course\",\"url\":null},\"store_listing\":{\"url\":\"\\/product-details\\/product\\/6a55cb4d03821e4f56e9e11f\",\"displayed_price\":\"$19.00\"},\"pricing\":{\"original_price\":50,\"current_sale_price\":19,\"currency\":\"USD\"},\"currency_note\":\"The canonical Laravel V2 course price is USD 19 with an original list price of USD 50.\",\"checkout\":{\"url\":null,\"status\":\"unconfirmed\"},\"name_variants\":[\"Influencing with Data & KPIs course\"],\"protected_video_url\":null,\"slide_url\":null,\"course_contents_status\":\"pending_real_learning_files\"}','2026-08-14 00:19:04','2026-08-17 21:00:36'),(14,'course_package','SL-PACKAGE-ALL-10','Learning Course Package Deal','learning-course-package-deal','Access to all 10 video courses','Can access the videos anytime, anywhere. Unlimited replays. Access to all 10 video courses.',NULL,150.00,'USD','one_time','active','{\"source\":\"legacy_html\",\"original_price\":190,\"current_sale_price\":150,\"currency\":\"USD\",\"price_wording\":\"Discount only for limited time\",\"legacy_package_urls\":[\"\\/package-page-4066\",\"\\/package-page-6341\",\"\\/package-page-12\",\"\\/package-page\",\"\\/package-pagefi\",\"\\/package-page-6219\",\"\\/package-page-9865\",\"\\/package-page-4157\"],\"verified_package_checkout_urls\":[\"\\/check-out-page-page\",\"\\/check-out-pagecourse-00\",\"\\/check-out-pagecourse23\",\"\\/check-out-pagecoursedeale\",\"\\/check-out-page2\"],\"included_course_order\":[\"SL-SQL-004\",\"SL-DA-003\",\"SL-AI-001\",\"SL-DMAI-002\",\"SL-FLM-005\",\"SL-EP-006\",\"SL-FF-007\",\"SL-CBDC-008\",\"SL-BSA-009\",\"SL-IDK-010\"],\"legacy_name_variants\":[\"Learning Course Package Deal\",\"Deal package @ 150\"],\"fintech_checkout_conflict\":\"\\/package-page-9865 displays this package but links to \\/check-out-pagecoursefintech, a USD 19 Fintech checkout.\",\"course_contents_status\":\"pending_real_learning_files\"}','2026-08-14 00:19:04','2026-08-14 00:19:04');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_engagements`
--

DROP TABLE IF EXISTS `service_engagements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_engagements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `workflow_audit_id` bigint(20) unsigned DEFAULT NULL,
  `order_item_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `engagement_type` enum('diagnostic','implementation','servicing') NOT NULL,
  `status` enum('planned','active','paused','completed','cancelled') NOT NULL DEFAULT 'planned',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `assigned_admin_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_service_engagements_workflow_audit` (`workflow_audit_id`),
  KEY `fk_service_engagements_order_item` (`order_item_id`),
  KEY `fk_service_engagements_product` (`product_id`),
  KEY `idx_service_engagements_user` (`user_id`),
  KEY `idx_service_engagements_lead` (`lead_id`),
  KEY `idx_service_engagements_type` (`engagement_type`),
  KEY `idx_service_engagements_status` (`status`),
  KEY `idx_service_engagements_admin` (`assigned_admin_id`),
  CONSTRAINT `fk_service_engagements_admin` FOREIGN KEY (`assigned_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_service_engagements_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_service_engagements_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_service_engagements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_service_engagements_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_service_engagements_workflow_audit` FOREIGN KEY (`workflow_audit_id`) REFERENCES `workflow_audits` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_engagements`
--

LOCK TABLES `service_engagements` WRITE;
/*!40000 ALTER TABLE `service_engagements` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_engagements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `email_verified_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visitor_sessions`
--

DROP TABLE IF EXISTS `visitor_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visitor_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visitor_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `session_uuid` char(36) NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `landing_url` varchar(1000) DEFAULT NULL,
  `referrer_url` varchar(1500) DEFAULT NULL,
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `utm_content` varchar(255) DEFAULT NULL,
  `utm_term` varchar(255) DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `operating_system` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visitor_sessions_uuid` (`session_uuid`),
  KEY `idx_visitor_sessions_visitor` (`visitor_id`),
  KEY `idx_visitor_sessions_user` (`user_id`),
  KEY `idx_visitor_sessions_started_at` (`started_at`),
  CONSTRAINT `fk_visitor_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_visitor_sessions_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitor_sessions`
--

LOCK TABLES `visitor_sessions` WRITE;
/*!40000 ALTER TABLE `visitor_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `visitor_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visitors`
--

DROP TABLE IF EXISTS `visitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visitors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visitor_uuid` char(36) NOT NULL,
  `linked_user_id` bigint(20) unsigned DEFAULT NULL,
  `first_seen_at` datetime NOT NULL,
  `last_seen_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visitors_uuid` (`visitor_uuid`),
  KEY `idx_visitors_linked_user` (`linked_user_id`),
  KEY `idx_visitors_last_seen` (`last_seen_at`),
  CONSTRAINT `fk_visitors_linked_user` FOREIGN KEY (`linked_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitors`
--

LOCK TABLES `visitors` WRITE;
/*!40000 ALTER TABLE `visitors` DISABLE KEYS */;
/*!40000 ALTER TABLE `visitors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_audit_answers`
--

DROP TABLE IF EXISTS `workflow_audit_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workflow_audit_answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_audit_id` bigint(20) unsigned NOT NULL,
  `question_key` varchar(100) NOT NULL,
  `answer_text` mediumtext DEFAULT NULL,
  `answer_number` decimal(15,4) DEFAULT NULL,
  `answer_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answer_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_workflow_audit_question` (`workflow_audit_id`,`question_key`),
  KEY `idx_workflow_audit_answers_question_key` (`question_key`),
  CONSTRAINT `fk_workflow_audit_answers_audit` FOREIGN KEY (`workflow_audit_id`) REFERENCES `workflow_audits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_audit_answers`
--

LOCK TABLES `workflow_audit_answers` WRITE;
/*!40000 ALTER TABLE `workflow_audit_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_audit_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_audit_results`
--

DROP TABLE IF EXISTS `workflow_audit_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workflow_audit_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_audit_id` bigint(20) unsigned NOT NULL,
  `automation_score` decimal(5,2) DEFAULT NULL,
  `potential_level` enum('low','medium','high','very_high') DEFAULT NULL,
  `current_monthly_labour_cost` decimal(12,2) DEFAULT NULL,
  `estimated_reduction_low` decimal(5,2) DEFAULT NULL,
  `estimated_reduction_high` decimal(5,2) DEFAULT NULL,
  `estimated_hours_saved_low` decimal(12,2) DEFAULT NULL,
  `estimated_hours_saved_high` decimal(12,2) DEFAULT NULL,
  `monthly_value_low` decimal(12,2) DEFAULT NULL,
  `monthly_value_high` decimal(12,2) DEFAULT NULL,
  `annual_value_low` decimal(12,2) DEFAULT NULL,
  `annual_value_high` decimal(12,2) DEFAULT NULL,
  `bottlenecks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bottlenecks`)),
  `ai_operator_opportunities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_operator_opportunities`)),
  `future_state_workflow` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`future_state_workflow`)),
  `recommended_next_step` text DEFAULT NULL,
  `implementation_direction` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`implementation_direction`)),
  `verified_workflow_map` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verified_workflow_map`)),
  `system_integration_review` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`system_integration_review`)),
  `risk_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_analysis`)),
  `data_assessment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_assessment`)),
  `detailed_roi` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detailed_roi`)),
  `solution_design` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`solution_design`)),
  `implementation_scope` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`implementation_scope`)),
  `assumptions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`assumptions`)),
  `ai_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_analysis`)),
  `ai_provider` varchar(100) DEFAULT NULL,
  `ai_model` varchar(150) DEFAULT NULL,
  `generated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_workflow_audit_results_audit` (`workflow_audit_id`),
  KEY `idx_workflow_audit_results_score` (`automation_score`),
  KEY `idx_workflow_audit_results_potential` (`potential_level`),
  CONSTRAINT `fk_workflow_audit_results_audit` FOREIGN KEY (`workflow_audit_id`) REFERENCES `workflow_audits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_audit_results`
--

LOCK TABLES `workflow_audit_results` WRITE;
/*!40000 ALTER TABLE `workflow_audit_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_audit_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_audits`
--

DROP TABLE IF EXISTS `workflow_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workflow_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `audit_uuid` char(36) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `visitor_id` bigint(20) unsigned DEFAULT NULL,
  `audit_type` enum('preliminary','paid_diagnostic') NOT NULL DEFAULT 'preliminary',
  `parent_audit_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `industry` varchar(150) DEFAULT NULL,
  `company_size` varchar(100) DEFAULT NULL,
  `workflow_name` varchar(255) NOT NULL,
  `department` varchar(150) DEFAULT NULL,
  `workflow_description` mediumtext DEFAULT NULL,
  `status` enum('started','submitted','under_review','completed','abandoned') NOT NULL DEFAULT 'started',
  `started_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_workflow_audits_uuid` (`audit_uuid`),
  KEY `idx_workflow_audits_type` (`audit_type`),
  KEY `idx_workflow_audits_status` (`status`),
  KEY `idx_workflow_audits_user` (`user_id`),
  KEY `idx_workflow_audits_visitor` (`visitor_id`),
  KEY `idx_workflow_audits_parent` (`parent_audit_id`),
  KEY `idx_workflow_audits_order` (`order_id`),
  KEY `idx_workflow_audits_created_at` (`created_at`),
  CONSTRAINT `fk_workflow_audits_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_workflow_audits_parent` FOREIGN KEY (`parent_audit_id`) REFERENCES `workflow_audits` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_workflow_audits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_workflow_audits_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_audits`
--

LOCK TABLES `workflow_audits` WRITE;
/*!40000 ALTER TABLE `workflow_audits` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'ainchors'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
