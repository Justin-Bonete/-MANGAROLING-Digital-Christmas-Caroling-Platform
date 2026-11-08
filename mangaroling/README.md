# MANGAROLING

Digital Christmas caroling + voluntary donation management (Laravel / React+TS / PostgreSQL / Redis / Docker).

## Status
- **Phase 1** Foundation: Docker Compose, Dockerfile, nginx, env templates, bootstrap script. ✅
- **Phase 2** Database: 6 migrations, enums, 15 models, factories, seeders, DB-rule tests. ✅ (not yet executed; see below)
- **Phase 3** Authentication & authorization: Sanctum SPA login, roles to permissions, account management, audit logging, React login/users/audit pages. ✅ (not yet executed; see below)
- Phases 4+ (campaigns, recipients, invitations, ...) not started.

## Setup
1. `cp .env.example .env` and set `DB_PASSWORD`.
2. `./scripts/bootstrap.sh` (generates Laravel + Vite skeletons, overlays this repo's files).
3. In `backend/.env` set `DB_PASSWORD` (same value) and `SEED_ADMIN_PASSWORD`.
4. `docker compose up -d`
5. `docker compose exec backend php artisan migrate --seed`
6. Tests (create the test DB once): 
   `docker compose exec postgres createdb -U mangaroling mangaroling_test`
   `docker compose exec backend php artisan test`
7. App: http://localhost:8080 (API), http://localhost:5173 (frontend).

## Design decisions (resolving spec gaps)
| Gap | Decision |
|---|---|
| Recipient email uniqueness | Unique `(organization_id, lower(email))` |
| Resending invitations | Only hashes stored, so resend = revoke old + create new invitation. One live invitation per campaign recipient (partial unique index) |
| Opt-out / suppression | `campaign_recipients.opted_out_at` + org-level `suppressions` table |
| Payment history (BR-015) | `donations.payment_account_snapshot` JSONB + `payment_method` |
| Duplicate donations (BR-014) | `unique(invitation_id, idempotency_key)` and `unique(payment_method, reference_number)` |
| Immutable history (BR-011) | PostgreSQL triggers block UPDATE/DELETE on `donation_verification_logs` and `audit_logs` |
| Verified donations | CHECK: non-pending rows must have `verified_by` and `verified_at` |
| Multi-org | v1 is single-org in practice; `organization_id` is on users, campaigns, recipients, suppressions so it can scale later |
| Token hash | `CHAR(64)` SHA-256 hex (high-entropy random token, so a fast hash is appropriate) |

## Phase 3 notes
- API reference: `docs/phase3-auth-api.md`.
- First login: the seeded admin has `must_change_password=true`; you will be sent to the change-password screen.
- Run the new tests: `docker compose exec backend php artisan test --filter='Authentication|Authorization|UserManagement'`
- Production: set `SESSION_SECURE_COOKIE=true`, serve over HTTPS, and set `SANCTUM_STATEFUL_DOMAINS` / `FRONTEND_URL` to the real frontend host.
- Known v1 limits: no password-reset-by-email (needs the Phase 5 mail pipeline), no 2FA, audit log is not org-scoped (single-organization v1).
