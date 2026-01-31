FROM node:24-alpine

# Install Bun
RUN npm install -g bun

WORKDIR /app

# Copy package files
COPY package.json bun.lock* ./

# Install dependencies
RUN bun install

# Copy application code
COPY . .

EXPOSE 3000

# Default command (can be overridden in docker-compose)
CMD ["bun", "run", "dev"]