# Phase 3 – Authentication & Authorization API

Base URL `/api/v1`. All responses are JSON. Auth is **Sanctum SPA cookie sessions**:
1. `GET /sanctum/csrf-cookie` (sets `XSRF-TOKEN`)
2. `POST /api/v1/auth/login`, then send `X-XSRF-TOKEN` on every state-changing request.
Requests must originate from a host in `SANCTUM_STATEFUL_DOMAINS` (the Vite dev server proxies `/api` and `/sanctum`, so the browser sees one origin).

## Endpoints
| Method | Path | Access | Notes |
|---|---|---|---|
| POST | `/auth/login` | public, `throttle:login` (5/min per email+IP, 20/min per IP) | 422 for any failure (same message for unknown/inactive/wrong password) |
| POST | `/auth/logout` | authenticated | 204 |
| GET | `/auth/me` | authenticated | user + roles + permissions |
| POST | `/auth/change-password` | authenticated | `current_password`, `password`, `password_confirmation`; clears `must_change_password`, revokes API tokens |
| GET | `/roles` | `users.manage` | roles and their permissions |
| GET | `/users` | `users.manage` | `?q=&role=&is_active=&page=`; scoped to caller's organization |
| POST | `/users` | `users.manage` | `name,email,password,roles[]`; user gets `must_change_password=true` |
| GET/PUT | `/users/{id}` | `users.manage` | PUT: `name`, `email` |
| PATCH | `/users/{id}/active` | `users.manage` | `is_active`; cannot deactivate self; revokes tokens |
| PUT | `/users/{id}/roles` | `users.manage` | `roles[]` (min 1); cannot remove own admin role |
| POST | `/users/{id}/reset-password` | `users.manage` | temporary password, forces change |
| GET | `/audit-logs` | `audit_logs.view` | `?action=prefix&entity_type=&user_id=&from=&to=&page=` |

## Error codes (`code` field)
`stateful_required` (400), `account_inactive` (403), `password_change_required` (403).
Other statuses: 401 unauthenticated, 403 forbidden, 404 (also for other-organization users), 419 CSRF, 422 validation, 429 throttled.

## Roles → permissions (`App\Support\RolePermissions`)
- **system_admin**: users.manage, settings.manage, organizations.manage, audit_logs.view, system.monitor
- **campaign_manager**: campaigns.manage, recipients.manage, invitations.manage, music.manage, reports.campaigns
- **finance_officer**: donations.view, donations.review, proofs.view, reports.financial

Permissions are Gate abilities. Use `->middleware('can:campaigns.manage')` on routes in later phases. A user with several roles gets the union. Inactive users have no permissions.

## Audit actions written in this phase
`auth.login`, `auth.login_failed` (reason: invalid_credentials | inactive), `auth.logout`, `auth.password_changed`,
`user.created`, `user.updated`, `user.activated`, `user.deactivated`, `user.roles_changed`, `user.password_reset`.
Passwords and tokens are never logged.
