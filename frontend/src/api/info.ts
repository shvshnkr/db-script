import { apiFetch } from './client';

export type InfoPage = {
  slug: string;
  title_key: string;
  title: string;
  lines: string[];
};

export async function fetchInfoPage(slug: string): Promise<InfoPage> {
  const body = await apiFetch<InfoPage>(`/info/${encodeURIComponent(slug.replace(/^\./, ''))}`);
  if (!body.data) {
    throw new Error('Info page not found');
  }
  return body.data;
}
