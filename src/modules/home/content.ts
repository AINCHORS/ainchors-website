export const services = [
  {
    title: "Corporate training",
    description: "Customised programmes that turn emerging technology and industry change into practical capability for your teams.",
    image: "/assets/services/corporate-training.webp",
    action: "Discuss your training needs",
    href: "/contact",
  },
  {
    title: "Self-learning courses",
    description: "Flexible, focused learning paths with concise lessons and applied exercises designed for independent progress.",
    image: "/assets/services/self-learning.webp",
    action: "Explore courses",
    href: "/courses",
  },
  {
    title: "Mentorship and coaching",
    description: "Guidance from experienced practitioners to help professionals build confidence and translate knowledge into results.",
    image: "/assets/services/mentorship.webp",
    action: "Talk to our team",
    href: "/contact",
  },
] as const;

export const trustSignals = [
  { value: "01", title: "Trusted by government regulators", text: "Learning designed for high-responsibility, policy-aware environments." },
  { value: "02", title: "Working with international banks", text: "Practical insight shaped by global financial services experience." },
  { value: "03", title: "Positive participant feedback", text: "Relevant courses that connect concepts with real-world application." },
] as const;

export const testimonials = [
  { quote: "Angie's knowledge in financial services and fintech creates a learning experience that is both practical and engaging.", name: "Ahmed", role: "Financial services professional", avatar: "/assets/testimonials/ahmed.webp" },
  { quote: "The sessions were insightful, relevant and delivered with a clear understanding of the industry's direction.", name: "Mouza", role: "Programme participant", avatar: "/assets/testimonials/mouza.jpg" },
  { quote: "A valuable programme that helped turn complex developments into clear and actionable understanding.", name: "Shamsah", role: "Programme participant", avatar: "/assets/testimonials/shamsah.jpg" },
] as const;

export const clientLogos = [
  ["Government partner", "/assets/clients/government-partner.webp"],
  ["Banking partner", "/assets/clients/banking-partner.webp"],
  ["CPA partner", "/assets/clients/cpa-partner.webp"],
  ["Dialectica", "/assets/clients/dialectica.webp"],
  ["EIF", "/assets/clients/eif.webp"],
  ["Saudi Fransi", "/assets/clients/saudi-fransi.webp"],
  ["Orbis", "/assets/clients/orbis.webp"],
  ["FONCO", "/assets/clients/fonco.webp"],
] as const;
