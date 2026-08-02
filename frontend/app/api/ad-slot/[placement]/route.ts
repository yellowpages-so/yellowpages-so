import { NextRequest, NextResponse } from "next/server";

const API_URL =
  process.env.YELLOWPAGES_API_URL ?? "http://127.0.0.1:8000/api";

export async function GET(
  request: NextRequest,
  context: { params: Promise<{ placement: string }> },
) {
  const { placement } = await context.params;
  const query = request.nextUrl.searchParams.toString();

  try {
    const response = await fetch(
      `${API_URL}/v1/advertising/slots/${encodeURIComponent(placement)}?${query}`,
      {
        headers: {
          Accept: "application/json",
          "X-Session-ID":
            request.cookies.get("yp_session")?.value ?? "",
        },
        cache: "no-store",
      },
    );

    if (!response.ok) {
      return NextResponse.json({ data: null });
    }

    return NextResponse.json(await response.json());
  } catch {
    return NextResponse.json({ data: null });
  }
}
