import { useState } from 'react';
import { executeSql, type SqlResult } from '../../api/sql';
import { Button } from '../ui/Button';
import { useToast } from '../ui/Toast';
import styles from './SqlPanel.module.css';

type Props = {
  tableId?: string;
  onClose: () => void;
};

export function SqlPanel({ tableId, onClose }: Props) {
  const toast = useToast();
  const [query, setQuery] = useState('SELECT * FROM demo_items LIMIT 10');
  const [result, setResult] = useState<SqlResult | null>(null);
  const [busy, setBusy] = useState(false);

  const run = async () => {
    setBusy(true);
    try {
      const data = await executeSql(query, tableId);
      setResult(data);
      toast.show(`SQL OK (${data.affected} row(s))`, 'success');
    } catch (err) {
      toast.show(err instanceof Error ? err.message : 'SQL failed', 'error');
      setResult(null);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className={styles.panel}>
      <div className={styles.header}>
        <strong>SQL</strong>
        <Button variant="ghost" onClick={onClose}>
          Close
        </Button>
      </div>
      <label className={styles.field}>
        <span className={styles.label}>Query</span>
        <textarea
          className={styles.textarea}
          rows={5}
          value={query}
          onChange={(event) => setQuery(event.target.value)}
        />
      </label>
      <div className={styles.actions}>
        <Button variant="primary" onClick={() => void run()} disabled={busy}>
          Execute
        </Button>
      </div>
      {result ? (
        <div className={styles.result}>
          <p className={styles.meta}>
            {result.kind} · affected {result.affected}
          </p>
          {result.columns.length > 0 ? (
            <div className={styles.tableWrap}>
              <table className={styles.table}>
                <thead>
                  <tr>
                    {result.columns.map((col) => (
                      <th key={col}>{col}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {result.rows.map((row, index) => (
                    <tr key={index}>
                      {result.columns.map((col) => (
                        <td key={col}>{String(row[col] ?? '')}</td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : null}
        </div>
      ) : null}
    </div>
  );
}
