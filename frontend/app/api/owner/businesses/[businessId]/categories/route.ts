import { NextResponse } from "next/server";
import { getApiUrl, getAuthToken } from "@/lib/auth";

type Context = {
  params: Promise<{ businessId: string }>;
};

async function forward(
  method: "GET" | "PUT",
  request: Request | null,
  context: Context,
) {
  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json(
      { message: "Unauthenticated." },
      { status: 401 },
    );
  }

  const { businessId } = await context.params;
  let body: unknown;

  if (method === "PUT" && request) {
    body = await request.json();
  }

  try {
    const response = await fetch(
      `${getApiUrl()}/v1/owner/businesses/${encodeURIComponent(
        businessId,
      )}/categories`,
      {
        method,
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
          ...(method === "PUT"
            ? { "Content-Type": "application/json" }
            : {}),
        },
        ...(method === "PUT"
          ? { body: JSON.stringify(body) }
          : {}),
        cache: "no-store",
      },
    );

    return NextResponse.json(await response.json(), {
      status: response.status,
    });
  } catch {
    return NextResponse.json(
      { message: "The category service is unavailable." },
      { status: 503 },
    );
  }
}

export async function GET(
  request: Request,
  context: Context,
) {
  return forward("GET", request, context);
}

export async function PUT(
  request: Request,
  context: Context,
) {
  return forward("PUT", request, context);
}
