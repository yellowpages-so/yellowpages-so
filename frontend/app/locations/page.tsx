import type { Metadata } from "next";
import Link from "next/link";

export const metadata: Metadata = {
  title: "Locations",
  description: "Browse businesses by city and region in Somalia.",
};

const cities = [
  "Mogadishu",
  "Hargeisa",
  "Bosaso",
  "Garowe",
  "Kismayo",
  "Baidoa",
  "Jowhar",
  "Beledweyne",
  "Dhusamareb",
];

export default function LocationsPage() {
  return (
    <div className="container-shell py-12">
      <h1 className="text-4xl font-black">Browse by location</h1>
      <p className="mt-3 text-neutral-600">
        Find businesses and services in major Somali cities.
      </p>
      <div className="mt-8 grid gap-4 sm:grid-cols-2 md:grid-cols-3">
        {cities.map((city) => (
          <Link
            key={city}
            href={`/search?city=${encodeURIComponent(city)}`}
            className="focus-ring card p-6 font-black transition hover:shadow-lg"
          >
            {city}
          </Link>
        ))}
      </div>
    </div>
  );
}
