import { NextRequest, NextResponse } from "next/server";

const API_URL =
  process.env.YELLOWPAGES_API_URL ?? "http://127.0.0.1:8000/api";

export async function GET(
  request: NextRequest,
  context: { params: Promise<{ creativeId: string }> },
) {
  const { creativeId } = await context.params;

  const response = await fetch(
    `${API_URL}/v1/advertising/click/${encodeURIComponent(creativeId)}`,
    {
      redirect: "manual",
      headers: {
        Accept: "application/json",
        "X-Session-ID":
          request.cookies.get("yp_session")?.value ?? "",
      },
      cache: "no-store",
    },
  );

  const location = response.headers.get("location");

  return NextResponse.redirect(
    location ?? new URL("/", request.url),
  );
}
