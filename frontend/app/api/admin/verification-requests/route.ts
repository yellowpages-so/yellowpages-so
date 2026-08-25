import { NextResponse } from "next/server";
import { getApiUrl, getAuthToken } from "@/lib/auth";

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
      `${getApiUrl()}/v1/admin/verification-requests`,
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
      {
        message:
          "The admin verification service is unavailable.",
      },
      { status: 503 },
    );
  }
}
