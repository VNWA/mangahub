import { registerAs } from '@nestjs/config';

export default registerAs('app', () => ({
  port: parseInt(process.env.PORT || '3000', 10),
  nodeEnv: process.env.NODE_ENV || 'development',
  vnwaDefaultUserId: parseInt(process.env.VNWA_DEFAULT_USER_ID || '1', 10),
}));
