import { useEffect, useState } from 'react';
import type { ColumnMeta, RowsPayload } from '../../api/editor';
import { rowPk } from '../../api/editor';
import styles from './DataGrid.module.css';

type Props = {
  payload: RowsPayload;
  columns: ColumnMeta[];
  selectedPk: string | null;
  selectedPks: Set<string>;
  onSelect: (pk: string | null) => void;
  onToggle: (pk: string, checked: boolean) => void;
  onDoubleClick: (pk: string) => void;
  selectable?: boolean;
  liveMod?: boolean;
  onCellSave?: (pk: string, column: string, value: string) => Promise<void>;
};

type LiveCellProps = {
  value: string;
  editable: boolean;
  onSave: (value: string) => Promise<void>;
};

function LiveCell({ value, editable, onSave }: LiveCellProps) {
  const [draft, setDraft] = useState(value);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    setDraft(value);
  }, [value]);

  if (!editable) {
    return <>{value}</>;
  }

  const commit = async () => {
    if (draft === value || saving) {
      return;
    }
    setSaving(true);
    try {
      await onSave(draft);
    } finally {
      setSaving(false);
    }
  };

  return (
    <input
      className={styles.liveInput}
      value={draft}
      disabled={saving}
      onClick={(event) => event.stopPropagation()}
      onChange={(event) => setDraft(event.target.value)}
      onBlur={() => void commit()}
      onKeyDown={(event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          void commit();
          (event.target as HTMLInputElement).blur();
        }
        if (event.key === 'Escape') {
          setDraft(value);
          (event.target as HTMLInputElement).blur();
        }
      }}
    />
  );
}

export function DataGrid({
  payload,
  columns,
  selectedPk,
  selectedPks,
  onSelect,
  onToggle,
  onDoubleClick,
  selectable = true,
  liveMod = false,
  onCellSave,
}: Props) {
  const pkColumns = columns.filter((c) => c.primary).map((c) => c.name);
  const displayColumns =
    columns.length > 0 ? columns.map((c) => c.name) : Object.keys(payload.rows[0] ?? {});
  const editableColumns = new Set(
    columns.filter((column) => !column.primary).map((column) => column.name),
  );

  if (payload.rows.length === 0) {
    return <p className={styles.empty}>No records. Use Add to create one.</p>;
  }

  return (
    <div className={styles.wrap}>
      <table className={styles.grid}>
        <thead>
          <tr>
            {selectable ? <th className={styles.checkCol} aria-label="Select" /> : null}
            {displayColumns.map((column) => (
              <th key={column}>{column}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {payload.rows.map((row) => {
            const pk = rowPk(row, pkColumns);
            const selected = selectedPk === pk;
            return (
              <tr
                key={pk || JSON.stringify(row)}
                className={selected ? styles.selected : undefined}
                onClick={() => onSelect(pk)}
                onDoubleClick={() => {
                  if (!liveMod) {
                    onDoubleClick(pk);
                  }
                }}
              >
                {selectable ? (
                  <td className={styles.checkCol}>
                    <input
                      type="checkbox"
                      checked={selectedPks.has(pk)}
                      onChange={(event) => {
                        event.stopPropagation();
                        onToggle(pk, event.target.checked);
                      }}
                      aria-label={`Select row ${pk}`}
                    />
                  </td>
                ) : null}
                {displayColumns.map((column) => {
                  const text = String(row[column] ?? '');
                  const editable = liveMod && editableColumns.has(column) && onCellSave !== undefined;

                  return (
                    <td key={column} className={editable ? styles.liveCell : undefined}>
                      <LiveCell
                        value={text}
                        editable={editable}
                        onSave={async (next) => {
                          if (onCellSave) {
                            await onCellSave(pk, column, next);
                          }
                        }}
                      />
                    </td>
                  );
                })}
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}
