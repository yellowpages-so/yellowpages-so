import { WriteReviewForm } from "@/components/reviews/WriteReviewForm";
import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ReviewList } from "@/components/ReviewList";
import {
  BadgeCheck,
  Clock3,
  Globe,
  Mail,
  MapPin,
  MessageCircle,
  Phone,
  Star,
} from "lucide-react";
import {
getBusiness,
getBusinessReviews,
} from "@/lib/api";

type Props = {
  params: Promise<{ slug: string }>;
};

const dayNames = [
  "Sunday",
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
];

function formatTime(value?: string | null) {
  if (!value) return "";

  const [hour, minute] = value.split(":");
  const hourNumber = Number(hour);

  if (Number.isNaN(hourNumber)) {
    return value;
  }

  const suffix = hourNumber >= 12 ? "PM" : "AM";
  const displayHour =
    hourNumber % 12 === 0 ? 12 : hourNumber % 12;

  return `${displayHour}:${minute} ${suffix}`;
}

export async function generateMetadata({
  params,
}: Props): Promise<Metadata> {
  const { slug } = await params;
const business = await getBusiness(slug);
if (!business) {
return { title: "Business not found" };
}
  const location = [
    business.district,
    business.city,
  ]
    .filter(Boolean)
    .join(", ");

  return {
    title: business.trading_name,
    description:
      business.short_description ??
      `View ${business.trading_name}${
        location ? ` in ${location}` : ""
      } on YellowPages.so.`,
    alternates: {
      canonical: `/business/${business.slug}`,
    },
    openGraph: {
      title: business.trading_name,
      description:
        business.short_description ??
        `View ${business.trading_name} on YellowPages.so.`,
      type: "website",
    },
  };
}

export default async function BusinessPage({
  params,
}: Props) {
  const { slug } = await params;
  const business = await getBusiness(slug);

  if (!business) {
    notFound();
  }
const reviews = await getBusinessReviews(business.public_id);
  const phone = business.contacts?.find(
    (item) => item.contact_type === "phone",
  );

  const whatsapp = business.contacts?.find(
    (item) => item.contact_type === "whatsapp",
  );

  const email = business.contacts?.find(
    (item) => item.contact_type === "email",
  );

  const addressParts = [
    business.branch?.address_line1,
    business.branch?.address_line2,
    business.district,
    business.city,
    business.region,
  ].filter(Boolean);

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    name: business.trading_name,
    description: business.short_description,
    url: business.website_url,
    telephone:
      phone?.value ?? business.branch?.phone,
    email:
      email?.value ?? business.branch?.email,
    address: {
      "@type": "PostalAddress",
      streetAddress: [
        business.branch?.address_line1,
        business.branch?.address_line2,
      ]
        .filter(Boolean)
        .join(", "),
      addressLocality: business.city,
      addressRegion: business.region,
      addressCountry: "SO",
    },

    openingHoursSpecification:
      business.opening_hours?.map((row) => ({
        "@type": "OpeningHoursSpecification",
        dayOfWeek: dayNames[row.weekday],
        opens: row.is_closed
          ? undefined
          : row.opens_at,
        closes: row.is_closed
          ? undefined
          : row.closes_at,
      })),
  };

  return (
    <div>
      <section className="bg-[#171717] py-12 text-white">
        <div className="container-shell">
          <div className="flex flex-col gap-6 md:flex-row md:items-center">
            <div className="grid size-24 shrink-0 place-items-center rounded-3xl bg-[#f5c400] text-3xl font-black text-black">
              {business.trading_name
                .slice(0, 2)
                .toUpperCase()}
            </div>

            <div>
              <div className="flex flex-wrap items-center gap-3">
                <h1 className="text-3xl font-black md:text-5xl">
                  {business.trading_name}
                </h1>

                {business.is_verified === true && (
                  <BadgeCheck className="text-blue-400" />
                )}
              </div>

              <div className="mt-4 flex flex-wrap gap-5 text-sm text-neutral-300">
              {business.categories?.[0] ? (
              <Link href={`/category/${business.categories[0].slug}`}
                 className="font-bold text-[#f5c400] hover:underline" >
                   {business.categories[0].name} </Link> ) : null}

                <span className="flex items-center gap-1.5">
                  <Star
                    className="fill-[#f5c400] text-[#f5c400]"
                    size={17}
                  />
                  {Number(
                    business.average_rating ?? 0,
                  ).toFixed(1)}{" "}
                  ({business.review_count ?? 0} reviews)
                </span>

                {(business.city ||
                  business.district) && (
                  <span className="flex items-center gap-1.5">
                    <MapPin size={17} />
                    {[
                      business.district,
                      business.city,
                    ]
                      .filter(Boolean)
                      .join(", ")}
                  </span>
                )}
              </div>
            </div>
          </div>
        </div>
      </section>

      <div className="container-shell grid gap-7 py-10 lg:grid-cols-[1fr_340px]">
        <div className="space-y-7">
          <section className="card p-7">
            <h2 className="text-2xl font-black">
              About
            </h2>

            <p className="mt-4 whitespace-pre-line leading-7 text-neutral-700">
              {business.description ??
                business.short_description ??
                "Business information will be added soon."}
            </p>
          </section>

          <section className="card p-7">
            <h2 className="text-2xl font-black">
              Services
            </h2>

            <div className="mt-5 grid gap-3 sm:grid-cols-2">
              {business.services?.length ? (
                business.services.map(
                  (service, index) => (
                    <div
                      key={
                        service.id ??
                        `${service.slug}-${index}`
                      }
                      className="rounded-xl border border-black/10 p-4"
                    >
                      <div className="flex items-start justify-between gap-4">
                        <p className="font-bold">
                          {service.name ??
                            service.custom_name ??
                            "Business service"}
                        </p>

                        {service.price_from ? (
                          <span className="shrink-0 text-sm font-black">
                            From {service.price_from}{" "}
                            {service.currency ?? ""}
                          </span>
                        ) : null}
                      </div>

                      {service.description && (
                        <p className="mt-2 text-sm leading-6 text-neutral-600">
                          {service.description}
                        </p>
                      )}
                    </div>
                  ),
                )
              ) : (
                <p className="text-neutral-600">
                  Service details will be added soon.
                </p>
              )}
            </div>
          </section>
          <section className="card p-7">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 className="text-2xl font-black">
                  Customer reviews
                </h2>

                <p className="mt-1 text-sm text-neutral-600">
                  {business.review_count ?? 0} reviews,{" "}
                  {Number(
                    business.average_rating ?? 0,
                  ).toFixed(1)} average rating
                </p>
              </div>

              <Link
                href={`/business/${business.slug}#write-review`}
                className="rounded-xl bg-black px-4 py-2 text-sm font-bold text-white"
              >
                Write a review
              </Link>
            </div>

            <div className="mt-5">
              <ReviewList reviews={reviews} />
            </div>
          </section>

         <WriteReviewForm businessId={business.public_id} />
          <section className="card p-7">
            <h2 className="text-2xl font-black">
              Opening hours
            </h2>

            {business.opening_hours?.length ? (
              <div className="mt-5 divide-y divide-black/10">
                {business.opening_hours.map(
                  (row) => (
                    <div
                      key={row.weekday}
                      className="flex items-center justify-between gap-5 py-3 text-sm"
                    >
                      <span className="font-bold">
                        {dayNames[row.weekday]}
                      </span>

                      <span className="text-neutral-600">
                        {row.is_closed
                          ? "Closed"
                          : `${formatTime(
                              row.opens_at,
                            )} - ${formatTime(
                              row.closes_at,
                            )}`}
                      </span>
                    </div>
                  ),
                )}
              </div>
            ) : (
              <p className="mt-4 text-neutral-600">
                Opening hours will be added soon.
              </p>
            )}
          </section>

          <section className="card p-7">
            <h2 className="text-2xl font-black">
              Categories
            </h2>

            <div className="mt-5 flex flex-wrap gap-2">
              {business.categories?.length ? (
                business.categories.map(
                  (category) => (
                    <Link
                      key={category.slug}
                      href={`/category/${category.slug}`}
                      className="focus-ring rounded-full bg-[#f5c400]/20 px-4 py-2 text-sm font-bold"
                    >
                      {category.name}
                    </Link>
                  ),
                )
              ) : (
                <p className="text-neutral-600">
                  Categories will be added soon.
                </p>
              )}
            </div>
          </section>
        </div>

        <aside className="space-y-5">
          <div className="card p-6">
            <h2 className="text-xl font-black">
              Contact business
            </h2>

            <div className="mt-5 grid gap-3">
              {phone && (
                <a
                  href={`tel:${phone.value}`}
                  className="focus-ring flex items-center gap-3 rounded-xl bg-black px-4 py-3 font-bold text-white"
                >
                  <Phone size={19} /> Call
                </a>
              )}

              {whatsapp && (
                <a
                  href={`https://wa.me/${whatsapp.value.replace(
                    /\D/g,
                    "",
                  )}`}
                  target="_blank"
                  rel="noreferrer"
                  className="focus-ring flex items-center gap-3 rounded-xl border border-black/10 px-4 py-3 font-bold"
                >
                  <MessageCircle size={19} />
                  WhatsApp
                </a>
              )}

              {email && (
                <a
                  href={`mailto:${email.value}`}
                  className="focus-ring flex items-center gap-3 rounded-xl border border-black/10 px-4 py-3 font-bold"
                >
                  <Mail size={19} /> Email
                </a>
              )}

              {business.website_url && (
                <a
                  href={business.website_url}
                  target="_blank"
                  rel="noreferrer"
                  className="focus-ring flex items-center gap-3 rounded-xl border border-black/10 px-4 py-3 font-bold"
                >
                  <Globe size={19} /> Website
                </a>
              )}
            </div>
          </div>

          <div className="card p-6">
            <h2 className="text-xl font-black">
              Location
            </h2>

            {addressParts.length ? (
              <div className="mt-4 flex items-start gap-2 text-sm leading-6 text-neutral-600">
                <MapPin
                  className="mt-0.5 shrink-0"
                  size={18}
                />
                <div>
                  <p>{addressParts.join(", ")}</p>

                  {business.branch?.landmark ? (
                    <p className="mt-2">
                      Landmark:{" "}
                      {business.branch.landmark}
                    </p>
                  ) : null}
                </div>
              </div>
            ) : (
              <p className="mt-3 text-sm text-neutral-600">
                Location information will be added soon.
              </p>
            )}
          </div>

          {business.opening_hours?.length ? (
            <div className="card p-6">
              <div className="flex items-center gap-2">
                <Clock3 size={19} />
                <h2 className="text-xl font-black">
                  Business hours
                </h2>
              </div>

              <p className="mt-3 text-sm text-neutral-600">
                See the full weekly schedule on this
                page.
              </p>
            </div>
          ) : null}
        </aside>
      </div>

      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{
          __html: JSON.stringify(jsonLd),
        }}
      />
    </div>
  );
}
