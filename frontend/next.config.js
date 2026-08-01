/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  async rewrites() {
    const api = process.env.NEXT_PUBLIC_API_BASE || 'http://localhost:8080';
    return [
      {
        source: '/uploads/:path*',
        destination: `${api}/uploads/:path*`,
      },
    ];
  },
};

module.exports = nextConfig;
