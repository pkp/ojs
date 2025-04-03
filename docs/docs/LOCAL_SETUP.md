# Landing Page and Documentation Site Setup Guide

This guide will help you set up the Modern OJS landing page and documentation site locally for development. This is a separate project from the main Modern OJS application and is used to showcase and document the project.

## Prerequisites

Before you begin, ensure you have the following installed:

- Node.js (v18 or higher)
- npm (v8 or higher)
- Git

## Step 1: Clone the Repository

```bash
git clone https://github.com/your-username/modern-ojs.git
cd modern-ojs
```

## Step 2: Install Dependencies

Navigate to the docs directory and install the required dependencies:

```bash
cd docs
npm install
```

This will install all the necessary packages for the landing page and documentation site, including:
- React
- Vite
- TailwindCSS
- TypeScript
- And other development dependencies

## Step 3: Configuration

The project comes with pre-configured files:

- `postcss.config.cjs` - PostCSS configuration for TailwindCSS
- `tailwind.config.cjs` - TailwindCSS configuration
- `vite.config.ts` - Vite configuration
- `tsconfig.json` - TypeScript configuration

No additional configuration is required to start development.

## Step 4: Start the Development Server

Run the development server:

```bash
npm run dev
```

The server will start and you can access:
- Landing page at: http://localhost:3000/
- Documentation at: http://localhost:3000/docs/
- Network: Use `--host` flag to expose to your local network

## Available Scripts

- `npm run dev` - Start the development server
- `npm run build` - Build the project for production
- `npm run preview` - Preview the production build locally

## Project Structure

```
docs/
├── src/              # Source files for landing page
│   ├── components/   # React components
│   ├── pages/        # Page components
│   └── styles/       # CSS and Tailwind styles
├── docs/             # Documentation files
│   ├── assets/       # Documentation assets
│   └── *.md          # Markdown documentation files
├── public/           # Static assets
├── index.html        # Entry HTML file
├── package.json      # Project dependencies
├── tsconfig.json     # TypeScript configuration
├── vite.config.ts    # Vite configuration
├── postcss.config.cjs # PostCSS configuration
└── tailwind.config.cjs # TailwindCSS configuration
```

## Troubleshooting

### Common Issues

1. **PostCSS Configuration Error**
   - Ensure you're using `.cjs` extension for PostCSS and Tailwind config files
   - Check that the configuration uses CommonJS syntax (`module.exports`)

2. **Dependencies Not Found**
   - Run `npm install` to install all dependencies
   - Clear npm cache if needed: `npm cache clean --force`

3. **TypeScript Errors**
   - Ensure all TypeScript dependencies are installed
   - Check `tsconfig.json` for correct configuration

4. **Vite Not Found**
   - Make sure you're in the correct directory (docs/)
   - Run `npm install` to ensure Vite is installed

### Development Tips

- Use `npm run dev` to start the development server with hot reloading
- Press `h + enter` in the terminal to show Vite help menu
- Use `--host` flag to expose the server to your local network
- Check the browser console for any runtime errors

## Additional Resources

- [Vite Documentation](https://vitejs.dev/guide/)
- [React Documentation](https://react.dev/)
- [TailwindCSS Documentation](https://tailwindcss.com/docs)
- [TypeScript Documentation](https://www.typescriptlang.org/docs/)

## Support

If you encounter any issues not covered in this guide:
1. Check the project's GitHub issues
2. Create a new issue with detailed information about your problem
3. Contact the development team through the project's communication channels 