import { NextResponse } from "next/server";
import { AUTH_COOKIE, getApiUrl, getAuthToken } from "@/lib/auth";

export async function POST() {
  const token = await getAuthToken();

  if (token) {
    try {
      await fetch(`${getApiUrl()}/auth/logout`, {
        method: "POST",
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
        },
        cache: "no-store",
      });
    } catch {
      // Clear the local session even if Laravel is temporarily unavailable.
    }
  }

  const response = NextResponse.json({
    message: "Logged out successfully.",
  });

  response.cookies.delete(AUTH_COOKIE);

  return response;
}
