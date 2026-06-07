import { Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider, useAuth } from './auth/AuthContext';
import { ToastProvider } from './components/ui/Toast';
import { I18nProvider } from './i18n/I18nContext';
import { AppShell } from './layout/AppShell';
import { ConverterPage } from './pages/ConverterPage';
import { EditorPage } from './pages/EditorPage';
import { FilesPage } from './pages/FilesPage';
import { InfoPageView } from './pages/InfoPage';
import { LoginPage } from './pages/LoginPage';
import { ReaderPage } from './pages/ReaderPage';

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { user, loading } = useAuth();

  if (loading) {
    return <p style={{ padding: 16 }}>Loading…</p>;
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  return <>{children}</>;
}

export function App() {
  return (
    <AuthProvider>
      <I18nProvider>
        <ToastProvider>
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route
              path="/"
              element={
                <ProtectedRoute>
                  <AppShell />
                </ProtectedRoute>
              }
            >
              <Route index element={<Navigate to="/editor" replace />} />
              <Route path="editor" element={<EditorPage />} />
              <Route path="editor/:tableId" element={<EditorPage />} />
              <Route path="reader" element={<ReaderPage />} />
              <Route path="reader/:tableId" element={<ReaderPage />} />
              <Route path="files" element={<FilesPage />} />
              <Route path="converter" element={<ConverterPage />} />
              <Route path="info/:slug" element={<InfoPageView />} />
            </Route>
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </ToastProvider>
      </I18nProvider>
    </AuthProvider>
  );
}
