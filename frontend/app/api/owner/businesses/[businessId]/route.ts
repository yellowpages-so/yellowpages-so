import { NextResponse } from "next/server";
import { getApiUrl, getAuthToken } from "@/lib/auth";

type Ctx = { params: Promise<{ businessId: string }> };

async function proxy(method: "GET" | "PATCH", request: Request | null, context: Ctx) {
  const token = await getAuthToken();
  if (!token) return NextResponse.json({ message: "Unauthenticated." }, { status: 401 });
  const { businessId } = await context.params;
  let body: unknown = undefined;
  if (request && method === "PATCH") {
    try { body = await request.json(); }
    catch { return NextResponse.json({ message: "Invalid request." }, { status: 400 }); }
  }
  try {
    const response = await fetch(`${getApiUrl()}/businesses/${encodeURIComponent(businessId)}`, {
      method,
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
        ...(body ? { "Content-Type": "application/json" } : {}),
      },
      ...(body ? { body: JSON.stringify(body) } : {}),
      cache: "no-store",
    });
    const payload = await response.json();
    return NextResponse.json(payload, { status: response.status });
  } catch {
    return NextResponse.json({ message: "The business service is unavailable." }, { status: 503 });
  }
}

export async function GET(request: Request, context: Ctx) { return proxy("GET", request, context); }
export async function PATCH(request: Request, context: Ctx) { return proxy("PATCH", request, context); }
