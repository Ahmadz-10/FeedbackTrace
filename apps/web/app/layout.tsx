import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'FeedbackTrace',
  description: 'Student feedback intelligence and service-quality decision support.',
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return <html lang="en"><body>{children}</body></html>;
}
