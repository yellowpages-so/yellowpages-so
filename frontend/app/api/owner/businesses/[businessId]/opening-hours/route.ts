import { NextResponse } from "next/server";
import { getApiUrl, getAuthToken } from "@/lib/auth";

type Context = { params: Promise<{ businessId: string }> };

export async function PUT(request: Request, context: Context) {
  const token = await getAuthToken();
  if (!token) return NextResponse.json({ message: "Unauthenticated." }, { status: 401 });

  const { businessId } = await context.params;
  let body: unknown;

  try { body = await request.json(); }
  catch { return NextResponse.json({ message: "Invalid request." }, { status: 400 }); }

  try {
    const response = await fetch(
      `${getApiUrl()}/v1/owner/businesses/${encodeURIComponent(businessId)}/opening-hours`,
      {
        method: "PUT",
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify(body),
        cache: "no-store",
      },
    );

    const text = await response.text();
    const payload = text ? JSON.parse(text) : {};
    return NextResponse.json(payload, { status: response.status });
  } catch {
    return NextResponse.json({ message: "The opening-hours service is unavailable." }, { status: 503 });
  }
}
