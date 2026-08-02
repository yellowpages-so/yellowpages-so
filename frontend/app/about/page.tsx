import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "About",
};

export default function AboutPage() {
  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <h1 className="text-4xl font-black">About YellowPages.so</h1>
        <p className="mt-5 text-lg leading-8 text-neutral-700">
          YellowPages.so helps people find trusted businesses,
          products, services, and organisations across Somalia.
        </p>
      </div>
    </div>
  );
}
