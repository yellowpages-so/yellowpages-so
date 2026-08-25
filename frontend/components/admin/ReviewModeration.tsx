"use client";

import { useEffect, useState } from "react";

type Review = {
  id: string;
  trading_name?: string | null;
  reviewer_name?: string | null;
  rating: number;
  title?: string | null;
  body?: string | null;
  status?: string | null;
  moderation_score?: number | null;
  created_at?: string | null;
};

type Payload = {
  data?: {
    data?: Review[];
  };
};

export function ReviewModeration() {
  const [reviews, setReviews] = useState<Review[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [busyId, setBusyId] = useState<string | null>(null);

  async function loadReviews() {
    setLoading(true);
    setError("");

    try {
      const response = await fetch("/api/admin/reviews", {
        cache: "no-store",
      });

      const payload = (await response.json()) as Payload & {
        message?: string;
      };

      if (!response.ok) {
        setError(payload.message ?? "Failed to load reviews.");
        return;
      }

      setReviews(payload.data?.data ?? []);
    } catch {
      setError("Failed to load reviews.");
    } finally {
      setLoading(false);
    }
  }

  async function moderate(
    reviewId: string,
    action: "publish" | "hide" | "reject" | "restore",
  ) {
    setBusyId(reviewId);
    setError("");

    try {
      const response = await fetch(
        `/api/admin/reviews/${encodeURIComponent(
          reviewId,
        )}/moderate`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            action,
            reason_code: null,
            notes: null,
          }),
        },
      );

      const payload = await response.json();

      if (!response.ok) {
        setError(
          payload.message ?? "Review moderation failed.",
        );
        return;
      }

      await loadReviews();
    } catch {
      setError("Review moderation failed.");
    } finally {
      setBusyId(null);
    }
  }

  useEffect(() => {
    void loadReviews();
  }, []);

  if (loading) {
    return (
      <p className="text-sm text-black/60">
        Loading reviews...
      </p>
    );
  }

  if (error && reviews.length === 0) {
    return (
      <p className="text-sm text-red-700">
        {error}
      </p>
    );
  }

  return (
    <div className="grid gap-5">
      {error ? (
        <p className="text-sm text-red-700">
          {error}
        </p>
      ) : null}

      {reviews.length === 0 ? (
        <div className="rounded-2xl border border-black/10 bg-white p-6">
          <p className="text-black/60">
            No reviews in the moderation queue.
          </p>
        </div>
      ) : (
        reviews.map((review) => (
          <article
            key={review.id}
            className="rounded-2xl border border-black/10 bg-white p-6"
          >
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <p className="text-sm font-bold text-black/50">
                  {review.trading_name ?? "Business"}
                </p>

                <h2 className="mt-1 text-lg font-black">
                  {review.reviewer_name ??
                    "YellowPages.so user"}
                </h2>

                <p className="mt-2 text-sm text-black/60">
                  {"★".repeat(review.rating)}
                  {"☆".repeat(
                    Math.max(0, 5 - review.rating),
                  )}
                </p>
              </div>

              <div className="text-right text-xs text-black/50">
                <p>
                  Status: {review.status ?? "unknown"}
                </p>
                <p className="mt-1">
                  Moderation score:{" "}
                  {review.moderation_score ?? 0}
                </p>
              </div>
            </div>

            {review.title ? (
              <h3 className="mt-5 font-black">
                {review.title}
              </h3>
            ) : null}

            {review.body ? (
              <p className="mt-2 leading-7 text-black/70">
                {review.body}
              </p>
            ) : null}
            <div className="mt-5 flex flex-wrap gap-3">
              {review.status === "pending" ? (
                <>
                  <button
                    type="button"
                    onClick={() =>
                      void moderate(review.id, "publish")
                    }
                    disabled={busyId === review.id}
                    className="rounded-xl bg-black px-4 py-2 text-sm font-bold text-white disabled:opacity-40"
                  >
                    Publish
                  </button>

                  <button
                    type="button"
                    onClick={() =>
                      void moderate(review.id, "reject")
                    }
                    disabled={busyId === review.id}
                    className="rounded-xl border border-black/15 px-4 py-2 text-sm font-bold disabled:opacity-40"
                  >
                    Reject
                  </button>
                </>
              ) : null}

              {review.status === "approved" ? (
                <button
                  type="button"
                  onClick={() =>
                    void moderate(review.id, "hide")
                  }
                  disabled={busyId === review.id}
                  className="rounded-xl border border-black/15 px-4 py-2 text-sm font-bold disabled:opacity-40"
                >
                  Hide
                </button>
              ) : null}

              {review.status === "hidden" ||
              review.status === "rejected" ? (
                <button
                  type="button"
                  onClick={() =>
                    void moderate(review.id, "restore")
                  }
                  disabled={busyId === review.id}
                  className="rounded-xl bg-black px-4 py-2 text-sm font-bold text-white disabled:opacity-40"
                >
                  Restore
                </button>
              ) : null}
            </div>
          </article>
        ))
      )}
    </div>
  );
}
