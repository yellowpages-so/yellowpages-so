import { NextResponse } from "next/server";
import { getApiUrl, getAuthToken } from "@/lib/auth";

type Context = {
  params: Promise<{ businessId: string }>;
};

export async function GET(
  request: Request,
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

  try {
    const response = await fetch(
      `${getApiUrl()}/v1/owner/businesses/${encodeURIComponent(
        businessId,
      )}/verification-status`,
      {
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
        },
        cache: "no-store",
      },
    );

    return NextResponse.json(
      await response.json(),
      { status: response.status },
    );
  } catch {
    return NextResponse.json(
      { message: "The verification service is unavailable." },
      { status: 503 },
    );
  }
}

export async function POST(
  request: Request,
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
  try {
    body = await request.json();
  } catch {
    return NextResponse.json(
      { message: "Invalid request." },
      { status: 400 },
    );
  }

  try {
    const response = await fetch(
      `${getApiUrl()}/v1/businesses/${encodeURIComponent(
        businessId,
      )}/verification-requests`,
      {
        method: "POST",
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify(body),
        cache: "no-store",
      },
    );

    return NextResponse.json(
      await response.json(),
      { status: response.status },
    );
  } catch {
    return NextResponse.json(
      { message: "The verification service is unavailable." },
      { status: 503 },
    );
  }
}
