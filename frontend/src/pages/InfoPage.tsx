import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { fetchInfoPage, type InfoPage } from '../api/info';
import { useI18n } from '../i18n/I18nContext';
import styles from './PlaceholderPage.module.css';

export function InfoPageView() {
  const { slug = 'ver' } = useParams();
  const { t } = useI18n();
  const [page, setPage] = useState<InfoPage | null>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    setError('');
    fetchInfoPage(slug)
      .then(setPage)
      .catch((err: Error) => setError(err.message));
  }, [slug]);

  const title = page ? t(page.title_key, page.title) : t('MNU_4', 'Info');

  return (
    <div className={styles.page}>
      <h1>{title}</h1>
      {error ? <p className={styles.meta}>{error}</p> : null}
      {!error && page ? (
        <ul className={styles.meta}>
          {page.lines.map((line) => (
            <li key={line}>{line}</li>
          ))}
        </ul>
      ) : null}
    </div>
  );
}
