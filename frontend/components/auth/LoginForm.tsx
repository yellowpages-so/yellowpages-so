"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

type ErrorPayload = {
  message?: string;
  errors?: Record<string, string[]>;
};

export function LoginForm() {
  const router = useRouter();
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setSubmitting(true);

    const form = new FormData(event.currentTarget);

    const response = await fetch("/api/auth/login", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        email: String(form.get("email") ?? ""),
        password: String(form.get("password") ?? ""),
        device_name: "yellowpages-web",
      }),
    });

    const payload = (await response.json()) as ErrorPayload;

    if (!response.ok) {
      const firstValidationError = payload.errors
        ? Object.values(payload.errors).flat()[0]
        : undefined;

      setError(
        firstValidationError ??
          payload.message ??
          "We could not sign you in.",
      );
      setSubmitting(false);
      return;
    }

    router.push("/owner");
    router.refresh();
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-5">
      <div>
        <label
          htmlFor="email"
          className="mb-2 block text-sm font-semibold text-black"
        >
          Email address
        </label>
        <input
          id="email"
          name="email"
          type="email"
          autoComplete="email"
          required
          className="focus-ring w-full rounded-xl border border-black/15 px-4 py-3 outline-none"
          placeholder="you@example.com"
        />
      </div>

      <div>
        <label
          htmlFor="password"
          className="mb-2 block text-sm font-semibold text-black"
        >
          Password
        </label>
        <input
          id="password"
          name="password"
          type="password"
          autoComplete="current-password"
          required
          className="focus-ring w-full rounded-xl border border-black/15 px-4 py-3 outline-none"
          placeholder="Your password"
        />
      </div>

      {error ? (
        <div
          role="alert"
          className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
        >
          {error}
        </div>
      ) : null}

      <button
        type="submit"
        disabled={submitting}
        className="focus-ring w-full rounded-xl bg-black px-5 py-3 font-bold text-white disabled:cursor-not-allowed disabled:opacity-60"
      >
        {submitting ? "Signing in..." : "Sign in"}
      </button>

      <p className="text-center text-sm text-black/65">
        New to YellowPages.so?{" "}
        <Link href="/register" className="font-bold text-black underline">
          Create an account
        </Link>
      </p>
    </form>
  );
}
