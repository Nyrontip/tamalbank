const API_BASE = '/api';

export interface Account {
  person_id: string;
  balance: number;
  currency: string;
}

export interface Product {
  id: number;
  name: string;
  type: string;
  price: number;
  gives_tamalbits: boolean;
}

export interface Expense {
  id: number;
  product_id: number;
  type: string;
  description: string;
  amount: number;
  created_at: string;
}

export interface Tamalbits {
  tamalbits_total: number;
  calculation: string;
  rule: string;
}

export interface Status {
  status: string;
  timestamp: string;
  version: string;
  checks: {
    database: string;
    external_api: string;
  };
}

async function fetchApi<T>(url: string, options?: RequestInit): Promise<T> {
  const response = await fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...options?.headers,
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Error' }));
    throw new Error(error.message || `HTTP ${response.status}`);
  }

  return response.json();
}

export const api = {
  login: (personId: string) =>
    fetchApi<{ success: boolean }>(`${API_BASE}/auth/login`, {
      method: 'POST',
      body: JSON.stringify({ person_id: personId }),
    }),

  getStatus: () => fetchApi<Status>(`${API_BASE}/status`),

  getAccount: (personId: string) =>
    fetchApi<Account>(`${API_BASE}/account/${personId}`),

  deductAccount: (personId: string, amount: number, reason: string) =>
    fetchApi<Account>(`${API_BASE}/account/${personId}/deduct`, {
      method: 'POST',
      body: JSON.stringify({ amount, reason }),
    }),

  getProducts: (type?: string) => {
    const url = type ? `${API_BASE}/products?type=${type}` : `${API_BASE}/products`;
    return fetchApi<Product[]>(url);
  },

  getProduct: (id: number) => fetchApi<Product>(`${API_BASE}/products/${id}`),

  getExpenses: (personId: string, options?: { limit?: number; offset?: number; type?: string }) => {
    const params = new URLSearchParams();
    if (options?.limit) params.set('limit', String(options.limit));
    if (options?.offset) params.set('offset', String(options.offset));
    if (options?.type) params.set('type', options.type);
    const query = params.toString();
    return fetchApi<Expense[]>(`${API_BASE}/expenses/${personId}${query ? `?${query}` : ''}`);
  },

  createExpense: (personId: string, data: { product_id: number; type: string; description: string }) =>
    fetchApi<Expense>(`${API_BASE}/expenses/${personId}`, {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  getTamalbits: (personId: string) =>
    fetchApi<Tamalbits>(`${API_BASE}/tamalbits/${personId}`),
};