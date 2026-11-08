import { Navigate, Route, Routes } from 'react-router-dom'
import { RequireAuth, RequirePermission } from './features/auth/guards'
import AdminLayout from './layouts/AdminLayout'
import AuditLogsPage from './pages/AuditLogsPage'
import ChangePasswordPage from './pages/ChangePasswordPage'
import DashboardPage from './pages/DashboardPage'
import LoginPage from './pages/LoginPage'
import UsersPage from './pages/UsersPage'

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route
        path="/change-password"
        element={
          <RequireAuth allowPasswordChange>
            <ChangePasswordPage />
          </RequireAuth>
        }
      />
      <Route
        element={
          <RequireAuth>
            <AdminLayout />
          </RequireAuth>
        }
      >
        <Route index element={<DashboardPage />} />
        <Route
          path="users"
          element={
            <RequirePermission permission="users.manage">
              <UsersPage />
            </RequirePermission>
          }
        />
        <Route
          path="audit-logs"
          element={
            <RequirePermission permission="audit_logs.view">
              <AuditLogsPage />
            </RequirePermission>
          }
        />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
