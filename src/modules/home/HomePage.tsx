import { MainLayout } from "../../shared/layouts/MainLayout/MainLayout";
import { ClientLogos } from "./ClientLogos";
import { FinalCta } from "./FinalCta";
import { Hero } from "./Hero";
import { Services } from "./Services";
import { TestimonialsPreview } from "./TestimonialsPreview";
import { TrustSection } from "./TrustSection";

export function HomePage() {
  return <MainLayout><Hero /><ClientLogos /><Services /><TrustSection /><TestimonialsPreview /><FinalCta /></MainLayout>;
}
