import { useCallback, useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { fetchColumnMeta, fetchTables, rowPk, type ColumnMeta, type TableMeta } from '../api/editor';
import { fetchReaderRow, readerExportUrl, searchReader } from '../api/reader';
import type { RowsPayload } from '../api/editor';
import { DataGrid } from '../components/editor/DataGrid';
import { Button } from '../components/ui/Button';
import { Modal } from '../components/ui/Modal';
import { useToast } from '../components/ui/Toast';
import { useI18n } from '../i18n/I18nContext';
import styles from './EditorPage.module.css';

export function ReaderPage() {
  const { tableId } = useParams();
  const navigate = useNavigate();
  const toast = useToast();
  const { t } = useI18n();

  const [tables, setTables] = useState<TableMeta[]>([]);
  const [columns, setColumns] = useState<ColumnMeta[]>([]);
  const [rowsPayload, setRowsPayload] = useState<RowsPayload | null>(null);
  const [page, setPage] = useState(1);
  const [query, setQuery] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [selectedPk, setSelectedPk] = useState<string | null>(null);
  const [viewRow, setViewRow] = useState<Record<string, unknown> | null>(null);
  const [viewOpen, setViewOpen] = useState(false);

  const reload = useCallback(async () => {
    if (!tableId) {
      return;
    }
    setLoading(true);
    try {
      const data = await searchReader(tableId, page, 50, query);
      setRowsPayload(data);
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'Search failed', 'error');
    } finally {
      setLoading(false);
    }
  }, [tableId, page, query, toast]);

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
    setQuery('');
    setSearchInput('');
    setSelectedPk(null);
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
    void reload();
  }, [reload]);

  const openView = async (pk: string) => {
    if (!tableId) {
      return;
    }
    try {
      const row = await fetchReaderRow(tableId, pk);
      setViewRow(row.row);
      setSelectedPk(pk);
      setViewOpen(true);
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'Failed to load row', 'error');
    }
  };

  const totalPages = rowsPayload ? Math.max(1, Math.ceil(rowsPayload.total / rowsPayload.limit)) : 1;
  const pkColumns = columns.filter((col) => col.primary).map((col) => col.name);

  return (
    <div className={styles.page}>
      <header className={styles.top}>
        <div>
          <h1>{t('MNU_3', 'Search')}</h1>
          <p className={styles.meta}>
            {rowsPayload?.visual_name ?? (tableId ? `Table #${tableId}` : t('MNU_3', 'Search'))}
          </p>
        </div>
        <select
          className={styles.picker}
          value={tableId ?? ''}
          onChange={(event) => {
            const next = event.target.value;
            navigate(next ? `/reader/${next}` : '/reader');
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
          <form
            className={styles.toolbar}
            onSubmit={(event) => {
              event.preventDefault();
              setPage(1);
              setQuery(searchInput.trim());
            }}
          >
            <input
              className={styles.picker}
              value={searchInput}
              onChange={(event) => setSearchInput(event.target.value)}
              placeholder={t('SRCH_FILE', 'Search…')}
            />
            <Button variant="secondary" type="submit">
              {t('KEY_SEARCH', 'Search')}
            </Button>
            <Button
              variant="ghost"
              type="button"
              onClick={() => {
                window.location.href = readerExportUrl(tableId, query);
              }}
            >
              {t('A_IMPEXP', 'Export CSV')}
            </Button>
          </form>

          {loading ? <p className={styles.meta}>{t('KEY_LOAD', 'Loading…')}</p> : null}

          {!loading && rowsPayload ? (
            <>
              <DataGrid
                payload={rowsPayload}
                columns={columns}
                selectedPk={selectedPk}
                selectedPks={new Set()}
                onSelect={setSelectedPk}
                onToggle={() => undefined}
                onDoubleClick={(pk) => void openView(pk)}
                selectable={false}
              />
              <footer className={styles.footer}>
                <Button
                  variant="ghost"
                  disabled={page <= 1}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                >
                  {t('KEY_PREV', 'Prev')}
                </Button>
                <span>
                  Page {page} / {totalPages} · {rowsPayload.total} rows
                  {query ? ` · "${query}"` : ''}
                </span>
                <Button variant="ghost" disabled={page >= totalPages} onClick={() => setPage((p) => p + 1)}>
                  {t('KEY_NEXT', 'Next')}
                </Button>
              </footer>
            </>
          ) : null}
        </>
      ) : (
        <p className={styles.empty}>{t('KEY_HEAD', 'Select a table to search records.')}</p>
      )}

      <Modal open={viewOpen} title={t('KEY_VIEW', 'View record')} onClose={() => setViewOpen(false)}>
        {viewRow ? (
          <dl className={styles.meta}>
            {Object.entries(viewRow).map(([key, value]) => (
              <div key={key}>
                <dt>
                  <strong>{key}</strong>
                </dt>
                <dd>{String(value ?? '')}</dd>
              </div>
            ))}
            {selectedPk ? (
              <p>
                PK: {selectedPk} ({rowPk(viewRow, pkColumns)})
              </p>
            ) : null}
          </dl>
        ) : null}
      </Modal>
    </div>
  );
}
