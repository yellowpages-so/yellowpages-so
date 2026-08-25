import { NextResponse } from "next/server";
import { getApiUrl, getAuthToken } from "@/lib/auth";

type Context = {
  params: Promise<{ requestId: string }>;
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

  const { requestId } = await context.params;

  try {
    const response = await fetch(
      `${getApiUrl()}/v1/admin/verification-requests/${encodeURIComponent(
        requestId,
      )}`,
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
