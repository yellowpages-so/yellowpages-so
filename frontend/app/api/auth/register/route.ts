import { NextResponse } from "next/server";
import { AUTH_COOKIE, getApiUrl } from "@/lib/auth";

type LaravelRegisterResponse = {
  message?: string;
  token?: string;
  user?: unknown;
  errors?: Record<string, string[]>;
};

export async function POST(request: Request) {
  let body: unknown;

  try {
    body = await request.json();
  } catch {
    return NextResponse.json(
      { message: "Invalid request." },
      { status: 400 },
    );
  }

  try {
    const response = await fetch(`${getApiUrl()}/auth/register`, {
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify(body),
      cache: "no-store",
    });

    const payload = (await response.json()) as LaravelRegisterResponse;

    if (!response.ok || !payload.token) {
      return NextResponse.json(payload, { status: response.status });
    }

    const result = NextResponse.json(
      {
        message: payload.message ?? "Account created successfully.",
        user: payload.user,
      },
      { status: 201 },
    );

    result.cookies.set({
      name: AUTH_COOKIE,
      value: payload.token,
      httpOnly: true,
      secure: process.env.NODE_ENV === "production",
      sameSite: "lax",
      path: "/",
    });

    return result;
  } catch {
    return NextResponse.json(
      { message: "The registration service is unavailable." },
      { status: 503 },
    );
  }
}
