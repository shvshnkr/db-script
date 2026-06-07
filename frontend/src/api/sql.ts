import { apiFetch } from './client';

export type SqlResult = {
  kind: 'select' | 'write';
  affected: number;
  columns: string[];
  rows: Record<string, unknown>[];
  query: string;
};

export async function executeSql(query: string, tableId?: string): Promise<SqlResult> {
  const body = await apiFetch<SqlResult>('/sql/execute', {
    method: 'POST',
    body: JSON.stringify({ query, table_id: tableId ?? '' }),
  });
  if (!body.data) {
    throw new Error('Empty SQL response');
  }
  return body.data;
}

export async function importCsv(tableId: string, csv: string): Promise<{ imported: number; skipped: number }> {
  const response = await fetch(`/api/v1/tables/${tableId}/import`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'text/csv',
      'X-Requested-With': 'DbscriptSPA',
    },
    body: csv,
  });
  const body = await response.json();
  if (!response.ok) {
    throw new Error(body.errors?.[0]?.message ?? 'Import failed');
  }
  return body.data ?? { imported: 0, skipped: 0 };
}
