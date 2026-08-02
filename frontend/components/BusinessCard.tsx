import Link from "next/link";
import { BadgeCheck, MapPin, Star } from "lucide-react";
import type { Business } from "@/lib/types";

export function BusinessCard({ business }: { business: Business }) {
  const rating = Number(business.average_rating ?? 0);

  return (
    <article className="card overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg">
      <Link
        href={`/business/${business.slug}`}
        className="focus-ring block rounded-[18px]"
      >
        <div className="flex gap-4 p-5">
          <div className="grid size-16 shrink-0 place-items-center rounded-2xl bg-[#f5c400]/20 text-xl font-black">
            {business.trading_name.slice(0, 2).toUpperCase()}
          </div>
          <div className="min-w-0 flex-1">
            <div className="flex items-start gap-2">
              <h3 className="truncate text-lg font-black">
                {business.trading_name}
              </h3>
              {business.status === "published" && (
                <BadgeCheck
                  className="shrink-0 text-blue-600"
                  size={19}
                  aria-label="Published"
                />
              )}
            </div>
            <p className="mt-1 line-clamp-2 text-sm leading-6 text-neutral-600">
              {business.short_description ??
                "View business details and services."}
            </p>
            <div className="mt-4 flex flex-wrap items-center gap-4 text-xs font-semibold text-neutral-500">
              <span className="flex items-center gap-1">
                <Star size={15} className="fill-[#f5c400] text-[#f5c400]" />
                {rating.toFixed(1)} ({business.review_count ?? 0})
              </span>
              {(business.city || business.district) && (
                <span className="flex items-center gap-1">
                  <MapPin size={15} />
                  {[business.district, business.city]
                    .filter(Boolean)
                    .join(", ")}
                </span>
              )}
            </div>
          </div>
        </div>
      </Link>
    </article>
  );
}
