"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

type Props = {
  reviewId: string;
  initialReply?: string | null;
};

export function ReviewReplyForm({
  reviewId,
  initialReply,
}: Props) {
  const router = useRouter();

  const [reply, setReply] = useState(
    initialReply ?? "",
  );
  const [loading, setLoading] =
    useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  async function submitReply() {
    setLoading(true);
    setError("");
    setSuccess("");

    try {
      const response = await fetch(
        `/api/reviews/${encodeURIComponent(
          reviewId,
        )}/reply`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            reply: reply.trim(),
          }),
        },
      );

      const payload = await response.json();

      if (!response.ok) {
        setError(
          payload.message ??
            "Review reply failed.",
        );
        return;
      }

      setSuccess("Reply saved successfully.");
      router.refresh();
    } catch {
      setError("Review reply failed.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mt-4 rounded-xl bg-neutral-50 p-4">
      <label className="grid gap-2">
        <span className="text-sm font-bold">
          Business reply
        </span>

        <textarea
          value={reply}
          onChange={(event) =>
            setReply(event.target.value)
          }
          minLength={2}
          maxLength={3000}
          rows={4}
          placeholder="Reply to this customer review."
          className="rounded-xl border border-black/15 bg-white px-3 py-2"
        />
      </label>

      {error ? (
        <p className="mt-3 text-sm text-red-700">
          {error}
        </p>
      ) : null}

      {success ? (
        <p className="mt-3 text-sm text-green-700">
          {success}
        </p>
      ) : null}

      <button
        type="button"
        onClick={() => void submitReply()}
        disabled={
          loading || reply.trim().length < 2
        }
        className="mt-4 rounded-xl bg-black px-4 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-40"
      >
        {loading
          ? "Saving..."
          : initialReply
            ? "Update reply"
            : "Post reply"}
      </button>
    </div>
  );
}
