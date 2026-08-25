import { redirect } from "next/navigation";

import { ReviewModeration } from "@/components/admin/ReviewModeration";
import { getCurrentUser } from "@/lib/auth";

export default async function Page() {
  if (!(await getCurrentUser())) {
    redirect("/login");
  }

  return (
    <main className="mx-auto w-full max-w-7xl px-5 py-12 sm:py-16">
      <h1 className="text-3xl font-black">
        Review moderation
      </h1>

      <p className="mt-2 text-black/60">
        Review customer feedback and publish, hide,
        reject, or restore submissions.
      </p>

      <div className="py-8">
        <ReviewModeration />
      </div>
    </main>
  );
}
