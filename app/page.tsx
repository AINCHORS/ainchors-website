import type { Metadata } from "next";
import { HomePage } from "../src/modules/home/HomePage";

export const metadata: Metadata = {
  title: "Empowering Talent to Shape The Future",
  description:
    "Explore AINCHORS corporate training, self-learning courses, mentorship and strategic consulting services.",
};

export default function Home() {
  return <HomePage />;
}
