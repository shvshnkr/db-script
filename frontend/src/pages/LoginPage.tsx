import { FormEvent, useState } from 'react';
import { Navigate } from 'react-router-dom';
import { ApiClientError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { useI18n } from '../i18n/I18nContext';
import styles from './LoginPage.module.css';

export function LoginPage() {
  const { user, loading, login } = useAuth();
  const { t } = useI18n();
  const [loginName, setLoginName] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  if (!loading && user) {
    return <Navigate to="/" replace />;
  }

  const onSubmit = async (event: FormEvent) => {
    event.preventDefault();
    setError('');
    setSubmitting(true);

    try {
      await login(loginName.trim(), password);
    } catch (err) {
      if (err instanceof ApiClientError) {
        setError(err.message);
      } else {
        setError(t('KEY_AUTH_FAIL', 'Login failed. Please try again.'));
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className={styles.page}>
      <form className={styles.card} onSubmit={onSubmit}>
        <h1>Dbscript</h1>
        <p className={styles.subtitle}>{t('ENTER', 'Sign in to continue')}</p>

        <Input
          label={t('KEY_LOGIN', 'Login')}
          name="login"
          autoComplete="username"
          value={loginName}
          onChange={(event) => setLoginName(event.target.value)}
          disabled={submitting}
        />

        <Input
          label={t('KEY_PASS', 'Password')}
          name="password"
          type="password"
          autoComplete="current-password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          disabled={submitting}
          error={error}
        />

        <Button type="submit" loading={submitting} className={styles.submit}>
          {t('ENTER', 'Sign in')}
        </Button>
      </form>
    </div>
  );
}
