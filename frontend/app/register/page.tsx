import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { RegisterForm } from "@/components/auth/RegisterForm";
import { getCurrentUser } from "@/lib/auth";

export const metadata: Metadata = {
  title: "Create account",
};

export default async function RegisterPage() {
  const user = await getCurrentUser();

  if (user) {
    redirect("/owner");
  }

  return (
    <main className="mx-auto w-full max-w-2xl px-5 py-16 sm:py-24">
      <div className="rounded-3xl border border-black/10 bg-white p-6 shadow-sm sm:p-10">
        <p className="text-sm font-bold uppercase tracking-[0.18em] text-black/50">
          Business account
        </p>
        <h1 className="mt-3 text-3xl font-black tracking-tight">
          Create your YellowPages.so account
        </h1>
        <p className="mt-3 mb-8 text-black/65">
          Start managing your business presence and customer enquiries.
        </p>
        <RegisterForm />
      </div>
    </main>
  );
}
