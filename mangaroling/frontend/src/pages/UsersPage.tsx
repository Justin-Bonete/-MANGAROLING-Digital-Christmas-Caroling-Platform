import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { usersApi } from '../services/adminApi'
import { messageOf } from '../services/http'
import { useAuth } from '../features/auth/useAuth'
import type { AuthUser, RoleInfo } from '../types/api'

type Panel = { mode: 'create' } | { mode: 'roles'; user: AuthUser } | { mode: 'password'; user: AuthUser } | null

const label = (role: string) => role.replaceAll('_', ' ')
const field = 'w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none'

function RoleChecklist({ roles, selected, onChange }: { roles: RoleInfo[]; selected: string[]; onChange: (v: string[]) => void }) {
  return (
    <div className="space-y-2">
      {roles.map((r) => (
        <label key={r.name} className="flex items-start gap-2 text-sm">
          <input type="checkbox" className="mt-1" checked={selected.includes(r.name)}
            onChange={(e) => onChange(e.target.checked ? [...selected, r.name] : selected.filter((n) => n !== r.name))} />
          <span><span className="font-medium capitalize">{label(r.name)}</span>
            {r.description && <span className="block text-xs text-stone-500">{r.description}</span>}</span>
        </label>
      ))}
    </div>
  )
}

function SidePanel({ panel, roles, onClose, onDone }: { panel: NonNullable<Panel>; roles: RoleInfo[]; onClose: () => void; onDone: () => void }) {
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [selected, setSelected] = useState<string[]>(panel.mode === 'roles' ? panel.user.roles : [])
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  async function submit(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    setError(null)
    try {
      if (panel.mode === 'create') await usersApi.create({ name, email, password, roles: selected })
      else if (panel.mode === 'roles') await usersApi.setRoles(panel.user.id, selected)
      else await usersApi.resetPassword(panel.user.id, password)
      onDone()
    } catch (err) {
      setError(messageOf(err))
    } finally {
      setBusy(false)
    }
  }

  const title = panel.mode === 'create' ? 'New user' : panel.mode === 'roles' ? `Roles: ${panel.user.name}` : `Reset password: ${panel.user.name}`

  return (
    <form onSubmit={submit} className="w-80 shrink-0 space-y-4 self-start rounded-lg border border-stone-200 bg-white p-4">
      <div className="flex items-center justify-between">
        <h2 className="font-semibold">{title}</h2>
        <button type="button" onClick={onClose} aria-label="Close" className="text-stone-400 hover:text-stone-700">✕</button>
      </div>
      {error && <div role="alert" className="rounded-md bg-red-50 p-2 text-sm text-red-700">{error}</div>}

      {panel.mode === 'create' && (
        <>
          <input className={field} placeholder="Full name" required value={name} onChange={(e) => setName(e.target.value)} />
          <input className={field} placeholder="Email" type="email" required value={email} onChange={(e) => setEmail(e.target.value)} />
        </>
      )}
      {panel.mode !== 'roles' && (
        <div>
          <input className={field} placeholder="Temporary password" type="password" required minLength={12} autoComplete="new-password"
            value={password} onChange={(e) => setPassword(e.target.value)} />
          <p className="mt-1 text-xs text-stone-500">The user must change it at next sign-in.</p>
        </div>
      )}
      {panel.mode !== 'password' && <RoleChecklist roles={roles} selected={selected} onChange={setSelected} />}

      <button disabled={busy || (panel.mode !== 'password' && selected.length === 0)}
        className="w-full rounded-md bg-emerald-700 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-60">
        {busy ? 'Saving…' : 'Save'}
      </button>
    </form>
  )
}

export default function UsersPage() {
  const { user: me } = useAuth()
  const [rows, setRows] = useState<AuthUser[]>([])
  const [roles, setRoles] = useState<RoleInfo[]>([])
  const [search, setSearch] = useState('')
  const [q, setQ] = useState('')
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [error, setError] = useState<string | null>(null)
  const [panel, setPanel] = useState<Panel>(null)

  const load = useCallback(async () => {
    try {
      const res = await usersApi.list({ q, page })
      setRows(res.data)
      setLastPage(res.meta.last_page)
      setError(null)
    } catch (e) {
      setError(messageOf(e))
    }
  }, [q, page])

  useEffect(() => { void load() }, [load])
  useEffect(() => { usersApi.roles().then((r) => setRoles(r.data)).catch(() => undefined) }, [])

  async function toggleActive(u: AuthUser) {
    if (u.is_active && !window.confirm(`Deactivate ${u.name}? They will be signed out immediately.`)) return
    try {
      await usersApi.setActive(u.id, !u.is_active)
      await load()
    } catch (e) {
      setError(messageOf(e))
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-bold text-emerald-900">Users</h1>
        <div className="flex gap-2">
          <form onSubmit={(e) => { e.preventDefault(); setPage(1); setQ(search) }} className="flex gap-2">
            <input className={field} placeholder="Search name or email" value={search} onChange={(e) => setSearch(e.target.value)} />
            <button className="rounded-md border border-stone-300 px-3 text-sm hover:bg-stone-100">Search</button>
          </form>
          <button onClick={() => setPanel({ mode: 'create' })} className="rounded-md bg-emerald-700 px-3 text-sm font-semibold text-white hover:bg-emerald-800">+ New user</button>
        </div>
      </div>

      {error && <div role="alert" className="rounded-md bg-red-50 p-3 text-sm text-red-700">{error}</div>}

      <div className="flex gap-4">
        <div className="min-w-0 flex-1 overflow-x-auto rounded-lg border border-stone-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="bg-stone-100 text-xs uppercase text-stone-500">
              <tr><th className="p-3">Name</th><th className="p-3">Roles</th><th className="p-3">Status</th><th className="p-3 text-right">Actions</th></tr>
            </thead>
            <tbody>
              {rows.length === 0 && <tr><td colSpan={4} className="p-6 text-center text-stone-500">No users found.</td></tr>}
              {rows.map((u) => (
                <tr key={u.id} className="border-t border-stone-100">
                  <td className="p-3"><div className="font-medium">{u.name}</div><div className="text-xs text-stone-500">{u.email}</div></td>
                  <td className="p-3 capitalize">{u.roles.map(label).join(', ') || '—'}</td>
                  <td className="p-3">
                    <span className={`rounded-full px-2 py-0.5 text-xs ${u.is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-200 text-stone-600'}`}>
                      {u.is_active ? 'Active' : 'Inactive'}
                    </span>
                    {u.must_change_password && <span className="ml-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-800">Temp password</span>}
                  </td>
                  <td className="space-x-2 p-3 text-right">
                    <button onClick={() => setPanel({ mode: 'roles', user: u })} className="text-emerald-700 hover:underline">Roles</button>
                    <button onClick={() => setPanel({ mode: 'password', user: u })} className="text-emerald-700 hover:underline">Reset password</button>
                    {u.id !== me?.id && (
                      <button onClick={() => toggleActive(u)} className="text-red-600 hover:underline">{u.is_active ? 'Deactivate' : 'Activate'}</button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className="flex items-center justify-between border-t border-stone-100 p-3 text-sm">
            <button disabled={page <= 1} onClick={() => setPage(page - 1)} className="disabled:opacity-40">← Previous</button>
            <span className="text-stone-500">Page {page} of {lastPage}</span>
            <button disabled={page >= lastPage} onClick={() => setPage(page + 1)} className="disabled:opacity-40">Next →</button>
          </div>
        </div>

        {panel && (
          <SidePanel key={panel.mode + ('user' in panel ? panel.user.id : '')} panel={panel} roles={roles}
            onClose={() => setPanel(null)} onDone={() => { setPanel(null); void load() }} />
        )}
      </div>
    </div>
  )
}
