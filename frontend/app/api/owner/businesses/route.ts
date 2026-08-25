import { NextResponse } from "next/server";
import { getApiUrl, getAuthToken } from "@/lib/auth";

async function proxy(method: "GET" | "POST", body?: unknown) {
  const token = await getAuthToken();
  if (!token) return NextResponse.json({ message: "Unauthenticated." }, { status: 401 });
  try {
    const response = await fetch(`${getApiUrl()}/businesses`, {
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

export async function GET() { return proxy("GET"); }
export async function POST(request: Request) {
  try { return proxy("POST", await request.json()); }
  catch { return NextResponse.json({ message: "Invalid request." }, { status: 400 }); }
}
