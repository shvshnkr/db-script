import { useEffect, useState } from 'react';
import {
  fetchConverterTables,
  previewConversion,
  runConversion,
  type ConverterPreview,
  type ConverterResult,
  type ConverterTable,
} from '../api/converter';
import { Button } from '../components/ui/Button';
import { useToast } from '../components/ui/Toast';
import { useI18n } from '../i18n/I18nContext';
import styles from './ConverterPage.module.css';

function tableLabel(table: ConverterTable): string {
  const name = table.visual_name || table.mysql_table || String(table.id);
  return `${name} (${table.engine})`;
}

export function ConverterPage() {
  const toast = useToast();
  const { t } = useI18n();

  const [tables, setTables] = useState<ConverterTable[]>([]);
  const [sourceId, setSourceId] = useState('');
  const [destId, setDestId] = useState('');
  const [preview, setPreview] = useState<ConverterPreview | null>(null);
  const [result, setResult] = useState<ConverterResult | null>(null);
  const [rewrite, setRewrite] = useState(true);
  const [useSemicolon, setUseSemicolon] = useState(false);
  const [uniqueId, setUniqueId] = useState(false);
  const [verbose, setVerbose] = useState(true);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    fetchConverterTables()
      .then((items) => {
        setTables(items);
        const mysql = items.find((item) => item.engine === 'mysql');
        const fdb = items.find((item) => item.engine === 'fdb');
        if (mysql) {
          setSourceId(String(mysql.id));
        }
        if (fdb) {
          setDestId(String(fdb.id));
        }
      })
      .catch((err: Error) => toast.show(err.message, 'error'));
  }, [toast]);

  const handlePreview = async () => {
    if (!sourceId || !destId) {
      return;
    }

    setBusy(true);
    setResult(null);
    try {
      const data = await previewConversion(Number(sourceId), Number(destId));
      setPreview(data);
    } catch (err) {
      setPreview(null);
      toast.show(err instanceof Error ? err.message : 'Preview failed', 'error');
    } finally {
      setBusy(false);
    }
  };

  const handleConvert = async () => {
    if (!sourceId || !destId) {
      return;
    }

    setBusy(true);
    try {
      const data = await runConversion(Number(sourceId), Number(destId), {
        rewrite,
        use_semicolon: useSemicolon,
        unique_id: uniqueId,
        verbose,
      });
      setPreview(data);
      setResult(data);
      toast.show(
        `${t('A_CONV', 'Transformation')}: ${data.affected} ${t('A_CONV', 'rows')}`,
        'success',
      );
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'Conversion failed', 'error');
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className={styles.page}>
      <header className={styles.top}>
        <h1>{t('A_IMPEXP', 'Import Export')}</h1>
        <p className={styles.meta}>{t('A_CONV_HELP', 'Convert between FDB (CSV) and MySQL tables.')}</p>
      </header>

      <div className={styles.form}>
        <div className={styles.row}>
          <label htmlFor="conv-source">{t('A_CONV_SRC', 'Source')}</label>
          <select
            id="conv-source"
            className={styles.picker}
            value={sourceId}
            onChange={(event) => {
              setSourceId(event.target.value);
              setPreview(null);
              setResult(null);
            }}
          >
            <option value="">{t('KEY_HEAD', 'Select table…')}</option>
            {tables.map((table) => (
              <option key={table.id} value={table.id}>
                {tableLabel(table)}
              </option>
            ))}
          </select>
        </div>

        <div className={styles.row}>
          <label htmlFor="conv-dest">{t('A_CONV_DEST', 'Destination')}</label>
          <select
            id="conv-dest"
            className={styles.picker}
            value={destId}
            onChange={(event) => {
              setDestId(event.target.value);
              setPreview(null);
              setResult(null);
            }}
          >
            <option value="">{t('KEY_HEAD', 'Select table…')}</option>
            {tables.map((table) => (
              <option key={table.id} value={table.id}>
                {tableLabel(table)}
              </option>
            ))}
          </select>
        </div>

        <div className={styles.options}>
          <label className={styles.option}>
            <input type="checkbox" checked={rewrite} onChange={(event) => setRewrite(event.target.checked)} />
            {t('A_CONV_SETREWR', 'Rewrite instead of append')}
          </label>
          <label className={styles.option}>
            <input type="checkbox" checked={useSemicolon} onChange={(event) => setUseSemicolon(event.target.checked)} />
            {t('USECOMMA2X', 'Use semicolon separator')}
          </label>
          <label className={styles.option}>
            <input type="checkbox" checked={uniqueId} onChange={(event) => setUniqueId(event.target.checked)} />
            {t('A_CONV_SETUID', 'Create unique ID (csv→sql)')}
          </label>
          <label className={styles.option}>
            <input type="checkbox" checked={verbose} onChange={(event) => setVerbose(event.target.checked)} />
            {t('WF_LOG', 'Show log')}
          </label>
        </div>

        <div className={styles.actions}>
          <Button variant="secondary" disabled={busy || !sourceId || !destId} onClick={() => void handlePreview()}>
            {t('A_CONV_TOEXEC', 'Preview')}
          </Button>
          <Button variant="primary" disabled={busy || !sourceId || !destId} onClick={() => void handleConvert()}>
            {t('A_CONV_START', 'Start conversion')}
          </Button>
        </div>

        {preview ? (
          <div className={styles.preview}>
            <strong>{t('A_CONV_TOEXEC', 'Will execute')}:</strong>
            <p>
              {preview.source.visual_name} ({preview.source.engine}) → {preview.destination.visual_name} (
              {preview.destination.engine})
            </p>
            <p>{preview.direction_label}</p>
          </div>
        ) : null}

        {result?.log?.length ? <pre className={styles.log}>{result.log.join('\n')}</pre> : null}
      </div>
    </div>
  );
}
