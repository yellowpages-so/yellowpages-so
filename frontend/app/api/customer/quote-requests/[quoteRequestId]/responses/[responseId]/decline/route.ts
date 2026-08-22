import { NextResponse } from "next/server";

import {
  getApiUrl,
  getAuthToken,
} from "@/lib/auth";

type Context = {
  params: Promise<{
    quoteRequestId: string;
    responseId: string;
  }>;
};

export async function POST(
  _request: Request,
  context: Context,
) {
  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json(
      { message: "Unauthenticated." },
      { status: 401 },
    );
  }

  const {
    quoteRequestId,
    responseId,
  } = await context.params;

  try {
    const response = await fetch(
      getApiUrl() +
        "/v1/customer/quote-requests/" +
        encodeURIComponent(quoteRequestId) +
        "/responses/" +
        encodeURIComponent(responseId) +
        "/decline",
      {
        method: "POST",
        headers: {
          Accept: "application/json",
          Authorization: "Bearer " + token,
        },
        cache: "no-store",
      },
    );

    const payload = await response.json();

    return NextResponse.json(
      payload,
      { status: response.status },
    );
  } catch {
    return NextResponse.json(
      {
        message:
          "The quote decline service is unavailable.",
      },
      { status: 503 },
    );
  }
}
