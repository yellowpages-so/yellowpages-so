import { DeclineQuoteButton } from "@/components/customer/DeclineQuoteButton";
import { AcceptQuoteButton } from "@/components/customer/AcceptQuoteButton";
import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";

import {
  getApiUrl,
  getAuthToken,
  getCurrentUser,
} from "@/lib/auth";

type QuoteResponseItem = {
id: string;
business_id: string;
business_public_id: string;
assignment_status?: string | null;
trading_name: string;
message: string;
currency?: string | null;
amount?: string | number | null;
estimated_days?: number | null;
status: string;
created_at: string;
};
type QuoteRequestItem = {
  id: string;
  reference_no: string;
  title: string;
  description: string;
  status: string;
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
  title: "My quote requests",
};

export default async function QuoteRequestsPage() {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login");
  }

  const token = await getAuthToken();

  let requests: QuoteRequestItem[] = [];

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

        requests = payload.data ?? [];
      }
    } catch {
      requests = [];
    }
  }

  return (
    <main className="mx-auto w-full max-w-6xl px-5 py-12 sm:py-16">
      <div className="border-b border-black/10 pb-8">
        <p className="text-sm font-bold uppercase tracking-[0.18em] text-black/45">
          Customer account
        </p>

        <h1 className="mt-2 text-3xl font-black tracking-tight sm:text-4xl">
          My quote requests
        </h1>

        <p className="mt-2 max-w-2xl text-black/60">
          Review your requests and compare responses from businesses.
        </p>
      </div>

      <section className="space-y-5 py-8">
        {requests.length ? (
          requests.map((request) => (
            <article
              key={request.id}
              className="rounded-2xl border border-black/10 p-6"
            >
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p className="text-xs font-bold uppercase tracking-wide text-black/40">
                    {request.reference_no}
                  </p>

<Link
  href={
    "/account/quote-requests/" +
    encodeURIComponent(request.id)
  }
  className="mt-2 block text-xl font-black hover:underline"
>
  {request.title}
</Link>
                  <p className="mt-3 max-w-3xl text-sm leading-6 text-black/60">
                    {request.description}
                  </p>
                </div>

                <span className="rounded-full bg-black/[0.06] px-3 py-1.5 text-xs font-bold uppercase">
                  {request.status}
                </span>
              </div>

              <div className="mt-4 flex flex-wrap gap-4 text-sm text-black/55">
                {request.category_name ? (
                  <span>{request.category_name}</span>
                ) : null}

                {request.city_name ? (
                  <span>{request.city_name}</span>
                ) : null}

                {request.required_by ? (
                  <span>
                    Required by {request.required_by}
                  </span>
                ) : null}

                {request.budget_min ||
                request.budget_max ? (
                  <span>
                    Budget{" "}
                    {request.budget_currency ?? ""}{" "}
                    {request.budget_min ?? ""}
                    {request.budget_max
                      ? " - " + request.budget_max
                      : ""}
                  </span>
                ) : null}
              </div>

              <div className="mt-6">
                <h3 className="font-black">
                  Business responses
                </h3>

                {request.responses?.length ? (
                  <div className="mt-4 grid gap-4">
                    {request.responses.map(
                      (response) => (
                        <div
                          key={response.id}
                          className="rounded-xl bg-black/[0.03] p-4"
                        >
                          <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                             <div className="flex flex-wrap items-center gap-2">
  <p className="font-black">
    {response.trading_name}
  </p>

  {response.assignment_status === "won" ? (
    <span className="rounded-full bg-black px-3 py-1 text-xs font-bold uppercase text-white">
      Accepted quote
    </span>
  ) : null}
</div>
                              <p className="mt-2 text-sm leading-6 text-black/65">
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
) : request.status === "open" ? (
  <div className="mt-4 flex flex-wrap items-center gap-3">
    <AcceptQuoteButton
      quoteRequestId={request.id}
      responseId={response.id}
    />

    <DeclineQuoteButton
      quoteRequestId={request.id}
      responseId={response.id}
    />
  </div>
) : (
  <p className="mt-4 text-sm font-semibold text-black/50">
    This request is closed.
  </p>
)}
                 </div>
                      ),
                    )}
                  </div>
                ) : (
                  <p className="mt-3 text-sm text-black/55">
                    No business responses yet.
                  </p>
                )}
              </div>
            </article>
          ))
        ) : (
          <div className="rounded-2xl border border-black/10 p-6">
            <h2 className="text-xl font-black">
              No quote requests yet
            </h2>

            <p className="mt-2 text-sm text-black/55">
              Submit a request to start receiving business responses.
            </p>

            <Link
              href="/request-a-quote"
              className="mt-4 inline-block rounded-xl bg-black px-4 py-2 text-sm font-bold text-white"
            >
              Request a quote
            </Link>
          </div>
        )}
      </section>
    </main>
  );
}

