import { NextResponse } from "next/server";
import { getApiUrl, getAuthToken } from "@/lib/auth";

type Context = { params: Promise<{ businessId: string }> };

async function forward(method: "GET" | "POST", request: Request | null, context: Context) {
  const token = await getAuthToken();
  if (!token) return NextResponse.json({ message: "Unauthenticated." }, { status: 401 });

  const { businessId } = await context.params;
  let body: unknown;

  if (method === "POST" && request) {
    try { body = await request.json(); }
    catch { return NextResponse.json({ message: "Invalid request." }, { status: 400 }); }
  }

  try {
    const response = await fetch(
      `${getApiUrl()}/v1/owner/businesses/${encodeURIComponent(businessId)}/contacts`,
      {
        method,
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
          ...(method === "POST" ? { "Content-Type": "application/json" } : {}),
        },
        ...(method === "POST" ? { body: JSON.stringify(body) } : {}),
        cache: "no-store",
      },
    );
    const payload = await response.json();
    return NextResponse.json(payload, { status: response.status });
  } catch {
    return NextResponse.json({ message: "The contact service is unavailable." }, { status: 503 });
  }
}

export async function GET(request: Request, context: Context) { return forward("GET", request, context); }
export async function POST(request: Request, context: Context) { return forward("POST", request, context); }
