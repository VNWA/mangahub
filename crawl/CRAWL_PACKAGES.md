# Crawl Packages Cần Cài Đặt

Sau khi đã cài các packages cơ bản, bạn cần cài thêm các packages sau để crawl service hoạt động:

## Production Dependencies

```bash
npm install puppeteer
npm install cheerio
```

Hoặc cài cùng lúc:

```bash
npm install puppeteer cheerio
```

## Dev Dependencies (TypeScript types)

```bash
npm install --save-dev @types/cheerio
```

## Tổng hợp tất cả packages cần cài

**Production:**
```bash
npm install puppeteer cheerio
```

**Dev:**
```bash
npm install --save-dev @types/cheerio
```

## Lưu ý

- `puppeteer` sẽ tự động download Chromium khi cài đặt
- Nếu gặp vấn đề với Puppeteer, có thể cần cài thêm system dependencies:
  ```bash
  # Ubuntu/Debian
  sudo apt-get install -y \
    ca-certificates \
    fonts-liberation \
    libappindicator3-1 \
    libasound2 \
    libatk-bridge2.0-0 \
    libatk1.0-0 \
    libc6 \
    libcairo2 \
    libcups2 \
    libdbus-1-3 \
    libexpat1 \
    libfontconfig1 \
    libgbm1 \
    libgcc1 \
    libglib2.0-0 \
    libgtk-3-0 \
    libnspr4 \
    libnss3 \
    libpango-1.0-0 \
    libpangocairo-1.0-0 \
    libstdc++6 \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxcomposite1 \
    libxcursor1 \
    libxdamage1 \
    libxext6 \
    libxfixes3 \
    libxi6 \
    libxrandr2 \
    libxrender1 \
    libxss1 \
    libxtst6 \
    lsb-release \
    wget \
    xdg-utils
  ```
