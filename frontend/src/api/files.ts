import { ApiClientError } from './client';

export type FileEntry = {
  id: number;
  hash: string;
  name: string;
  comment: string;
  downloads: number;
  size_bytes: number;
  delete_hash: string;
};

export async function fetchFiles(search = ''): Promise<FileEntry[]> {
  const query = search ? `?q=${encodeURIComponent(search)}` : '';
  const response = await fetch(`/api/v1/files${query}`, {
    credentials: 'include',
    headers: { 'X-Requested-With': 'DbscriptSPA' },
  });
  const body = await response.json();
  if (!response.ok) {
    throw new ApiClientError(body.errors?.[0]?.message ?? 'Request failed', response.status, body.errors ?? []);
  }
  return body.data ?? [];
}

export async function uploadFile(file: File): Promise<FileEntry> {
  const form = new FormData();
  form.append('file', file);

  const response = await fetch('/api/v1/files', {
    method: 'POST',
    body: form,
    credentials: 'include',
    headers: { 'X-Requested-With': 'DbscriptSPA' },
  });
  const body = await response.json();
  if (!response.ok) {
    throw new ApiClientError(body.errors?.[0]?.message ?? 'Upload failed', response.status, body.errors ?? []);
  }
  return body.data;
}

export function fileDownloadUrl(hash: string): string {
  return `/api/v1/files/${encodeURIComponent(hash)}/download`;
}

export async function deleteFile(hash: string, confirmHash?: string): Promise<void> {
  const response = await fetch(`/api/v1/files/${encodeURIComponent(hash)}`, {
    method: 'DELETE',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'DbscriptSPA',
    },
    body: JSON.stringify({ confirm_hash: confirmHash ?? '' }),
  });
  const body = await response.json();
  if (!response.ok) {
    throw new ApiClientError(body.errors?.[0]?.message ?? 'Delete failed', response.status, body.errors ?? []);
  }
}
