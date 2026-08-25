import "server-only";
import type { Business } from "@/lib/types";
import { getApiUrl, getAuthToken } from "@/lib/auth";

export type OwnerBusiness = Business & {
  public_id?: string;
  registration_number?: string | null;
  tax_number?: string | null;
  established_year?: number | null;
  profile_completeness?: number | null;
  status?: string | null;
};

type Collection<T> = { data?: T[] };
type Single<T> = { data?: T };

async function ownerFetch<T>(path: string): Promise<T | null> {
  const token = await getAuthToken();
  if (!token) return null;
  try {
    const response = await fetch(`${getApiUrl()}${path}`, {
      headers: { Accept: "application/json", Authorization: `Bearer ${token}` },
      cache: "no-store",
    });
    if (!response.ok) return null;
    return (await response.json()) as T;
  } catch {
    return null;
  }
}

export async function getOwnerBusinesses(): Promise<OwnerBusiness[]> {
  const payload = await ownerFetch<Collection<OwnerBusiness>>("/businesses");
  return Array.isArray(payload?.data) ? payload.data : [];
}

export async function getOwnerBusiness(id: string): Promise<OwnerBusiness | null> {
  const payload = await ownerFetch<Single<OwnerBusiness>>(`/businesses/${encodeURIComponent(id)}`);
  return payload?.data ?? null;
}
