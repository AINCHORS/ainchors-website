import Image from "next/image";
import { Section } from "../../shared/components/Section/Section";
import { SectionTitle } from "../../shared/components/SectionTitle/SectionTitle";
import { clientLogos } from "./content";

export function ClientLogos() {
  return (
    <Section className="clients-section">
      <SectionTitle eyebrow="Global relationships" title="Our International Clients and Partners" intro="Experience spanning regulators, financial institutions, professional bodies and industry partners." />
      <div className="client-grid" aria-label="AINCHORS client and partner logos">
        {clientLogos.map(([name, image]) => <div className="client-logo" key={name}><Image src={image} alt={name} width={180} height={90} sizes="(max-width: 767px) 40vw, 18vw" /></div>)}
      </div>
    </Section>
  );
}
