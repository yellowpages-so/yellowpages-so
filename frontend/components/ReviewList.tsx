type Review = {
  id: string;
  rating: number;
  title?: string | null;
  body?: string | null;
  reviewer_name?: string | null;
  verified_customer?: boolean;
  helpful_count?: number;
  business_reply?: string | null;
};

export function ReviewList({ reviews }: { reviews: Review[] }) {
  if (reviews.length === 0) {
    return <p className="text-neutral-600">No reviews yet.</p>;
  }

  return (
    <div className="grid gap-4">
      {reviews.map((review) => (
        <article
          key={review.id}
          className="rounded-2xl border border-black/10 p-5"
        >
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p className="font-black">
                {review.reviewer_name ?? "YellowPages.so user"}
              </p>
              <p className="mt-1 text-sm text-neutral-500">
                {"★".repeat(review.rating)}
                {"☆".repeat(Math.max(0, 5 - review.rating))}
              </p>
            </div>
            {review.verified_customer && (
              <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                Verified customer
              </span>
            )}
          </div>

          {review.title && (
            <h3 className="mt-4 font-black">{review.title}</h3>
          )}

          {review.body && (
            <p className="mt-2 leading-7 text-neutral-700">
              {review.body}
            </p>
          )}

          {review.business_reply && (
            <div className="mt-4 rounded-xl bg-neutral-50 p-4">
              <p className="text-xs font-black uppercase tracking-wide text-neutral-500">
                Business reply
              </p>
              <p className="mt-2 text-sm leading-6">
                {review.business_reply}
              </p>
            </div>
          )}

          <p className="mt-4 text-xs text-neutral-500">
            Helpful: {review.helpful_count ?? 0}
          </p>
        </article>
      ))}
    </div>
  );
}
