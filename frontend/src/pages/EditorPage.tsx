import { useCallback, useEffect, useState } from 'react';
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
import { DataGrid } from '../components/editor/DataGrid';
import { RecordForm } from '../components/editor/RecordForm';
import { Button } from '../components/ui/Button';
import { Modal } from '../components/ui/Modal';
import { useToast } from '../components/ui/Toast';
import styles from './EditorPage.module.css';

type ModalMode = 'add' | 'edit' | null;

export function EditorPage() {
  const { tableId } = useParams();
  const navigate = useNavigate();
  const toast = useToast();

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
        toast.show('Record created', 'success');
      } else if (modalMode === 'edit' && selectedPk) {
        await updateRow(tableId, selectedPk, data);
        toast.show('Record saved', 'success');
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

    if (!window.confirm(`Delete ${pks.length} record(s)?`)) {
      return;
    }

    setBusy(true);
    try {
      const deleted = await deleteRows(tableId, pks);
      toast.show(`Deleted ${deleted} record(s)`, 'success');
      setSelectedPk(null);
      setSelectedPks(new Set());
      await reloadRows();
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'Delete failed', 'error');
    } finally {
      setBusy(false);
    }
  };

  const totalPages = rowsPayload ? Math.max(1, Math.ceil(rowsPayload.total / rowsPayload.limit)) : 1;
  const hasSelection = selectedPk !== null || selectedPks.size > 0;

  return (
    <div className={styles.page}>
      <header className={styles.top}>
        <div>
          <h1>Editor</h1>
          <p className={styles.meta}>
            {rowsPayload?.visual_name ?? (tableId ? `Table #${tableId}` : 'Select a table')}
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
          <option value="">Select table…</option>
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
            <Button variant="secondary" onClick={openAdd} disabled={busy || columns.length === 0}>
              Add
            </Button>
            <Button
              variant="secondary"
              onClick={() => selectedPk && void openEdit(selectedPk)}
              disabled={!selectedPk || busy}
            >
              Edit
            </Button>
            <Button variant="danger" onClick={() => void handleDelete()} disabled={!hasSelection || busy}>
              Delete
            </Button>
          </div>

          {loading ? <p className={styles.meta}>Loading…</p> : null}

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
              />
              <footer className={styles.footer}>
                <Button
                  variant="ghost"
                  disabled={page <= 1 || busy}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                >
                  Prev
                </Button>
                <span>
                  Page {page} / {totalPages} · {rowsPayload.total} rows
                </span>
                <Button
                  variant="ghost"
                  disabled={page >= totalPages || busy}
                  onClick={() => setPage((p) => p + 1)}
                >
                  Next
                </Button>
              </footer>
            </>
          ) : null}
        </>
      ) : (
        <p className={styles.empty}>Select a table to view records.</p>
      )}

      <Modal
        open={modalMode !== null}
        title={modalMode === 'add' ? 'Add record' : 'Edit record'}
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
    </div>
  );
}
