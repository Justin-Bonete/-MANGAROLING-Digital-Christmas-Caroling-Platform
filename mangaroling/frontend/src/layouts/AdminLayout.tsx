import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useAuth } from '../features/auth/useAuth'

const NAV = [
  { to: '/', label: 'Dashboard', permission: null, end: true },
  { to: '/users', label: 'Users', permission: 'users.manage', end: false },
  { to: '/audit-logs', label: 'Audit Logs', permission: 'audit_logs.view', end: false },
]

export default function AdminLayout() {
  const { user, logout, can } = useAuth()
  const navigate = useNavigate()

  async function handleLogout() {
    await logout()
    navigate('/login', { replace: true })
  }

  return (
    <div className="flex min-h-screen">
      <aside className="w-56 shrink-0 bg-emerald-900 p-4 text-emerald-50">
        <div className="mb-6 text-lg font-bold tracking-wide">🎄 MANGAROLING</div>
        <nav className="space-y-1">
          {NAV.filter((n) => !n.permission || can(n.permission)).map((n) => (
            <NavLink
              key={n.to}
              to={n.to}
              end={n.end}
              className={({ isActive }) =>
                `block rounded-md px-3 py-2 text-sm ${isActive ? 'bg-amber-400 font-semibold text-emerald-950' : 'hover:bg-emerald-800'}`
              }
            >
              {n.label}
            </NavLink>
          ))}
        </nav>
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="flex items-center justify-end gap-4 border-b border-stone-200 bg-white px-6 py-3">
          <div className="text-right text-sm">
            <div className="font-medium">{user?.name}</div>
            <div className="text-xs text-stone-500">{user?.roles.join(', ').replaceAll('_', ' ') || 'no role'}</div>
          </div>
          <button onClick={handleLogout} className="rounded-md border border-stone-300 px-3 py-1.5 text-sm hover:bg-stone-100">
            Log out
          </button>
        </header>
        <main className="flex-1 p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
