import "server-only";

import { cookies } from "next/headers";

export const AUTH_COOKIE = "yp_auth_token";

const API_URL =
  process.env.YELLOWPAGES_API_URL ?? "http://127.0.0.1:8000/api";

export type AuthUser = {
  id: string;
  first_name?: string | null;
  last_name?: string | null;
  display_name?: string | null;
  email?: string | null;
  status?: string | null;
  email_verified_at?: string | null;
  created_at?: string | null;
};

type MeResponse = {
  user: AuthUser;
};

export async function getAuthToken(): Promise<string | null> {
  const cookieStore = await cookies();
  return cookieStore.get(AUTH_COOKIE)?.value ?? null;
}

export async function getCurrentUser(): Promise<AuthUser | null> {
  const token = await getAuthToken();

  if (!token) {
    return null;
  }

  try {
    const response = await fetch(`${API_URL}/auth/me`, {
      method: "GET",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      cache: "no-store",
    });

    if (!response.ok) {
      return null;
    }

    const payload = (await response.json()) as MeResponse;
    return payload.user ?? null;
  } catch {
    return null;
  }
}

export function getApiUrl(): string {
  return API_URL;
}
