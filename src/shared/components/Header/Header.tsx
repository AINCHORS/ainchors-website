"use client";

import { useEffect, useState } from "react";
import Image from "next/image";
import { primaryNavigation, routes } from "../../../core/routes/routes";
import { Container } from "../Container/Container";
import { Button } from "../Button/Button";

export function Header() {
  const [open, setOpen] = useState(false);

  useEffect(() => {
    document.body.classList.toggle("nav-open", open);
    return () => document.body.classList.remove("nav-open");
  }, [open]);

  return (
    <header className="site-header">
      <Container className="site-header__inner">
        <a className="site-header__brand" href={routes.home} aria-label="AINCHORS home">
          <Image src="/assets/brand/ainchors-logo.webp" alt="AINCHORS Training & Consulting" width={1200} height={500} priority />
        </a>
        <button
          className="menu-button"
          type="button"
          aria-label={open ? "Close navigation" : "Open navigation"}
          aria-expanded={open}
          aria-controls="primary-navigation"
          onClick={() => setOpen((value) => !value)}
        >
          <span />
          <span />
          <span />
        </button>
        <nav id="primary-navigation" className={`site-nav ${open ? "site-nav--open" : ""}`} aria-label="Primary navigation">
          <ul>
            {primaryNavigation.map((item) => (
              <li key={item.href}><a href={item.href} onClick={() => setOpen(false)}>{item.label}</a></li>
            ))}
          </ul>
          <Button href={routes.contact} className="site-nav__contact" onClick={() => setOpen(false)}>Contact us</Button>
        </nav>
      </Container>
    </header>
  );
}
