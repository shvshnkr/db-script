import { apiFetch } from './client';
import type { RowPayload, RowsPayload } from './editor';

export async function searchReader(
  tableId: string,
  page = 1,
  limit = 50,
  q = '',
): Promise<RowsPayload & { q?: string }> {
  const params = new URLSearchParams({
    page: String(page),
    limit: String(limit),
  });
  if (q) {
    params.set('q', q);
  }

  const body = await apiFetch<RowsPayload & { q?: string }>(
    `/reader/tables/${tableId}/search?${params.toString()}`,
  );
  if (!body.data) {
    throw new Error('Empty reader response');
  }
  return body.data;
}

export async function fetchReaderRow(tableId: string, pk: string): Promise<RowPayload> {
  const body = await apiFetch<RowPayload>(
    `/reader/tables/${tableId}/rows/${encodeURIComponent(pk)}`,
  );
  if (!body.data) {
    throw new Error('Row not found');
  }
  return body.data;
}

export function readerExportUrl(tableId: string, q = ''): string {
  const params = new URLSearchParams();
  if (q) {
    params.set('q', q);
  }
  const suffix = params.toString();
  return `/api/v1/reader/tables/${tableId}/export.csv${suffix ? `?${suffix}` : ''}`;
}
