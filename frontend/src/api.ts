import type { AuthUser, BillRecord, FeeType, StudentRecord } from './types';

const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost:8081/api';

async function req<T>(path: string, options: RequestInit = {}, schoolCode = '1'): Promise<T> {
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      'X-School-Code': schoolCode,
      ...(options.headers || {})
    }
  });

  if (!response.ok) {
    const text = await response.text();
    let message = '';
    try {
      const payload = JSON.parse(text) as { error?: string; message?: string };
      message = payload.error || payload.message || '';
    } catch {
      message = '';
    }
    throw new Error(message || text || 'Request failed');
  }
  return (await response.json()) as T;
}

export async function login(username: string, password: string, schoolCode: string): Promise<AuthUser> {
  const data = await req<{ user: AuthUser }>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ username, password, school_code: schoolCode })
  }, schoolCode);
  return data.user;
}

export async function fetchCurrentUser(): Promise<AuthUser | null> {
  try {
    const data = await req<{ user: AuthUser }>('/auth/me');
    return data.user;
  } catch {
    return null;
  }
}

export async function logout(): Promise<void> {
  await req('/auth/logout', { method: 'POST' });
}

export async function fetchStudents(schoolCode: string): Promise<StudentRecord[]> {
  const data = await req<{ data: StudentRecord[] }>('/students', {}, schoolCode);
  return data.data;
}

export async function fetchFeeTypes(schoolCode: string): Promise<FeeType[]> {
  const data = await req<{ data: FeeType[] }>('/fee-types', {}, schoolCode);
  return data.data;
}

export async function createFeeType(payload: Partial<FeeType>, schoolCode: string): Promise<void> {
  await req('/fee-types', { method: 'POST', body: JSON.stringify(payload) }, schoolCode);
}

export async function updateFeeType(payload: Partial<FeeType> & { id: number }, schoolCode: string): Promise<void> {
  await req('/fee-types', { method: 'PUT', body: JSON.stringify(payload) }, schoolCode);
}

export async function fetchBills(schoolCode: string, limit = 10): Promise<BillRecord[]> {
  const data = await req<{ data: BillRecord[] }>(`/bills?limit=${limit}`, {}, schoolCode);
  return data.data;
}

export async function generateBill(payload: Record<string, unknown>, schoolCode: string): Promise<{ bill_id: number; bill_no: string }> {
  return req('/bills/generate', { method: 'POST', body: JSON.stringify(payload) }, schoolCode);
}

export async function createEmiPlan(billId: number, installments: Array<{ due_date: string; amount: number }>, schoolCode: string): Promise<void> {
  await req(`/bills/${billId}/emi-plan`, { method: 'POST', body: JSON.stringify({ installments }) }, schoolCode);
}

export function invoicePdfUrl(billId: number): string {
  return `${API_BASE}/invoices/${billId}/pdf`;
}
