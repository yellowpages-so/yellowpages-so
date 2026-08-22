
import { CancelQuoteRequestButton } from "@/components/customer/CancelQuoteRequestButton";
import { DeclineQuoteButton } from "@/components/customer/DeclineQuoteButton";
import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";

import { AcceptQuoteButton } from "@/components/customer/AcceptQuoteButton";
import {
  getApiUrl,
  getAuthToken,
  getCurrentUser,
} from "@/lib/auth";

type PageProps = {
  params: Promise<{
    quoteRequestId: string;
  }>;
};

type QuoteResponseItem = {
  id: string;
  business_id: string;
  business_public_id: string;
  trading_name: string;
  assignment_status?: string | null;
  message: string;
  currency?: string | null;
  amount?: string | number | null;
  estimated_days?: number | null;
  status: string;
  created_at: string;
};
type QuoteRequestHistoryItem = {
  id: string;
  event_type: string;
  metadata: string;
  created_at: string;
  actor_user_id?: string | null;
  actor_name?: string | null;
};

type QuoteRequestItem = {
  id: string;
  reference_no: string;
  title: string;
  description: string;
  status: string;
  history?: QuoteRequestHistoryItem[];
  budget_currency?: string | null;
  budget_min?: string | number | null;
  budget_max?: string | number | null;
  required_by?: string | null;
  preferred_contact?: string | null;
  created_at: string;
  expires_at?: string | null;
  category_name?: string | null;
  city_name?: string | null;
  responses?: QuoteResponseItem[];
};

type QuoteRequestResponse = {
  success: boolean;
  data?: QuoteRequestItem[];
};

export const metadata: Metadata = {
  title: "Quote request details",
};

export default async function QuoteRequestDetailPage({
  params,
}: PageProps) {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login");
  }

  const { quoteRequestId } = await params;
  const token = await getAuthToken();

  let quoteRequest: QuoteRequestItem | null = null;

  if (token) {
    try {
      const response = await fetch(
        getApiUrl() + "/v1/customer/quote-requests",
        {
          headers: {
            Accept: "application/json",
            Authorization: "Bearer " + token,
          },
          cache: "no-store",
        },
      );

      if (response.ok) {
        const payload =
          (await response.json()) as QuoteRequestResponse;

        quoteRequest =
          payload.data?.find(
            (item) => item.id === quoteRequestId,
          ) ?? null;
      }
    } catch {
      quoteRequest = null;
    }
  }

  if (!quoteRequest) {
    redirect("/account/quote-requests");
  }

  return (
    <main className="mx-auto w-full max-w-5xl px-5 py-12 sm:py-16">
      <Link
        href="/account/quote-requests"
        className="text-sm font-semibold text-black/55 hover:text-black"
      >
        Back to quote requests
      </Link>

      <div className="mt-5 border-b border-black/10 pb-8">
        <p className="text-xs font-bold uppercase tracking-wide text-black/40">
          {quoteRequest.reference_no}
        </p>

        <div className="mt-3 flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-3xl font-black tracking-tight sm:text-4xl">
              {quoteRequest.title}
            </h1>

            <p className="mt-3 max-w-3xl leading-7 text-black/65">
              {quoteRequest.description}
            </p>
          </div>

          <span className="rounded-full bg-black/[0.06] px-3 py-1.5 text-xs font-bold uppercase">
            {quoteRequest.status}
          </span>
        </div>
      </div>

      <section className="grid gap-5 py-8 md:grid-cols-2">
        <div className="rounded-2xl border border-black/10 p-6">
          <h2 className="text-xl font-black">
            Request details
          </h2>

          <dl className="mt-5 grid gap-4 text-sm">
            <div>
              <dt className="font-bold text-black/45">
                Category
              </dt>
              <dd className="mt-1">
                {quoteRequest.category_name ?? "Not specified"}
              </dd>
            </div>

            <div>
              <dt className="font-bold text-black/45">
                City
              </dt>
              <dd className="mt-1">
                {quoteRequest.city_name ?? "Not specified"}
              </dd>
            </div>

            <div>
              <dt className="font-bold text-black/45">
                Required by
              </dt>
              <dd className="mt-1">
                {quoteRequest.required_by ?? "Not specified"}
              </dd>
            </div>

            <div>
              <dt className="font-bold text-black/45">
                Preferred contact
              </dt>
              <dd className="mt-1 capitalize">
                {quoteRequest.preferred_contact ?? "Not specified"}
              </dd>
            </div>

            <div>
              <dt className="font-bold text-black/45">
                Budget
              </dt>
              <dd className="mt-1">
                {quoteRequest.budget_min ||
                quoteRequest.budget_max
                  ? `${quoteRequest.budget_currency ?? ""} ${
                      quoteRequest.budget_min ?? ""
                    }${
                      quoteRequest.budget_max
                        ? ` - ${quoteRequest.budget_max}`
                        : ""
                    }`
                  : "Not specified"}
              </dd>
            </div>
          </dl>
        </div>

        <div className="rounded-2xl border border-black/10 p-6">
          <h2 className="text-xl font-black">
            Status
          </h2>

<p className="mt-4 text-sm leading-6 text-black/60">
  {quoteRequest.status === "open"
    ? "This request is open and waiting for business responses."
    : "This request is closed."}
</p>

{quoteRequest.status === "open" ? (
  <CancelQuoteRequestButton
    quoteRequestId={quoteRequest.id}
  />
) : null}
        </div>
      </section>

      <section className="rounded-2xl border border-black/10 p-6">
        <h2 className="text-xl font-black">
          Business responses
        </h2>

        {quoteRequest.responses?.length ? (
          <div className="mt-5 grid gap-4">
            {quoteRequest.responses.map((response) => (
              <article
                key={response.id}
                className="rounded-xl bg-black/[0.03] p-5"
              >
                <div className="flex flex-wrap items-start justify-between gap-4">
                  <div>
                   <div className="flex flex-wrap items-center gap-2">
  <h3 className="text-lg font-black">
    {response.trading_name}
  </h3>

  {response.assignment_status === "won" ? (
    <span className="rounded-full bg-black px-3 py-1 text-xs font-bold uppercase text-white">
      Accepted quote
    </span>
  ) : null}
</div>
                    <p className="mt-3 max-w-3xl text-sm leading-6 text-black/65">
                      {response.message}
                    </p>
                  </div>

                  <Link
                    href={
                      "/business/" +
                      encodeURIComponent(
                        response.business_public_id,
                      )
                    }
                    className="text-sm font-bold underline"
                  >
                    View business
                  </Link>
                </div>

                <div className="mt-4 flex flex-wrap gap-4 text-sm font-semibold">
                  {response.currency &&
                  response.amount ? (
                    <span>
                      {response.currency}{" "}
                      {response.amount}
                    </span>
                  ) : null}

                  {response.estimated_days ? (
                    <span>
                      {response.estimated_days} day
                      {response.estimated_days === 1
                        ? ""
                        : "s"}
                    </span>
                  ) : null}
                </div>
{response.assignment_status === "lost" ? (
  <span className="mt-4 inline-flex rounded-full bg-black/[0.06] px-3 py-1.5 text-xs font-bold uppercase text-black/60">
    Declined
  </span>
) : response.assignment_status === "won" ? (
  <span className="mt-4 inline-flex rounded-full bg-black px-3 py-1.5 text-xs font-bold uppercase text-white">
    Accepted quote
  </span>
) : quoteRequest.status === "open" ? (
  <div className="mt-4 flex flex-wrap items-center gap-3">
    <AcceptQuoteButton
      quoteRequestId={quoteRequest.id}
      responseId={response.id}
    />

    <DeclineQuoteButton
      quoteRequestId={quoteRequest.id}
      responseId={response.id}
    />
  </div>
) : (
  <p className="mt-4 text-sm font-semibold text-black/50">
    This request is closed.
  </p>
)}
<section className="mt-6 rounded-2xl border border-black/10 p-6">
  <h2 className="text-xl font-black">
    Request history
  </h2>

  {quoteRequest.history?.length ? (
    <div className="mt-5 space-y-4">
      {quoteRequest.history.map((item) => {
        let metadata: {
          status?: string;
          note?: string | null;
        } = {};

        try {
          metadata = JSON.parse(item.metadata);
        } catch {
          metadata = {};
        }

        const label =
          item.event_type === "quote_request_created"
            ? "Quote request created"
            : item.event_type === "quote_submitted"
              ? "Quote submitted"
              : item.event_type === "quote_accepted"
                ? "Quote accepted"
                : item.event_type === "quote_declined"
                  ? "Quote declined"
                  : item.event_type === "quote_request_cancelled"
                    ? "Quote request cancelled"
                    : item.event_type === "lead_status_changed"
                      ? `Status changed to ${metadata.status ?? "unknown"}`
                      : item.event_type;

        return (
          <div
            key={item.id}
            className="border-l-2 border-black/10 pl-4"
          >
            <div className="flex flex-wrap items-center justify-between gap-3">
              <p className="font-bold">
                {label}
              </p>

              <time className="text-xs text-black/45">
                {new Date(item.created_at).toLocaleString()}
              </time>
            </div>

            <p className="mt-1 text-sm text-black/55">
              {item.actor_name ?? "System"}
            </p>

            {metadata.note ? (
              <p className="mt-2 text-sm text-black/65">
                {metadata.note}
              </p>
            ) : null}
          </div>
        );
      })}
    </div>
  ) : (
    <p className="mt-4 text-sm text-black/55">
      No request history recorded yet.
    </p>
  )}
</section>
         </article>
            ))}
          </div>
        ) : (
          <p className="mt-4 text-sm text-black/55">
            No business responses yet.
          </p>
        )}
      </section>
    </main>
  );
}
