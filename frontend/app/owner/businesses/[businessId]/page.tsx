import type { Metadata } from "next";
import Link from "next/link";
import { notFound, redirect } from "next/navigation";
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
};

type VerificationStatus = {
  is_verified: boolean;
  current_level?: {
    code: string;
    name: string;
  } | null;
};

export const metadata: Metadata = {
  title: "Manage business",
};

export default async function ManageBusinessPage({
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

  const completeness =
    business.profile_completeness ?? 0;

  const token = await getAuthToken();

  let verification: VerificationStatus | null =
    null;

  if (token) {
    try {
      const url =
        getApiUrl() +
        "/v1/owner/businesses/" +
        encodeURIComponent(businessId) +
        "/verification-status";

      const response = await fetch(url, {
        headers: {
          Accept: "application/json",
          Authorization: "Bearer " + token,
        },
        cache: "no-store",
      });

      if (response.ok) {
        const payload =
          (await response.json()) as {
            data?: VerificationStatus | null;
          };

        verification =
          payload.data ?? null;
      }
    } catch {
      verification = null;
    }
  }

  const actions = [
    {
      title: "Contact details",
      description:
        "Add phone, WhatsApp and email contact channels.",
      href:
        "/owner/businesses/" +
        encodeURIComponent(businessId) +
        "/contacts",
      cta: "Manage contacts",
      active: true,
    },
    {
      title: "Services",
      description:
        "Add services, descriptions and starting prices.",
      href:
        "/owner/businesses/" +
        encodeURIComponent(businessId) +
        "/services",
      cta: "Manage services",
      active: true,
    },
    {
      title: "Locations & branches",
      description:
        "Add head office, branches, districts and opening hours.",
      href:
        "/owner/businesses/" +
        encodeURIComponent(businessId) +
        "/locations",
      cta: "Manage locations",
      active: true,
    },
    {
      title: "Verification",
      description: verification?.is_verified
        ? "Current status: " +
          (verification.current_level?.name ??
            "Verified") +
          "."
        : "Submit business documents and request verified status.",
      href:
        "/owner/businesses/" +
        encodeURIComponent(businessId) +
        "/trust",
      cta: verification?.is_verified
        ? verification.current_level?.name ??
          "Verified"
        : "Manage verification",
      active: true,
    },
    {
      title: "Leads",
      description:
        "Receive customer enquiries and quotation opportunities.",
      href:
    "/owner/businesses/" +
encodeURIComponent(businessId) +
"/leads",
cta: "Manage leads",
active: true,
    },
{
title: "Reviews",
description:
"Read customer feedback and reply to published reviews.",
href:
"/owner/businesses/" +
encodeURIComponent(businessId) +
"/reviews",
cta: "Manage reviews",
active: true,
},
    {
      title: "Analytics",
      description:
        "Track profile views, calls, WhatsApp clicks and leads.",
      href: "#",
      cta: "Coming next",
      active: false,
    },
  ];

  return (
    <main className="mx-auto w-full max-w-6xl px-5 py-12 sm:py-16">
      <div className="border-b border-black/10 pb-8">
        <Link
          href="/owner/businesses"
          className="text-sm font-semibold text-black/55 hover:text-black"
        >
          Your businesses
        </Link>

        <div className="mt-4 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <div className="flex flex-wrap items-center gap-3">
              <h1 className="text-3xl font-black tracking-tight sm:text-4xl">
                {business.trading_name}
              </h1>

              <span className="rounded-full bg-black/[0.06] px-3 py-1 text-xs font-bold uppercase tracking-wide">
                {business.status ?? "draft"}
              </span>

              {verification?.is_verified ? (
                <span className="rounded-full bg-black px-3 py-1 text-xs font-bold text-white">
                  {verification.current_level?.name ??
                    "Verified"}
                </span>
              ) : null}
            </div>

            <p className="mt-2 text-black/55">
              {business.legal_name}
            </p>
          </div>

          {business.slug ? (
            <Link
              href={
                "/business/" + business.slug
              }
              className="focus-ring rounded-xl border border-black/15 px-4 py-2.5 text-sm font-bold"
            >
              View public profile
            </Link>
          ) : null}
        </div>
      </div>

      <section className="grid gap-5 py-8 lg:grid-cols-[1.2fr_0.8fr]">
        <div className="rounded-2xl border border-black/10 p-6">
          <h2 className="text-xl font-black">
            Business profile
          </h2>

          <p className="mt-2 text-sm leading-6 text-black/60">
            {business.short_description ||
              "Add a short description so customers quickly understand what this business offers."}
          </p>

          <dl className="mt-6 grid gap-5 sm:grid-cols-2">
            <div>
              <dt className="text-xs font-bold uppercase tracking-wide text-black/40">
                Registration number
              </dt>
              <dd className="mt-1 font-semibold">
                {business.registration_number ||
                  "Not added"}
              </dd>
            </div>

            <div>
              <dt className="text-xs font-bold uppercase tracking-wide text-black/40">
                Established
              </dt>
              <dd className="mt-1 font-semibold">
                {business.established_year ||
                  "Not added"}
              </dd>
            </div>

            <div>
              <dt className="text-xs font-bold uppercase tracking-wide text-black/40">
                Website
              </dt>
              <dd className="mt-1 font-semibold">
                {business.website_url ||
                  "Not added"}
              </dd>
            </div>

            <div>
              <dt className="text-xs font-bold uppercase tracking-wide text-black/40">
                Business ID
              </dt>
              <dd className="mt-1 break-all text-sm font-semibold">
                {business.public_id ??
                  business.id}
              </dd>
            </div>
          </dl>
        </div>

        <div className="rounded-2xl border border-black/10 p-6">
          <h2 className="text-xl font-black">
            Profile completeness
          </h2>

          <div className="mt-5 text-4xl font-black">
            {completeness}%
          </div>

          <div className="mt-3 h-2 overflow-hidden rounded-full bg-black/10">
            <div
              className="h-full rounded-full bg-black"
              style={{
                width:
                  Math.min(
                    Math.max(
                      completeness,
                      0,
                    ),
                    100,
                  ) + "%",
              }}
            />
          </div>

          <p className="mt-4 text-sm leading-6 text-black/60">
            Keep your customer-facing
            information complete and current.
            Locations, opening hours and
            verification are available below.
          </p>
        </div>
      </section>

      <section>
        <div className="flex items-end justify-between gap-4">
          <div>
            <h2 className="text-xl font-black">
              Business management
            </h2>
            <p className="mt-1 text-sm text-black/55">
              Complete the parts customers use
              to choose and contact this
              business.
            </p>
          </div>
        </div>

        <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {actions.map((action) =>
            action.active ? (
              <Link
                key={action.title}
                href={action.href}
                className="rounded-2xl border border-black/10 p-5 transition hover:border-black/30 hover:shadow-sm"
              >
                <h3 className="font-black">
                  {action.title}
                </h3>
                <p className="mt-2 text-sm leading-6 text-black/55">
                  {action.description}
                </p>
                <p className="mt-4 text-sm font-bold underline">
                  {action.cta}
                </p>
              </Link>
            ) : (
              <div
                key={action.title}
                className="rounded-2xl border border-black/10 p-5"
              >
                <h3 className="font-black">
                  {action.title}
                </h3>
                <p className="mt-2 text-sm leading-6 text-black/55">
                  {action.description}
                </p>
                <p className="mt-4 text-xs font-bold uppercase tracking-wide text-black/35">
                  {action.cta}
                </p>
              </div>
            ),
          )}
        </div>
      </section>
    </main>
  );
}
