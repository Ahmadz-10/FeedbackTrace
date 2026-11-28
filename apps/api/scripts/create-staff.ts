import { config } from 'dotenv';
import { hash } from 'bcryptjs';
import { PrismaPg } from '@prisma/adapter-pg';
import { PrismaClient } from '@feedbacktrace/database';

config({ path: '../../.env' });

const email = process.env.STAFF_EMAIL?.toLowerCase();
const password = process.env.STAFF_PASSWORD;
const name = process.env.STAFF_NAME || 'FeedbackTrace Staff';
if (!email || !password || password.length < 12) {
  throw new Error('Set STAFF_EMAIL and STAFF_PASSWORD (minimum 12 characters) in the root .env before running this script.');
}
const adapter = new PrismaPg({ connectionString: process.env.DATABASE_URL! });
const prisma = new PrismaClient({ adapter });
try {
  const passwordHash = await hash(password, 12);
  await prisma.user.upsert({
    where: { email },
    update: { name, passwordHash, role: 'STAFF' },
    create: { name, email, passwordHash, role: 'STAFF' },
  });
  console.log('Staff account ' + email + ' is ready.');
} finally {
  await prisma.$disconnect();
}
