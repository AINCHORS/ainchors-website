export const routes = {
  home: "/",
  about: "/about",
  training: "/training",
  trainers: "/trainers",
  courses: "/courses",
  consulting: "/consulting",
  consultingGovernment: "/consulting/government",
  consultingPrivate: "/consulting/private-sector",
  testimonials: "/testimonials",
  events: "/events",
  careers: "/careers",
  faq: "/faq",
  contact: "/contact",
  checkout: "/checkout",
} as const;

export const primaryNavigation = [
  { label: "Home", href: routes.home },
  { label: "About us", href: routes.about },
  { label: "Training", href: routes.training },
  { label: "Consulting", href: routes.consulting },
  { label: "FAQ’s", href: routes.faq },
  { label: "Join Us", href: routes.careers },
] as const;
