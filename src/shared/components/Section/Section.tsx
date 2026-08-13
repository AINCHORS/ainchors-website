import type { ReactNode } from "react";
import { Container } from "../Container/Container";

export function Section({
  children,
  id,
  tone = "default",
  className = "",
  contained = true,
}: {
  children: ReactNode;
  id?: string;
  tone?: "default" | "soft" | "dark" | "brand";
  className?: string;
  contained?: boolean;
}) {
  const content = contained ? <Container>{children}</Container> : children;
  return <section id={id} className={`section section--${tone} ${className}`.trim()}>{content}</section>;
}
