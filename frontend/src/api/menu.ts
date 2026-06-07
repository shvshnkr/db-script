import { apiFetch } from './client';

export type MenuItem = {
  id: string;
  label_key: string;
  label: string;
  href: string;
  spa: boolean;
};

export async function fetchMenu(): Promise<MenuItem[]> {
  const body = await apiFetch<{ items: MenuItem[] }>('/menu');
  return body.data?.items ?? [];
}
