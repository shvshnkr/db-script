import { apiFetch } from './client';

export type ConverterTable = {
  id: number;
  visual_name: string;
  engine: 'mysql' | 'fdb';
  file_base: string;
  mysql_table: string;
};

export type ConverterPreview = {
  source: ConverterTable;
  destination: ConverterTable;
  direction: 'fdb_to_mysql' | 'mysql_to_fdb';
  direction_label: string;
};

export type ConverterResult = ConverterPreview & {
  affected: number;
  imported: number;
  skipped: number;
  destination_file: string | null;
  log: string[];
};

export async function fetchConverterTables(): Promise<ConverterTable[]> {
  const body = await apiFetch<ConverterTable[]>('/converter/tables');
  return body.data ?? [];
}

export async function previewConversion(sourceId: number, destinationId: number): Promise<ConverterPreview> {
  const body = await apiFetch<ConverterPreview>('/converter/preview', {
    method: 'POST',
    body: JSON.stringify({ source_id: sourceId, destination_id: destinationId }),
  });
  if (!body.data) {
    throw new Error('Preview failed');
  }
  return body.data;
}

export type ConverterOptions = {
  rewrite?: boolean;
  use_semicolon?: boolean;
  unique_id?: boolean;
  verbose?: boolean;
};

export async function runConversion(
  sourceId: number,
  destinationId: number,
  options: ConverterOptions = {},
): Promise<ConverterResult> {
  const body = await apiFetch<ConverterResult>('/converter/run', {
    method: 'POST',
    body: JSON.stringify({
      source_id: sourceId,
      destination_id: destinationId,
      ...options,
    }),
  });
  if (!body.data) {
    throw new Error('Conversion failed');
  }
  return body.data;
}
