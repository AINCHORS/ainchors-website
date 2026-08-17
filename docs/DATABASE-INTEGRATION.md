# Database integration

Laravel connects to the existing local MySQL database `ainchors`. The database
is the schema source of truth: do not run migrations to recreate these tables.

## Model map

| Domain | Tables / Models |
| --- | --- |
| User | `users` / `User` |
| Training | `products`, `product_relations`, `course_contents`, `enrollments` / `Product`, `ProductRelation`, `CourseContent`, `Enrollment` |
| Commerce | `orders`, `order_items`, `payments` / `Order`, `OrderItem`, `Payment` |
| Workflow audit | `workflow_audits`, `workflow_audit_answers`, `workflow_audit_results` / `WorkflowAudit`, `WorkflowAuditAnswer`, `WorkflowAuditResult` |
| CRM | `leads`, `consultation_requests`, `service_engagements` / `Lead`, `ConsultationRequest`, `ServiceEngagement` |
| Analytics | `visitors`, `visitor_sessions`, `activity_events`, `privacy_consents` / `Visitor`, `VisitorSession`, `ActivityEvent`, `PrivacyConsent` |

## Service roots

- `app/Services/Courses/`
- `app/Services/Commerce/`
- `app/Services/WorkflowAudit/`
- `app/Services/CRM/`
- `app/Services/Analytics/`

Database-backed controllers and Blade modules must consume these services. A
legacy route is only replaced after its database records have been checked
against the original AINCHORS content.
