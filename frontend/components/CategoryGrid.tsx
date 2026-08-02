import Link from "next/link";
import {
  Building2,
  GraduationCap,
  HeartPulse,
  Landmark,
  Plane,
  Shield,
  ShoppingBag,
  Utensils,
} from "lucide-react";
import type { Category } from "@/lib/types";

const icons = [
  HeartPulse,
  Shield,
  Landmark,
  Utensils,
  Plane,
  Building2,
  GraduationCap,
  ShoppingBag,
];

export function CategoryGrid({
  categories,
}: {
  categories: Category[];
}) {
  return (
    <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
      {categories.slice(0, 8).map((category, index) => {
        const Icon = icons[index % icons.length];

        return (
          <Link
            key={category.id}
            href={`/category/${category.slug}`}
            className="focus-ring card group p-5 transition hover:-translate-y-0.5 hover:shadow-lg"
          >
            <span className="grid size-11 place-items-center rounded-xl bg-[#f5c400]/20">
              <Icon size={22} />
            </span>
            <h3 className="mt-5 font-black">{category.name}</h3>
            {category.name_so && (
              <p className="mt-1 text-sm text-neutral-500">
                {category.name_so}
              </p>
            )}
          </Link>
        );
      })}
    </div>
  );
}
