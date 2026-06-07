import { useCallback, useEffect, useRef, useState } from 'react';
import { deleteFile, fetchFiles, fileDownloadUrl, uploadFile, type FileEntry } from '../api/files';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { useToast } from '../components/ui/Toast';
import { useAuth } from '../auth/AuthContext';
import { useI18n } from '../i18n/I18nContext';
import styles from './FilesPage.module.css';

function formatSize(bytes: number): string {
  if (bytes < 1024) {
    return `${bytes} B`;
  }
  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`;
  }
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

export function FilesPage() {
  const toast = useToast();
  const { user } = useAuth();
  const { t } = useI18n();
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [files, setFiles] = useState<FileEntry[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(false);
  const [busy, setBusy] = useState(false);

  const reload = useCallback(async () => {
    setLoading(true);
    try {
      setFiles(await fetchFiles(search.trim()));
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'Failed to load files', 'error');
    } finally {
      setLoading(false);
    }
  }, [search, toast]);

  useEffect(() => {
    void reload();
  }, [reload]);

  const handleUpload = async (fileList: FileList | null) => {
    const file = fileList?.[0];
    if (!file) {
      return;
    }

    setBusy(true);
    try {
      await uploadFile(file);
      toast.show(t('FMG_UPLOAD', 'File uploaded'), 'success');
      await reload();
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'Upload failed', 'error');
    } finally {
      setBusy(false);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  const handleDelete = async (entry: FileEntry) => {
    if (!window.confirm(`${t('PHYS_DEL', 'Delete')} ${entry.name}?`)) {
      return;
    }

    setBusy(true);
    try {
      await deleteFile(entry.hash, user?.role === 'admin' ? undefined : entry.delete_hash);
      toast.show(t('PHYS_DEL', 'Deleted'), 'success');
      await reload();
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'Delete failed', 'error');
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <div>
          <h1>{t('MNU_7', 'File manager')}</h1>
          <p className={styles.meta}>{files.length} file(s)</p>
        </div>
        <div className={styles.actions}>
          <Input
            label={t('SRCH_FILE', 'Search file')}
            value={search}
            onChange={(event) => setSearch(event.target.value)}
          />
          <Button variant="secondary" onClick={() => void reload()} disabled={loading || busy}>
            {t('KEY_SEARCH', 'Search')}
          </Button>
          <Button variant="primary" onClick={() => fileInputRef.current?.click()} disabled={busy}>
            {t('FMG_UPLOAD', 'Upload')}
          </Button>
          <input
            ref={fileInputRef}
            type="file"
            hidden
            onChange={(event) => void handleUpload(event.target.files)}
          />
        </div>
      </header>

      {loading ? <p className={styles.meta}>{t('KEY_LOAD', 'Loading…')}</p> : null}

      {!loading && files.length === 0 ? (
        <p className={styles.meta}>{t('SRCH_FND', 'No files found.')}</p>
      ) : null}

      {!loading && files.length > 0 ? (
        <div className={styles.tableWrap}>
          <table className={styles.table}>
            <thead>
              <tr>
                <th>{t('KEY_NAME', 'Name')}</th>
                <th>{t('KEY_SIZE', 'Size')}</th>
                <th>{t('FMG_DOWNLOAD', 'Download')}</th>
                <th>{t('PHYS_DEL', 'Delete')}</th>
              </tr>
            </thead>
            <tbody>
              {files.map((file) => (
                <tr key={file.hash}>
                  <td>
                    <strong>{file.name}</strong>
                    {file.comment ? <div className={styles.meta}>{file.comment}</div> : null}
                  </td>
                  <td>{formatSize(file.size_bytes)}</td>
                  <td>
                    <a href={fileDownloadUrl(file.hash)} className={styles.link}>
                      {t('FMG_DOWNLOAD', 'Download')}
                    </a>
                  </td>
                  <td>
                    <Button variant="danger" onClick={() => void handleDelete(file)} disabled={busy}>
                      {t('PHYS_DEL', 'Delete')}
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : null}
    </div>
  );
}
