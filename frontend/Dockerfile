# Frontend Dockerfile - using Bun for better compatibility
FROM oven/bun:1.1-alpine

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies with Bun
RUN bun install

# Copy source code
COPY . .

# Build for production
ENV NODE_ENV=production
RUN bun run build

# Expose port
EXPOSE 3000

# Start server in production mode
CMD ["bun", "run", "preview"]