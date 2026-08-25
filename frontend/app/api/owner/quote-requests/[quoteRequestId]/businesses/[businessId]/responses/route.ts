import { NextResponse } from "next/server";
import {
  getApiUrl,
  getAuthToken,
} from "@/lib/auth";

type Context = {
  params: Promise<{
    quoteRequestId: string;
    businessId: string;
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

  const {
    quoteRequestId,
    businessId,
  } = await context.params;

  const body = await request.json();

  try {
    const response = await fetch(
      getApiUrl() +
        "/v1/quote-requests/" +
        encodeURIComponent(quoteRequestId) +
        "/businesses/" +
        encodeURIComponent(businessId) +
        "/responses",
      {
        method: "POST",
        headers: {
          Accept: "application/json",
          Authorization: "Bearer " + token,
          "Content-Type": "application/json",
        },
        body: JSON.stringify(body),
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
          "The quote response service is unavailable.",
      },
      { status: 503 },
    );
  }
}
