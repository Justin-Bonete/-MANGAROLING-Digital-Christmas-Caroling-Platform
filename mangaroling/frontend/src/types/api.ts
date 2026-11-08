export interface AuthUser {
  id: number
  name: string
  email: string
  is_active: boolean
  organization_id: number | null
  must_change_password: boolean
  last_login_at: string | null
  created_at: string
  roles: string[]
  permissions: string[]
}

export interface RoleInfo {
  name: string
  description: string | null
  permissions: string[]
}

export interface PageMeta {
  current_page: number
  last_page: number
  total: number
}

export interface Paginated<T> {
  data: T[]
  meta: PageMeta
}

export interface AuditLogEntry {
  id: number
  action: string
  entity_type: string
  entity_id: number | null
  details: Record<string, unknown> | null
  created_at: string
  user: { id: number; name: string; email: string } | null
}
