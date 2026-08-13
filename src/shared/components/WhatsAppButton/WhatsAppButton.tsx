import { siteConfig } from "../../../core/config/site";

export function WhatsAppButton() {
  return (
    <a className="whatsapp-button" href={siteConfig.whatsapp} target="_blank" rel="noreferrer" aria-label="Chat with AINCHORS on WhatsApp">
      <span aria-hidden="true">WA</span><strong>Chat with us</strong>
    </a>
  );
}
