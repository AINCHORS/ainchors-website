import type { ReactNode } from "react";
import { Header } from "../../components/Header/Header";
import { Footer } from "../../components/Footer/Footer";
import { WhatsAppButton } from "../../components/WhatsAppButton/WhatsAppButton";

export function MainLayout({ children }: { children: ReactNode }) {
  return <><Header /><main>{children}</main><Footer /><WhatsAppButton /></>;
}
