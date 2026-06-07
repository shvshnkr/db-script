import { NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { Button } from '../components/ui/Button';
import styles from './AppShell.module.css';

export function AppShell() {
  const { user, logout } = useAuth();

  return (
    <div className={styles.shell}>
      <header className={styles.header}>
        <div className={styles.brand}>
          <strong>Dbscript</strong>
          {user ? <span className={styles.user}>{user.login}</span> : null}
        </div>
        {user ? (
          <Button variant="ghost" onClick={() => void logout()}>
            Logout
          </Button>
        ) : null}
      </header>

      <div className={styles.body}>
        <aside className={styles.sidebar} aria-label="Main navigation">
          <nav className={styles.nav}>
            <NavLink to="/editor" className={({ isActive }) => (isActive ? styles.active : undefined)}>
              Editor
            </NavLink>
            <NavLink to="/files" className={({ isActive }) => (isActive ? styles.active : undefined)}>
              Files
            </NavLink>
            <a href="/admin-arch.php" className={styles.external}>
              Admin (SSR)
            </a>
          </nav>
        </aside>

        <main className={styles.main}>
          <Outlet />
        </main>
      </div>
    </div>
  );
}
