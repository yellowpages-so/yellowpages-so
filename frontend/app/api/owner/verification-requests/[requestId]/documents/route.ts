import { NextResponse } from "next/server";
import { getApiUrl, getAuthToken } from "@/lib/auth";

type Context = {
  params: Promise<{ requestId: string }>;
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

  const { requestId } = await context.params;

  let form: FormData;
  try {
    form = await request.formData();
  } catch {
    return NextResponse.json(
      { message: "Invalid upload." },
      { status: 400 },
    );
  }

  try {
    const response = await fetch(
      `${getApiUrl()}/v1/verification-requests/${encodeURIComponent(
        requestId,
      )}/documents`,
      {
        method: "POST",
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: form,
        cache: "no-store",
      },
    );

    return NextResponse.json(
      await response.json(),
      { status: response.status },
    );
  } catch {
    return NextResponse.json(
      { message: "The verification document service is unavailable." },
      { status: 503 },
    );
  }
}
