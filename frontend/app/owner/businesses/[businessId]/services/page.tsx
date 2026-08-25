import Link from "next/link";
import { redirect } from "next/navigation";
import { ServiceManager } from "@/components/owner/ServiceManager";
import { getCurrentUser } from "@/lib/auth";

type Props = { params: Promise<{ businessId: string }> };

export default async function Page({ params }: Props) {
  if (!(await getCurrentUser())) redirect("/login");
  const { businessId } = await params;

  return (
    <main className="mx-auto w-full max-w-6xl px-5 py-12">
      <Link href={`/owner/businesses/${encodeURIComponent(businessId)}`} className="text-sm font-semibold text-black/55">Back to business</Link>
      <h1 className="mt-5 text-3xl font-black">Services</h1>
      <p className="mt-2 text-black/60">Add the services customers should find on YellowPages.so.</p>
      <div className="py-8"><ServiceManager businessId={businessId} /></div>
    </main>
  );
}
