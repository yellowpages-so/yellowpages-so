import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";

import { LeadActions } from "@/components/owner/LeadActions";
import {
  getApiUrl,
  getAuthToken,
  getCurrentUser,
} from "@/lib/auth";
import { getOwnerBusiness } from "@/lib/owner-api";

type PageProps = {
  params: Promise<{
    businessId: string;
  }>;
  searchParams: Promise<{
    status?: string;
  }>;
};

type Lead = {
  response_id?: string | null;
  response_message?: string | null;
  response_currency?: string | null;
  response_amount?: string | number | null;
  response_estimated_days?: number | null;
  response_status?: string | null;
  response_created_at?: string | null;

  assignment_id: string;
  business_id: string;
  business_public_id: string;
  assignment_status: string;

  id: string;
  reference_no: string;
  title: string;
  description: string;

  budget_currency?: string | null;
  budget_min?: string | number | null;
  budget_max?: string | number | null;
  required_by?: string | null;
  preferred_contact?: string | null;

  lead_score?: number | null;
  category_name?: string | null;
  city_name?: string | null;
};

type LeadAnalytics = {
  total: number;
  new: number;
  quoted: number;
  won: number;
  lost: number;
  conversion_rate: number;
};

type LeadResponse = {
  success: boolean;
  data?: {
    data?: Lead[];
  };
};

type AnalyticsResponse = {
  success: boolean;
  data?: LeadAnalytics;
};

const allowedStatuses = [
  "new",
  "viewed",
  "contacted",
  "quoted",
  "won",
  "lost",
  "closed",
];

export const metadata: Metadata = {
  title: "Business leads",
};

export default async function BusinessLeadsPage({
  params,
  searchParams,
}: PageProps) {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login");
  }

  const { businessId } = await params;
  const { status } = await searchParams;

  const activeStatus =
    status && allowedStatuses.includes(status)
      ? status
      : "";

  const business = await getOwnerBusiness(businessId);

  if (!business) {
    redirect("/owner/businesses");
  }

  const token = await getAuthToken();

  let leads: Lead[] = [];

  if (token) {
    try {
      const response = await fetch(
        getApiUrl() +
          "/v1/owner/leads" +
          (activeStatus
            ? "?status=" +
              encodeURIComponent(activeStatus)
            : ""),
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
          (await response.json()) as LeadResponse;

        leads =
          payload.data?.data?.filter(
            (lead) =>
              lead.assignment_id &&
              lead.title &&
              lead.business_public_id ===
                businessId,
          ) ?? [];
      }
    } catch {
      leads = [];
    }
  }

  let analytics: LeadAnalytics | null = null;

  const internalBusinessId =
    leads[0]?.business_id ?? null;

  if (token && internalBusinessId) {
    try {
      const response = await fetch(
        getApiUrl() +
          "/v1/owner/leads/analytics?business_id=" +
          encodeURIComponent(
            internalBusinessId,
          ),
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
          (await response.json()) as AnalyticsResponse;

        analytics = payload.data ?? null;
      }
    } catch {
      analytics = null;
    }
  }

  return (
    <main className="mx-auto w-full max-w-6xl px-5 py-12 sm:py-16">
      <Link
        href={
          "/owner/businesses/" +
          encodeURIComponent(businessId)
        }
        className="text-sm font-semibold text-black/55 hover:text-black"
      >
        Back to business
      </Link>

      <div className="mt-5 border-b border-black/10 pb-8">
        <p className="text-sm font-bold uppercase tracking-[0.18em] text-black/45">
          {business.trading_name}
        </p>

        <h1 className="mt-2 text-3xl font-black tracking-tight sm:text-4xl">
          Leads
        </h1>

        <p className="mt-2 max-w-2xl text-black/60">
          Review customer enquiries and quotation
          opportunities assigned to this business.
        </p>
      </div>

      {analytics ? (
        <section className="grid gap-3 pt-8 sm:grid-cols-3 lg:grid-cols-6">
          {[
            ["Total", analytics.total],
            ["New", analytics.new],
            ["Quoted", analytics.quoted],
            ["Won", analytics.won],
            ["Lost", analytics.lost],
            [
              "Conversion",
              `${analytics.conversion_rate}%`,
            ],
          ].map(([label, value]) => (
            <div
              key={String(label)}
              className="rounded-2xl border border-black/10 p-4"
            >
              <p className="text-xs font-bold uppercase tracking-wide text-black/40">
                {label}
              </p>

              <p className="mt-2 text-2xl font-black">
                {value}
              </p>
            </div>
          ))}
        </section>
      ) : null}

      <form className="mt-6 rounded-2xl border border-black/10 p-4">
        <label className="text-sm font-semibold">
          Filter by status

          <select
            name="status"
            defaultValue={activeStatus}
            className="ml-3 rounded-xl border border-black/15 bg-white px-3 py-2"
          >
            <option value="">All</option>
            <option value="new">New</option>
            <option value="viewed">Viewed</option>
            <option value="contacted">
              Contacted
            </option>
            <option value="quoted">Quoted</option>
            <option value="won">Won</option>
            <option value="lost">Lost</option>
            <option value="closed">Closed</option>
          </select>
        </label>

        <button
          type="submit"
          className="ml-3 rounded-xl bg-black px-4 py-2 text-sm font-bold text-white"
        >
          Apply
        </button>
      </form>

      <section className="space-y-4 py-8">
        {leads.length ? (
          leads.map((lead) => (
            <article
              key={lead.assignment_id}
              className="rounded-2xl border border-black/10 p-6"
            >
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p className="text-xs font-bold uppercase tracking-wide text-black/40">
                    {lead.reference_no}
                  </p>

                  <Link href={ "/owner/businesses/" + encodeURIComponent(businessId) + "/leads/" + encodeURIComponent(lead.id) } className="mt-2 block text-xl font-black hover:underline" > {lead.title} </Link>

                    
          

                  <p className="mt-3 max-w-3xl text-sm leading-6 text-black/60">
                    {lead.description}
                  </p>
                </div>

                <div className="text-right">
                  <span className="rounded-full bg-black/[0.06] px-3 py-1.5 text-xs font-bold uppercase">
                    {lead.assignment_status}
                  </span>

                  <p className="mt-2 text-sm font-bold">
                    Score {lead.lead_score ?? 0}
                  </p>
                </div>
              </div>

              <div className="mt-4 flex flex-wrap gap-3 text-sm text-black/55">
                {lead.category_name ? (
                  <span>
                    {lead.category_name}
                  </span>
                ) : null}

                {lead.city_name ? (
                  <span>{lead.city_name}</span>
                ) : null}

                {lead.required_by ? (
                  <span>
                    Required by{" "}
                    {lead.required_by}
                  </span>
                ) : null}

                {lead.budget_min ||
                lead.budget_max ? (
                  <span>
                    Budget{" "}
                    {lead.budget_currency ??
                      ""}{" "}
                    {lead.budget_min ?? ""}
                    {lead.budget_max
                      ? " - " +
                        lead.budget_max
                      : ""}
                  </span>
                ) : null}
              </div>

              {lead.response_id ? (
                <div className="mt-6 rounded-xl bg-black/[0.03] p-4">
                  <p className="text-xs font-bold uppercase tracking-wide text-black/45">
                    Submitted quote
                  </p>

                  <p className="mt-3 text-sm leading-6 text-black/70">
                    {lead.response_message}
                  </p>

                  <div className="mt-4 flex flex-wrap gap-4 text-sm font-semibold">
                    {lead.response_currency &&
                    lead.response_amount ? (
                      <span>
                        {
                          lead.response_currency
                        }{" "}
                        {lead.response_amount}
                      </span>
                    ) : null}

                    {lead.response_estimated_days ? (
                      <span>
                        {
                          lead.response_estimated_days
                        }{" "}
                        day
                        {lead.response_estimated_days ===
                        1
                          ? ""
                          : "s"}
                      </span>
                    ) : null}
                  </div>
                </div>
              ) : (
                <LeadActions
                  assignmentId={
                    lead.assignment_id
                  }
                  quoteRequestId={lead.id}
                  businessId={lead.business_id}
                  currentStatus={
                    lead.assignment_status
                  }
                />
              )}
            </article>
          ))
        ) : (
          <div className="rounded-2xl border border-black/10 p-6">
            <h2 className="text-xl font-black">
              Customer enquiries
            </h2>

            <p className="mt-2 text-sm leading-6 text-black/60">
              No leads found for this filter.
            </p>
          </div>
        )}
      </section>
    </main>
  );
}
