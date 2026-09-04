# AINCHORS V1 Production Hosting Design

Date: 2026-09-04
Status: Approved architecture pending user review of written spec

## Goal

Deploy the existing AINCHORS Laravel application to a dedicated company Mac mini M4 Pro 48GB as a production-grade, always-on staging/UAT environment with no new monthly hosting fee. V1 explicitly excludes AI services. The public UAT hostname is `staging.demoainchors.com`; the existing `ainchors.com` production website and DNS must remain untouched.

## Scope

V1 includes the current Laravel website, authentication, admin, courses, checkout, Stripe Sandbox, PayPal Sandbox, purchase history, protected learning content, database, queue/scheduler runtime, backups, health checks, restart behavior, monitoring basics, and Cloudflare public ingress.

V1 does not include Ollama, AI Assistant, AI agents, workflow orchestration, vector databases, or AI audit features.

## Host Architecture

The Mac mini is a dedicated 24/7 server and may also host other projects later. The macOS host remains minimal and contains only host-level infrastructure:

- Colima as the container runtime layer using Apple Silicon ARM64 and Apple virtualization.
- Docker CLI / Compose-compatible tooling.
- Native `cloudflared` for public ingress.
- Host monitoring and backup automation.

AINCHORS application services run as containers. Production application services must not be installed directly into macOS with Homebrew unless they are host-level infrastructure.

## Application Stack

The AINCHORS V1 stack consists of:

- Nginx reverse proxy / web server.
- PHP-FPM compatible with Laravel 13 and the repository's PHP requirements.
- Laravel application image built from a tested Git commit.
- MariaDB/MySQL database on a private container network.
- Dedicated Laravel queue worker.
- Dedicated Laravel scheduler process.

Redis is intentionally excluded from V1 because the current application uses file sessions/cache and synchronous queues. Redis may be introduced later as a separate, tested application change.

## Container Design

The deployment must use native `linux/arm64` images where available and must not depend on x86/amd64 emulation.

Application source code is baked into immutable/versioned images for deployment. Production must not depend on a long-lived macOS bind mount for the Laravel source tree.

Database storage uses managed container volumes rather than ordinary project-folder bind mounts.

Services must use explicit health checks, restart policies, private networks, predictable service names, and resource limits appropriate for sharing the host with future projects.

Containers must not run as root where the base image and service allow a non-root runtime.

## Environment Isolation

V1 initially deploys one UAT/staging environment at `staging.demoainchors.com` using Sandbox payments.

The architecture must permit a later separate production environment without sharing:

- database,
- credentials,
- environment files,
- application storage,
- payment secrets,
- container network,
- service names.

The future production hostname may be `ainchors.com`, but V1 must not alter the current `ainchors.com` site or DNS.

## Networking and Public Ingress

Public traffic path:

`Internet -> Cloudflare -> Named Tunnel -> native cloudflared on macOS -> localhost-only published Nginx port -> Laravel containers`

No router port forwarding is required for the web application.

MariaDB, PHP-FPM, internal application ports, and future private services must never be exposed directly to the public Internet.

The custom public UAT hostname is:

`https://staging.demoainchors.com`

This requires administrative control of the `demoainchors.com` DNS zone and the ability to add it to or manage it through the Cloudflare account used for the Named Tunnel.

## Laravel Runtime Configuration

The deployed environment uses production-safe Laravel settings even though it is UAT:

- `APP_ENV=production` or an explicitly supported staging environment with production-safe behavior.
- `APP_DEBUG=false`.
- `APP_URL=https://staging.demoainchors.com`.
- Dedicated non-root database application user.
- Sandbox payment configuration only.
- No secrets committed to GitHub or baked into images.
- Config, route, and view caching enabled when compatible with the application.
- Writable runtime directories limited to the required Laravel storage/cache paths.

The current repository's behavior is preserved; deployment must not silently redesign payment or application business logic.

## Payment UAT

Stripe remains Sandbox/Test mode and PayPal remains Sandbox mode during V1.

Public webhook endpoints use the fixed UAT hostname:

- `https://staging.demoainchors.com/payments/stripe/webhook`
- `https://staging.demoainchors.com/payments/paypal/webhook`

Provider secrets and webhook signing values are stored only on the server in protected environment configuration.

No Live credentials or real-money payment tests are part of V1.

## Deployment Model

Deployment flow:

1. Select a tested Git commit.
2. Build a versioned ARM64 application image.
3. Install Composer production dependencies without dev packages.
4. Build frontend assets.
5. Start/update containers through Compose.
6. Run safe database migrations explicitly.
7. Warm Laravel caches.
8. Verify health checks and application endpoints.
9. Keep the previous deployable image available for rollback.

Deployments must not rely on ad-hoc edits inside running containers.

## Backup and Recovery

Backups are mandatory before the environment is considered production-grade.

Minimum protected data:

- MariaDB database dumps/backups.
- Laravel persistent storage/uploads.
- deployment configuration excluding unnecessary credential duplication.

Backup policy must provide automated retention and at least one copy outside the active application/database volume. If available, use an external SSD, NAS, or second company machine.

A backup is not considered valid until a restore test succeeds.

## Availability and Restart Behavior

The environment must recover automatically after host reboot without manual container-by-container intervention.

Validation includes intentionally rebooting the Mac mini and verifying:

- container runtime returns,
- database becomes healthy,
- application containers restart,
- queue/scheduler restart,
- cloudflared reconnects,
- `staging.demoainchors.com` becomes reachable again.

Because this is a single physical host, Mac mini power, storage, office Internet, and router connectivity remain single points of failure. This is an accepted V1 trade-off for zero new monthly hosting cost.

## Security Baseline

- Keep macOS and server tooling patched.
- Use a dedicated server account and least privilege where practical.
- Disable unnecessary sharing/listening services.
- Prefer wired Ethernet.
- Do not expose database or container management sockets publicly.
- Do not store secrets in Git.
- Keep application debug output disabled publicly.
- Preserve Laravel CSRF/auth/payment verification protections.
- Use Cloudflare only as public ingress; origin services remain private.

## Verification Gate

V1 is not complete until all of the following are verified:

- Existing Laravel full regression suite remains green on the deployment commit.
- Frontend production build succeeds.
- Staging site is reachable over HTTPS at `staging.demoainchors.com`.
- Login, admin, courses, checkout, purchase history, and protected content work.
- Stripe Sandbox end-to-end flow and webhook verification work.
- PayPal Sandbox invoicing/payment flow and webhook verification work.
- Database is not publicly reachable.
- Host reboot recovery succeeds.
- Backup restore succeeds.

## Implementation Order

1. Mac mini host baseline and security settings.
2. Install and validate Colima/container tooling.
3. Add production container definitions and image build.
4. Configure Nginx/PHP-FPM/MariaDB/queue/scheduler.
5. Prepare protected staging environment variables.
6. Import/initialize application data and run database checks.
7. Run application regression/build checks.
8. Configure Cloudflare Named Tunnel for `staging.demoainchors.com`.
9. Configure Stripe and PayPal Sandbox webhooks.
10. Run UAT validation.
11. Configure automated backups.
12. Perform reboot/recovery test.
13. Perform restore test.

## Explicit Non-Goals

- No AI/Ollama work in V1.
- No move of `ainchors.com` production traffic.
- No Live Stripe/PayPal credentials.
- No VPS or paid hosting subscription.
- No Kubernetes.
- No unnecessary Redis/vector/agent infrastructure.
- No application business-logic refactor merely for deployment.
