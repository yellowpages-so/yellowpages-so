import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Contact",
};

export default function ContactPage() {
  const email =
    process.env.NEXT_PUBLIC_CONTACT_EMAIL ?? "hello@yellowpages.so";

  return (
    <div className="container-shell py-12">
      <h1 className="text-4xl font-black">Contact us</h1>
      <p className="mt-4 text-neutral-700">
        Email us at{" "}
        <a className="font-bold underline" href={`mailto:${email}`}>
          {email}
        </a>
        .
      </p>
    </div>
  );
}
