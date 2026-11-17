FROM mcr.microsoft.com/playwright:jammy

WORKDIR /e2e

COPY package*.json ./
RUN npm install --no-audit --no-fund

COPY . .

CMD ["npx", "playwright", "test", "--reporter=list"]