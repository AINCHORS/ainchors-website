import Image from "next/image";
import { siteConfig } from "../../core/config/site";
import { Button } from "../../shared/components/Button/Button";
import { Container } from "../../shared/components/Container/Container";

export function Hero() {
  return (
    <section className="hero">
      <Container className="hero__grid">
        <div className="hero__content">
          <p className="eyebrow">Training · Consulting · Transformation</p>
          <h1>Empowering Talent to Shape <span>The Future</span></h1>
          <p className="hero__intro"><strong>AINCHORS</strong> is a global fintech learning and strategy firm delivering high-impact corporate training and strategic consulting to organisations around the world.</p>
          <p className="hero__support">We create practical learning journeys enhanced by digital tools and artificial intelligence, helping people and enterprises navigate continuous change.</p>
          <div className="hero__actions">
            <Button href={siteConfig.whatsapp} target="_blank" rel="noreferrer">Get in touch</Button>
            <Button href="#services" variant="secondary">Explore what we offer</Button>
          </div>
        </div>
        <figure className="hero__media">
          <Image src="/assets/home/hero-immersive-experience.webp" alt="Visitors exploring an immersive digital experience" width={1200} height={900} priority sizes="(max-width: 767px) 100vw, 45vw" />
          <figcaption><span>Global perspective</span><strong>Practical knowledge for real transformation</strong></figcaption>
        </figure>
      </Container>
    </section>
  );
}
