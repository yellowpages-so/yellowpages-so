"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

type Props = {
  quoteRequestId: string;
};

export function CancelQuoteRequestButton({
  quoteRequestId,
}: Props) {
  const router = useRouter();

  const [loading, setLoading] =
    useState(false);

  const [error, setError] =
    useState("");

  async function cancelRequest() {
    setLoading(true);
    setError("");

    try {
      const response = await fetch(
        "/api/customer/quote-requests/" +
          encodeURIComponent(quoteRequestId) +
          "/cancel",
        {
          method: "POST",
        },
      );

      const payload = await response.json();

      if (!response.ok) {
        setError(
          payload.message ??
            "Quote request cancellation failed.",
        );
        return;
      }

      router.refresh();
    } catch {
      setError(
        "Quote request cancellation failed.",
      );
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mt-4">
      {error ? (
        <p className="mb-3 text-sm text-red-700">
          {error}
        </p>
      ) : null}

      <button
        type="button"
        onClick={() => void cancelRequest()}
        disabled={loading}
        className="rounded-xl border border-black/15 px-4 py-2 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-40"
      >
        {loading
          ? "Cancelling..."
          : "Cancel request"}
      </button>
    </div>
  );
}
