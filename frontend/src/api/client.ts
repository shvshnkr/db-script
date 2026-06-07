export type ApiError = {
  code: string;
  message: string;
};

export type ApiEnvelope<T> = {
  data: T | null;
  meta: Record<string, unknown>;
  errors: ApiError[] | null;
};

export class ApiClientError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly errors: ApiError[] = [],
  ) {
    super(message);
    this.name = 'ApiClientError';
  }
}

const API_BASE = '/api/v1';

async function parseJson<T>(response: Response): Promise<ApiEnvelope<T>> {
  const text = await response.text();
  if (text === '') {
    return { data: null, meta: {}, errors: null };
  }

  return JSON.parse(text) as ApiEnvelope<T>;
}

export async function apiFetch<T>(
  path: string,
  init: RequestInit = {},
): Promise<ApiEnvelope<T>> {
  const headers = new Headers(init.headers);
  if (!headers.has('Content-Type') && init.body) {
    headers.set('Content-Type', 'application/json');
  }
  headers.set('X-Requested-With', 'DbscriptSPA');

  const response = await fetch(`${API_BASE}${path}`, {
    ...init,
    headers,
    credentials: 'include',
  });

  const body = await parseJson<T>(response);
  if (!response.ok) {
    const errors = body.errors ?? [{ code: 'http_error', message: response.statusText }];
    throw new ApiClientError(errors[0]?.message ?? 'Request failed', response.status, errors);
  }

  return body;
}

export type AuthUser = {
  login: string;
  role: string;
  active?: boolean;
};

export type LoginResult = {
  token: string;
  user: AuthUser;
};

export async function login(loginName: string, password: string): Promise<LoginResult> {
  const body = await apiFetch<LoginResult>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ login: loginName, password }),
  });

  if (!body.data?.token) {
    throw new ApiClientError('Login failed', 401);
  }

  return body.data;
}

export async function logout(): Promise<void> {
  await apiFetch('/auth/logout', { method: 'POST' });
}

export async function fetchMe(): Promise<AuthUser> {
  const body = await apiFetch<AuthUser>('/auth/me');
  if (!body.data) {
    throw new ApiClientError('Not authenticated', 401);
  }

  return body.data;
}

export async function fetchTheme(): Promise<Record<string, string>> {
  const body = await apiFetch<{ css_variables: Record<string, string> }>('/theme');
  return body.data?.css_variables ?? {};
}

export function applyThemeVariables(vars: Record<string, string>): void {
  const root = document.documentElement;
  const map: Record<string, string> = {
    '--dbs-bg': '--color-bg',
    '--dbs-fg': '--color-text',
    '--dbs-accent': '--color-accent',
    '--dbs-border': '--color-border',
  };

  for (const [from, to] of Object.entries(map)) {
    if (vars[from]) {
      root.style.setProperty(to, vars[from]);
    }
  }
}
