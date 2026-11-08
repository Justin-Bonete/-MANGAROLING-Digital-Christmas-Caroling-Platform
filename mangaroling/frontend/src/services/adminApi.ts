import type { AuditLogEntry, AuthUser, Paginated, RoleInfo } from '../types/api'
import { api } from './http'

export interface CreateUserInput {
  name: string
  email: string
  password: string
  roles: string[]
}

export const usersApi = {
  list(params: { q?: string; page?: number }) {
    const qs = new URLSearchParams()
    if (params.q) qs.set('q', params.q)
    if (params.page) qs.set('page', String(params.page))
    return api<Paginated<AuthUser>>(`/users?${qs.toString()}`)
  },
  create: (input: CreateUserInput) => api<{ data: AuthUser }>('/users', { method: 'POST', body: input }),
  setActive: (id: number, is_active: boolean) =>
    api<{ data: AuthUser }>(`/users/${id}/active`, { method: 'PATCH', body: { is_active } }),
  setRoles: (id: number, roles: string[]) =>
    api<{ data: AuthUser }>(`/users/${id}/roles`, { method: 'PUT', body: { roles } }),
  resetPassword: (id: number, password: string) =>
    api<{ data: AuthUser }>(`/users/${id}/reset-password`, { method: 'POST', body: { password } }),
  roles: () => api<{ data: RoleInfo[] }>('/roles'),
}

export const auditApi = {
  list(params: { action?: string; page?: number }) {
    const qs = new URLSearchParams()
    if (params.action) qs.set('action', params.action)
    if (params.page) qs.set('page', String(params.page))
    return api<Paginated<AuditLogEntry>>(`/audit-logs?${qs.toString()}`)
  },
}
