import type { ReactNode } from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from './useAuth'

/** Requires a signed-in user; forces the temporary-password change first. */
export function RequireAuth({ children, allowPasswordChange = false }: { children: ReactNode; allowPasswordChange?: boolean }) {
  const { user, loading } = useAuth()
  const location = useLocation()

  if (loading) return <p className="p-8 text-center text-stone-500">Loading…</p>
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />
  if (user.must_change_password && !allowPasswordChange) return <Navigate to="/change-password" replace />
  return <>{children}</>
}

/** UI-level convenience only. The API enforces permissions regardless of what the UI shows. */
export function RequirePermission({ permission, children }: { permission: string; children: ReactNode }) {
  const { can } = useAuth()
  if (!can(permission)) {
    return (
      <div className="rounded-lg border border-red-200 bg-red-50 p-6 text-red-800">
        <h1 className="text-lg font-semibold">Access denied</h1>
        <p className="mt-1 text-sm">Your role does not include access to this page.</p>
      </div>
    )
  }
  return <>{children}</>
}
