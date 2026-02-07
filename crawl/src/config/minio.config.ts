import { registerAs } from '@nestjs/config';

export default registerAs('minio', () => ({
  endpoint: process.env.MINIO_ENDPOINT || 'http://localhost:9000',
  accessKeyId: process.env.MINIO_ACCESS_KEY_ID || 'vnwaadmin',
  secretAccessKey: process.env.MINIO_SECRET_ACCESS_KEY || 'vnwa123456',
  bucket: process.env.MINIO_BUCKET || 'vnwa',
  region: process.env.MINIO_REGION || 'us-east-1',
  usePathStyleEndpoint: process.env.MINIO_USE_PATH_STYLE_ENDPOINT === 'true' || true,
  url: process.env.MINIO_URL || 'http://localhost:9000',
}));
