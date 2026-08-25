import { NextResponse } from "next/server";
import { getApiUrl } from "@/lib/auth";

export async function GET() {
  try {
    const response = await fetch(
      `${getApiUrl()}/v1/categories`,
      {
        headers: { Accept: "application/json" },
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
