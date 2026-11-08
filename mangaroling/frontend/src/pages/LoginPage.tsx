import { useState, type FormEvent } from 'react'
import { Navigate, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../features/auth/useAuth'
import { ApiError, messageOf } from '../services/http'

export default function LoginPage() {
  const { user, loading, login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  if (!loading && user) return <Navigate to={user.must_change_password ? '/change-password' : '/'} replace />

  async function onSubmit(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    setError(null)
    try {
      const u = await login(email, password)
      const from = (location.state as { from?: string } | null)?.from
      navigate(u.must_change_password ? '/change-password' : (from ?? '/'), { replace: true })
    } catch (err) {
      setError(err instanceof ApiError && err.status === 429 ? 'Too many attempts. Please wait a minute and try again.' : messageOf(err))
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-b from-emerald-900 to-emerald-800 p-4">
      <form onSubmit={onSubmit} className="w-full max-w-sm space-y-4 rounded-xl bg-white p-8 shadow-xl">
        <div className="text-center">
          <div className="text-3xl">🎄</div>
          <h1 className="mt-1 text-xl font-bold text-emerald-900">MANGAROLING</h1>
          <p className="text-sm text-stone-500">Administrator sign in</p>
        </div>

        {error && <div role="alert" className="rounded-md bg-red-50 p-3 text-sm text-red-700">{error}</div>}

        <label className="block text-sm">
          <span className="mb-1 block font-medium">Email</span>
          <input type="email" required autoComplete="username" value={email} onChange={(e) => setEmail(e.target.value)}
            className="w-full rounded-md border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:outline-none" />
        </label>
        <label className="block text-sm">
          <span className="mb-1 block font-medium">Password</span>
          <input type="password" required autoComplete="current-password" value={password} onChange={(e) => setPassword(e.target.value)}
            className="w-full rounded-md border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:outline-none" />
        </label>
        <button disabled={busy} className="w-full rounded-md bg-emerald-700 py-2 font-semibold text-white hover:bg-emerald-800 disabled:opacity-60">
          {busy ? 'Signing in…' : 'Sign in'}
        </button>
      </form>
    </div>
  )
}
