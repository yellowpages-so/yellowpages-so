import { NextResponse } from "next/server";
import { getApiUrl, getAuthToken } from "@/lib/auth";

type Context = {
  params: Promise<{
    requestId: string;
    checkId: string;
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

  const { requestId, checkId } =
    await context.params;

  const body = await request.json();

  try {
    const response = await fetch(
      `${getApiUrl()}/v1/admin/verification-requests/${encodeURIComponent(
        requestId,
      )}/checks/${encodeURIComponent(
        checkId,
      )}`,
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
      {
        message:
          "The verification check service is unavailable.",
      },
      { status: 503 },
    );
  }
}
