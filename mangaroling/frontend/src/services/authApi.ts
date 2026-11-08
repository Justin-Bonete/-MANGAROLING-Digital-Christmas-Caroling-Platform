import type { AuthUser } from '../types/api'
import { api, ensureCsrfCookie } from './http'

export const authApi = {
  async login(email: string, password: string): Promise<AuthUser> {
    await ensureCsrfCookie(true)
    const res = await api<{ data: AuthUser }>('/auth/login', { method: 'POST', body: { email, password } })
    return res.data
  },
  logout: () => api<void>('/auth/logout', { method: 'POST' }),
  async me(): Promise<AuthUser> {
    return (await api<{ data: AuthUser }>('/auth/me')).data
  },
  async changePassword(current_password: string, password: string, password_confirmation: string): Promise<AuthUser> {
    const res = await api<{ data: AuthUser }>('/auth/change-password', {
      method: 'POST',
      body: { current_password, password, password_confirmation },
    })
    return res.data
  },
}
