import Link from "next/link";
import { notFound, redirect } from "next/navigation";

import { ReviewReplyForm } from "@/components/reviews/ReviewReplyForm";
import { getApiUrl, getCurrentUser } from "@/lib/auth";
import { getOwnerBusiness } from "@/lib/owner-api";
import type { Review } from "@/lib/types";

type PageProps = {
  params: Promise<{
    businessId: string;
  }>;
};

type ReviewsResponse = {
  data?: {
    data?: Review[];
  };
};

export default async function OwnerReviewsPage({
  params,
}: PageProps) {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login");
  }

  const { businessId } = await params;
  const business = await getOwnerBusiness(businessId);

  if (!business) {
    notFound();
  }

const publicId = business.public_id ?? business.id;
  if (!publicId) {
    notFound();
  }

  let reviews: Review[] = [];

  try {
    const response = await fetch(
      `${getApiUrl()}/v1/businesses/${encodeURIComponent(
        publicId,
      )}/reviews`,
      {
        headers: {
          Accept: "application/json",
        },
        cache: "no-store",
      },
    );

    if (response.ok) {
      const payload =
        (await response.json()) as ReviewsResponse;

      reviews = payload.data?.data ?? [];
    }
  } catch {
    reviews = [];
  }

  return (
    <main className="mx-auto w-full max-w-5xl px-5 py-12 sm:py-16">
      <Link
        href={`/owner/businesses/${encodeURIComponent(
          businessId,
        )}`}
        className="text-sm font-semibold text-black/55 hover:text-black"
      >
        Back to business
      </Link>

      <div className="mt-4">
        <h1 className="text-3xl font-black tracking-tight sm:text-4xl">
          Reviews
        </h1>

        <p className="mt-2 text-neutral-600">
          Read customer feedback and manage replies for{" "}
          {business.trading_name}.
        </p>
      </div>

      <div className="mt-8 grid gap-5">
        {reviews.length ? (
          reviews.map((review) => (
            <article
              key={review.id}
              className="rounded-2xl border border-black/10 bg-white p-6"
            >
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <p className="font-black">
                    {review.reviewer_name ??
                      "YellowPages.so user"}
                  </p>

                  <p className="mt-1 text-sm text-neutral-500">
                    {"★".repeat(review.rating)}
                    {"☆".repeat(
                      Math.max(0, 5 - review.rating),
                    )}
                  </p>
                </div>

                {review.verified_customer ? (
                  <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                    Verified customer
                  </span>
                ) : null}
              </div>

              {review.title ? (
                <h2 className="mt-4 text-lg font-black">
                  {review.title}
                </h2>
              ) : null}

              {review.body ? (
                <p className="mt-2 leading-7 text-neutral-700">
                  {review.body}
                </p>
              ) : null}

              <ReviewReplyForm
                reviewId={review.id}
                initialReply={review.business_reply}
              />
            </article>
          ))
        ) : (
          <div className="rounded-2xl border border-black/10 bg-white p-6">
            <p className="text-neutral-600">
              No published reviews yet.
            </p>
          </div>
        )}
      </div>
    </main>
  );
}
