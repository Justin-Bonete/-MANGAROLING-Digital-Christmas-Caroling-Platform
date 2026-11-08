const BASE = '/api/v1'

export class ApiError extends Error {
  status: number
  errors: Record<string, string[]>
  code?: string

  constructor(status: number, message: string, errors: Record<string, string[]> = {}, code?: string) {
    super(message)
    this.status = status
    this.errors = errors
    this.code = code
  }
}

function xsrfToken(): string | null {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)
  return match ? decodeURIComponent(match[1]) : null
}

/** Sanctum SPA flow: fetch the CSRF cookie before the first state-changing request. */
export async function ensureCsrfCookie(force = false): Promise<void> {
  if (force || !xsrfToken()) {
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
  }
}

interface RequestOptions {
  method?: string
  body?: unknown
}

export async function api<T = unknown>(path: string, options: RequestOptions = {}, retry = true): Promise<T> {
  const method = (options.method ?? 'GET').toUpperCase()
  if (method !== 'GET') await ensureCsrfCookie()

  const headers: Record<string, string> = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
  if (options.body !== undefined) headers['Content-Type'] = 'application/json'
  const token = xsrfToken()
  if (token) headers['X-XSRF-TOKEN'] = token

  const res = await fetch(`${BASE}${path}`, {
    method,
    headers,
    credentials: 'include',
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  })

  if (res.status === 419 && retry) {
    await ensureCsrfCookie(true)
    return api<T>(path, options, false)
  }
  if (res.status === 204) return undefined as T

  const payload = await res.json().catch(() => null)
  if (!res.ok) {
    throw new ApiError(res.status, payload?.message ?? `Request failed (${res.status})`, payload?.errors ?? {}, payload?.code)
  }
  return payload as T
}

/** First validation message if present, otherwise the error message. */
export function messageOf(error: unknown): string {
  if (error instanceof ApiError) {
    const first = Object.values(error.errors)[0]?.[0]
    return first ?? error.message
  }
  return error instanceof Error ? error.message : 'Something went wrong.'
}
