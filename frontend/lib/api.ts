import type {
Business,
Category,
Review,
SearchResponse,
} from "@/lib/types";

const API_URL =
  process.env.YELLOWPAGES_API_URL ?? "http://127.0.0.1:8000/api";

async function apiFetch<T>(
  path: string,
  options: RequestInit = {},
): Promise<T | null> {
  try {
    const response = await fetch(`${API_URL}${path}`, {
      ...options,
      headers: {
        Accept: "application/json",
        ...options.headers,
      },
      next: { revalidate: 60 },
    });

    if (!response.ok) {
      return null;
    }

    return (await response.json()) as T;
  } catch {
    return null;
  }
}

export async function getCategoryTree(): Promise<Category[]> {
  const result = await apiFetch<{ data: Category[] }>(
    "/v1/categories/tree",
  );

  return result?.data ?? [];
}

export async function getCategories(): Promise<Category[]> {
  const result = await apiFetch<
    | { data: Category[] }
    | { data: { data: Category[] } }
  >("/v1/categories");

  if (!result) {
    return [];
  }

  if (Array.isArray(result.data)) {
    return result.data;
  }

  if (
    result.data &&
    typeof result.data === "object" &&
    Array.isArray(result.data.data)
  ) {
    return result.data.data;
  }

  return [];
}

export async function searchBusinesses(
  query: Record<string, string | undefined>,
): Promise<SearchResponse["data"]> {
  const params = new URLSearchParams();

  for (const [key, value] of Object.entries(query)) {
    if (value) {
      params.set(key, value);
    }
  }

  const result = await apiFetch<SearchResponse>(
    `/v1/directory/search?${params.toString()}`,
  );

  return (
    result?.data ?? {
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
    }
  );
}

export async function getBusiness(
  slug: string,
): Promise<Business | null> {
  const result = await apiFetch<{
    success: boolean;
    data: Business;
  }>(`/v1/businesses/${encodeURIComponent(slug)}`);

  return result?.data ?? null;
}
export async function getBusinessReviews(
  businessId: string,
): Promise<Review[]> {
  try {
    const response = await fetch(
      `${API_URL}/v1/businesses/${encodeURIComponent(
        businessId,
      )}/reviews`,
      {
        headers: {
          Accept: "application/json",
        },
        cache: "no-store",
      },
    );

    if (!response.ok) {
      return [];
    }

    const result = (await response.json()) as {
      success: boolean;
      data: {
        data: Review[];
      };
    };

    return result.data?.data ?? [];
  } catch {
    return [];
  }
}
export async function getFeaturedBusinesses(): Promise<Business[]> {
const result = await searchBusinesses({ per_page: "6" });
return result.data ?? [];
}
