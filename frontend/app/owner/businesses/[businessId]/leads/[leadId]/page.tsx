import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";

import {
LeadActions,
LeadStatusActions,
} from "@/components/owner/LeadActions";

import {
  getApiUrl,
  getAuthToken,
  getCurrentUser,
} from "@/lib/auth";
import { getOwnerBusiness } from "@/lib/owner-api";

type PageProps = {
  params: Promise<{
    businessId: string;
    leadId: string;
  }>;
};
type LeadHistoryItem = {
id: string;
event_type: string;
metadata: string;
created_at: string;
actor_user_id?: string | null;
actor_name?: string | null;
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

type LeadResponse = {
  success: boolean;
  data?: {
    data?: Lead[];
  };
};

export const metadata: Metadata = {
  title: "Lead details",
};

export default async function LeadDetailPage({
  params,
}: PageProps) {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login");
  }

  const { businessId, leadId } = await params;
  const business = await getOwnerBusiness(businessId);

  if (!business) {
    redirect("/owner/businesses");
  }

  const token = await getAuthToken();

  let lead: Lead | null = null;

  if (token) {
    try {
      const response = await fetch(
        getApiUrl() + "/v1/owner/leads",
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

        lead =
          payload.data?.data?.find(
            (item) =>
              item.id === leadId &&
              item.business_public_id === businessId,
          ) ?? null;
      }
    } catch {
      lead = null;
    }
  }
let history: LeadHistoryItem[] = [];
if (token && lead) {
try {
const response = await fetch(
getApiUrl() +
"/v1/quote-requests/" +
encodeURIComponent(lead.id) +
"/businesses/" +
encodeURIComponent(lead.business_id) +
"/history",
{
headers: {
Accept: "application/json",
Authorization: "Bearer " + token,
},
cache: "no-store",
},
);
if (response.ok) {
  const payload = (await response.json()) as {
    data?: LeadHistoryItem[];
  };

  history = payload.data ?? [];
}
} catch {
history = [];
}
}
  if (!lead) {
    redirect(
      "/owner/businesses/" +
        encodeURIComponent(businessId) +
        "/leads",
    );
  }

  return (
    <main className="mx-auto w-full max-w-5xl px-5 py-12 sm:py-16">
      <Link
        href={
          "/owner/businesses/" +
          encodeURIComponent(businessId) +
          "/leads"
        }
        className="text-sm font-semibold text-black/55 hover:text-black"
      >
        Back to leads
      </Link>

      <div className="mt-5 border-b border-black/10 pb-8">
        <p className="text-xs font-bold uppercase tracking-wide text-black/40">
          {lead.reference_no}
        </p>

        <div className="mt-3 flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-3xl font-black tracking-tight sm:text-4xl">
              {lead.title}
            </h1>

            <p className="mt-3 max-w-3xl leading-7 text-black/65">
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
      </div>

      <section className="grid gap-5 py-8 md:grid-cols-2">
        <div className="rounded-2xl border border-black/10 p-6">
          <h2 className="text-xl font-black">
            Request details
          </h2>

          <dl className="mt-5 grid gap-4 text-sm">
            <div>
              <dt className="font-bold text-black/45">
                Business
              </dt>
              <dd className="mt-1">
                {business.trading_name}
              </dd>
            </div>

            <div>
              <dt className="font-bold text-black/45">
                Category
              </dt>
              <dd className="mt-1">
                {lead.category_name ?? "Not specified"}
              </dd>
            </div>

            <div>
              <dt className="font-bold text-black/45">
                City
              </dt>
              <dd className="mt-1">
                {lead.city_name ?? "Not specified"}
              </dd>
            </div>

            <div>
              <dt className="font-bold text-black/45">
                Preferred contact
              </dt>
              <dd className="mt-1 capitalize">
                {lead.preferred_contact ?? "Not specified"}
              </dd>
            </div>

            <div>
              <dt className="font-bold text-black/45">
                Required by
              </dt>
              <dd className="mt-1">
                {lead.required_by ?? "Not specified"}
              </dd>
            </div>

            <div>
              <dt className="font-bold text-black/45">
                Budget
              </dt>
              <dd className="mt-1">
                {lead.budget_min || lead.budget_max
                  ? `${lead.budget_currency ?? ""} ${
                      lead.budget_min ?? ""
                    }${
                      lead.budget_max
                        ? ` - ${lead.budget_max}`
                        : ""
                    }`
                  : "Not specified"}
              </dd>
            </div>
          </dl>
        </div>

        <div className="rounded-2xl border border-black/10 p-6">
          <h2 className="text-xl font-black">
            Quote
          </h2>

         {lead.response_id ? (
  <>
    <div className="mt-5 rounded-xl bg-black/[0.03] p-4">
      <p className="text-sm leading-6 text-black/70">
        {lead.response_message}
      </p>

      <div className="mt-4 flex flex-wrap gap-4 text-sm font-semibold">
        {lead.response_currency &&
        lead.response_amount ? (
          <span>
            {lead.response_currency}{" "}
            {lead.response_amount}
          </span>
        ) : null}

        {lead.response_estimated_days ? (
          <span>
            {lead.response_estimated_days} day
            {lead.response_estimated_days === 1
              ? ""
              : "s"}
          </span>
        ) : null}
      </div>
    </div>

    <div className="mt-6">
      <LeadStatusActions
        assignmentId={lead.assignment_id}
        currentStatus={lead.assignment_status}
      />
    </div>
  </>
) : ["won", "lost", "closed"].includes(
  lead.assignment_status,
) ? (
  <div className="mt-5 rounded-xl bg-black/[0.03] p-4">
    <p className="text-sm font-semibold text-black/60">
      This lead is closed as{" "}
      {lead.assignment_status}.
    </p>
  </div>
) : (
  <LeadActions
    assignmentId={lead.assignment_id}
    quoteRequestId={lead.id}
    businessId={lead.business_id}
    currentStatus={lead.assignment_status}
  />
)}   
        </div>
      </section>
<section className="rounded-2xl border border-black/10 p-6">
  <h2 className="text-xl font-black">
    Lead history
  </h2>

  {history.length ? (
    <div className="mt-5 space-y-4">
      {history.map((item) => {
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
      No lead history recorded yet.
    </p>
  )}
</section>
    </main>
  );
}
