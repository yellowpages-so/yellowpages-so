import { NextResponse } from "next/server";

import {
  getApiUrl,
  getAuthToken,
} from "@/lib/auth";

export async function GET() {
  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json(
      { message: "Unauthenticated." },
      { status: 401 },
    );
  }

  try {
    const response = await fetch(
      getApiUrl() + "/v1/admin/reviews",
      {
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
          "The review moderation service is unavailable.",
      },
      { status: 503 },
    );
  }
}
