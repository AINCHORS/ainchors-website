import { siteConfig } from "../../core/config/site";
import { Button } from "../../shared/components/Button/Button";
import { Section } from "../../shared/components/Section/Section";

export function FinalCta() {
  return (
    <Section tone="brand" className="final-cta">
      <div><p className="eyebrow">Ready to move forward?</p><h2>Innovate and transform with AINCHORS</h2><p>Tell us where you want your people or organisation to go next. We will help shape a practical path forward.</p></div>
      <Button href={siteConfig.whatsapp} target="_blank" rel="noreferrer" variant="secondary">Start a conversation</Button>
    </Section>
  );
}
