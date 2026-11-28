const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8001';

export function getToken(): string | null {
  if (typeof window === 'undefined') return null;
  return window.sessionStorage.getItem('feedbacktrace-token');
}

export function setToken(token: string | null): void {
  if (typeof window === 'undefined') return;
  if (token) window.sessionStorage.setItem('feedbacktrace-token', token);
  else window.sessionStorage.removeItem('feedbacktrace-token');
}

export async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers = new Headers(options.headers);
  const token = getToken();
  if (token) headers.set('Authorization', 'Bearer ' + token);
  if (options.body && !(options.body instanceof FormData) && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json');
  }
  const response = await fetch(API_URL + path, { ...options, headers, cache: 'no-store' });
  const contentType = response.headers.get('content-type') ?? '';
  const payload = contentType.includes('application/json') ? await response.json() : await response.text();
  if (!response.ok) {
    const message = typeof payload === 'object' && payload && 'message' in payload
      ? String((payload as { message: unknown }).message)
      : 'Request failed with HTTP ' + response.status;
    throw new Error(message);
  }
  return payload as T;
}
