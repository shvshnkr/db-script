import { apiFetch } from './client';

export type LanguageInfo = {
  default: string;
  available: string[];
};

export async function fetchLanguages(): Promise<LanguageInfo> {
  const body = await apiFetch<LanguageInfo>('/i18n/languages');
  return body.data ?? { default: 'english', available: ['english'] };
}

export async function fetchI18nBundle(lang?: string): Promise<{ language: string; messages: Record<string, string> }> {
  const query = lang ? `?lang=${encodeURIComponent(lang)}` : '';
  const body = await apiFetch<{ language: string; messages: Record<string, string> }>(`/i18n${query}`);
  return body.data ?? { language: 'english', messages: {} };
}
