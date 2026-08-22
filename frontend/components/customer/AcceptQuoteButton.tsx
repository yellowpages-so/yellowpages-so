"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

type Props = {
  quoteRequestId: string;
  responseId: string;
  disabled?: boolean;
};

export function AcceptQuoteButton({
  quoteRequestId,
  responseId,
  disabled = false,
}: Props) {
  const router = useRouter();

  const [loading, setLoading] =
    useState(false);

  const [error, setError] =
    useState("");

  async function acceptQuote() {
    setLoading(true);
    setError("");

    try {
      const response = await fetch(
        "/api/customer/quote-requests/" +
          encodeURIComponent(quoteRequestId) +
          "/responses/" +
          encodeURIComponent(responseId) +
          "/accept",
        {
          method: "POST",
        },
      );

      const payload = await response.json();

      if (!response.ok) {
        setError(
          payload.message ??
            "Quote acceptance failed.",
        );
        return;
      }

      router.refresh();
    } catch {
      setError("Quote acceptance failed.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      {error ? (
        <p className="mb-3 text-sm text-red-700">
          {error}
        </p>
      ) : null}

      <button
        type="button"
        onClick={() => void acceptQuote()}
        disabled={disabled || loading}
        className="rounded-xl bg-black px-4 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-40"
      >
        {loading
          ? "Accepting..."
          : "Accept quote"}
      </button>
    </div>
  );
}
