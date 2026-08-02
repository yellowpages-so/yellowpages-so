import Link from "next/link";
import { ArrowRight, BadgeCheck, MapPinned, SearchCheck } from "lucide-react";
import { BusinessCard } from "@/components/BusinessCard";
import { CategoryGrid } from "@/components/CategoryGrid";
import { SearchForm } from "@/components/SearchForm";
import {
  getCategoryTree,
  getFeaturedBusinesses,
} from "@/lib/api";

export default async function HomePage() {
  const [categories, businesses] = await Promise.all([
    getCategoryTree(),
    getFeaturedBusinesses(),
  ]);

  return (
    <>
      <section className="bg-[#151515] py-18 text-white md:py-24">
        <div className="container-shell">
          <div className="max-w-3xl">
            <p className="inline-flex rounded-full bg-white/10 px-4 py-2 text-sm font-bold text-[#f5c400]">
              Somalia’s business discovery platform
            </p>
            <h1 className="mt-6 text-4xl font-black tracking-tight sm:text-6xl">
              Find the right business, service, or organisation.
            </h1>
            <p className="mt-5 max-w-2xl text-lg leading-8 text-neutral-300">
              Search verified listings, compare services, and connect
              with businesses across Somalia.
            </p>
          </div>
          <div className="mt-10">
            <SearchForm />
          </div>
        </div>
      </section>

      <section className="container-shell py-14">
        <div className="grid gap-4 md:grid-cols-3">
          {[
            [SearchCheck, "Useful search", "Search by business, service, category, or location."],
            [BadgeCheck, "Trusted profiles", "Clear business details and verification information."],
            [MapPinned, "Local discovery", "Browse businesses by city, district, and neighbourhood."],
          ].map(([Icon, title, description]) => {
            const FeatureIcon = Icon as typeof SearchCheck;
            return (
              <div className="card p-6" key={String(title)}>
                <FeatureIcon className="text-[#9a7b00]" />
                <h2 className="mt-4 text-lg font-black">{String(title)}</h2>
                <p className="mt-2 text-sm leading-6 text-neutral-600">
                  {String(description)}
                </p>
              </div>
            );
          })}
        </div>
      </section>

      <section className="container-shell py-8">
        <div className="flex items-end justify-between gap-4">
          <div>
            <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
              Browse
            </p>
            <h2 className="mt-2 text-3xl font-black">Popular categories</h2>
          </div>
          <Link
            href="/categories"
            className="focus-ring flex items-center gap-2 rounded-lg text-sm font-bold"
          >
            View all <ArrowRight size={17} />
          </Link>
        </div>
        <div className="mt-7">
          <CategoryGrid categories={categories} />
        </div>
      </section>

      <section className="container-shell py-14">
        <div className="flex items-end justify-between gap-4">
          <div>
            <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
              Discover
            </p>
            <h2 className="mt-2 text-3xl font-black">Featured businesses</h2>
          </div>
          <Link
            href="/search"
            className="focus-ring flex items-center gap-2 rounded-lg text-sm font-bold"
          >
            Search all <ArrowRight size={17} />
          </Link>
        </div>
        <div className="mt-7 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {businesses.length > 0 ? (
            businesses.map((business) => (
              <BusinessCard key={business.id} business={business} />
            ))
          ) : (
            <div className="card col-span-full p-8 text-center text-neutral-600">
              Business listings will appear here after publication.
            </div>
          )}
        </div>
      </section>

      <section className="container-shell py-10">
        <div className="rounded-3xl bg-[#f5c400] p-8 md:flex md:items-center md:justify-between md:p-12">
          <div>
            <h2 className="text-3xl font-black">Grow your business visibility.</h2>
            <p className="mt-3 max-w-xl leading-7 text-black/70">
              Create a listing, add your services, and connect with more customers.
            </p>
          </div>
          <Link
            href="/list-your-business"
            className="focus-ring mt-6 inline-block rounded-xl bg-black px-6 py-3 font-bold text-white md:mt-0"
          >
            List your business
          </Link>
        </div>
      </section>
    </>
  );
}
