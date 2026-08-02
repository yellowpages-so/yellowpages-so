import type { Metadata } from "next";
import { BusinessCard } from "@/components/BusinessCard";
import { SearchForm } from "@/components/SearchForm";
import { searchBusinesses } from "@/lib/api";

type Props = {
  params: Promise<{ slug: string }>;
};

function titleFromSlug(slug: string) {
  return slug
    .split("-")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}

export async function generateMetadata({
  params,
}: Props): Promise<Metadata> {
  const { slug } = await params;
  const title = titleFromSlug(slug);

  return {
    title,
    description: `Find ${title.toLowerCase()} businesses across Somalia.`,
  };
}

export default async function CategoryPage({ params }: Props) {
  const { slug } = await params;
  const title = titleFromSlug(slug);
  const results = await searchBusinesses({
    category: slug,
    per_page: "24",
  });

  return (
    <div className="container-shell py-12">
      <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
        Category
      </p>
      <h1 className="mt-2 text-4xl font-black">{title}</h1>
      <p className="mt-3 text-neutral-600">
        Browse {title.toLowerCase()} providers across Somalia.
      </p>
      <div className="mt-7">
        <SearchForm compact query={title} />
      </div>
      <div className="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {results.data.length ? (
          results.data.map((business) => (
            <BusinessCard key={business.id} business={business} />
          ))
        ) : (
          <div className="card col-span-full p-10 text-center text-neutral-600">
            No published businesses are listed in this category yet.
          </div>
        )}
      </div>
    </div>
  );
}
