import { useAuth } from '../features/auth/useAuth'

export default function DashboardPage() {
  const { user } = useAuth()
  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold text-emerald-900">Welcome, {user?.name} 🎅</h1>
      <p className="text-stone-600">
        You are signed in as <strong>{user?.roles.join(', ').replaceAll('_', ' ') || 'no role assigned'}</strong>.
      </p>
      <div className="rounded-lg border border-stone-200 bg-white p-4">
        <h2 className="mb-2 text-sm font-semibold uppercase tracking-wide text-stone-500">Your permissions</h2>
        {user && user.permissions.length > 0 ? (
          <ul className="flex flex-wrap gap-2">
            {user.permissions.map((p) => (
              <li key={p} className="rounded-full bg-emerald-50 px-3 py-1 text-xs text-emerald-800">{p}</li>
            ))}
          </ul>
        ) : (
          <p className="text-sm text-stone-500">No permissions. Ask a system administrator to assign a role.</p>
        )}
      </div>
    </div>
  )
}
