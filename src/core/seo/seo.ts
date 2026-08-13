export type SeoConfig = {
  title: string;
  description: string;
  canonicalPath: string;
  image?: string;
};

export const homeSeo: SeoConfig = {
  title: "Empowering Talent to Shape The Future",
  description: "Practical training and consulting for an AI-enabled future.",
  canonicalPath: "/",
  image: "/og.png",
};
