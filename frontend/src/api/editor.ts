import { apiFetch, ApiClientError } from './client';

export type ColumnMeta = {
  name: string;
  type: string;
  nullable: boolean;
  primary: boolean;
};

export type TableMeta = {
  id: number;
  visual_name?: string;
  mysql_table?: string;
};

export type RowsPayload = {
  rows: Record<string, unknown>[];
  total: number;
  page: number;
  limit: number;
  visual_name?: string;
};

export type RowPayload = {
  pk: string;
  pk_columns: string[];
  row: Record<string, unknown>;
};

export async function fetchTables(): Promise<TableMeta[]> {
  const body = await apiFetch<TableMeta[]>('/tables');
  return body.data ?? [];
}

export async function fetchColumnMeta(tableId: string): Promise<ColumnMeta[]> {
  const body = await apiFetch<ColumnMeta[]>(`/tables/${tableId}/meta`);
  return body.data ?? [];
}

export async function fetchRows(tableId: string, page = 1, limit = 50): Promise<RowsPayload> {
  const body = await apiFetch<RowsPayload>(
    `/tables/${tableId}/rows?page=${page}&limit=${limit}`,
  );
  if (!body.data) {
    throw new ApiClientError('Empty rows response', 500);
  }
  return body.data;
}

export async function fetchRow(tableId: string, pk: string): Promise<RowPayload> {
  const body = await apiFetch<RowPayload>(`/tables/${tableId}/rows/${encodeURIComponent(pk)}`);
  if (!body.data) {
    throw new ApiClientError('Row not found', 404);
  }
  return body.data;
}

export async function createRow(tableId: string, data: Record<string, unknown>): Promise<string> {
  const body = await apiFetch<{ pk: string }>(`/tables/${tableId}/rows`, {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return body.data?.pk ?? '';
}

export async function updateRow(
  tableId: string,
  pk: string,
  data: Record<string, unknown>,
): Promise<void> {
  await apiFetch(`/tables/${tableId}/rows/${encodeURIComponent(pk)}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  });
}

export async function deleteRows(tableId: string, pks: string[]): Promise<number> {
  const body = await apiFetch<{ deleted: number }>(`/tables/${tableId}/rows`, {
    method: 'DELETE',
    body: JSON.stringify({ pks }),
  });
  return body.data?.deleted ?? 0;
}

export function rowPk(row: Record<string, unknown>, pkColumns: string[]): string {
  if (pkColumns.length === 0) {
    return '';
  }
  const parts = pkColumns.map((col) => String(row[col] ?? ''));
  return pkColumns.length === 1 ? parts[0] : parts.join('|');
}
