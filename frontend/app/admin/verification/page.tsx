import { redirect } from "next/navigation";
import { VerificationReview } from "@/components/admin/VerificationReview";
import { getCurrentUser } from "@/lib/auth";

export default async function Page() {
  if (!(await getCurrentUser())) {
    redirect("/login");
  }

  return (
    <main className="mx-auto w-full max-w-7xl px-5 py-12 sm:py-16">
      <h1 className="text-3xl font-black">
        Verification review
      </h1>

      <p className="mt-2 text-black/60">
        Review business verification requests,
        supporting documents and decisions.
      </p>

      <div className="py-8">
        <VerificationReview />
      </div>
    </main>
  );
}
