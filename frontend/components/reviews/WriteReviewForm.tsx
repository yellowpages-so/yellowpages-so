"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

type Props = {
  businessId: string;
};

export function WriteReviewForm({
  businessId,
}: Props) {
  const router = useRouter();

  const [rating, setRating] = useState(5);
  const [title, setTitle] = useState("");
  const [body, setBody] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  async function submitReview() {
    setLoading(true);
    setError("");
    setSuccess("");

    try {
      const response = await fetch(
        `/api/businesses/${encodeURIComponent(
          businessId,
        )}/reviews`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            rating,
            title: title.trim() || null,
            body: body.trim(),
          }),
        },
      );

      const payload = await response.json();

      if (!response.ok) {
        setError(
          payload.message ??
            "Review submission failed.",
        );
        return;
      }

      setSuccess("Review submitted successfully.");
      setTitle("");
      setBody("");
      setRating(5);
      router.refresh();
    } catch {
      setError("Review submission failed.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <section
      id="write-review"
      className="card p-7"
    >
      <h2 className="text-2xl font-black">
        Write a review
      </h2>

      <p className="mt-2 text-sm text-neutral-600">
        Share your experience with this business.
      </p>

      <div className="mt-5 grid gap-4">
        <label className="grid gap-2">
          <span className="text-sm font-bold">
            Rating
          </span>

          <select
            value={rating}
            onChange={(event) =>
              setRating(Number(event.target.value))
            }
            className="rounded-xl border border-black/15 bg-white px-3 py-2"
          >
            <option value={5}>5 stars</option>
            <option value={4}>4 stars</option>
            <option value={3}>3 stars</option>
            <option value={2}>2 stars</option>
            <option value={1}>1 star</option>
          </select>
        </label>

        <label className="grid gap-2">
          <span className="text-sm font-bold">
            Title
          </span>

          <input
            value={title}
            onChange={(event) =>
              setTitle(event.target.value)
            }
            maxLength={255}
            className="rounded-xl border border-black/15 px-3 py-2"
            placeholder="Optional review title"
          />
        </label>

        <label className="grid gap-2">
          <span className="text-sm font-bold">
            Review
          </span>

          <textarea
            value={body}
            onChange={(event) =>
              setBody(event.target.value)
            }
            minLength={10}
            maxLength={5000}
            rows={6}
            className="rounded-xl border border-black/15 px-3 py-2"
            placeholder="Tell others about your experience."
          />
        </label>

        {error ? (
          <p className="text-sm text-red-700">
            {error}
          </p>
        ) : null}

        {success ? (
          <p className="text-sm text-green-700">
            {success}
          </p>
        ) : null}

        <button
          type="button"
          onClick={() => void submitReview()}
          disabled={
            loading || body.trim().length < 10
          }
          className="rounded-xl bg-black px-4 py-3 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-40"
        >
          {loading
            ? "Submitting..."
            : "Submit review"}
        </button>
      </div>
    </section>
  );
}
