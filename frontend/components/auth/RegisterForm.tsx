"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

type ErrorPayload = {
  message?: string;
  errors?: Record<string, string[]>;
};

export function RegisterForm() {
  const router = useRouter();
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setSubmitting(true);

    const form = new FormData(event.currentTarget);
    const password = String(form.get("password") ?? "");
    const passwordConfirmation = String(
      form.get("password_confirmation") ?? "",
    );

    if (password !== passwordConfirmation) {
      setError("Passwords do not match.");
      setSubmitting(false);
      return;
    }

    const response = await fetch("/api/auth/register", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        first_name: String(form.get("first_name") ?? ""),
        last_name: String(form.get("last_name") ?? ""),
        email: String(form.get("email") ?? ""),
        password,
        password_confirmation: passwordConfirmation,
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
          "We could not create your account.",
      );
      setSubmitting(false);
      return;
    }

    router.push("/owner");
    router.refresh();
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-5">
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label
            htmlFor="first_name"
            className="mb-2 block text-sm font-semibold text-black"
          >
            First name
          </label>
          <input
            id="first_name"
            name="first_name"
            type="text"
            autoComplete="given-name"
            required
            className="focus-ring w-full rounded-xl border border-black/15 px-4 py-3 outline-none"
          />
        </div>

        <div>
          <label
            htmlFor="last_name"
            className="mb-2 block text-sm font-semibold text-black"
          >
            Last name
          </label>
          <input
            id="last_name"
            name="last_name"
            type="text"
            autoComplete="family-name"
            required
            className="focus-ring w-full rounded-xl border border-black/15 px-4 py-3 outline-none"
          />
        </div>
      </div>

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
          minLength={8}
          autoComplete="new-password"
          required
          className="focus-ring w-full rounded-xl border border-black/15 px-4 py-3 outline-none"
          placeholder="At least 8 characters"
        />
      </div>

      <div>
        <label
          htmlFor="password_confirmation"
          className="mb-2 block text-sm font-semibold text-black"
        >
          Confirm password
        </label>
        <input
          id="password_confirmation"
          name="password_confirmation"
          type="password"
          minLength={8}
          autoComplete="new-password"
          required
          className="focus-ring w-full rounded-xl border border-black/15 px-4 py-3 outline-none"
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
        {submitting ? "Creating account..." : "Create account"}
      </button>

      <p className="text-center text-sm text-black/65">
        Already have an account?{" "}
        <Link href="/login" className="font-bold text-black underline">
          Sign in
        </Link>
      </p>
    </form>
  );
}
