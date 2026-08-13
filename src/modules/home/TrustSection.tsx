import { Section } from "../../shared/components/Section/Section";
import { SectionTitle } from "../../shared/components/SectionTitle/SectionTitle";
import { trustSignals } from "./content";

export function TrustSection() {
  return (
    <Section tone="dark" className="trust-section">
      <div className="trust-section__header"><SectionTitle align="left" eyebrow="Why AINCHORS" title="Credibility built through practical experience" /><p>We connect global financial-services insight with approachable learning, thoughtful facilitation and clear business outcomes.</p></div>
      <div className="trust-grid">
        {trustSignals.map((item) => <article key={item.value}><span>{item.value}</span><h3>{item.title}</h3><p>{item.text}</p></article>)}
      </div>
    </Section>
  );
}
