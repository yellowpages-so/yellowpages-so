import type { Metadata } from "next";
import "@/app/globals.css";
import { SiteFooter } from "@/components/SiteFooter";
import { SiteHeader } from "@/components/SiteHeader";

const siteUrl =
  process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000";

export const metadata: Metadata = {
  metadataBase: new URL(siteUrl),
  title: {
    default: "YellowPages.so | Find businesses in Somalia",
    template: "%s | YellowPages.so",
  },
  description:
    "Search trusted businesses, services, and organisations across Somalia.",
  openGraph: {
    title: "YellowPages.so",
    description:
      "Find trusted businesses, services, and organisations across Somalia.",
    url: siteUrl,
    siteName: "YellowPages.so",
    locale: "en_GB",
    type: "website",
  },
  robots: {
    index: true,
    follow: true,
  },
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en">
      <body>
        <SiteHeader />
        <main>{children}</main>
        <SiteFooter />
      </body>
    </html>
  );
}
