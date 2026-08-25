import { NextResponse } from "next/server";
import { getApiUrl } from "@/lib/auth";

export async function GET() {
  try {
    const response = await fetch(
      `${getApiUrl()}/v1/locations/regions`,
      {
        headers: { Accept: "application/json" },
        cache: "no-store",
      },
    );

    const payload = await response.json();

    return NextResponse.json(payload, {
      status: response.status,
    });
  } catch {
    return NextResponse.json(
      { message: "The location service is unavailable." },
      { status: 503 },
    );
  }
}
