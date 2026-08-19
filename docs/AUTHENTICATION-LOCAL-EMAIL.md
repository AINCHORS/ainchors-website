# Authentication and local password reset email

AINCHORS uses Laravel's standard password broker with the existing
`password_reset_tokens` table. Reset requests are deliberately handled by the
framework broker; tokens are hashed by Laravel and are never written to the
application log by this project.

Local development defaults to `MAIL_MAILER=log` in `.env.example`. A successful
reset request is therefore structurally complete but writes the reset email to
the Laravel log instead of sending a real email. Configure an approved mailer
and its credentials through environment variables before enabling real email
delivery. No SMTP credentials are committed to this repository.

The test environment uses Laravel's `array` mailer. Feature tests verify the
broker notification and a valid token-based password replacement without
requiring external email delivery.
