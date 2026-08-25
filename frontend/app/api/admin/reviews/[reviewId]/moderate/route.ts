import { NextResponse } from "next/server";

import {
  getApiUrl,
  getAuthToken,
} from "@/lib/auth";

type Context = {
  params: Promise<{
    reviewId: string;
  }>;
};

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

  const { reviewId } = await context.params;

  try {
    const body = await request.json();

    const response = await fetch(
      getApiUrl() +
        "/v1/admin/reviews/" +
        encodeURIComponent(reviewId) +
        "/moderate",
      {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          Authorization: "Bearer " + token,
        },
        body: JSON.stringify(body),
        cache: "no-store",
      },
    );

    const payload = await response.json();

    return NextResponse.json(payload, {
      status: response.status,
    });
  } catch {
    return NextResponse.json(
      {
        message:
          "The review moderation service is unavailable.",
      },
      { status: 503 },
    );
  }
}
