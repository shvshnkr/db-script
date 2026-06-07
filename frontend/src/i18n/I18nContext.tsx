import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { fetchI18nBundle, fetchLanguages, type LanguageInfo } from '../api/i18n';

type I18nState = {
  language: string;
  languages: LanguageInfo;
  t: (key: string, fallback?: string) => string;
  setLanguage: (lang: string) => void;
  loading: boolean;
};

const I18nContext = createContext<I18nState | null>(null);

const STORAGE_KEY = 'dbs_lang';

export function I18nProvider({ children }: { children: ReactNode }) {
  const [language, setLanguageState] = useState(() => localStorage.getItem(STORAGE_KEY) ?? '');
  const [messages, setMessages] = useState<Record<string, string>>({});
  const [languages, setLanguages] = useState<LanguageInfo>({ default: 'english', available: ['english'] });
  const [loading, setLoading] = useState(true);

  const loadBundle = useCallback(async (lang: string) => {
    setLoading(true);
    try {
      const bundle = await fetchI18nBundle(lang || undefined);
      setMessages(bundle.messages);
      setLanguageState(bundle.language);
      localStorage.setItem(STORAGE_KEY, bundle.language);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchLanguages()
      .then(setLanguages)
      .catch(() => undefined);
  }, []);

  useEffect(() => {
    void loadBundle(language);
  }, [language, loadBundle]);

  const setLanguage = useCallback((lang: string) => {
    setLanguageState(lang);
  }, []);

  const t = useCallback(
    (key: string, fallback?: string) => messages[key] ?? fallback ?? key,
    [messages],
  );

  const value = useMemo(
    () => ({ language, languages, t, setLanguage, loading }),
    [language, languages, t, setLanguage, loading],
  );

  return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
}

export function useI18n(): I18nState {
  const ctx = useContext(I18nContext);
  if (!ctx) {
    throw new Error('useI18n must be used within I18nProvider');
  }

  return ctx;
}
