import type { Metadata } from "next";
import { BusinessCard } from "@/components/BusinessCard";
import { SearchForm } from "@/components/SearchForm";
import { searchBusinesses } from "@/lib/api";

export const metadata: Metadata = {
  title: "Search businesses",
  description: "Search businesses and services across Somalia.",
};

type Props = {
  searchParams: Promise<{
    q?: string;
    city?: string;
    category?: string;
    page?: string;
  }>;
};

export default async function SearchPage({ searchParams }: Props) {
  const params = await searchParams;
  const results = await searchBusinesses({
    q: params.q,
    city: params.city,
    category: params.category,
    page: params.page,
    per_page: "18",
  });

  return (
    <div className="container-shell py-10">
      <h1 className="text-3xl font-black">Search businesses</h1>
      <p className="mt-2 text-neutral-600">
        Find services, organisations, and companies across Somalia.
      </p>

      <div className="mt-7">
        <SearchForm
          compact
          query={params.q}
          location={params.city}
        />
      </div>

      <div className="mt-10 flex items-center justify-between">
        <h2 className="text-xl font-black">
          {results.total ?? results.data.length} results
        </h2>
      </div>

      <div className="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {results.data.length > 0 ? (
          results.data.map((business) => (
            <BusinessCard key={business.id} business={business} />
          ))
        ) : (
          <div className="card col-span-full p-10 text-center">
            <p className="font-bold">No matching businesses found.</p>
            <p className="mt-2 text-sm text-neutral-600">
              Try another business name, service, or location.
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
