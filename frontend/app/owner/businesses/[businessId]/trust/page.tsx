import Link from "next/link";
import { redirect } from "next/navigation";
import { TrustManager } from "@/components/owner/TrustManager";
import { getCurrentUser } from "@/lib/auth";

type Props = {
  params: Promise<{ businessId: string }>;
};

export default async function Page({
  params,
}: Props) {
  if (!(await getCurrentUser())) {
    redirect("/login");
  }

  const { businessId } = await params;

  return (
    <main className="mx-auto w-full max-w-5xl px-5 py-12 sm:py-16">
      <Link
        href={`/owner/businesses/${encodeURIComponent(
          businessId,
        )}`}
        className="text-sm font-semibold text-black/55"
      >
        Back to business
      </Link>

      <h1 className="mt-5 text-3xl font-black">
        Categories & verification
      </h1>

      <p className="mt-2 text-black/60">
        Classify this business accurately and review
        its verification status.
      </p>

      <div className="py-8">
        <TrustManager businessId={businessId} />
      </div>
    </main>
  );
}
