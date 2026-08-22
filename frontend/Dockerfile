FROM node:20-alpine

WORKDIR /app

# Copy package files first for layer caching
COPY package*.json ./

# Install dependencies
RUN npm install --no-audit --no-fund

# Copy source code
COPY . .

# Build for production
ENV NODE_ENV=production
RUN npm run build

# Expose port
EXPOSE 3000

# Start server in production mode
CMD ["npm", "run", "preview"]