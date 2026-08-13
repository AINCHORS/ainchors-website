import type { Metadata, Viewport } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: {
    default: "AINCHORS | Training & Consulting",
    template: "%s | AINCHORS",
  },
  description:
    "AINCHORS delivers practical corporate training and strategic consulting for organisations navigating digital and AI-led change.",
  metadataBase: new URL("https://ainchors.com"),
  openGraph: {
    title: "AINCHORS | Training & Consulting",
    description: "Empowering talent to shape the future.",
    type: "website",
    images: [{ url: "/og.png", width: 1200, height: 630, alt: "AINCHORS — Empowering Talent to Shape The Future" }],
  },
  twitter: {
    card: "summary_large_image",
    title: "AINCHORS | Training & Consulting",
    description: "Empowering talent to shape the future.",
    images: ["/og.png"],
  },
};

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  themeColor: "#2bae83",
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
