import { NavLink, Outlet } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { fetchMenu, type MenuItem } from '../api/menu';
import { useAuth } from '../auth/AuthContext';
import { Button } from '../components/ui/Button';
import { useI18n } from '../i18n/I18nContext';
import styles from './AppShell.module.css';

export function AppShell() {
  const { user, logout } = useAuth();
  const { t, language, languages, setLanguage } = useI18n();
  const [menu, setMenu] = useState<MenuItem[]>([]);

  useEffect(() => {
    fetchMenu()
      .then(setMenu)
      .catch(() => setMenu([]));
  }, []);

  return (
    <div className={styles.shell}>
      <header className={styles.header}>
        <div className={styles.brand}>
          <strong>Dbscript</strong>
          {user ? <span className={styles.user}>{user.login}</span> : null}
        </div>
        <div className={styles.headerActions}>
          {languages.available.length > 1 ? (
            <select
              className={styles.lang}
              value={language}
              onChange={(event) => setLanguage(event.target.value)}
              aria-label="Language"
            >
              {languages.available.map((lang) => (
                <option key={lang} value={lang}>
                  {lang}
                </option>
              ))}
            </select>
          ) : null}
          {user ? (
            <Button variant="ghost" onClick={() => void logout()}>
              {t('KEY_EXIT', 'Logout')}
            </Button>
          ) : null}
        </div>
      </header>

      <div className={styles.body}>
        <aside className={styles.sidebar} aria-label="Main navigation">
          <nav className={styles.nav}>
            {menu.map((item) =>
              item.spa ? (
                <NavLink
                  key={item.id}
                  to={item.href.replace(/^\/app/, '') || '/'}
                  className={({ isActive }) => (isActive ? styles.active : undefined)}
                >
                  {item.label_key ? t(item.label_key, item.label) : item.label}
                </NavLink>
              ) : (
                <a key={item.id} href={item.href} className={styles.external}>
                  {item.label_key ? t(item.label_key, item.label) : item.label}
                </a>
              ),
            )}
          </nav>
        </aside>

        <main className={styles.main}>
          <Outlet />
        </main>
      </div>
    </div>
  );
}
