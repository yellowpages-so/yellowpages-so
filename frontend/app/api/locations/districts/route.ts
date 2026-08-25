import { NextResponse } from "next/server";
import { getApiUrl } from "@/lib/auth";

export async function GET(request: Request) {
  const url = new URL(request.url);
  const cityId = url.searchParams.get("city_id");
  const regionId = url.searchParams.get("region_id");
  const params = new URLSearchParams();

  if (cityId) {
    params.set("city_id", cityId);
  }

  if (regionId) {
    params.set("region_id", regionId);
  }

  try {
    const response = await fetch(
      `${getApiUrl()}/v1/locations/districts?${params.toString()}`,
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
