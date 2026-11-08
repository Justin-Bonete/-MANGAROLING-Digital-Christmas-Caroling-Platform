import { useState, type FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../features/auth/useAuth'
import { authApi } from '../services/authApi'
import { messageOf } from '../services/http'

export default function ChangePasswordPage() {
  const { user, refresh, logout } = useAuth()
  const navigate = useNavigate()
  const [current, setCurrent] = useState('')
  const [next, setNext] = useState('')
  const [confirm, setConfirm] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  async function onSubmit(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    setError(null)
    try {
      await authApi.changePassword(current, next, confirm)
      await refresh()
      navigate('/', { replace: true })
    } catch (err) {
      setError(messageOf(err))
    } finally {
      setBusy(false)
    }
  }

  const input = 'w-full rounded-md border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:outline-none'

  return (
    <div className="flex min-h-screen items-center justify-center bg-stone-100 p-4">
      <form onSubmit={onSubmit} className="w-full max-w-sm space-y-4 rounded-xl bg-white p-8 shadow">
        <h1 className="text-xl font-bold text-emerald-900">Change your password</h1>
        {user?.must_change_password && (
          <p className="rounded-md bg-amber-50 p-3 text-sm text-amber-800">Your password is temporary. Please choose a new one to continue.</p>
        )}
        {error && <div role="alert" className="rounded-md bg-red-50 p-3 text-sm text-red-700">{error}</div>}

        <label className="block text-sm"><span className="mb-1 block font-medium">Current password</span>
          <input type="password" required autoComplete="current-password" value={current} onChange={(e) => setCurrent(e.target.value)} className={input} /></label>
        <label className="block text-sm"><span className="mb-1 block font-medium">New password</span>
          <input type="password" required minLength={12} autoComplete="new-password" value={next} onChange={(e) => setNext(e.target.value)} className={input} />
          <span className="mt-1 block text-xs text-stone-500">At least 12 characters with upper and lower case letters and a number.</span></label>
        <label className="block text-sm"><span className="mb-1 block font-medium">Confirm new password</span>
          <input type="password" required autoComplete="new-password" value={confirm} onChange={(e) => setConfirm(e.target.value)} className={input} /></label>

        <button disabled={busy} className="w-full rounded-md bg-emerald-700 py-2 font-semibold text-white hover:bg-emerald-800 disabled:opacity-60">
          {busy ? 'Saving…' : 'Update password'}
        </button>
        <button type="button" onClick={() => logout().then(() => navigate('/login'))} className="w-full text-sm text-stone-500 hover:underline">
          Log out
        </button>
      </form>
    </div>
  )
}
