import { useCallback, useEffect, useState } from 'react'
import { auditApi } from '../services/adminApi'
import { messageOf } from '../services/http'
import type { AuditLogEntry } from '../types/api'

export default function AuditLogsPage() {
  const [rows, setRows] = useState<AuditLogEntry[]>([])
  const [filter, setFilter] = useState('')
  const [action, setAction] = useState('')
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async () => {
    try {
      const res = await auditApi.list({ action, page })
      setRows(res.data)
      setLastPage(res.meta.last_page)
      setError(null)
    } catch (e) {
      setError(messageOf(e))
    }
  }, [action, page])

  useEffect(() => { void load() }, [load])

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-bold text-emerald-900">Audit Logs</h1>
        <form onSubmit={(e) => { e.preventDefault(); setPage(1); setAction(filter) }} className="flex gap-2">
          <input value={filter} onChange={(e) => setFilter(e.target.value)} placeholder="Action prefix, e.g. auth. or user."
            className="rounded-md border border-stone-300 px-3 py-2 text-sm" />
          <button className="rounded-md border border-stone-300 px-3 text-sm hover:bg-stone-100">Filter</button>
        </form>
      </div>
      {error && <div role="alert" className="rounded-md bg-red-50 p-3 text-sm text-red-700">{error}</div>}

      <div className="overflow-x-auto rounded-lg border border-stone-200 bg-white">
        <table className="w-full text-left text-sm">
          <thead className="bg-stone-100 text-xs uppercase text-stone-500">
            <tr><th className="p-3">When</th><th className="p-3">Actor</th><th className="p-3">Action</th><th className="p-3">Entity</th><th className="p-3">IP</th></tr>
          </thead>
          <tbody>
            {rows.length === 0 && <tr><td colSpan={5} className="p-6 text-center text-stone-500">No audit entries.</td></tr>}
            {rows.map((r) => (
              <tr key={r.id} className="border-t border-stone-100">
                <td className="whitespace-nowrap p-3">{new Date(r.created_at).toLocaleString()}</td>
                <td className="p-3">{r.user ? r.user.name : <span className="text-stone-400">anonymous</span>}</td>
                <td className="p-3 font-mono text-xs">{r.action}</td>
                <td className="p-3">{r.entity_type}{r.entity_id ? ` #${r.entity_id}` : ''}</td>
                <td className="p-3 text-stone-500">{String(r.details?.ip ?? '')}</td>
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
    </div>
  )
}
