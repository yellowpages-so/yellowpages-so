import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import {
  BadgeCheck,
  Globe,
  Mail,
  MapPin,
  MessageCircle,
  Phone,
  Star,
} from "lucide-react";
import { getBusiness } from "@/lib/api";

type Props = {
  params: Promise<{ slug: string }>;
};

export async function generateMetadata({
  params,
}: Props): Promise<Metadata> {
  const { slug } = await params;
  const business = await getBusiness(slug);

  if (!business) {
    return { title: "Business not found" };
  }

  return {
    title: business.trading_name,
    description:
      business.short_description ??
      `View ${business.trading_name} on YellowPages.so.`,
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

export default async function BusinessPage({ params }: Props) {
  const { slug } = await params;
  const business = await getBusiness(slug);

  if (!business) {
    notFound();
  }

  const phone = business.contacts?.find(
    (item) => item.contact_type === "phone",
  );
  const whatsapp = business.contacts?.find(
    (item) => item.contact_type === "whatsapp",
  );
  const email = business.contacts?.find(
    (item) => item.contact_type === "email",
  );

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    name: business.trading_name,
    description: business.short_description,
    url: business.website_url,
    telephone: phone?.value,
    address: {
      "@type": "PostalAddress",
      addressLocality: business.city,
      addressRegion: business.district,
      addressCountry: "SO",
    },
  };

  return (
    <div>
      <section className="bg-[#171717] py-12 text-white">
        <div className="container-shell">
          <div className="flex flex-col gap-6 md:flex-row md:items-center">
            <div className="grid size-24 shrink-0 place-items-center rounded-3xl bg-[#f5c400] text-3xl font-black text-black">
              {business.trading_name.slice(0, 2).toUpperCase()}
            </div>
            <div>
              <div className="flex flex-wrap items-center gap-3">
                <h1 className="text-3xl font-black md:text-5xl">
                  {business.trading_name}
                </h1>
                {business.status === "published" && (
                  <BadgeCheck className="text-blue-400" />
                )}
              </div>
              <div className="mt-4 flex flex-wrap gap-5 text-sm text-neutral-300">
                <span className="flex items-center gap-1.5">
                  <Star className="fill-[#f5c400] text-[#f5c400]" size={17} />
                  {Number(business.average_rating ?? 0).toFixed(1)}
                  {" "}({business.review_count ?? 0} reviews)
                </span>
                {(business.city || business.district) && (
                  <span className="flex items-center gap-1.5">
                    <MapPin size={17} />
                    {[business.district, business.city]
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
            <h2 className="text-2xl font-black">About</h2>
            <p className="mt-4 whitespace-pre-line leading-7 text-neutral-700">
              {business.description ??
                business.short_description ??
                "Business information will be added soon."}
            </p>
          </section>

          <section className="card p-7">
            <h2 className="text-2xl font-black">Services</h2>
            <div className="mt-5 grid gap-3 sm:grid-cols-2">
              {business.services?.length ? (
                business.services.map((service, index) => (
                  <div
                    key={service.id ?? `${service.slug}-${index}`}
                    className="rounded-xl border border-black/10 p-4"
                  >
                    <p className="font-bold">
                      {service.name ??
                        service.custom_name ??
                        "Business service"}
                    </p>
                    {service.description && (
                      <p className="mt-2 text-sm leading-6 text-neutral-600">
                        {service.description}
                      </p>
                    )}
                  </div>
                ))
              ) : (
                <p className="text-neutral-600">
                  Service details will be added soon.
                </p>
              )}
            </div>
          </section>

          <section className="card p-7">
            <h2 className="text-2xl font-black">Categories</h2>
            <div className="mt-5 flex flex-wrap gap-2">
              {business.categories?.length ? (
                business.categories.map((category) => (
                  <Link
                    key={category.slug}
                    href={`/category/${category.slug}`}
                    className="focus-ring rounded-full bg-[#f5c400]/20 px-4 py-2 text-sm font-bold"
                  >
                    {category.name}
                  </Link>
                ))
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
            <h2 className="text-xl font-black">Contact business</h2>
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
                  href={`https://wa.me/${whatsapp.value.replace(/\D/g, "")}`}
                  className="focus-ring flex items-center gap-3 rounded-xl border border-black/10 px-4 py-3 font-bold"
                >
                  <MessageCircle size={19} /> WhatsApp
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
            <h2 className="text-xl font-black">Location</h2>
            <p className="mt-3 flex items-start gap-2 text-sm leading-6 text-neutral-600">
              <MapPin className="mt-0.5 shrink-0" size={18} />
              {[business.district, business.city, "Somalia"]
                .filter(Boolean)
                .join(", ")}
            </p>
          </div>
        </aside>
      </div>

      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />
    </div>
  );
}
