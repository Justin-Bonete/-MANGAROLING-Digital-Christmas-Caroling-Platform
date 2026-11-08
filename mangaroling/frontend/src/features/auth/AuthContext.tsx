import { createContext, useCallback, useEffect, useMemo, useState, type ReactNode } from 'react'
import { authApi } from '../../services/authApi'
import { ApiError } from '../../services/http'
import type { AuthUser } from '../../types/api'

export interface AuthState {
  user: AuthUser | null
  loading: boolean
  login: (email: string, password: string) => Promise<AuthUser>
  logout: () => Promise<void>
  refresh: () => Promise<void>
  can: (permission: string) => boolean
}

// eslint-disable-next-line react-refresh/only-export-components
export const AuthContext = createContext<AuthState | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null)
  const [loading, setLoading] = useState(true)

  const refresh = useCallback(async () => {
    try {
      setUser(await authApi.me())
    } catch (e) {
      // 401 = not signed in; 403 account_inactive = treated as signed out.
      if (e instanceof ApiError && (e.status === 401 || e.status === 403)) setUser(null)
      else throw e
    }
  }, [])

  useEffect(() => {
    refresh()
      .catch(() => setUser(null))
      .finally(() => setLoading(false))
  }, [refresh])

  const value = useMemo<AuthState>(
    () => ({
      user,
      loading,
      refresh,
      login: async (email, password) => {
        const u = await authApi.login(email, password)
        setUser(u)
        return u
      },
      logout: async () => {
        try {
          await authApi.logout()
        } finally {
          setUser(null)
        }
      },
      can: (permission) => !!user?.permissions.includes(permission),
    }),
    [user, loading, refresh],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
