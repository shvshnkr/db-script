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
};

export function DataGrid({
  payload,
  columns,
  selectedPk,
  selectedPks,
  onSelect,
  onToggle,
  onDoubleClick,
}: Props) {
  const pkColumns = columns.filter((c) => c.primary).map((c) => c.name);
  const displayColumns =
    columns.length > 0 ? columns.map((c) => c.name) : Object.keys(payload.rows[0] ?? {});

  if (payload.rows.length === 0) {
    return <p className={styles.empty}>No records. Use Add to create one.</p>;
  }

  return (
    <div className={styles.wrap}>
      <table className={styles.grid}>
        <thead>
          <tr>
            <th className={styles.checkCol} aria-label="Select" />
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
                onDoubleClick={() => onDoubleClick(pk)}
              >
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
                {displayColumns.map((column) => (
                  <td key={column}>{String(row[column] ?? '')}</td>
                ))}
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}
