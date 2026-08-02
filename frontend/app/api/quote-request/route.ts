import { NextRequest, NextResponse } from "next/server";

const API_URL =
  process.env.YELLOWPAGES_API_URL ?? "http://127.0.0.1:8000/api";

export async function POST(request: NextRequest) {
  const form = await request.formData();

  const payload = {
    title: form.get("title"),
    description: form.get("description"),
    contact_name: form.get("contact_name"),
    contact_email: form.get("contact_email"),
    preferred_contact: form.get("preferred_contact") ?? "email",
  };

  const response = await fetch(`${API_URL}/v1/quote-requests`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
    },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    return NextResponse.redirect(
      new URL("/request-a-quote?status=error", request.url),
    );
  }

  return NextResponse.redirect(
    new URL("/request-a-quote?status=success", request.url),
  );
}
