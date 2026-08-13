import Image from "next/image";
import { Card } from "../../shared/components/Card/Card";
import { Section } from "../../shared/components/Section/Section";
import { SectionTitle } from "../../shared/components/SectionTitle/SectionTitle";
import { testimonials } from "./content";

export function TestimonialsPreview() {
  return (
    <Section>
      <SectionTitle eyebrow="Social proof" title="What our customers are saying" intro="Feedback from professionals who have experienced AINCHORS learning programmes." />
      <div className="testimonial-grid">
        {testimonials.map((item) => <Card className="testimonial-card" key={item.name}><span className="testimonial-card__quote">“</span><blockquote>{item.quote}</blockquote><footer><Image src={item.avatar} alt="" width={64} height={64} /><div><strong>{item.name}</strong><span>{item.role}</span></div></footer></Card>)}
      </div>
      <p className="section-action"><a href="/testimonials">Read more testimonials <span aria-hidden="true">→</span></a></p>
    </Section>
  );
}
