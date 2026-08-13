import Image from "next/image";
import { Card } from "../../shared/components/Card/Card";
import { Section } from "../../shared/components/Section/Section";
import { SectionTitle } from "../../shared/components/SectionTitle/SectionTitle";
import { services } from "./content";

export function Services() {
  return (
    <Section id="services" tone="soft">
      <SectionTitle eyebrow="What we offer" title="Learning designed around real work" intro="Our programmes are shaped by industry practitioners and built to help people apply what they learn." />
      <div className="service-grid">
        {services.map((service, index) => (
          <Card className="service-card" key={service.title}>
            <div className="service-card__media"><Image src={service.image} alt="" width={1200} height={800} sizes="(max-width: 767px) 100vw, (max-width: 1023px) 50vw, 33vw" /><span>0{index + 1}</span></div>
            <div className="service-card__body"><h3>{service.title}</h3><p>{service.description}</p><a href={service.href}>{service.action}<span aria-hidden="true">→</span></a></div>
          </Card>
        ))}
      </div>
    </Section>
  );
}
