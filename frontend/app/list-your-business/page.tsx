import type { Metadata } from "next";
import Link from "next/link";

export const metadata: Metadata = {
  title: "List your business",
};

export default function ListBusinessPage() {
  return (
    <div className="container-shell py-12">
      <div className="rounded-3xl bg-[#f5c400] p-8 md:p-14">
        <h1 className="text-4xl font-black">List your business</h1>
        <p className="mt-4 max-w-2xl text-lg leading-8 text-black/70">
          Create your business profile, add services, publish contact
          details, and start reaching new customers.
        </p>
        <Link
          href="/owner"
          className="focus-ring mt-7 inline-block rounded-xl bg-black px-6 py-3 font-bold text-white"
        >
          Open business portal
        </Link>
      </div>
    </div>
  );
}
