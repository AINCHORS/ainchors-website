import Image from "next/image";
import { siteConfig } from "../../../core/config/site";
import { routes } from "../../../core/routes/routes";
import { Container } from "../Container/Container";

export function Footer() {
  return (
    <footer className="site-footer">
      <Container>
        <div className="site-footer__grid">
          <div className="site-footer__brand">
            <Image src="/assets/brand/ainchors-logo-white.webp" alt="AINCHORS" width={1200} height={500} />
            <p>Practical learning and strategic guidance for organisations shaping an AI-enabled future.</p>
          </div>
          <div>
            <h3>Explore</h3>
            <ul>
              <li><a href={routes.about}>About us</a></li>
              <li><a href={routes.training}>Training</a></li>
              <li><a href={routes.courses}>Courses</a></li>
              <li><a href={routes.consulting}>Consulting</a></li>
            </ul>
          </div>
          <div>
            <h3>Connect</h3>
            <ul>
              <li><a href={routes.contact}>Contact us</a></li>
              <li><a href={siteConfig.whatsapp} target="_blank" rel="noreferrer">WhatsApp</a></li>
              <li><a href={`mailto:${siteConfig.email}`}>{siteConfig.email}</a></li>
            </ul>
          </div>
          <div>
            <h3>Locations</h3>
            <p>Malaysia · Australia<br />Serving clients internationally</p>
          </div>
        </div>
        <div className="site-footer__bottom">
          <p>© {new Date().getFullYear()} {siteConfig.legalName}. All rights reserved.</p>
          <div><a href="/privacy">Privacy</a><a href="/terms">Terms</a></div>
        </div>
      </Container>
    </footer>
  );
}
