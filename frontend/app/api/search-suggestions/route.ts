import { NextRequest, NextResponse } from "next/server";

const API_URL =
  process.env.YELLOWPAGES_API_URL ?? "http://127.0.0.1:8000/api";

export async function GET(request: NextRequest) {
  const query = request.nextUrl.searchParams.get("q") ?? "";

  if (query.trim().length < 2) {
    return NextResponse.json({ data: [] });
  }

  try {
    const response = await fetch(
      `${API_URL}/v1/search/suggestions?q=${encodeURIComponent(query)}`,
      {
        headers: { Accept: "application/json" },
        cache: "no-store",
      },
    );

    if (!response.ok) {
      return NextResponse.json({ data: [] });
    }

    const body = (await response.json()) as { data?: unknown[] };

    return NextResponse.json({ data: body.data ?? [] });
  } catch {
    return NextResponse.json({ data: [] });
  }
}
