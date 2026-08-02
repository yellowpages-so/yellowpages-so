import type { Metadata } from "next";
import { CategoryGrid } from "@/components/CategoryGrid";
import { getCategories } from "@/lib/api";

export const metadata: Metadata = {
  title: "Business categories",
  description: "Browse business categories available on YellowPages.so.",
};

export default async function CategoriesPage() {
  const categories = await getCategories();

  return (
    <div className="container-shell py-12">
      <h1 className="text-4xl font-black">Business categories</h1>
      <p className="mt-3 max-w-2xl leading-7 text-neutral-600">
        Browse products and services by category.
      </p>
      <div className="mt-8">
        <CategoryGrid categories={categories} />
      </div>
    </div>
  );
}
