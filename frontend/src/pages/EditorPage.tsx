import { useCallback, useEffect, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
  createRow,
  deleteRows,
  fetchColumnMeta,
  fetchRow,
  fetchRows,
  fetchTables,
  updateRow,
  type ColumnMeta,
  type RowsPayload,
  type TableMeta,
} from '../api/editor';
import { readerExportUrl } from '../api/reader';
import { importCsv } from '../api/sql';
import { useAuth } from '../auth/AuthContext';
import { DataGrid } from '../components/editor/DataGrid';
import { RecordForm } from '../components/editor/RecordForm';
import { SqlPanel } from '../components/editor/SqlPanel';
import { Button } from '../components/ui/Button';
import { Modal } from '../components/ui/Modal';
import { useToast } from '../components/ui/Toast';
import { useI18n } from '../i18n/I18nContext';
import styles from './EditorPage.module.css';

type ModalMode = 'add' | 'edit' | null;

export function EditorPage() {
  const { tableId } = useParams();
  const navigate = useNavigate();
  const toast = useToast();
  const { t } = useI18n();
  const { user } = useAuth();
  const importRef = useRef<HTMLInputElement>(null);

  const [tables, setTables] = useState<TableMeta[]>([]);
  const [columns, setColumns] = useState<ColumnMeta[]>([]);
  const [rowsPayload, setRowsPayload] = useState<RowsPayload | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false);
  const [selectedPk, setSelectedPk] = useState<string | null>(null);
  const [selectedPks, setSelectedPks] = useState<Set<string>>(new Set());
  const [modalMode, setModalMode] = useState<ModalMode>(null);
  const [editRow, setEditRow] = useState<Record<string, unknown> | undefined>();
  const [busy, setBusy] = useState(false);
  const [sqlOpen, setSqlOpen] = useState(false);

  const canEdit = user?.role === 'admin' || user?.role === 'editor';
  const canSql = user !== null;

  const reloadRows = useCallback(async () => {
    if (!tableId) {
      return;
    }
    setLoading(true);
    try {
      const data = await fetchRows(tableId, page);
      setRowsPayload(data);
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'Failed to load rows', 'error');
    } finally {
      setLoading(false);
    }
  }, [tableId, page, toast]);

  useEffect(() => {
    fetchTables()
      .then(setTables)
      .catch((err: Error) => toast.show(err.message, 'error'));
  }, [toast]);

  useEffect(() => {
    if (!tableId) {
      setColumns([]);
      setRowsPayload(null);
      return;
    }

    setPage(1);
    setSelectedPk(null);
    setSelectedPks(new Set());
  }, [tableId]);

  useEffect(() => {
    if (!tableId) {
      return;
    }

    fetchColumnMeta(tableId)
      .then(setColumns)
      .catch((err: Error) => toast.show(err.message, 'error'));
  }, [tableId, toast]);

  useEffect(() => {
    void reloadRows();
  }, [reloadRows]);

  const openAdd = () => {
    setEditRow(undefined);
    setModalMode('add');
  };

  const openEdit = async (pk: string) => {
    if (!tableId) {
      return;
    }
    setBusy(true);
    try {
      const row = await fetchRow(tableId, pk);
      setEditRow(row.row);
      setSelectedPk(pk);
      setModalMode('edit');
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'Failed to load row', 'error');
    } finally {
      setBusy(false);
    }
  };

  const handleSave = async (data: Record<string, unknown>) => {
    if (!tableId) {
      return;
    }

    try {
      if (modalMode === 'add') {
        await createRow(tableId, data);
        toast.show(t('KEY_ADD', 'Record created'), 'success');
      } else if (modalMode === 'edit' && selectedPk) {
        await updateRow(tableId, selectedPk, data);
        toast.show(t('KEY_EDIT', 'Record saved'), 'success');
      }
      setModalMode(null);
      await reloadRows();
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'Save failed', 'error');
      throw err;
    }
  };

  const handleDelete = async () => {
    if (!tableId) {
      return;
    }

    const pks = selectedPks.size > 0 ? [...selectedPks] : selectedPk ? [selectedPk] : [];
    if (pks.length === 0) {
      return;
    }

    if (!window.confirm(`${t('KEY_DEL', 'Delete')} ${pks.length}?`)) {
      return;
    }

    setBusy(true);
    try {
      const deleted = await deleteRows(tableId, pks);
      toast.show(`${t('KEY_DEL', 'Deleted')} ${deleted}`, 'success');
      setSelectedPk(null);
      setSelectedPks(new Set());
      await reloadRows();
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'Delete failed', 'error');
    } finally {
      setBusy(false);
    }
  };

  const handleImport = async (fileList: FileList | null) => {
    if (!tableId) {
      return;
    }
    const file = fileList?.[0];
    if (!file) {
      return;
    }

    setBusy(true);
    try {
      const csv = await file.text();
      const result = await importCsv(tableId, csv);
      toast.show(`${t('A_IMPEXP', 'Import')}: ${result.imported}, skipped ${result.skipped}`, 'success');
      await reloadRows();
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'Import failed', 'error');
    } finally {
      setBusy(false);
      if (importRef.current) {
        importRef.current.value = '';
      }
    }
  };

  const totalPages = rowsPayload ? Math.max(1, Math.ceil(rowsPayload.total / rowsPayload.limit)) : 1;
  const hasSelection = selectedPk !== null || selectedPks.size > 0;

  return (
    <div className={styles.page}>
      <header className={styles.top}>
        <div>
          <h1>{t('MNU_2', 'Editor')}</h1>
          <p className={styles.meta}>
            {rowsPayload?.visual_name ?? (tableId ? `Table #${tableId}` : t('MNU_2', 'Editor'))}
          </p>
        </div>
        <select
          className={styles.picker}
          value={tableId ?? ''}
          onChange={(event) => {
            const next = event.target.value;
            navigate(next ? `/editor/${next}` : '/editor');
          }}
        >
          <option value="">{t('KEY_HEAD', 'Select table…')}</option>
          {tables.map((table) => (
            <option key={table.id} value={table.id}>
              {table.visual_name ?? table.mysql_table ?? table.id}
            </option>
          ))}
        </select>
      </header>

      {tableId ? (
        <>
          <div className={styles.toolbar}>
            {canEdit ? (
              <>
                <Button variant="secondary" onClick={openAdd} disabled={busy || columns.length === 0}>
                  {t('KEY_ADD', 'Add')}
                </Button>
                <Button
                  variant="secondary"
                  onClick={() => selectedPk && void openEdit(selectedPk)}
                  disabled={!selectedPk || busy}
                >
                  {t('KEY_EDIT', 'Edit')}
                </Button>
                <Button variant="danger" onClick={() => void handleDelete()} disabled={!hasSelection || busy}>
                  {t('KEY_DEL', 'Delete')}
                </Button>
              </>
            ) : null}
            {canSql ? (
              <Button variant="secondary" onClick={() => setSqlOpen(true)} disabled={busy}>
                {t('KEY_EXECUTE', 'SQL')}
              </Button>
            ) : null}
            <Button
              variant="ghost"
              disabled={busy}
              onClick={() => {
                window.location.href = readerExportUrl(tableId);
              }}
            >
              {t('A_IMPEXP', 'Export CSV')}
            </Button>
            {canEdit ? (
              <>
                <Button variant="ghost" disabled={busy} onClick={() => importRef.current?.click()}>
                  {t('A_IE_SRC', 'Import CSV')}
                </Button>
                <input
                  ref={importRef}
                  type="file"
                  accept=".csv,text/csv"
                  hidden
                  onChange={(event) => void handleImport(event.target.files)}
                />
              </>
            ) : null}
          </div>

          {loading ? <p className={styles.meta}>{t('KEY_LOAD', 'Loading…')}</p> : null}

          {!loading && rowsPayload ? (
            <>
              <DataGrid
                payload={rowsPayload}
                columns={columns}
                selectedPk={selectedPk}
                selectedPks={selectedPks}
                onSelect={setSelectedPk}
                onToggle={(pk, checked) => {
                  setSelectedPks((prev) => {
                    const next = new Set(prev);
                    if (checked) {
                      next.add(pk);
                    } else {
                      next.delete(pk);
                    }
                    return next;
                  });
                }}
                onDoubleClick={(pk) => void openEdit(pk)}
                selectable={canEdit}
              />
              <footer className={styles.footer}>
                <Button
                  variant="ghost"
                  disabled={page <= 1 || busy}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                >
                  {t('KEY_PREV', 'Prev')}
                </Button>
                <span>
                  Page {page} / {totalPages} · {rowsPayload.total} rows
                </span>
                <Button
                  variant="ghost"
                  disabled={page >= totalPages || busy}
                  onClick={() => setPage((p) => p + 1)}
                >
                  {t('KEY_NEXT', 'Next')}
                </Button>
              </footer>
            </>
          ) : null}
        </>
      ) : (
        <p className={styles.empty}>{t('KEY_HEAD', 'Select a table to view records.')}</p>
      )}

      <Modal
        open={modalMode !== null}
        title={modalMode === 'add' ? t('KEY_ADD', 'Add record') : t('KEY_EDIT', 'Edit record')}
        onClose={() => setModalMode(null)}
      >
        {modalMode && columns.length > 0 ? (
          <RecordForm
            columns={columns}
            initial={modalMode === 'edit' ? editRow : undefined}
            title=""
            onCancel={() => setModalMode(null)}
            onSubmit={handleSave}
          />
        ) : null}
      </Modal>

      <Modal open={sqlOpen} title={t('KEY_EXECUTE', 'SQL')} onClose={() => setSqlOpen(false)}>
        {sqlOpen ? <SqlPanel tableId={tableId} onClose={() => setSqlOpen(false)} /> : null}
      </Modal>
    </div>
  );
}
